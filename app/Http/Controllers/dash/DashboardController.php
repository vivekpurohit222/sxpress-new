<?php

namespace App\Http\Controllers\dash;

use App\Http\Controllers\Controller;
use App\Models\Gr;
use App\Models\Freight;
use App\Models\gatepass;
use App\Models\challan;
use App\Traits\OfficeScopeTrait;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    use OfficeScopeTrait;

    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        $user = Auth::user();
        $office = $this->currentOffice();
        $today = Carbon::today();

        $counts = [
            'gr' => $this->officeScope(Gr::query())->whereDate('copy_date', $today)->count(),
            'challan' => $this->officeScope(challan::query())->whereDate('challan_date', $today)->count(),
            'freight_memo' => $this->officeScope(Freight::query())->whereDate('fm_date', $today)->count(),
            'import_challan' => $office ? challan::where('to_dest', $office)->where('status', 'in_transit')->count() : challan::where('status', 'in_transit')->count(),
            'gate_pass' => $office ? gatepass::where('office', $office)->whereDate('gp_date', $today)->count() : gatepass::whereDate('gp_date', $today)->count(),
            'dds' => $office ? gatepass::where('office', $office)->whereDate('gp_date', $today)->count() : gatepass::whereDate('gp_date', $today)->count(),
        ];

        return view('admin.dashboard', compact('user', 'office', 'counts'));
    }
}
