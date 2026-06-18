<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\BranchSerial;
use Illuminate\Support\Facades\DB;

class SerialNumberService
{
    /**
     * Get current financial year prefix.
     * If month >= April, FY = current_year_short/next_year_short (e.g. June 2026 → '26/27')
     * If Jan-March, FY = prev_year/current_year (e.g. Feb 2027 → '26/27')
     */
    public static function currentFyPrefix(): string
    {
        $month = (int) now()->format('n');
        $year = (int) now()->format('Y');

        if ($month >= 4) {
            return substr($year, 2) . '/' . substr($year + 1, 2);
        } else {
            return substr($year - 1, 2) . '/' . substr($year, 2);
        }
    }

    /**
     * Generate next serial number for a branch+module (atomic with locking).
     * Returns formatted string like '26/27 - 000045'.
     *
     * @throws \Exception if range not configured or limit reached
     */
    public static function generateNext(int $branchId, string $module): string
    {
        return DB::transaction(function () use ($branchId, $module) {
            $fyYear = self::currentFyPrefix();

            $serial = BranchSerial::where('branch_id', $branchId)
                ->where('module', $module)
                ->where('fy_year', $fyYear)
                ->lockForUpdate()
                ->first();

            if (!$serial) {
                throw new \Exception("Serial range not configured for this branch (module: {$module}, FY: {$fyYear})");
            }

            // First use: start from range_start. Subsequent: increment current_value.
            if ($serial->current_value == 0) {
                $nextValue = $serial->range_start;
            } else {
                $nextValue = $serial->current_value + 1;
            }

            if ($nextValue > $serial->range_end) {
                throw new \Exception("Serial limit reached for module '{$module}'. Maximum: {$serial->range_end}");
            }

            $serial->update(['current_value' => $nextValue]);

            return $fyYear . ' - ' . str_pad($nextValue, 6, '0', STR_PAD_LEFT);
        });
    }

    /**
     * Preview next number (non-locking, for form display).
     * Returns what the next number would be without actually consuming it.
     */
    public static function previewNext(int $branchId, string $module): string
    {
        $fyYear = self::currentFyPrefix();

        $serial = BranchSerial::where('branch_id', $branchId)
            ->where('module', $module)
            ->where('fy_year', $fyYear)
            ->first();

        if (!$serial) {
            return $fyYear . ' - 000000';
        }

        if ($serial->current_value == 0) {
            $nextValue = $serial->range_start;
        } else {
            $nextValue = $serial->current_value + 1;
        }

        return $fyYear . ' - ' . str_pad($nextValue, 6, '0', STR_PAD_LEFT);
    }

    /**
     * Check if a range overlaps with existing assignments for a module+fy.
     * Returns the conflicting branch name or null.
     */
    public static function detectCollision(string $module, string $fyYear, int $start, int $end, ?int $excludeBranchId = null): ?string
    {
        $query = BranchSerial::where('module', $module)
            ->where('fy_year', $fyYear)
            ->where(function ($q) use ($start, $end) {
                // Overlap condition: existing.start <= new.end AND existing.end >= new.start
                $q->where('range_start', '<=', $end)
                  ->where('range_end', '>=', $start);
            });

        if ($excludeBranchId) {
            $query->where('branch_id', '!=', $excludeBranchId);
        }

        $conflict = $query->first();

        if ($conflict) {
            $branch = Branch::find($conflict->branch_id);
            return $branch ? $branch->branch_name : 'Unknown Branch';
        }

        return null;
    }

    /**
     * Get remaining count for a branch+module in current FY.
     */
    public static function remaining(int $branchId, string $module): int
    {
        $fyYear = self::currentFyPrefix();

        $serial = BranchSerial::where('branch_id', $branchId)
            ->where('module', $module)
            ->where('fy_year', $fyYear)
            ->first();

        if (!$serial) {
            return 0;
        }

        if ($serial->current_value == 0) {
            // Not yet started — full range available
            return $serial->range_end - $serial->range_start + 1;
        }

        return max(0, $serial->range_end - $serial->current_value);
    }
}
