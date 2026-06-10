<?php

namespace App\Http\Controllers\dash;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Gr;
use App\Models\Vehicle;
use App\Traits\OfficeScopeTrait;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    use OfficeScopeTrait;

    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Role-based dashboard.
     * SuperAdmin: cross-branch overview with filter
     * Admin/Manager: own-branch operational KPIs
     * Staff: own-branch day-to-day summary
     * Viewer: read-only summary
     */
    public function index(Request $request)
    {
        $user = Auth::user();

        // Determine office scope
        if ($this->isSuperAdmin() && $request->filled('branch')) {
            $office = $request->branch;
        } else {
            $office = $user->office;
        }

        // Common data for all roles
        $today = Carbon::now()->format('d M Y');
        $branches = $this->isSuperAdmin() ? Branch::active()->orderBy('branch_name')->pluck('branch_name') : collect();

        // Build KPIs based on office scope
        $kpis = $this->buildKpis($office);
        $charts = $this->buildCharts($office);
        $recentGRs = $this->getRecentGrs($office);

        return view('admin.dashboard', compact(
            'user', 'office', 'today', 'branches', 'kpis', 'charts', 'recentGRs'
        ));
    }

    private function buildKpis(string $office): array
    {
        $isGlobal = $this->isSuperAdmin() && request('branch') === null && !request()->filled('branch');

        $query = fn() => $isGlobal ? Gr::query() : Gr::where('office', $office);

        return [
            'grs_today' => $query()->whereDate('copy_date', Carbon::today())->count(),

            'grs_month' => $query()
                ->whereMonth('copy_date', Carbon::now()->month)
                ->whereYear('copy_date', Carbon::now()->year)
                ->count(),

            'revenue_month' => $query()
                ->whereMonth('copy_date', Carbon::now()->month)
                ->whereYear('copy_date', Carbon::now()->year)
                ->sum('total_amount'),

            'pending_topay_amount' => $query()
                ->where('to_pay', 1)
                ->where(function($q) { $q->where('topay_collected', 0)->orWhereNull('topay_collected'); })
                ->sum('total_amount'),

            'pending_topay_count' => $query()
                ->where('to_pay', 1)
                ->where(function($q) { $q->where('topay_collected', 0)->orWhereNull('topay_collected'); })
                ->count(),

            'collected_topay_month' => $query()
                ->where('to_pay', 1)
                ->where('topay_collected', 1)
                ->whereMonth('topay_collected_date', Carbon::now()->month)
                ->sum('total_amount'),

            'pending_delivery' => $query()
                ->whereIn('status', ['dispatched', 'in_transit'])
                ->count(),

            'pending_pod' => $query()
                ->where('status', 'delivered')
                ->whereNull('pod_file')
                ->count(),

            'active_vehicles' => Vehicle::where('status', 'active')->count(),

            'delivered_today' => $query()
                ->where('status', 'delivered')
                ->whereDate('status_updated_at', Carbon::today())
                ->count(),
        ];
    }

    private function buildCharts(string $office): array
    {
        $isGlobal = $this->isSuperAdmin() && !request()->filled('branch');

        $query = fn() => $isGlobal ? Gr::query() : Gr::where('office', $office);

        // GRs per day (last 30 days)
        $grsPerDay = $query()
            ->whereDate('copy_date', '>=', Carbon::now()->subDays(30))
            ->selectRaw('DATE(copy_date) as date, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Paid vs ToPay this month
        $paidCount = $query()
            ->where('paid', 1)
            ->whereMonth('copy_date', Carbon::now()->month)
            ->whereYear('copy_date', Carbon::now()->year)
            ->count();

        $topayCount = $query()
            ->where('to_pay', 1)
            ->whereMonth('copy_date', Carbon::now()->month)
            ->whereYear('copy_date', Carbon::now()->year)
            ->count();

        // Monthly revenue (last 6 months)
        $monthlyRevenue = [];
        for ($i = 5; $i >= 0; $i--) {
            $month = Carbon::now()->subMonths($i);
            $total = $query()
                ->whereMonth('copy_date', $month->month)
                ->whereYear('copy_date', $month->year)
                ->sum('total_amount');
            $monthlyRevenue[] = [
                'month' => $month->format('M Y'),
                'total' => (float) $total,
            ];
        }

        return [
            'grs_per_day' => $grsPerDay,
            'paid_count' => $paidCount,
            'topay_count' => $topayCount,
            'monthly_revenue' => $monthlyRevenue,
        ];
    }

    private function getRecentGrs(string $office)
    {
        $isGlobal = $this->isSuperAdmin() && !request()->filled('branch');

        $query = $isGlobal ? Gr::query() : Gr::where('office', $office);

        return $query->latest()->take(10)->get();
    }
}
