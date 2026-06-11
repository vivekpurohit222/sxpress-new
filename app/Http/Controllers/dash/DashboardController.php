<?php

namespace App\Http\Controllers\dash;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Gr;
use App\Models\Freight;
use App\Models\Vehicle;
use App\Models\gatepass;
use App\Models\challan;
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

    public function index(Request $request)
    {
        $user = Auth::user();
        $office = $this->currentOffice();
        $today = Carbon::now()->format('d M Y');

        $kpis = $this->buildKpis();
        $charts = $this->buildCharts();
        $recentGRs = $this->getRecentGrs();

        return view('admin.dashboard', compact('user', 'office', 'today', 'kpis', 'charts', 'recentGRs'));
    }

    private function buildKpis(): array
    {
        $query = fn() => $this->officeScope(Gr::query());

        return [
            'grs_today' => (clone $query())->whereDate('copy_date', Carbon::today())->count(),

            'grs_month' => (clone $query())
                ->whereMonth('copy_date', Carbon::now()->month)
                ->whereYear('copy_date', Carbon::now()->year)
                ->count(),

            'revenue_month' => (clone $query())
                ->whereMonth('copy_date', Carbon::now()->month)
                ->whereYear('copy_date', Carbon::now()->year)
                ->sum('total_amount'),

            'pending_topay_amount' => (clone $query())
                ->where('to_pay', 1)
                ->where(function($q) { $q->where('topay_collected', 0)->orWhereNull('topay_collected'); })
                ->sum('total_amount'),

            'pending_topay_count' => (clone $query())
                ->where('to_pay', 1)
                ->where(function($q) { $q->where('topay_collected', 0)->orWhereNull('topay_collected'); })
                ->count(),

            'collected_topay_month' => (clone $query())
                ->where('to_pay', 1)
                ->where('topay_collected', 1)
                ->whereMonth('topay_collected_date', Carbon::now()->month)
                ->sum('total_amount'),

            'pending_delivery' => (clone $query())
                ->whereIn('status', ['dispatched', 'in_transit'])
                ->count(),

            'pending_pod' => (clone $query())
                ->whereIn('status', ['dispatched', 'in_transit'])
                ->where(function($q) { $q->whereNull('pod_file')->orWhere('pod_file', ''); })
                ->count(),

            'active_vehicles' => Vehicle::where('status', 'active')->count(),

            'delivered_today' => (clone $query())
                ->where('status', 'delivered')
                ->whereDate('status_updated_at', Carbon::today())
                ->count(),

            'gatepass_month' => $this->officeScope(gatepass::query())
                ->whereMonth('gp_date', Carbon::now()->month)
                ->count(),

            'challan_month' => $this->officeScope(challan::query())
                ->whereMonth('challan_date', Carbon::now()->month)
                ->count(),
        ];
    }

    private function buildCharts(): array
    {
        $query = fn() => $this->officeScope(Gr::query());

        // GRs per day (last 30 days)
        $grsPerDay = (clone $query())
            ->whereDate('copy_date', '>=', Carbon::now()->subDays(30))
            ->selectRaw('DATE(copy_date) as date, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Paid vs ToPay this month
        $paidCount = (clone $query())
            ->where('paid', 1)
            ->whereMonth('copy_date', Carbon::now()->month)
            ->whereYear('copy_date', Carbon::now()->year)
            ->count();

        $topayCount = (clone $query())
            ->where('to_pay', 1)
            ->whereMonth('copy_date', Carbon::now()->month)
            ->whereYear('copy_date', Carbon::now()->year)
            ->count();

        // Monthly revenue (last 6 months)
        $monthlyRevenue = [];
        for ($i = 5; $i >= 0; $i--) {
            $month = Carbon::now()->subMonths($i);
            $total = (clone $query())
                ->whereMonth('copy_date', $month->month)
                ->whereYear('copy_date', $month->year)
                ->sum('total_amount');
            $monthlyRevenue[] = [
                'month' => $month->format('M'),
                'total' => (float) $total,
            ];
        }

        // Status distribution
        $statusDist = (clone $query())
            ->whereMonth('copy_date', Carbon::now()->month)
            ->selectRaw("status, COUNT(*) as count")
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        // Weekly comparison
        $thisWeek = (clone $query())->whereBetween('copy_date', [Carbon::now()->startOfWeek(), Carbon::now()])->count();
        $lastWeek = (clone $query())->whereBetween('copy_date', [Carbon::now()->subWeek()->startOfWeek(), Carbon::now()->subWeek()->endOfWeek()])->count();

        return [
            'grs_per_day' => $grsPerDay,
            'paid_count' => $paidCount,
            'topay_count' => $topayCount,
            'monthly_revenue' => $monthlyRevenue,
            'status_dist' => $statusDist,
            'this_week' => $thisWeek,
            'last_week' => $lastWeek,
        ];
    }

    private function getRecentGrs()
    {
        return $this->officeScope(Gr::query())->latest()->take(10)->get();
    }
}
