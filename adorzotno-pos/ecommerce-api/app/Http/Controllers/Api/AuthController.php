<?php

namespace App\Http\Controllers\Api;

use App\Models\Customer;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AuthController extends BaseApiController
{
    /**
     * Register a new customer
     */
    public function register(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email|unique:customers,email',
            'password' => 'required|string|min:6|confirmed',
            'phone' => 'nullable|string|max:30|unique:users,phone|unique:customers,phone',
            'device_name' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors()->toArray());
        }

        try {
            $payload = DB::transaction(function () use ($request): array {
                $user = User::create([
                    'name' => $request->string('name')->toString(),
                    'email' => $request->string('email')->toString(),
                    'password' => $request->string('password')->toString(),
                    'phone' => $request->input('phone'),
                ]);

                $customer = Customer::query()->create([
                    'name' => $user->name,
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'user_id' => $user->id,
                    'customer_code' => 'CUS-'.str_pad((string) ((int) Customer::query()->max('id') + 1), 6, '0', STR_PAD_LEFT),
                    'opening_balance' => 0,
                    'current_due' => 0,
                    'loyalty_points' => 0,
                    'status' => 'active',
                ]);

                $token = $user->createToken($this->resolveTokenName($request))->plainTextToken;

                return [
                    'user' => $user,
                    'customer' => $customer,
                    'token' => $token,
                ];
            });

            return $this->success([
                'user' => $payload['user']->load('customer'),
                'customer' => $payload['customer'],
                'token' => $payload['token'],
            ], 'Registration successful', 201);
        } catch (\Exception $e) {
            return $this->serverError('Registration failed: '.$e->getMessage());
        }
    }

    /**
     * Login user
     */
    public function login(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|string|email',
            'password' => 'required|string|min:6',
            'device_name' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors()->toArray());
        }

        $user = User::query()->where('email', $request->string('email')->toString())->first();

        if ($user !== null && Hash::check($request->string('password')->toString(), $user->password)) {
            $user->forceFill([
                'last_login_at' => now(),
            ])->save();

            $token = $user->createToken($this->resolveTokenName($request))->plainTextToken;

            $isAdmin = $user->hasRoleSlug(['super-admin', 'admin', 'manager', 'cashier']) || in_array($user->email, ['admin@gmail.com', 'admin.mirpur@gmail.com']);

            return $this->success([
                'user' => $user->load('customer'),
                'token' => $token,
                'is_admin' => $isAdmin,
                'admin_redirect_url' => $isAdmin ? 'http://localhost:8001/sso-login?token='.$token : null,
            ], 'Login successful', 200);
        }

        return $this->unauthorized('Invalid credentials');
    }

    /**
     * Logout user
     */
    public function logout(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user === null) {
            return $this->unauthorized('Not authenticated');
        }

        $currentAccessToken = $user->currentAccessToken();

        if ($currentAccessToken !== null) {
            $currentAccessToken->delete();
        } elseif (auth('web')->check()) {
            auth('web')->logout();

            if ($request->hasSession()) {
                $request->session()->invalidate();
                $request->session()->regenerateToken();
            }
        }

        return $this->success(null, 'Logout successful', 200);
    }

    /**
     * Get current user profile
     */
    public function me(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user === null) {
            return $this->unauthorized('Not authenticated');
        }

        return $this->success([
            'user' => $user->load('customer'),
        ], 'Profile retrieved successfully', 200);
    }

    /**
     * Update user profile
     */
    public function updateProfile(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user === null) {
            return $this->unauthorized('Not authenticated');
        }

        $customer = $this->authenticatedCustomer(true);

        $validator = Validator::make($request->all(), [
            'name' => 'nullable|string|max:255',
            'email' => 'nullable|string|email|max:255|unique:users,email,'.$user->id.'|unique:customers,email,'.($customer?->id ?? 'NULL'),
            'phone' => 'nullable|string|max:30|unique:users,phone,'.$user->id.'|unique:customers,phone,'.($customer?->id ?? 'NULL'),
            'billing_address' => 'nullable|string',
            'shipping_address' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors()->toArray());
        }

        try {
            if ($request->filled('name')) {
                $user->name = $request->string('name')->toString();
            }
            if ($request->filled('email')) {
                $user->email = $request->string('email')->toString();
            }
            if ($request->filled('phone')) {
                $user->phone = $request->string('phone')->toString();
            }
            $user->save();

            if ($customer !== null) {
                if ($request->filled('name')) {
                    $customer->name = $user->name;
                }
                if ($request->filled('email')) {
                    $customer->email = $user->email;
                }
                if ($request->filled('phone')) {
                    $customer->phone = $user->phone;
                }
                if ($request->has('billing_address')) {
                    $customer->billing_address = $request->input('billing_address');
                }
                if ($request->has('shipping_address')) {
                    $customer->shipping_address = $request->input('shipping_address');
                }

                $customer->save();
            }

            return $this->success([
                'user' => $user->load('customer'),
            ], 'Profile updated successfully', 200);
        } catch (\Exception $e) {
            return $this->serverError('Profile update failed: '.$e->getMessage());
        }
    }

    public function changePassword(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user === null) {
            return $this->unauthorized('Not authenticated');
        }

        $validator = Validator::make($request->all(), [
            'current' => 'required_without:current_password|string',
            'current_password' => 'required_without:current|string',
            'new' => 'required_without:new_password|string|min:6',
            'new_password' => 'required_without:new|string|min:6',
            'confirm' => 'required_without:new_password_confirmation|string',
            'new_password_confirmation' => 'required_without:confirm|string',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors()->toArray());
        }

        $currentPassword = (string) ($request->input('current_password') ?? $request->input('current'));
        $newPassword = (string) ($request->input('new_password') ?? $request->input('new'));
        $confirmPassword = (string) ($request->input('new_password_confirmation') ?? $request->input('confirm'));

        if ($newPassword !== $confirmPassword) {
            return $this->validationError([
                'confirm' => ['The confirm password field must match the new password field.'],
            ]);
        }

        if (!Hash::check($currentPassword, $user->password)) {
            return $this->validationError([
                'current' => ['Current password is incorrect.'],
            ], 'Invalid current password');
        }

        $user->password = $newPassword;
        $user->save();

        return $this->success(null, 'Password changed successfully', 200);
    }

    protected function resolveTokenName(Request $request): string
    {
        if ($request->filled('device_name')) {
            return $request->string('device_name')->toString();
        }

        return $request->userAgent() ?: 'api-client';
    }
}
