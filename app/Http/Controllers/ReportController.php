<?php

namespace App\Http\Controllers;

use App\Models\Gr;
use App\Models\Freight;
use App\Models\Vehicle;
use App\Models\truckdriver;
use App\Models\Branch;
use App\Traits\OfficeScopeTrait;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * ReportController — SXpress Logistics Reports
 *
 * All reports: Manager+ access. Office-scoped (Admin sees own branch, SuperAdmin sees all).
 * Every report supports: filters, CSV export (?export=csv), and browser print (window.print).
 */
class ReportController extends Controller
{
    use OfficeScopeTrait;

    public function __construct()
    {
        $this->middleware(['auth', 'role:SuperAdmin|BranchManager']);
    }

    /**
     * Reports Dashboard — index of all available reports
     */
    public function index()
    {
        $branches = $this->getBranchOptions();
        return view('admin.reports.index', compact('branches'));
    }

    // ═══════════════════════════════════════════════════════════════════════════
    // GR REGISTER
    // ═══════════════════════════════════════════════════════════════════════════

    public function grRegister(Request $request)
    {
        $fromDate = $request->from_date ?? Carbon::now()->startOfMonth()->format('Y-m-d');
        $toDate = $request->to_date ?? Carbon::now()->format('Y-m-d');

        $query = Gr::query();
        $this->officeScope($query);

        $query->whereDate('copy_date', '>=', $fromDate)
              ->whereDate('copy_date', '<=', $toDate);

        if ($this->isSuperAdmin() && $branch = $request->branch) {
            $query->where('office', $branch);
        }
        if ($status = $request->status) {
            $query->where('status', $status);
        }
        if ($payment = $request->payment) {
            if ($payment === 'paid') $query->where('paid', 1);
            if ($payment === 'to_pay') $query->where('to_pay', 1);
        }

        $grs = $query->orderBy('copy_date', 'desc')->get();
        $branches = $this->getBranchOptions();

        $totals = [
            'count'   => $grs->count(),
            'freight' => $grs->sum('frieght_amount'),
            'total'   => $grs->sum('total_amount'),
            'weight'  => $grs->sum('weight'),
        ];

        if ($request->export === 'csv') {
            return $this->exportCsv("GR_Register_{$fromDate}_to_{$toDate}.csv", [
                'GR No', 'Date', 'From', 'To', 'Consignor', 'Consignee',
                'Weight', 'Freight', 'Total', 'Type', 'Status', 'Office'
            ], $grs->map(fn($gr) => [
                $gr->gr_no, optional($gr->copy_date)->format('Y-m-d'), $gr->from_dest, $gr->to_dest,
                $gr->consignor, $gr->consignee, $gr->weight, $gr->frieght_amount,
                $gr->total_amount, $gr->paid ? 'Paid' : 'To-Pay', $gr->status ?? 'created', $gr->office,
            ])->toArray());
        }

        $reportTitle = 'GR Register';
        $reportSubtitle = Carbon::parse($fromDate)->format('d M Y') . ' — ' . Carbon::parse($toDate)->format('d M Y');

        return view('admin.reports.gr_register', compact(
            'grs', 'branches', 'totals', 'fromDate', 'toDate', 'reportTitle', 'reportSubtitle'
        ));
    }

    // ═══════════════════════════════════════════════════════════════════════════
    // DAILY BOOKING
    // ═══════════════════════════════════════════════════════════════════════════

    public function dailyBooking(Request $request)
    {
        $date = $request->date ?? Carbon::now()->format('Y-m-d');

        $query = Gr::whereDate('copy_date', $date);
        $this->officeScope($query);

        if ($this->isSuperAdmin() && $branch = $request->branch) {
            $query->where('office', $branch);
        }

        $grs = $query->orderBy('gr_no')->get();

        $totals = [
            'count'        => $grs->count(),
            'freight'      => $grs->sum('frieght_amount'),
            'total'        => $grs->sum('total_amount'),
            'weight'       => $grs->sum('weight'),
            'nugs'         => $grs->sum('nugs'),
            'paid_count'   => $grs->where('paid', 1)->count(),
            'topay_count'  => $grs->where('to_pay', 1)->count(),
            'paid_amount'  => $grs->where('paid', 1)->sum('total_amount'),
            'topay_amount' => $grs->where('to_pay', 1)->sum('total_amount'),
        ];

        if ($request->export === 'csv') {
            return $this->exportCsv("Daily_Booking_{$date}.csv", [
                'GR No', 'From', 'To', 'Consignor', 'Consignee', 'Pkgs', 'Weight', 'Freight', 'Total', 'Type'
            ], $grs->map(fn($gr) => [
                $gr->gr_no, $gr->from_dest, $gr->to_dest, $gr->consignor, $gr->consignee,
                $gr->nugs, $gr->weight, $gr->frieght_amount, $gr->total_amount,
                $gr->paid ? 'Paid' : 'To-Pay',
            ])->toArray());
        }

        $branches = $this->getBranchOptions();
        $reportTitle = 'Daily Booking Report';
        $reportSubtitle = Carbon::parse($date)->format('d M Y (l)');

        return view('admin.reports.daily_booking', compact(
            'grs', 'branches', 'totals', 'date', 'reportTitle', 'reportSubtitle'
        ));
    }

    // ═══════════════════════════════════════════════════════════════════════════
    // REVENUE REPORT
    // ═══════════════════════════════════════════════════════════════════════════

    public function revenue(Request $request)
    {
        $fromDate = $request->from_date ?? Carbon::now()->startOfMonth()->format('Y-m-d');
        $toDate = $request->to_date ?? Carbon::now()->format('Y-m-d');

        $query = Gr::whereDate('copy_date', '>=', $fromDate)->whereDate('copy_date', '<=', $toDate);
        $this->officeScope($query);

        if ($this->isSuperAdmin() && $branch = $request->branch) {
            $query->where('office', $branch);
        }

        $grs = $query->get();

        // Monthly breakdown
        $monthlyQuery = Gr::whereDate('copy_date', '>=', $fromDate)->whereDate('copy_date', '<=', $toDate);
        $this->officeScope($monthlyQuery);
        if ($this->isSuperAdmin() && $request->branch) {
            $monthlyQuery->where('office', $request->branch);
        }

        $monthly = $monthlyQuery
            ->selectRaw('MONTH(copy_date) as month, YEAR(copy_date) as year,
                         COUNT(*) as gr_count,
                         SUM(frieght_amount) as freight_total,
                         SUM(total_amount) as total_revenue,
                         SUM(CASE WHEN paid = 1 THEN total_amount ELSE 0 END) as paid_total,
                         SUM(CASE WHEN to_pay = 1 AND topay_collected = 0 THEN total_amount ELSE 0 END) as pending_total')
            ->groupByRaw('YEAR(copy_date), MONTH(copy_date)')
            ->orderByRaw('YEAR(copy_date), MONTH(copy_date)')
            ->get();

        $totals = [
            'gr_count'  => $grs->count(),
            'freight'   => $grs->sum('frieght_amount'),
            'total'     => $grs->sum('total_amount'),
            'collected' => $grs->where('paid', 1)->sum('total_amount') + $grs->where('topay_collected', 1)->sum('total_amount'),
            'pending'   => $grs->where('to_pay', 1)->where('topay_collected', 0)->sum('total_amount'),
        ];

        if ($request->export === 'csv') {
            return $this->exportCsv("Revenue_{$fromDate}_to_{$toDate}.csv", [
                'Month', 'GR Count', 'Freight', 'Total Revenue', 'Paid', 'Pending'
            ], $monthly->map(fn($m) => [
                Carbon::create($m->year, $m->month)->format('F Y'),
                $m->gr_count, $m->freight_total, $m->total_revenue, $m->paid_total, $m->pending_total,
            ])->toArray());
        }

        $branches = $this->getBranchOptions();
        $reportTitle = 'Revenue Report';
        $reportSubtitle = Carbon::parse($fromDate)->format('d M Y') . ' — ' . Carbon::parse($toDate)->format('d M Y');

        return view('admin.reports.revenue', compact(
            'grs', 'branches', 'totals', 'monthly', 'fromDate', 'toDate', 'reportTitle', 'reportSubtitle'
        ));
    }

    // ═══════════════════════════════════════════════════════════════════════════
    // BRANCH PERFORMANCE (SuperAdmin only sees all; Admin/Manager see own)
    // ═══════════════════════════════════════════════════════════════════════════

    public function branchPerformance(Request $request)
    {
        $fromDate = $request->from_date ?? Carbon::now()->startOfMonth()->format('Y-m-d');
        $toDate = $request->to_date ?? Carbon::now()->format('Y-m-d');

        $branchQuery = Branch::query();

        // Office isolation: non-SuperAdmin only sees own branch
        if (!$this->isSuperAdmin()) {
            $branchQuery->where('branch_name', $this->currentOffice());
        }

        $branchStats = $branchQuery->withCount([
            'grs as total_grs' => function ($q) use ($fromDate, $toDate) {
                $q->whereDate('copy_date', '>=', $fromDate)->whereDate('copy_date', '<=', $toDate);
            },
            'grs as dispatched_grs' => function ($q) use ($fromDate, $toDate) {
                $q->whereDate('copy_date', '>=', $fromDate)->whereDate('copy_date', '<=', $toDate)
                  ->whereNotIn('status', ['created', 'cancelled']);
            },
            'grs as delivered_grs' => function ($q) use ($fromDate, $toDate) {
                $q->whereDate('copy_date', '>=', $fromDate)->whereDate('copy_date', '<=', $toDate)
                  ->whereIn('status', ['delivered', 'closed']);
            },
        ])
        ->withSum(['grs as total_freight_sum' => function ($q) use ($fromDate, $toDate) {
            $q->whereDate('copy_date', '>=', $fromDate)->whereDate('copy_date', '<=', $toDate);
        }], 'frieght_amount')
        ->withSum(['grs as total_revenue_sum' => function ($q) use ($fromDate, $toDate) {
            $q->whereDate('copy_date', '>=', $fromDate)->whereDate('copy_date', '<=', $toDate);
        }], 'total_amount')
        ->orderBy('branch_name')
        ->get();

        if ($request->export === 'csv') {
            return $this->exportCsv("Branch_Performance_{$fromDate}_to_{$toDate}.csv", [
                'Branch', 'Total GRs', 'Dispatched', 'Delivered', 'Freight (₹)', 'Revenue (₹)', 'Delivery %'
            ], $branchStats->map(fn($b) => [
                $b->branch_name, $b->total_grs, $b->dispatched_grs, $b->delivered_grs,
                $b->total_freight_sum ?? 0, $b->total_revenue_sum ?? 0,
                $b->total_grs > 0 ? round(($b->delivered_grs / $b->total_grs) * 100, 1) . '%' : 'N/A',
            ])->toArray());
        }

        $reportTitle = 'Branch Performance';
        $reportSubtitle = Carbon::parse($fromDate)->format('d M Y') . ' — ' . Carbon::parse($toDate)->format('d M Y');

        return view('admin.reports.branch_performance', compact(
            'branchStats', 'fromDate', 'toDate', 'reportTitle', 'reportSubtitle'
        ));
    }

    // ═══════════════════════════════════════════════════════════════════════════
    // PENDING POD — GRs dispatched/in_transit WITHOUT a POD file
    // ═══════════════════════════════════════════════════════════════════════════

    public function pendingPod(Request $request)
    {
        // FIX C1: whereNull (not whereNotNull) — pending means NO pod uploaded yet
        $query = Gr::whereIn('status', ['dispatched', 'in_transit'])
                   ->where(function ($q) {
                       $q->whereNull('pod_file')->orWhere('pod_file', '');
                   });
        $this->officeScope($query);

        if ($this->isSuperAdmin() && $branch = $request->branch) {
            $query->where('office', $branch);
        }

        $grs = $query->orderBy('copy_date', 'asc')->get()->map(function ($gr) {
            $gr->days_pending = Carbon::parse($gr->copy_date)->diffInDays(now());
            return $gr;
        });

        $totals = [
            'count'        => $grs->count(),
            'total_amount' => $grs->sum('total_amount'),
        ];

        if ($request->export === 'csv') {
            return $this->exportCsv('Pending_POD.csv', [
                'GR No', 'Date', 'From', 'To', 'Consignee', 'Weight', 'Amount', 'Days Pending', 'Status'
            ], $grs->map(fn($gr) => [
                $gr->gr_no, optional($gr->copy_date)->format('Y-m-d'), $gr->from_dest, $gr->to_dest,
                $gr->consignee, $gr->weight, $gr->total_amount, $gr->days_pending, $gr->status,
            ])->toArray());
        }

        $branches = $this->getBranchOptions();
        $reportTitle = 'Pending POD';
        $reportSubtitle = 'GRs dispatched/in-transit without POD upload';

        return view('admin.reports.pending_pod', compact(
            'grs', 'branches', 'totals', 'reportTitle', 'reportSubtitle'
        ));
    }

    // ═══════════════════════════════════════════════════════════════════════════
    // PENDING DELIVERY — GRs dispatched/in_transit not yet delivered
    // ═══════════════════════════════════════════════════════════════════════════

    public function pendingDelivery(Request $request)
    {
        $query = Gr::whereIn('status', ['dispatched', 'in_transit']);
        $this->officeScope($query);

        if ($this->isSuperAdmin() && $branch = $request->branch) {
            $query->where('office', $branch);
        }

        $grs = $query->orderBy('copy_date', 'asc')->get()->map(function ($gr) {
            $gr->days_in_transit = Carbon::parse($gr->status_updated_at ?? $gr->copy_date)->diffInDays(now());
            return $gr;
        });

        $totals = [
            'count'        => $grs->count(),
            'total_amount' => $grs->sum('total_amount'),
            'overdue'      => $grs->where('days_in_transit', '>', 7)->count(),
        ];

        if ($request->export === 'csv') {
            return $this->exportCsv('Pending_Delivery.csv', [
                'GR No', 'Date', 'From', 'To', 'Consignee', 'Weight', 'Amount', 'Days In Transit', 'Status'
            ], $grs->map(fn($gr) => [
                $gr->gr_no, optional($gr->copy_date)->format('Y-m-d'), $gr->from_dest, $gr->to_dest,
                $gr->consignee, $gr->weight, $gr->total_amount, $gr->days_in_transit, $gr->status,
            ])->toArray());
        }

        $branches = $this->getBranchOptions();
        $reportTitle = 'Pending Delivery';
        $reportSubtitle = $totals['overdue'] > 0
            ? "{$totals['count']} GRs in transit — {$totals['overdue']} overdue (>7 days)"
            : "{$totals['count']} GRs in transit";

        return view('admin.reports.pending_delivery', compact(
            'grs', 'branches', 'totals', 'reportTitle', 'reportSubtitle'
        ));
    }

    // ═══════════════════════════════════════════════════════════════════════════
    // PENDING TO-PAY — Uncollected To-Pay amounts with aging
    // ═══════════════════════════════════════════════════════════════════════════

    public function pendingTopay(Request $request)
    {
        $query = Gr::where('to_pay', 1)->where('topay_collected', 0);
        $this->officeScope($query);

        if ($this->isSuperAdmin() && $branch = $request->branch) {
            $query->where('office', $branch);
        }

        $grs = $query->orderBy('copy_date', 'asc')->get();

        // Aging buckets
        $aging = [
            'overdue_30' => $grs->filter(fn($gr) => Carbon::parse($gr->copy_date)->diffInDays(now()) > 30),
            'overdue_15' => $grs->filter(fn($gr) => ($d = Carbon::parse($gr->copy_date)->diffInDays(now())) > 15 && $d <= 30),
            'overdue_7'  => $grs->filter(fn($gr) => ($d = Carbon::parse($gr->copy_date)->diffInDays(now())) > 7 && $d <= 15),
            'recent'     => $grs->filter(fn($gr) => Carbon::parse($gr->copy_date)->diffInDays(now()) <= 7),
        ];

        $totals = [
            'count'        => $grs->count(),
            'total_amount' => $grs->sum('total_amount'),
        ];

        if ($request->export === 'csv') {
            return $this->exportCsv('Pending_ToPay.csv', [
                'GR No', 'Date', 'From', 'To', 'Consignee', 'Amount', 'Age (Days)', 'Office'
            ], $grs->map(fn($gr) => [
                $gr->gr_no, optional($gr->copy_date)->format('Y-m-d'), $gr->from_dest, $gr->to_dest,
                $gr->consignee, $gr->total_amount, Carbon::parse($gr->copy_date)->diffInDays(now()), $gr->office,
            ])->toArray());
        }

        $branches = $this->getBranchOptions();
        $reportTitle = 'Pending TO-PAY Collection';
        $reportSubtitle = '₹' . number_format($totals['total_amount'], 0) . ' uncollected across ' . $totals['count'] . ' GRs';

        return view('admin.reports.pending_topay', compact(
            'grs', 'branches', 'totals', 'aging', 'reportTitle', 'reportSubtitle'
        ));
    }

    // ═══════════════════════════════════════════════════════════════════════════
    // FREIGHT MEMO REPORT (rebuilt for challan-linked memos)
    // ═══════════════════════════════════════════════════════════════════════════

    public function freightReport(Request $request)
    {
        $fromDate = $request->from_date ?? Carbon::now()->startOfMonth()->format('Y-m-d');
        $toDate = $request->to_date ?? Carbon::now()->format('Y-m-d');

        // FIX H3: Query both fm_date (new) and memo_date (legacy)
        $query = Freight::where(function ($q) use ($fromDate, $toDate) {
            $q->where(function ($q2) use ($fromDate, $toDate) {
                $q2->whereNotNull('fm_date')
                   ->whereDate('fm_date', '>=', $fromDate)
                   ->whereDate('fm_date', '<=', $toDate);
            })->orWhere(function ($q2) use ($fromDate, $toDate) {
                $q2->whereNull('fm_date')
                   ->whereDate('memo_date', '>=', $fromDate)
                   ->whereDate('memo_date', '<=', $toDate);
            });
        });
        $this->officeScope($query);

        if ($this->isSuperAdmin() && $branch = $request->branch) {
            $query->where('office', $branch);
        }

        $memos = $query->orderByDesc('id')->get();

        // FIX C2: Use correct columns for new freight memos
        $totals = [
            'count'         => $memos->count(),
            'truck_freight' => $memos->sum('truck_freight'),
            'commission'    => $memos->sum('commission'),
            'balance_due'   => $memos->sum('balance_due'),
            'other_charges' => $memos->sum('other_charges'),
        ];

        if ($request->export === 'csv') {
            return $this->exportCsv("Freight_Memos_{$fromDate}_to_{$toDate}.csv", [
                'FM No', 'Date', 'Truck', 'From', 'To', 'Truck Freight', 'Commission', 'Balance Due', 'Office'
            ], $memos->map(fn($m) => [
                $m->fm_no ?: $m->memo_no,
                optional($m->fm_date ?? $m->memo_date)->format('Y-m-d'),
                $m->truck_no ?? '', $m->from_dest ?? '', $m->to_dest ?? '',
                $m->truck_freight ?? 0, $m->commission ?? 0, $m->balance_due ?? 0, $m->office,
            ])->toArray());
        }

        $branches = $this->getBranchOptions();
        $reportTitle = 'Freight Memo Report';
        $reportSubtitle = Carbon::parse($fromDate)->format('d M Y') . ' — ' . Carbon::parse($toDate)->format('d M Y');

        return view('admin.reports.freight', compact(
            'memos', 'branches', 'totals', 'fromDate', 'toDate', 'reportTitle', 'reportSubtitle'
        ));
    }

    // ═══════════════════════════════════════════════════════════════════════════
    // VEHICLE REPORT (office-scoped)
    // ═══════════════════════════════════════════════════════════════════════════

    public function vehicleReport(Request $request)
    {
        // FIX C4: Remove non-existent 'gatepasses' relationship
        $query = Vehicle::query();

        if ($status = $request->status) {
            $query->where('status', $status);
        }

        $vehicles = $query->orderBy('vehicle_number')->get();

        $totals = [
            'total'    => $vehicles->count(),
            'active'   => $vehicles->where('status', 'active')->count(),
            'inactive' => $vehicles->where('status', '!=', 'active')->count(),
        ];

        if ($request->export === 'csv') {
            return $this->exportCsv('Vehicle_Report.csv', [
                'Vehicle No', 'Type', 'Capacity', 'Owner', 'Status'
            ], $vehicles->map(fn($v) => [
                $v->vehicle_number, $v->vehicle_type ?? '', $v->capacity ?? '',
                $v->owner_name ?? '', $v->status,
            ])->toArray());
        }

        $reportTitle = 'Vehicle Report';

        return view('admin.reports.vehicle', compact('vehicles', 'totals', 'reportTitle'));
    }

    // ═══════════════════════════════════════════════════════════════════════════
    // DRIVER REPORT
    // ═══════════════════════════════════════════════════════════════════════════

    public function driverReport(Request $request)
    {
        $query = truckdriver::query();

        if ($request->has('status') && $request->status !== '') {
            $query->where('status', (int) $request->status);
        }

        $drivers = $query->orderBy('driver_name')->get();

        // FIX C3: status is tinyint (1=active, 0=inactive)
        $totals = [
            'total'    => $drivers->count(),
            'active'   => $drivers->where('status', 1)->count(),
            'inactive' => $drivers->where('status', 0)->count(),
        ];

        if ($request->export === 'csv') {
            return $this->exportCsv('Driver_Report.csv', [
                'Driver Name', 'Truck No', 'License', 'Mobile', 'Address', 'Status'
            ], $drivers->map(fn($d) => [
                $d->driver_name, $d->truck_no ?? '', $d->license ?? '',
                $d->mobile_no1 ?? '', $d->driver_address ?? '', $d->status ? 'Active' : 'Inactive',
            ])->toArray());
        }

        $reportTitle = 'Driver Report';

        return view('admin.reports.driver', compact('drivers', 'totals', 'reportTitle'));
    }

    // ═══════════════════════════════════════════════════════════════════════════
    // PRINT (generic redirect)
    // ═══════════════════════════════════════════════════════════════════════════

    public function print(Request $request)
    {
        $report = $request->report ?? 'gr_register';
        $params = $request->except(['_token', 'report']);
        return redirect()->route("reports.{$report}", $params);
    }

    // ═══════════════════════════════════════════════════════════════════════════
    // HELPERS
    // ═══════════════════════════════════════════════════════════════════════════

    /**
     * FIX C5: Proper StreamedResponse for CSV export
     */
    private function exportCsv(string $filename, array $headers, array $rows): StreamedResponse
    {
        return response()->streamDownload(function () use ($headers, $rows) {
            $output = fopen('php://output', 'w');
            // UTF-8 BOM for Excel compatibility
            fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));
            fputcsv($output, $headers);
            foreach ($rows as $row) {
                fputcsv($output, $row);
            }
            fclose($output);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * Get branch dropdown options (only for SuperAdmin)
     */
    private function getBranchOptions()
    {
        return $this->isSuperAdmin()
            ? Branch::active()->orderBy('branch_name')->pluck('branch_name')
            : collect();
    }
}
