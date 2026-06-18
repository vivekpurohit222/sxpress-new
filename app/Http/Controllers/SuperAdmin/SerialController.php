<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\BranchSerial;
use App\Services\SerialNumberService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SerialController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'role:SuperAdmin']);
    }

    /**
     * Display serial assignment page — all branches, all 4 modules, current FY.
     */
    public function index()
    {
        $fyYear = SerialNumberService::currentFyPrefix();

        $branches = Branch::active()->orderBy('branch_name')->get();

        $serials = BranchSerial::where('fy_year', $fyYear)->get()->groupBy('branch_id');

        $modules = ['gr', 'challan', 'freight_memo', 'gate_pass'];
        $moduleLabels = [
            'gr' => 'GR',
            'challan' => 'Challan',
            'freight_memo' => 'Freight Memo',
            'gate_pass' => 'Gate Pass',
        ];

        return view('admin.superadmin.serial_assign', compact('branches', 'serials', 'modules', 'moduleLabels', 'fyYear'));
    }

    /**
     * Update serial range for a specific branch+module.
     * POST /serial-assign
     */
    public function assign(Request $request)
    {
        $validated = $request->validate([
            'branch_id'   => 'required|exists:branches,id',
            'module'      => 'required|in:gr,challan,freight_memo,gate_pass',
            'range_start' => 'required|numeric|min:1|max:999999',
            'range_end'   => 'required|numeric|min:1|max:999999|gt:range_start',
        ], [
            'range_start.required' => 'Range start is required.',
            'range_end.required'   => 'Range end is required.',
            'range_end.gt'         => 'Range end must be greater than range start.',
        ]);

        $fyYear = SerialNumberService::currentFyPrefix();

        // Collision detection
        $conflict = SerialNumberService::detectCollision(
            $validated['module'],
            $fyYear,
            $validated['range_start'],
            $validated['range_end'],
            $validated['branch_id']
        );

        if ($conflict) {
            return back()->withInput()->withErrors([
                'range_start' => "Range overlaps with branch '{$conflict}'."
            ]);
        }

        // Check if current_value exceeds new range
        $existing = BranchSerial::where('branch_id', $validated['branch_id'])
            ->where('module', $validated['module'])
            ->where('fy_year', $fyYear)
            ->first();

        if ($existing && $existing->current_value > 0) {
            if ($validated['range_start'] > $existing->current_value) {
                return back()->withInput()->withErrors([
                    'range_start' => "Cannot set range start above current value ({$existing->current_value}). Numbers already issued."
                ]);
            }
            if ($validated['range_end'] < $existing->current_value) {
                return back()->withInput()->withErrors([
                    'range_end' => "Cannot set range end below current value ({$existing->current_value}). Numbers already issued."
                ]);
            }
        }

        BranchSerial::updateOrCreate(
            [
                'branch_id' => $validated['branch_id'],
                'module'    => $validated['module'],
                'fy_year'   => $fyYear,
            ],
            [
                'range_start' => $validated['range_start'],
                'range_end'   => $validated['range_end'],
            ]
        );

        $branch = Branch::find($validated['branch_id']);
        $moduleLabel = ['gr' => 'GR', 'challan' => 'Challan', 'freight_memo' => 'Freight Memo', 'gate_pass' => 'Gate Pass'][$validated['module']];

        return redirect()->route('serial.index')
            ->with('success', "{$moduleLabel} serial range for {$branch->branch_name} updated: {$validated['range_start']} - {$validated['range_end']}");
    }
}
