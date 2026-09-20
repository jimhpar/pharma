<?php

namespace App\Http\Controllers;

use App\Support\BranchContext;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Validation\ValidationException;

class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;

    protected function branchContext(): BranchContext
    {
        return app(BranchContext::class);
    }

    protected function currentBranchId(): ?int
    {
        return $this->branchContext()->currentBranchId(auth()->user());
    }

    protected function resolveReportBranchId(Request $request, string $inputKey = 'branch_id'): ?int
    {
        $user = $request->user();
        $requestedBranchId = $request->filled($inputKey) ? (int) $request->input($inputKey) : null;

        if (!$this->branchContext()->hasCrossBranchAccess($user)) {
            return $this->currentBranchId();
        }

        if ($requestedBranchId === null) {
            return null;
        }

        if (!$this->branchContext()->hasBranchAccess($user, $requestedBranchId)) {
            throw ValidationException::withMessages([
                $inputKey => 'You do not have access to the selected branch.',
            ]);
        }

        return $requestedBranchId;
    }

    protected function scopeReportBranch($query, Request $request, string $column = 'branch_id', string $inputKey = 'branch_id', bool $includeNullForCrossBranch = true)
    {
        $user = $request->user();
        $branchId = $this->resolveReportBranchId($request, $inputKey);

        if ($branchId !== null) {
            return $query->where($column, $branchId);
        }

        if (!$this->branchContext()->hasCrossBranchAccess($user)) {
            return $query->whereRaw('1 = 0');
        }

        $branchIds = $this->branchContext()->accessibleBranchIds($user);

        if ($branchIds === []) {
            return $includeNullForCrossBranch
                ? $query->whereNull($column)
                : $query->whereRaw('1 = 0');
        }

        return $query->where(function ($branchQuery) use ($column, $branchIds, $includeNullForCrossBranch) {
            $branchQuery->whereIn($column, $branchIds);

            if ($includeNullForCrossBranch) {
                $branchQuery->orWhereNull($column);
            }
        });
    }

    protected function downloadCsv(string $filename, array $headers, iterable $rows)
    {
        $filename = str_ends_with($filename, '.csv') ? $filename : $filename . '.csv';

        return response()->streamDownload(function () use ($headers, $rows) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));
            fputcsv($handle, $headers);

            foreach ($rows as $row) {
                fputcsv($handle, $row instanceof Collection ? $row->all() : (array) $row);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
