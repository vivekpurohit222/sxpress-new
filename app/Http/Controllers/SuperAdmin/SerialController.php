<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\BranchSerial;
use App\Models\Gr;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SerialController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'role:SuperAdmin']);
    }

    /**
     * Display serial assignment page.
     * Per SXPRESS_LOGIC_SKILL section 13.
     */
    public function index()
    {
        $branches = Branch::with('serial')
            ->withCount('grs')
            ->get()
            ->map(function ($branch) {
                $branch->is_locked = $branch->grs_count > 0;
                return $branch;
            });

        return view('admin.superadmin.serial_assign', compact('branches'));
    }

    /**
     * Assign GR serial start number to a branch.
     * POST /dash/serial-assign
     */
    public function assign(Request $request)
    {
        $validated = $request->validate([
            'office'     => 'required|string|exists:branches,branch_name',
            'start_from' => 'required|integer|min:1|max:99999',
            'notes'      => 'nullable|string|max:500',
        ], [
            'office.required' => 'Please select an office.',
            'start_from.required' => 'Start number is required.',
            'start_from.min' => 'Start number must be at least 1.',
            'start_from.max' => 'Start number cannot exceed 99999.',
        ]);

        // Hard lock — cannot change once GRs exist
        if (Gr::where('office', $validated['office'])->exists()) {
            return back()->withInput()->withErrors([
                'start_from' => 'Serial is locked — this office already has GR records.'
            ]);
        }

        $branch = Branch::where('branch_name', $validated['office'])->first();

        BranchSerial::updateOrCreate(
            ['office' => $validated['office']],
            [
                'gr_prefix'   => $branch->gr_prefix,
                'start_from'  => $validated['start_from'],
                'assigned_by' => Auth::id(),
                'assigned_at' => now(),
                'notes'       => $validated['notes'],
            ]
        );

        return redirect()->route('serial.index')
            ->with('success', "Serial for {$validated['office']} set. First GR will be: {$branch->gr_prefix}-" . str_pad($validated['start_from'], 5, '0', STR_PAD_LEFT));
    }
}