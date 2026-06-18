<?php

namespace App\Http\Controllers\dash;

use App\Http\Controllers\Controller;
use App\Models\gatepass;
use App\Traits\OfficeScopeTrait;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DdsController extends Controller
{
    use OfficeScopeTrait;

    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Display Daily Delivery Statement — all gate passes for a selected date.
     */
    public function index(Request $request)
    {
        $date = $request->get('date', Carbon::today()->format('Y-m-d'));

        $query = gatepass::with(['grs', 'vehicle', 'driver'])
            ->whereDate('gp_date', $date);

        $this->officeScope($query);

        // SuperAdmin branch filter
        if ($this->isSuperAdmin() && $branch = $request->branch) {
            $query->where('office', $branch);
        }

        $items = $query->orderBy('gp_no')->get();
        $branches = $this->getBranchOptions();

        $totalWeight = $items->sum('weight');
        $totalAmount = $items->sum('total_amount');
        $totalNugs = $items->sum('nugs');

        return view('admin.category.dds.index', compact('items', 'date', 'branches', 'totalWeight', 'totalAmount', 'totalNugs'));
    }
}
