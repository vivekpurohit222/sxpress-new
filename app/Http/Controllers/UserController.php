<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    // ─────────────────────────────────────────────────────────────────────
    // INDEX
    // ─────────────────────────────────────────────────────────────────────

    public function index(Request $request)
    {
        $query = User::with('roles'); // eager-load roles (fix N+1)

        // Data isolation: Admin sees only own branch (per skill §2/§14)
        if (!Auth::user()->hasRole('SuperAdmin')) {
            $query->where('office', Auth::user()->office);
        }

        // Search filter
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        // Office filter (SuperAdmin only)
        if ($request->filled('office') && Auth::user()->hasRole('SuperAdmin')) {
            $query->where('office', $request->office);
        }

        // Status filter
        if ($request->filled('status')) {
            if ($request->status === 'active') {
                $query->where('is_active', true);
            } elseif ($request->status === 'inactive') {
                $query->where('is_active', false);
            }
        }

        $users = $query->orderBy('created_at', 'desc')->paginate(15);
        $users->appends($request->query());

        $offices = Auth::user()->hasRole('SuperAdmin')
            ? DB::table('branches')->pluck('name')
            : collect([Auth::user()->office]);

        return view('users.index', compact('users', 'offices'));
    }

    // ─────────────────────────────────────────────────────────────────────
    // CREATE
    // ─────────────────────────────────────────────────────────────────────

    public function create()
    {
        $roles = $this->assignableRoles();
        $offices = $this->availableOffices();
        return view('users.create', compact('roles', 'offices'));
    }

    public function store(Request $request)
    {
        $this->validate($request, [
            'name' => 'required|string|max:120',
            'email' => 'required|email|max:255|unique:users',
            'password' => [
                'required', 'string', 'min:8', 'confirmed',
                'regex:/[A-Z]/',
                'regex:/[0-9]/',
            ],
            'office' => 'required|string|exists:branches,name',
            'phone' => 'nullable|string|max:15',
            'roles' => 'required|array|min:1',
            'roles.*' => 'exists:roles,id',
            'is_active' => 'nullable|boolean',
        ], [
            'password.regex' => 'Password must contain at least one uppercase letter and one number.',
        ]);

        // Admin can only create for own branch (per skill §14)
        if (!Auth::user()->hasRole('SuperAdmin') && $request->office !== Auth::user()->office) {
            abort(403, 'You can only create users for your own office.');
        }

        // Validate role assignment restrictions
        $this->validateRoleAssignment($request->roles);

        // Resolve branch_id from office name
        $branchId = DB::table('branches')->where('name', $request->office)->value('id');

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => $request->password, // hashed cast handles bcrypt
            'office' => $request->office,
            'phone' => $request->phone,
            'branch_id' => $branchId,
            'is_active' => $request->boolean('is_active', true),
        ]);

        foreach ($request->roles as $roleId) {
            $role = Role::findOrFail($roleId);
            $user->assignRole($role);
        }

        return redirect()->route('users.index')
            ->with('flash_message', "User {$user->name} created successfully.");
    }

    // ─────────────────────────────────────────────────────────────────────
    // SHOW (redirects to index)
    // ─────────────────────────────────────────────────────────────────────

    public function show($id)
    {
        return redirect()->route('users.index');
    }

    // ─────────────────────────────────────────────────────────────────────
    // EDIT
    // ─────────────────────────────────────────────────────────────────────

    public function edit($id)
    {
        $user = User::findOrFail($id);

        // Admin can only edit own-branch users
        if (!Auth::user()->hasRole('SuperAdmin') && $user->office !== Auth::user()->office) {
            abort(403, 'You can only edit users in your own office.');
        }

        $roles = $this->assignableRoles();
        $offices = $this->availableOffices();
        return view('users.edit', compact('user', 'roles', 'offices'));
    }

    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);

        // Admin can only edit own-branch users
        if (!Auth::user()->hasRole('SuperAdmin') && $user->office !== Auth::user()->office) {
            abort(403, 'You can only edit users in your own office.');
        }

        $this->validate($request, [
            'name' => 'required|string|max:120',
            'email' => 'required|email|max:255|unique:users,email,' . $id,
            'password' => [
                'nullable', 'string', 'min:8', 'confirmed',
                'regex:/[A-Z]/',
                'regex:/[0-9]/',
            ],
            'office' => 'required|string|exists:branches,name',
            'phone' => 'nullable|string|max:15',
            'roles' => 'required|array|min:1',
            'roles.*' => 'exists:roles,id',
            'is_active' => 'nullable|boolean',
        ], [
            'password.regex' => 'Password must contain at least one uppercase letter and one number.',
        ]);

        // Admin cannot move user to another branch
        if (!Auth::user()->hasRole('SuperAdmin') && $request->office !== Auth::user()->office) {
            abort(403, 'You cannot transfer users to another office.');
        }

        // Validate role assignment restrictions
        $this->validateRoleAssignment($request->roles);

        // Resolve branch_id from office name
        $branchId = DB::table('branches')->where('name', $request->office)->value('id');

        $input = [
            'name' => $request->name,
            'email' => $request->email,
            'office' => $request->office,
            'phone' => $request->phone,
            'branch_id' => $branchId,
            'is_active' => $request->boolean('is_active', $user->is_active),
        ];

        if ($request->filled('password')) {
            $input['password'] = $request->password;
        }

        $user->fill($input)->save();
        $user->roles()->sync($request->roles);

        return redirect()->route('users.index')
            ->with('flash_message', "User {$user->name} updated successfully.");
    }

    // ─────────────────────────────────────────────────────────────────────
    // TOGGLE ACTIVE (Deactivate / Reactivate)
    // ─────────────────────────────────────────────────────────────────────

    public function toggleActive($id)
    {
        $user = User::findOrFail($id);

        // Admin can only toggle own-branch users
        if (!Auth::user()->hasRole('SuperAdmin') && $user->office !== Auth::user()->office) {
            abort(403);
        }

        // Cannot deactivate yourself
        if ($user->id === Auth::id()) {
            return back()->withErrors(['Cannot deactivate your own account.']);
        }

        // Cannot deactivate a SuperAdmin unless you are SuperAdmin
        if ($user->hasRole('SuperAdmin') && !Auth::user()->hasRole('SuperAdmin')) {
            abort(403, 'Cannot deactivate a SuperAdmin user.');
        }

        $user->update(['is_active' => !$user->is_active]);
        $status = $user->is_active ? 'activated' : 'deactivated';

        return back()->with('flash_message', "User {$user->name} has been {$status}.");
    }

    // ─────────────────────────────────────────────────────────────────────
    // DELETE (soft-delete)
    // ─────────────────────────────────────────────────────────────────────

    public function destroy($id)
    {
        $user = User::findOrFail($id);

        // Cannot delete yourself
        if ($user->id === Auth::id()) {
            return back()->withErrors(['Cannot delete your own account.']);
        }

        // Admin can only delete own-branch users
        if (!Auth::user()->hasRole('SuperAdmin') && $user->office !== Auth::user()->office) {
            abort(403);
        }

        // Cannot delete a SuperAdmin unless you are SuperAdmin
        if ($user->hasRole('SuperAdmin') && !Auth::user()->hasRole('SuperAdmin')) {
            abort(403, 'Cannot delete a SuperAdmin user.');
        }

        // Prevent deleting the last SuperAdmin
        if ($user->hasRole('SuperAdmin')) {
            $superAdminCount = User::role('SuperAdmin')->where('is_active', true)->count();
            if ($superAdminCount <= 1) {
                return back()->withErrors(['Cannot delete the only active SuperAdmin. Promote another user first.']);
            }
        }

        $user->delete(); // soft-delete (model uses SoftDeletes)

        return redirect()->route('users.index')
            ->with('flash_message', "User {$user->name} has been removed.");
    }

    // ─────────────────────────────────────────────────────────────────────
    // PRIVATE HELPERS
    // ─────────────────────────────────────────────────────────────────────

    private function assignableRoles()
    {
        if (Auth::user()->hasRole('SuperAdmin')) {
            return Role::orderBy('name')->get();
        }
        return Role::whereNotIn('name', ['SuperAdmin', 'Admin'])->orderBy('name')->get();
    }

    private function availableOffices()
    {
        if (Auth::user()->hasRole('SuperAdmin')) {
            return DB::table('branches')->orderBy('name')->pluck('name');
        }
        return collect([Auth::user()->office]);
    }

    private function validateRoleAssignment(array $roleIds): void
    {
        if (Auth::user()->hasRole('SuperAdmin')) {
            return;
        }

        $restricted = Role::whereIn('name', ['SuperAdmin', 'Admin'])->pluck('id')->all();
        foreach ($roleIds as $roleId) {
            if (in_array((int) $roleId, $restricted)) {
                abort(403, 'You cannot assign SuperAdmin or Admin roles. Contact SuperAdmin.');
            }
        }
    }
}
