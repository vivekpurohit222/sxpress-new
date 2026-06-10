<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class PermissionController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'role:SuperAdmin|Admin']);
    }

    public function index()
    {
        $permissions = Permission::orderBy('name')->get();
        return view('permissions.index')->with('permissions', $permissions);
    }

    public function create()
    {
        $roles = Role::get();
        return view('permissions.create')->with('roles', $roles);
    }

    public function store(Request $request)
    {
        $this->validate($request, [
            'name' => 'required|max:50|unique:permissions,name',
        ]);

        $permission = Permission::create([
            'name' => $request->name,
            'guard_name' => 'web',
        ]);

        if (!empty($request->roles)) {
            foreach ($request->roles as $roleId) {
                $role = Role::findOrFail($roleId);
                $role->givePermissionTo($permission);
            }
        }

        return redirect()->route('permissions.index')
            ->with('flash_message', 'Permission ' . $permission->name . ' added!');
    }

    public function show($id)
    {
        return redirect('permissions');
    }

    public function edit($id)
    {
        $permission = Permission::findOrFail($id);
        return view('permissions.edit', compact('permission'));
    }

    public function update(Request $request, $id)
    {
        $permission = Permission::findOrFail($id);
        $this->validate($request, [
            'name' => 'required|max:50|unique:permissions,name,' . $id,
        ]);

        $permission->update(['name' => $request->name]);

        return redirect()->route('permissions.index')
            ->with('flash_message', 'Permission ' . $permission->name . ' updated!');
    }

    public function destroy($id)
    {
        $permission = Permission::findOrFail($id);

        // Protect all seeded system permissions from deletion
        $systemPermissions = Permission::where('guard_name', 'web')
            ->where('name', 'like', '%-%')
            ->pluck('name')
            ->all();

        if (in_array($permission->name, $systemPermissions)) {
            return redirect()->route('permissions.index')
                ->with('flash_message', 'Cannot delete system permission: ' . $permission->name);
        }

        $permission->delete();

        return redirect()->route('permissions.index')
            ->with('flash_message', 'Permission deleted!');
    }
}
