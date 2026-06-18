<?php

namespace App\Services;

use App\Models\Accounting\GstEntry;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * GstService - Business logic for GST calculation, tax type determination,
 * and report aggregation for Phase 11.
 */
class GstService
{
    const VALID_RATES = [5, 12];
    const DEFAULT_HSN_SAC = '996511'; // SAC code for Goods Transport Agency

    /**
     * Determine GST type based on origin and destination states.
     * Same state = intra-state (CGST+SGST), different = inter-state (IGST).
     */
    public function determineGstType(string $originState, string $destinationState): string
    {
        $origin = strtolower(trim($originState));
        $destination = strtolower(trim($destinationState));

        if ($origin === $destination) {
            return 'cgst_sgst';
        }

        return 'igst';
    }

    /**
     * Calculate GST amounts for a given taxable value and rate.
     * Returns ['cgst_amount', 'sgst_amount', 'igst_amount', 'total_tax', 'gst_type']
     */
    public function calculateGst(float $taxableValue, float $rate, string $gstType): array
    {
        $totalTax = round($taxableValue * $rate / 100, 2);

        if ($gstType === 'igst') {
            return [
                'cgst_amount' => 0,
                'sgst_amount' => 0,
                'igst_amount' => $totalTax,
                'total_tax'   => $totalTax,
                'gst_type'    => 'igst',
            ];
        }

        // CGST + SGST split equally
        $sgst = round($totalTax / 2, 2);
        // Handle rounding: if 2*sgst != totalTax, adjust CGST
        $cgst = $totalTax - $sgst;

        return [
            'cgst_amount' => $cgst,
            'sgst_amount' => $sgst,
            'igst_amount' => 0,
            'total_tax'   => $totalTax,
            'gst_type'    => 'cgst_sgst',
        ];
    }

    /**
     * Resolve the current Indian Financial Year boundaries (April 1 - March 31).
     *
     * @return array [from_date, to_date] as Y-m-d strings
     */
    public function resolveFinancialYearDates(): array
    {
        $today = Carbon::today();

        if ($today->month >= 4) {
            $from = Carbon::create($today->year, 4, 1);
            $to = Carbon::create($today->year + 1, 3, 31);
        } else {
            $from = Carbon::create($today->year - 1, 4, 1);
            $to = Carbon::create($today->year, 3, 31);
        }

        return [$from->format('Y-m-d'), $to->format('Y-m-d')];
    }

    /**
     * Get GST summary data (output totals, input totals, net).
     */
    public function getSummaryData(string $fromDate, string $toDate, ?string $branch = null): array
    {
        $query = GstEntry::query()
            ->select(
                'tax_direction',
                DB::raw('COALESCE(SUM(cgst_amount), 0) as total_cgst'),
                DB::raw('COALESCE(SUM(sgst_amount), 0) as total_sgst'),
                DB::raw('COALESCE(SUM(igst_amount), 0) as total_igst'),
                DB::raw('COALESCE(SUM(total_tax), 0) as total_tax')
            )
            ->dateRange($fromDate, $toDate)
            ->groupBy('tax_direction');

        if ($branch) {
            $query->forBranch($branch);
        }

        $results = $query->get()->keyBy('tax_direction');

        $output = [
            'cgst'  => (float) ($results->get('output')->total_cgst ?? 0),
            'sgst'  => (float) ($results->get('output')->total_sgst ?? 0),
            'igst'  => (float) ($results->get('output')->total_igst ?? 0),
            'total' => (float) ($results->get('output')->total_tax ?? 0),
        ];

        $input = [
            'cgst'  => (float) ($results->get('input')->total_cgst ?? 0),
            'sgst'  => (float) ($results->get('input')->total_sgst ?? 0),
            'igst'  => (float) ($results->get('input')->total_igst ?? 0),
            'total' => (float) ($results->get('input')->total_tax ?? 0),
        ];

        $net = [
            'cgst'  => round($output['cgst'] - $input['cgst'], 2),
            'sgst'  => round($output['sgst'] - $input['sgst'], 2),
            'igst'  => round($output['igst'] - $input['igst'], 2),
            'total' => round($output['total'] - $input['total'], 2),
        ];

        return [
            'output' => $output,
            'input'  => $input,
            'net'    => $net,
        ];
    }

    /**
     * Get collection (output tax) report data with pagination.
     */
    public function getCollectionData(string $fromDate, string $toDate, ?string $branch = null, ?string $gstTypeFilter = null): array
    {
        $baseQuery = DB::table('gst_entries as ge')
            ->join('grs as g', function ($join) {
                $join->on('ge.taxable_id', '=', 'g.id')
                     ->where('ge.taxable_type', '=', 'App\\Models\\Gr');
            })
            ->where('ge.tax_direction', 'output')
            ->whereBetween('ge.transaction_date', [$fromDate, $toDate]);

        if ($branch) {
            $baseQuery->where('ge.branch', $branch);
        }

        if ($gstTypeFilter) {
            $baseQuery->where('ge.gst_type', $gstTypeFilter);
        }

        // Get totals (without pagination)
        $totals = (clone $baseQuery)->select([
            DB::raw('COALESCE(SUM(ge.taxable_value), 0) as taxable_value'),
            DB::raw('COALESCE(SUM(ge.cgst_amount), 0) as cgst_amount'),
            DB::raw('COALESCE(SUM(ge.sgst_amount), 0) as sgst_amount'),
            DB::raw('COALESCE(SUM(ge.igst_amount), 0) as igst_amount'),
            DB::raw('COALESCE(SUM(ge.total_tax), 0) as total_tax'),
        ])->first();

        // Get paginated entries
        $entries = $baseQuery->select([
            'ge.transaction_date',
            'ge.party_name',
            'ge.party_gst_number',
            'ge.taxable_value',
            'ge.cgst_amount',
            'ge.sgst_amount',
            'ge.igst_amount',
            'ge.total_tax',
            'ge.gst_type',
            'ge.branch',
            'g.gr_no',
        ])
        ->orderBy('ge.transaction_date', 'desc')
        ->paginate(50);

        return [
            'entries' => $entries,
            'totals'  => [
                'taxable_value' => (float) $totals->taxable_value,
                'cgst_amount'   => (float) $totals->cgst_amount,
                'sgst_amount'   => (float) $totals->sgst_amount,
                'igst_amount'   => (float) $totals->igst_amount,
                'total_tax'     => (float) $totals->total_tax,
            ],
        ];
    }

    /**
     * Get liability report grouped by month.
     */
    public function getLiabilityData(string $fromDate, string $toDate): array
    {
        $results = DB::table('gst_entries')
            ->select([
                DB::raw("DATE_FORMAT(transaction_date, '%Y-%m') as month"),
                'tax_direction',
                DB::raw('COALESCE(SUM(cgst_amount), 0) as cgst'),
                DB::raw('COALESCE(SUM(sgst_amount), 0) as sgst'),
                DB::raw('COALESCE(SUM(igst_amount), 0) as igst'),
                DB::raw('COALESCE(SUM(total_tax), 0) as total'),
            ])
            ->whereBetween('transaction_date', [$fromDate, $toDate])
            ->groupBy('month', 'tax_direction')
            ->orderBy('month')
            ->get();

        $months = [];

        foreach ($results as $row) {
            $month = $row->month;

            if (!isset($months[$month])) {
                $months[$month] = [
                    'output' => ['cgst' => 0, 'sgst' => 0, 'igst' => 0, 'total' => 0],
                    'input'  => ['cgst' => 0, 'sgst' => 0, 'igst' => 0, 'total' => 0],
                    'net'    => ['cgst' => 0, 'sgst' => 0, 'igst' => 0, 'total' => 0],
                ];
            }

            $months[$month][$row->tax_direction] = [
                'cgst'  => (float) $row->cgst,
                'sgst'  => (float) $row->sgst,
                'igst'  => (float) $row->igst,
                'total' => (float) $row->total,
            ];
        }

        // Calculate net for each month and build grand total
        $grandTotal = [
            'output' => ['cgst' => 0, 'sgst' => 0, 'igst' => 0, 'total' => 0],
            'input'  => ['cgst' => 0, 'sgst' => 0, 'igst' => 0, 'total' => 0],
            'net'    => ['cgst' => 0, 'sgst' => 0, 'igst' => 0, 'total' => 0],
            'credit_available' => false,
        ];

        foreach ($months as $month => &$data) {
            $data['net'] = [
                'cgst'  => round($data['output']['cgst'] - $data['input']['cgst'], 2),
                'sgst'  => round($data['output']['sgst'] - $data['input']['sgst'], 2),
                'igst'  => round($data['output']['igst'] - $data['input']['igst'], 2),
                'total' => round($data['output']['total'] - $data['input']['total'], 2),
            ];

            // Check if any net component is negative (credit available)
            if ($data['net']['cgst'] < 0 || $data['net']['sgst'] < 0 || $data['net']['igst'] < 0 || $data['net']['total'] < 0) {
                $data['credit_available'] = true;
            }

            // Accumulate grand totals
            foreach (['output', 'input'] as $direction) {
                foreach (['cgst', 'sgst', 'igst', 'total'] as $component) {
                    $grandTotal[$direction][$component] += $data[$direction][$component];
                }
            }
        }
        unset($data);

        // Calculate grand total net
        $grandTotal['net'] = [
            'cgst'  => round($grandTotal['output']['cgst'] - $grandTotal['input']['cgst'], 2),
            'sgst'  => round($grandTotal['output']['sgst'] - $grandTotal['input']['sgst'], 2),
            'igst'  => round($grandTotal['output']['igst'] - $grandTotal['input']['igst'], 2),
            'total' => round($grandTotal['output']['total'] - $grandTotal['input']['total'], 2),
        ];

        if ($grandTotal['net']['cgst'] < 0 || $grandTotal['net']['sgst'] < 0 || $grandTotal['net']['igst'] < 0 || $grandTotal['net']['total'] < 0) {
            $grandTotal['credit_available'] = true;
        }

        return [
            'months'      => $months,
            'grand_total' => $grandTotal,
        ];
    }

    /**
     * Get input tax report data with pagination.
     */
    public function getInputTaxData(string $fromDate, string $toDate, ?string $branch = null, ?string $expenseType = null): array
    {
        $baseQuery = DB::table('gst_entries as ge')
            ->join('expenses as e', function ($join) {
                $join->on('ge.taxable_id', '=', 'e.id')
                     ->where('ge.taxable_type', '=', 'App\\Models\\Accounting\\Expense');
            })
            ->where('ge.tax_direction', 'input')
            ->whereBetween('ge.transaction_date', [$fromDate, $toDate])
            ->whereNull('e.deleted_at');

        if ($branch) {
            $baseQuery->where('ge.branch', $branch);
        }

        if ($expenseType) {
            $baseQuery->where('e.expense_type', $expenseType);
        }

        // Get totals (without pagination)
        $totals = (clone $baseQuery)->select([
            DB::raw('COALESCE(SUM(ge.taxable_value), 0) as taxable_value'),
            DB::raw('COALESCE(SUM(ge.cgst_amount), 0) as cgst_amount'),
            DB::raw('COALESCE(SUM(ge.sgst_amount), 0) as sgst_amount'),
            DB::raw('COALESCE(SUM(ge.igst_amount), 0) as igst_amount'),
            DB::raw('COALESCE(SUM(ge.total_tax), 0) as total_tax'),
        ])->first();

        // Get paginated entries
        $entries = $baseQuery->select([
            'ge.transaction_date',
            'ge.party_name',
            'ge.party_gst_number',
            'ge.taxable_value',
            'ge.cgst_amount',
            'ge.sgst_amount',
            'ge.igst_amount',
            'ge.total_tax',
            'ge.gst_type',
            'ge.branch',
            'e.expense_no',
            'e.expense_type',
        ])
        ->orderBy('ge.transaction_date', 'desc')
        ->paginate(50);

        return [
            'entries' => $entries,
            'totals'  => [
                'taxable_value' => (float) $totals->taxable_value,
                'cgst_amount'   => (float) $totals->cgst_amount,
                'sgst_amount'   => (float) $totals->sgst_amount,
                'igst_amount'   => (float) $totals->igst_amount,
                'total_tax'     => (float) $totals->total_tax,
            ],
        ];
    }

    /**
     * Get output tax report grouped by GST type with pagination.
     */
    public function getOutputTaxData(string $fromDate, string $toDate, ?string $branch = null, ?string $rateFilter = null): array
    {
        $baseQuery = DB::table('gst_entries as ge')
            ->join('grs as g', function ($join) {
                $join->on('ge.taxable_id', '=', 'g.id')
                     ->where('ge.taxable_type', '=', 'App\\Models\\Gr');
            })
            ->where('ge.tax_direction', 'output')
            ->whereBetween('ge.transaction_date', [$fromDate, $toDate]);

        if ($branch) {
            $baseQuery->where('ge.branch', $branch);
        }

        if ($rateFilter) {
            $baseQuery->where('ge.gst_rate', $rateFilter);
        }

        // Get paginated entries
        $entries = (clone $baseQuery)->select([
            'ge.transaction_date',
            'ge.party_name',
            'ge.party_gst_number',
            'ge.taxable_value',
            'ge.cgst_amount',
            'ge.sgst_amount',
            'ge.igst_amount',
            'ge.total_tax',
            'ge.gst_type',
            'ge.gst_rate',
            'ge.branch',
            'g.gr_no',
        ])
        ->orderBy('ge.gst_type', 'asc')
        ->orderBy('ge.transaction_date', 'desc')
        ->paginate(50);

        // Get summary by rate
        $summary = (clone $baseQuery)->select([
            'ge.gst_rate',
            DB::raw('COALESCE(SUM(ge.taxable_value), 0) as taxable_value'),
            DB::raw('COALESCE(SUM(ge.total_tax), 0) as total_tax'),
        ])
        ->groupBy('ge.gst_rate')
        ->orderBy('ge.gst_rate', 'asc')
        ->get()
        ->map(function ($row) {
            return [
                'gst_rate'      => (float) $row->gst_rate,
                'taxable_value' => (float) $row->taxable_value,
                'total_tax'     => (float) $row->total_tax,
            ];
        })
        ->toArray();

        return [
            'entries' => $entries,
            'summary' => $summary,
        ];
    }
}
