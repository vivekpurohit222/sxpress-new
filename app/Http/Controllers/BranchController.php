<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Branch;
use App\Models\Gr;
use App\Models\User;

class BranchController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'role:SuperAdmin']);
    }

    public function index(Request $request)
    {
        $query = Branch::query();

        if ($request->filled('search')) {
            $query->search($request->search);
        }

        $branches = $query->orderBy('branch_name')->paginate(15);
        $branches->appends($request->query());

        return view('admin.category.Branch.branch_list', compact('branches'));
    }

    public function create()
    {
        return view('admin.category.Branch.branch');
    }

    public function store(Request $request)
    {
        $request->validate([
            'branch_name' => 'required|string|max:100|unique:branches,branch_name',
            'branch_code' => 'required|string|max:20|unique:branches,branch_code|regex:/^[A-Z0-9]+$/',
            'gr_prefix'   => 'required|string|size:2|regex:/^[A-Z]{2}$/|unique:branches,gr_prefix',
            'address'     => 'nullable|string|max:500',
            'city'        => 'nullable|string|max:100',
            'state'       => 'nullable|string|max:100',
            'pincode'     => 'nullable|string|digits:6',
            'phone'       => 'nullable|string|max:20',
            'email'       => 'nullable|email|max:100',
        ], [
            'branch_name.unique'  => 'This branch name already exists.',
            'branch_code.unique'  => 'This branch code already exists.',
            'branch_code.regex'   => 'Branch code must be uppercase letters/numbers only.',
            'gr_prefix.size'      => 'GR Prefix must be exactly 2 uppercase letters.',
            'gr_prefix.regex'     => 'GR Prefix must be 2 uppercase letters (e.g. AA, CG).',
            'gr_prefix.unique'    => 'This GR Prefix is already in use by another branch.',
            'pincode.digits'      => 'Pincode must be exactly 6 digits.',
        ]);

        // Create branch — sync both column sets for backward compatibility
        Branch::create([
            'branch_name' => $request->branch_name,
            'branch_code' => $request->branch_code,
            'gr_prefix'   => $request->gr_prefix,
            'name'        => $request->branch_name,  // sync legacy column
            'code'        => $request->branch_code,  // sync legacy column
            'address'     => $request->address,
            'city'        => $request->city,
            'state'       => $request->state,
            'pincode'     => $request->pincode,
            'phone'       => $request->phone,
            'email'       => $request->email,
            'status'      => $request->boolean('status', true),
            'is_active'   => $request->boolean('status', true),
        ]);

        return redirect('/branch')->with('success', 'Branch created successfully.');
    }

    public function show($id)
    {
        $branch = Branch::findOrFail($id);
        $grCount = Gr::where('office', $branch->branch_name)->count();
        $userCount = User::where('office', $branch->branch_name)->count();

        return view('admin.category.Branch.branch_view', compact('branch', 'grCount', 'userCount'));
    }

    public function edit($id)
    {
        $branch = Branch::findOrFail($id);
        $hasGrs = Gr::where('office', $branch->branch_name)->exists();

        return view('admin.category.Branch.branch_edit', compact('branch', 'hasGrs'));
    }

    public function update(Request $request, $id)
    {
        $branch = Branch::findOrFail($id);
        $hasGrs = Gr::where('office', $branch->branch_name)->exists();

        $rules = [
            'branch_name' => 'required|string|max:100|unique:branches,branch_name,' . $id,
            'branch_code' => 'required|string|max:20|unique:branches,branch_code,' . $id . '|regex:/^[A-Z0-9]+$/',
            'gr_prefix'   => 'required|string|size:2|regex:/^[A-Z]{2}$/|unique:branches,gr_prefix,' . $id,
            'address'     => 'nullable|string|max:500',
            'city'        => 'nullable|string|max:100',
            'state'       => 'nullable|string|max:100',
            'pincode'     => 'nullable|string|digits:6',
            'phone'       => 'nullable|string|max:20',
            'email'       => 'nullable|email|max:100',
        ];

        $request->validate($rules, [
            'gr_prefix.size'  => 'GR Prefix must be exactly 2 uppercase letters.',
            'gr_prefix.regex' => 'GR Prefix must be 2 uppercase letters (e.g. AA, CG).',
            'pincode.digits'  => 'Pincode must be exactly 6 digits.',
        ]);

        // Block GR prefix change if GRs exist
        if ($hasGrs && $request->gr_prefix !== $branch->gr_prefix) {
            return back()->withInput()->withErrors([
                'gr_prefix' => 'Cannot change GR Prefix — this branch already has GR records using prefix "' . $branch->gr_prefix . '".',
            ]);
        }

        // Block branch_name rename if data exists (would orphan users + GRs)
        $oldName = $branch->branch_name;
        $newName = $request->branch_name;

        if ($oldName !== $newName && $hasGrs) {
            // Cascade update: rename in users.office and grs.office
            DB::transaction(function () use ($oldName, $newName) {
                User::where('office', $oldName)->update(['office' => $newName]);
                Gr::where('office', $oldName)->update(['office' => $newName]);
                Gr::where('from_dest', $oldName)->update(['from_dest' => $newName]);
            });
        }

        $branch->update([
            'branch_name' => $newName,
            'branch_code' => $request->branch_code,
            'gr_prefix'   => $request->gr_prefix,
            'name'        => $newName,          // sync legacy column
            'code'        => $request->branch_code, // sync legacy column
            'address'     => $request->address,
            'city'        => $request->city,
            'state'       => $request->state,
            'pincode'     => $request->pincode,
            'phone'       => $request->phone,
            'email'       => $request->email,
            'status'      => $request->boolean('status', $branch->status),
            'is_active'   => $request->boolean('status', $branch->status),
        ]);

        return redirect('/branch')->with('success', 'Branch updated successfully.');
    }

    public function destroy($id)
    {
        $branch = Branch::findOrFail($id);

        // Guard: cannot delete if GRs exist
        if (Gr::where('office', $branch->branch_name)->exists()) {
            return back()->withErrors(['Cannot delete — this branch has existing GR records. Deactivate it instead.']);
        }

        // Guard: cannot delete if users are assigned
        if (User::where('office', $branch->branch_name)->exists()) {
            return back()->withErrors(['Cannot delete — users are assigned to this branch. Reassign them first.']);
        }

        $branch->delete();

        return redirect('/branch')->with('success', 'Branch deleted successfully.');
    }
}
