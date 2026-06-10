<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Gr;
use App\Models\Freight;
use App\Models\Vehicle;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'role:SuperAdmin']);
    }

    /**
     * SuperAdmin cross-branch dashboard.
     * Per SXPRESS_LOGIC_SKILL section 12.
     */
    public function index()
    {
        // Overall KPIs
        $totalGRsToday = Gr::whereDate('copy_date', Carbon::today())->count();
        $totalGRsMonth = Gr::whereMonth('copy_date', Carbon::now()->month)
                           ->whereYear('copy_date', Carbon::now()->year)
                           ->count();

        $pendingTopay = Gr::where('to_pay', 1)
                          ->where('topay_collected', 0)
                          ->sum('total_amount');

        $totalRevenue = Gr::whereMonth('copy_date', Carbon::now()->month)
                          ->whereYear('copy_date', Carbon::now()->year)
                          ->sum('total_amount');

        $activeVehicles = Vehicle::where('status', 'active')->count();

        // Branch-wise summary
        $branchSummary = Branch::withCount([
            'grs as grs_count',
            'grs as grs_today_count' => fn($q) => $q->whereDate('copy_date', Carbon::today()),
            'grs as pending_topay_count' => fn($q) => $q->where('to_pay', 1)->where('topay_collected', 0),
            'grs as month_revenue' => fn($q) => $q
                ->whereMonth('copy_date', Carbon::now()->month)
                ->whereYear('copy_date', Carbon::now()->year),
        ])->get();

        // Recent GRs across all branches
        $recentGRs = Gr::with('creator')
            ->latest()
            ->take(20)
            ->get();

        return view('admin.superadmin.dashboard', compact(
            'totalGRsToday',
            'totalGRsMonth',
            'pendingTopay',
            'totalRevenue',
            'activeVehicles',
            'branchSummary',
            'recentGRs'
        ));
    }
}