<?php

namespace App\Http\Controllers\dash;

use App\Http\Controllers\Controller;
use App\Models\challan;
use App\Models\ChallanItem;
use App\Models\Gr;
use App\Traits\OfficeScopeTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ImportChallanController extends Controller
{
    use OfficeScopeTrait;

    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * List in_transit challans destined for the current office.
     */
    public function index(Request $request)
    {
        $office = $this->currentOffice();

        $query = challan::with(['vehicle', 'driver'])
            ->withCount('items')
            ->where('status', 'in_transit')
            ->where(function ($q) use ($office) {
                // Match by to_dest string OR to_branch_id
                $q->where('to_dest', $office);
                if (auth()->user()->branch_id) {
                    $q->orWhere('to_branch_id', auth()->user()->branch_id);
                }
            });

        // Search
        if ($s = $request->search) {
            $query->where(function ($q) use ($s) {
                $q->where('challan_no', 'like', "%{$s}%")
                  ->orWhere('from_dest', 'like', "%{$s}%");
            });
        }

        // Date range
        if ($from = $request->from_date) {
            $query->whereDate('challan_date', '>=', $from);
        }
        if ($to = $request->to_date) {
            $query->whereDate('challan_date', '<=', $to);
        }

        // SuperAdmin branch filter
        if ($this->isSuperAdmin() && $branch = $request->branch) {
            $query->where('to_dest', $branch);
        }

        $items = $query->latest()->paginate(25)->withQueryString();
        $branches = $this->getBranchOptions();

        return view('admin.category.import_challan.index', compact('items', 'branches'));
    }

    /**
     * Import a challan — marks challan as completed, GRs as in_transit.
     */
    public function import($id)
    {
        $challan = challan::with('items')->findOrFail($id);

        // Verify challan is destined for current office and is in_transit
        $office = $this->currentOffice();
        $branchId = auth()->user()->branch_id;
        $isDestined = ($challan->to_dest === $office) || ($branchId && $challan->to_branch_id == $branchId);

        if (!$isDestined) {
            abort(403, 'This challan is not destined for your office.');
        }

        if ($challan->status !== 'in_transit') {
            return back()->withErrors(['error' => 'This challan is not in transit and cannot be imported.']);
        }

        DB::transaction(function () use ($challan) {
            // Mark challan as completed
            $challan->update(['status' => 'completed']);

            // Get all GR numbers from challan items
            $grNos = $challan->items->pluck('gr_no')->filter();

            // Update all linked GRs to 'in_transit'
            if ($grNos->isNotEmpty()) {
                Gr::whereIn('gr_no', $grNos)->update([
                    'status'            => 'in_transit',
                    'status_updated_at' => now(),
                    'status_updated_by' => auth()->id(),
                ]);
            }
        });

        return redirect()->route('import_challan.index')
            ->with('success', "Challan {$challan->challan_no} imported successfully. GRs marked as In Transit.");
    }
}
