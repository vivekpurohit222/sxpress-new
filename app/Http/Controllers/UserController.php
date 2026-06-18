<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\Branch;
use App\Models\UserPermission;

/**
 * User (Agent) Management — super_admin only.
 *
 * Agents are created here with:
 * - name, email, password
 * - branch_id (dropdown)
 * - permissions checkboxes (6 sub-modules)
 */
class UserController extends Controller
{
    const MODULES = ['gr', 'challan', 'freight_memo', 'import_challan', 'gate_pass', 'dds'];

    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request)
    {
        if (!Auth::user()->isSuperAdmin()) abort(403);

        $query = User::where('role', 'agent');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($request->filled('branch_id')) {
            $query->where('branch_id', $request->branch_id);
        }

        $users = $query->with('branch')->orderByDesc('created_at')->paginate(15)->withQueryString();
        $branches = Branch::active()->orderBy('branch_name')->get();

        return view('users.index', compact('users', 'branches'));
    }

    public function create()
    {
        if (!Auth::user()->isSuperAdmin()) abort(403);

        $branches = Branch::active()->orderBy('branch_name')->get();
        $modules = self::MODULES;

        return view('users.create', compact('branches', 'modules'));
    }

    public function store(Request $request)
    {
        if (!Auth::user()->isSuperAdmin()) abort(403);

        $request->validate([
            'name'      => 'required|string|max:120',
            'email'     => 'required|email|max:255|unique:users',
            'password'  => 'required|string|min:6|confirmed',
            'branch_id' => 'required|exists:branches,id',
            'permissions' => 'nullable|array',
            'permissions.*' => 'in:' . implode(',', self::MODULES),
        ]);

        $branch = Branch::findOrFail($request->branch_id);

        $user = User::create([
            'name'      => $request->name,
            'email'     => $request->email,
            'password'  => $request->password,
            'role'      => 'agent',
            'branch_id' => $branch->id,
            'office'    => $branch->branch_name,
            'is_active' => true,
        ]);

        // Store permissions
        if ($request->permissions) {
            foreach ($request->permissions as $perm) {
                UserPermission::create(['user_id' => $user->id, 'permission' => $perm]);
            }
        }

        return redirect()->route('users.index')->with('flash_message', "Agent {$user->name} created.");
    }

    public function show($id)
    {
        return redirect()->route('users.index');
    }

    public function edit($id)
    {
        if (!Auth::user()->isSuperAdmin()) abort(403);

        $user = User::findOrFail($id);
        $branches = Branch::active()->orderBy('branch_name')->get();
        $modules = self::MODULES;
        $userPerms = UserPermission::where('user_id', $user->id)->pluck('permission')->toArray();

        return view('users.edit', compact('user', 'branches', 'modules', 'userPerms'));
    }

    public function update(Request $request, $id)
    {
        if (!Auth::user()->isSuperAdmin()) abort(403);

        $user = User::findOrFail($id);

        $request->validate([
            'name'      => 'required|string|max:120',
            'email'     => 'required|email|max:255|unique:users,email,' . $id,
            'password'  => 'nullable|string|min:6|confirmed',
            'branch_id' => 'required|exists:branches,id',
            'permissions' => 'nullable|array',
            'permissions.*' => 'in:' . implode(',', self::MODULES),
        ]);

        $branch = Branch::findOrFail($request->branch_id);

        $data = [
            'name'      => $request->name,
            'email'     => $request->email,
            'branch_id' => $branch->id,
            'office'    => $branch->branch_name,
        ];

        if ($request->filled('password')) {
            $data['password'] = $request->password;
        }

        $user->update($data);

        // Sync permissions
        UserPermission::where('user_id', $user->id)->delete();
        if ($request->permissions) {
            foreach ($request->permissions as $perm) {
                UserPermission::create(['user_id' => $user->id, 'permission' => $perm]);
            }
        }

        return redirect()->route('users.index')->with('flash_message', "Agent {$user->name} updated.");
    }

    public function toggleActive($id)
    {
        if (!Auth::user()->isSuperAdmin()) abort(403);

        $user = User::findOrFail($id);
        if ($user->id === Auth::id()) {
            return back()->withErrors(['Cannot deactivate your own account.']);
        }

        $user->update(['is_active' => !$user->is_active]);
        $status = $user->is_active ? 'activated' : 'deactivated';

        return back()->with('flash_message', "User {$user->name} has been {$status}.");
    }

    public function destroy($id)
    {
        if (!Auth::user()->isSuperAdmin()) abort(403);

        $user = User::findOrFail($id);
        if ($user->id === Auth::id()) {
            return back()->withErrors(['Cannot delete your own account.']);
        }

        UserPermission::where('user_id', $user->id)->delete();
        $user->delete();

        return redirect()->route('users.index')->with('flash_message', "User removed.");
    }
}
