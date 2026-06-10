<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RoleController extends Controller
{
    /** System roles that cannot be deleted or renamed. */
    private const PROTECTED_ROLES = ['SuperAdmin', 'Admin', 'Manager', 'Staff', 'Viewer'];

    public function __construct()
    {
        $this->middleware(['auth', 'role:SuperAdmin|Admin']);
    }

    public function index()
    {
        $roles = Role::all();
        return view('roles.index')->with('roles', $roles);
    }

    public function create()
    {
        $permissions = Permission::all();
        return view('roles.create', ['permissions' => $permissions]);
    }

    public function store(Request $request)
    {
        $this->validate($request, [
            'name' => 'required|unique:roles|max:50',
            'permissions' => 'required|array',
        ]);

        $role = Role::create(['name' => $request->name, 'guard_name' => 'web']);
        $role->syncPermissions($request->permissions);

        return redirect()->route('roles.index')
            ->with('flash_message', 'Role ' . $role->name . ' added!');
    }

    public function show($id)
    {
        return redirect('roles');
    }

    public function edit($id)
    {
        $role = Role::findOrFail($id);
        $permissions = Permission::all();
        return view('roles.edit', compact('role', 'permissions'));
    }

    public function update(Request $request, $id)
    {
        $role = Role::findOrFail($id);

        $this->validate($request, [
            'name' => 'required|max:50|unique:roles,name,' . $id,
            'permissions' => 'required|array',
        ]);

        // Prevent renaming protected roles
        if (in_array($role->name, self::PROTECTED_ROLES) && $request->name !== $role->name) {
            return back()->withErrors(['name' => 'System roles cannot be renamed.']);
        }

        $role->update(['name' => $request->name]);
        $role->syncPermissions($request->permissions);

        return redirect()->route('roles.index')
            ->with('flash_message', 'Role ' . $role->name . ' updated!');
    }

    public function destroy($id)
    {
        $role = Role::findOrFail($id);

        // Prevent deletion of protected system roles
        if (in_array($role->name, self::PROTECTED_ROLES)) {
            return redirect()->route('roles.index')
                ->with('flash_message', 'Cannot delete system role: ' . $role->name);
        }

        $role->delete();

        return redirect()->route('roles.index')
            ->with('flash_message', 'Role deleted!');
    }
}
