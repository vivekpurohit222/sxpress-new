<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Branch;
use App\Models\BranchPermission;
use App\Models\BranchSerial;
use App\Models\Gr;
use App\Models\User;
use App\Services\SerialNumberService;

class BranchController extends Controller
{
    const MODULES = ['gr', 'challan', 'freight_memo', 'import_challan', 'gate_pass', 'dds'];
    const SERIAL_MODULES = ['gr', 'challan', 'freight_memo', 'gate_pass'];

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
        $branches = $query->orderBy('branch_name')->paginate(15)->withQueryString();

        return view('admin.category.Branch.branch_list', compact('branches'));
    }

    public function create()
    {
        $modules = self::MODULES;
        return view('admin.category.Branch.branch', compact('modules'));
    }

    public function store(Request $request)
    {
        $rules = [
            'branch_name' => 'required|string|max:100|unique:branches,branch_name',
            'branch_code' => 'required|string|max:20|unique:branches,branch_code|regex:/^[A-Z0-9]+$/',
            'address'     => 'nullable|string|max:500',
            'city'        => 'nullable|string|max:100',
            'state'       => 'nullable|string|max:100',
            'pincode'     => 'nullable|string|digits:6',
            'phone'       => 'nullable|string|max:20',
            'email'       => 'nullable|email|max:100',
            // Branch manager fields
            'manager_name'     => 'required|string|max:120',
            'manager_email'    => 'required|email|max:255|unique:users,email',
            'manager_password' => 'required|string|min:6',
            // Permissions
            'permissions'      => 'nullable|array',
            'permissions.*'    => 'in:' . implode(',', self::MODULES),
            // Serial ranges
            'gr_range_start'           => 'required|numeric|min:1|max:999999',
            'gr_range_end'             => 'required|numeric|min:1|max:999999|gt:gr_range_start',
            'challan_range_start'      => 'required|numeric|min:1|max:999999',
            'challan_range_end'        => 'required|numeric|min:1|max:999999|gt:challan_range_start',
            'freight_memo_range_start' => 'required|numeric|min:1|max:999999',
            'freight_memo_range_end'   => 'required|numeric|min:1|max:999999|gt:freight_memo_range_start',
            'gate_pass_range_start'    => 'required|numeric|min:1|max:999999',
            'gate_pass_range_end'      => 'required|numeric|min:1|max:999999|gt:gate_pass_range_start',
        ];

        $request->validate($rules, [
            'branch_code.regex' => 'Branch code must be uppercase letters/numbers only.',
        ]);

        // Collision detection for serial ranges
        $fyYear = SerialNumberService::currentFyPrefix();
        $serialInputs = [
            'gr'           => ['start' => $request->gr_range_start, 'end' => $request->gr_range_end],
            'challan'      => ['start' => $request->challan_range_start, 'end' => $request->challan_range_end],
            'freight_memo' => ['start' => $request->freight_memo_range_start, 'end' => $request->freight_memo_range_end],
            'gate_pass'    => ['start' => $request->gate_pass_range_start, 'end' => $request->gate_pass_range_end],
        ];

        foreach ($serialInputs as $module => $range) {
            $conflict = SerialNumberService::detectCollision($module, $fyYear, $range['start'], $range['end']);
            if ($conflict) {
                return back()->withInput()->withErrors([
                    "{$module}_range_start" => "Range overlaps with branch '{$conflict}' for module '{$module}'."
                ]);
            }
        }

        DB::transaction(function () use ($request, $serialInputs, $fyYear) {
            // Create branch
            $branch = Branch::create([
                'branch_name' => $request->branch_name,
                'branch_code' => $request->branch_code,
                'gr_prefix'   => strtoupper(substr($request->branch_code, 0, 2)),
                'name'        => $request->branch_name,
                'code'        => $request->branch_code,
                'address'     => $request->address,
                'city'        => $request->city,
                'state'       => $request->state,
                'pincode'     => $request->pincode,
                'phone'       => $request->phone,
                'email'       => $request->email,
                'status'      => true,
                'is_active'   => true,
            ]);

            // Create branch manager user
            User::create([
                'name'      => $request->manager_name,
                'email'     => $request->manager_email,
                'password'  => $request->manager_password,
                'role'      => 'branch_manager',
                'branch_id' => $branch->id,
                'office'    => $branch->branch_name,
                'is_active' => true,
            ]);

            // Store branch permissions
            if ($request->permissions) {
                foreach ($request->permissions as $perm) {
                    BranchPermission::create(['branch_id' => $branch->id, 'permission' => $perm]);
                }
            }

            // Create serial ranges for all 4 modules
            foreach ($serialInputs as $module => $range) {
                BranchSerial::create([
                    'branch_id'     => $branch->id,
                    'module'        => $module,
                    'fy_year'       => $fyYear,
                    'range_start'   => $range['start'],
                    'range_end'     => $range['end'],
                    'current_value' => 0,
                ]);
            }
        });

        return redirect('/branch')->with('success', 'Branch created with manager, permissions, and serial ranges.');
    }

    public function show($id)
    {
        $branch = Branch::findOrFail($id);
        $grCount = Gr::where('office', $branch->branch_name)->count();
        $userCount = User::where('branch_id', $branch->id)->count();
        $manager = User::where('branch_id', $branch->id)->where('role', 'branch_manager')->first();
        $permissions = BranchPermission::where('branch_id', $branch->id)->pluck('permission')->toArray();

        return view('admin.category.Branch.branch_view', compact('branch', 'grCount', 'userCount', 'manager', 'permissions'));
    }

    public function edit($id)
    {
        $branch = Branch::findOrFail($id);
        $hasGrs = Gr::where('office', $branch->branch_name)->exists();
        $modules = self::MODULES;
        $branchPerms = BranchPermission::where('branch_id', $branch->id)->pluck('permission')->toArray();
        $manager = User::where('branch_id', $branch->id)->where('role', 'branch_manager')->first();

        // Load serial ranges for current FY
        $fyYear = SerialNumberService::currentFyPrefix();
        $serials = BranchSerial::where('branch_id', $branch->id)
            ->where('fy_year', $fyYear)
            ->get()
            ->keyBy('module');

        return view('admin.category.Branch.branch_edit', compact('branch', 'hasGrs', 'modules', 'branchPerms', 'manager', 'serials'));
    }

    public function update(Request $request, $id)
    {
        $branch = Branch::findOrFail($id);
        $hasGrs = Gr::where('office', $branch->branch_name)->exists();
        $manager = User::where('branch_id', $branch->id)->where('role', 'branch_manager')->first();

        $rules = [
            'branch_name' => 'required|string|max:100|unique:branches,branch_name,' . $id,
            'branch_code' => 'required|string|max:20|unique:branches,branch_code,' . $id . '|regex:/^[A-Z0-9]+$/',
            'address'     => 'nullable|string|max:500',
            'city'        => 'nullable|string|max:100',
            'state'       => 'nullable|string|max:100',
            'pincode'     => 'nullable|string|digits:6',
            'phone'       => 'nullable|string|max:20',
            'email'       => 'nullable|email|max:100',
            // Manager fields
            'manager_name'     => 'required|string|max:120',
            'manager_email'    => 'required|email|max:255|unique:users,email,' . ($manager ? $manager->id : ''),
            'manager_password' => 'nullable|string|min:6',
            // Permissions
            'permissions'      => 'nullable|array',
            'permissions.*'    => 'in:' . implode(',', self::MODULES),
            // Serial ranges
            'gr_range_start'           => 'required|numeric|min:1|max:999999',
            'gr_range_end'             => 'required|numeric|min:1|max:999999|gt:gr_range_start',
            'challan_range_start'      => 'required|numeric|min:1|max:999999',
            'challan_range_end'        => 'required|numeric|min:1|max:999999|gt:challan_range_start',
            'freight_memo_range_start' => 'required|numeric|min:1|max:999999',
            'freight_memo_range_end'   => 'required|numeric|min:1|max:999999|gt:freight_memo_range_start',
            'gate_pass_range_start'    => 'required|numeric|min:1|max:999999',
            'gate_pass_range_end'      => 'required|numeric|min:1|max:999999|gt:gate_pass_range_start',
        ];

        $request->validate($rules);

        // Collision detection for serial ranges
        $fyYear = SerialNumberService::currentFyPrefix();
        $serialInputs = [
            'gr'           => ['start' => $request->gr_range_start, 'end' => $request->gr_range_end],
            'challan'      => ['start' => $request->challan_range_start, 'end' => $request->challan_range_end],
            'freight_memo' => ['start' => $request->freight_memo_range_start, 'end' => $request->freight_memo_range_end],
            'gate_pass'    => ['start' => $request->gate_pass_range_start, 'end' => $request->gate_pass_range_end],
        ];

        foreach ($serialInputs as $module => $range) {
            $conflict = SerialNumberService::detectCollision($module, $fyYear, $range['start'], $range['end'], $branch->id);
            if ($conflict) {
                return back()->withInput()->withErrors([
                    "{$module}_range_start" => "Range overlaps with branch '{$conflict}' for module '{$module}'."
                ]);
            }
        }

        DB::transaction(function () use ($request, $branch, $manager, $hasGrs, $serialInputs, $fyYear) {
            $oldName = $branch->branch_name;
            $newName = $request->branch_name;

            // Cascade rename if needed
            if ($oldName !== $newName && $hasGrs) {
                User::where('office', $oldName)->update(['office' => $newName]);
                Gr::where('office', $oldName)->update(['office' => $newName]);
                Gr::where('from_dest', $oldName)->update(['from_dest' => $newName]);
            }

            $branch->update([
                'branch_name' => $newName,
                'branch_code' => $request->branch_code,
                'name'        => $newName,
                'code'        => $request->branch_code,
                'address'     => $request->address,
                'city'        => $request->city,
                'state'       => $request->state,
                'pincode'     => $request->pincode,
                'phone'       => $request->phone,
                'email'       => $request->email,
                'status'      => $request->boolean('status', $branch->status),
                'is_active'   => $request->boolean('status', $branch->status),
            ]);

            // Update or create manager
            $managerData = [
                'name'      => $request->manager_name,
                'email'     => $request->manager_email,
                'role'      => 'branch_manager',
                'branch_id' => $branch->id,
                'office'    => $newName,
            ];
            if ($request->filled('manager_password')) {
                $managerData['password'] = $request->manager_password;
            }

            if ($manager) {
                $manager->update($managerData);
            } else {
                $managerData['password'] = $request->manager_password ?? 'password';
                $managerData['is_active'] = true;
                User::create($managerData);
            }

            // Sync branch permissions
            BranchPermission::where('branch_id', $branch->id)->delete();
            if ($request->permissions) {
                foreach ($request->permissions as $perm) {
                    BranchPermission::create(['branch_id' => $branch->id, 'permission' => $perm]);
                }
            }

            // Update or create serial ranges
            foreach ($serialInputs as $module => $range) {
                BranchSerial::updateOrCreate(
                    [
                        'branch_id' => $branch->id,
                        'module'    => $module,
                        'fy_year'   => $fyYear,
                    ],
                    [
                        'range_start' => $range['start'],
                        'range_end'   => $range['end'],
                    ]
                );
            }
        });

        return redirect('/branch')->with('success', 'Branch updated.');
    }

    public function destroy($id)
    {
        $branch = Branch::findOrFail($id);

        if (Gr::where('office', $branch->branch_name)->exists()) {
            return back()->withErrors(['Cannot delete — this branch has existing GR records.']);
        }

        DB::transaction(function () use ($branch) {
            BranchPermission::where('branch_id', $branch->id)->delete();
            BranchSerial::where('branch_id', $branch->id)->delete();
            User::where('branch_id', $branch->id)->delete();
            $branch->delete();
        });

        return redirect('/branch')->with('success', 'Branch deleted.');
    }
}
