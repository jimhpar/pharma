<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\LoyaltyPointLedger;
use App\Models\SalesOrder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class LoyaltyService
{
    /**
     * Load all loyalty settings from the loyalty_settings table into a typed array.
     * Falls back to sensible defaults if the table row doesn't exist yet.
     */
    public function getSettings(): array
    {
        $row = DB::table('loyalty_settings')->first();

        // PHP 8.2 throws a Fatal Error on null->property, so we must guard explicitly.
        if ($row === null) {
            return $this->defaultSettings();
        }

        return [
            'enabled'              => (bool) $row->enabled,
            'redemption_enabled'   => (bool) $row->redemption_enabled,
            // How many purchase taka equal 1 point (e.g. 100 → 100 taka = 1 point)
            'points_per_amount'    => max(1, (float) $row->points_per_amount),
            // How much one point is worth in taka (e.g. 1 → 1 point = 1 taka)
            'point_value'          => max(0.01, (float) $row->point_value),
            // Total purchase threshold to become a member (taka)
            'membership_threshold' => max(0, (float) $row->membership_threshold),
        ];
    }

    private function defaultSettings(): array
    {
        return [
            'enabled'              => true,
            'redemption_enabled'   => true,
            'points_per_amount'    => 100.0,
            'point_value'          => 1.0,
            'membership_threshold' => 5000.0,
        ];
    }

    /**
     * Points earned for a given paid amount using floor calculation.
     * Example: paidAmount=850, pointsPerAmount=100 → floor(850/100)=8 points.
     */
    public function calculateEarnedPoints(float $paidAmount, array $settings): int
    {
        if (!$settings['enabled'] || $paidAmount <= 0) {
            return 0;
        }

        return (int) floor($paidAmount / $settings['points_per_amount']);
    }

    /**
     * Money discount value of a given number of points.
     * Example: points=10, pointValue=1 → 10 taka discount.
     */
    public function calculatePointDiscount(int $points, array $settings): float
    {
        if ($points <= 0) {
            return 0.0;
        }

        return round($points * $settings['point_value'], 2);
    }

    /**
     * Validate whether a customer can redeem a given number of points.
     * Throws RuntimeException with a user-readable message on failure.
     */
    public function canRedeemPoints(Customer $customer, int $points, float $payableAmount, array $settings): void
    {
        if (!$settings['enabled']) {
            throw new RuntimeException('Loyalty point system is currently disabled.');
        }

        if (!$settings['redemption_enabled']) {
            throw new RuntimeException('Point redemption is currently disabled.');
        }

        if ($points <= 0) {
            throw new RuntimeException('Redemption points must be greater than zero.');
        }

        if ($points > (int) $customer->loyalty_points) {
            throw new RuntimeException(
                "Cannot redeem {$points} points. Customer only has {$customer->loyalty_points} available."
            );
        }

        $discountAmount = $this->calculatePointDiscount($points, $settings);

        if ($discountAmount > $payableAmount) {
            throw new RuntimeException(
                "Point discount ({$discountAmount}) cannot exceed the payable amount ({$payableAmount})."
            );
        }
    }

    /**
     * Award earned points to a customer after a successful sale.
     * Updates customer record and writes a ledger entry. Call inside DB transaction.
     */
    public function applyEarnedPoints(Customer $customer, SalesOrder $order, array $settings): void
    {
        $earnedPoints = $this->calculateEarnedPoints((float) $order->paid_total, $settings);

        if ($earnedPoints <= 0) {
            return;
        }

        $order->update(['earned_points' => $earnedPoints]);

        $customer->increment('loyalty_points', $earnedPoints);
        $customer->increment('lifetime_earned_points', $earnedPoints);

        LoyaltyPointLedger::create([
            'customer_id'    => $customer->id,
            'sales_order_id' => $order->id,
            'type'           => 'earned',
            'points'         => $earnedPoints,
            'amount_value'   => $order->paid_total,
            'note'           => "Earned from order #{$order->order_no}",
            'created_by'     => auth()->id(),
            'created_at'     => now(),
        ]);
    }

    /**
     * Deduct redeemed points from a customer after a successful sale.
     * Updates customer record and writes a ledger entry. Call inside DB transaction.
     */
    public function redeemPoints(Customer $customer, SalesOrder $order, int $points, array $settings): void
    {
        if ($points <= 0) {
            return;
        }

        $discountAmount = $this->calculatePointDiscount($points, $settings);

        $order->update([
            'redeemed_points'       => $points,
            'point_discount_amount' => $discountAmount,
        ]);

        // Prevent loyalty_points from going below zero
        $newBalance = max(0, (int) $customer->loyalty_points - $points);
        $actualDeducted = (int) $customer->loyalty_points - $newBalance;

        $customer->update(['loyalty_points' => $newBalance]);
        $customer->increment('lifetime_redeemed_points', $actualDeducted);

        LoyaltyPointLedger::create([
            'customer_id'    => $customer->id,
            'sales_order_id' => $order->id,
            'type'           => 'redeemed',
            'points'         => -$actualDeducted,
            'amount_value'   => $discountAmount,
            'note'           => "Redeemed on order #{$order->order_no}",
            'created_by'     => auth()->id(),
            'created_at'     => now(),
        ]);
    }

    /**
     * Update total purchase amount and promote customer to member if threshold met.
     * Call inside DB transaction after order is created.
     */
    public function updateMembershipStatus(Customer $customer, float $paidAmount, array $settings): void
    {
        $newTotal = round((float) $customer->total_purchase_amount + $paidAmount, 2);
        $customer->update(['total_purchase_amount' => $newTotal]);

        if (!$customer->is_member && $settings['membership_threshold'] > 0 && $newTotal >= $settings['membership_threshold']) {
            $customer->update([
                'is_member'             => true,
                'membership_started_at' => now(),
            ]);
        }
    }

    /**
     * Admin manual point adjustment (add or remove points).
     * type: 'adjusted'. points value is signed: positive = add, negative = remove.
     */
    public function adjustPoints(Customer $customer, int $points, string $note, ?int $createdBy = null): void
    {
        if ($points === 0) {
            throw new RuntimeException('Adjustment points cannot be zero.');
        }

        $currentPoints = (int) $customer->loyalty_points;
        $newBalance = $currentPoints + $points;

        if ($newBalance < 0) {
            throw new RuntimeException(
                "Cannot remove {$points} points. Customer only has {$currentPoints} points."
            );
        }

        DB::transaction(function () use ($customer, $points, $note, $createdBy, $newBalance) {
            $customer->update(['loyalty_points' => $newBalance]);

            if ($points > 0) {
                $customer->increment('lifetime_earned_points', $points);
            } else {
                $customer->increment('lifetime_redeemed_points', abs($points));
            }

            LoyaltyPointLedger::create([
                'customer_id'    => $customer->id,
                'sales_order_id' => null,
                'type'           => 'adjusted',
                'points'         => $points,
                'amount_value'   => 0,
                'note'           => $note,
                'created_by'     => $createdBy ?? auth()->id(),
                'created_at'     => now(),
            ]);
        });
    }
}
