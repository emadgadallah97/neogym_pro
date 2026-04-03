<?php

namespace App\Http\Controllers\roles;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\Permission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class rolescontroller extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:roles_view', ['only' => ['index', 'show']]);
        $this->middleware('permission:roles_create', ['only' => ['create', 'store']]);
        $this->middleware('permission:roles_edit', ['only' => ['edit', 'update']]);
        $this->middleware('permission:roles_delete', ['only' => ['destroy']]);
    }

    public function index(Request $request)
    {
        $roles = Role::orderBy('id', 'DESC')->get();
        return view('roles.index', compact('roles'));
    }

    public function create()
    {
        $groupedpermission = \App\Models\Permission::select('category', 'name', 'title', 'id')
            ->orderBy('id')
            ->get()
            ->groupBy(fn($p) => $p->getTranslation('category', app()->getLocale()));

        return view('roles.create', compact('groupedpermission'));
    }

    public function store(Request $request)
    {
        $this->validate($request, [
            'name' => 'required|unique:roles,name',
            'permission' => 'required|array',
        ]);

        $role = Role::create([
            'name' => $request->input('name')
        ]);

        $role->syncPermissions($request->input('permission'));

        return redirect()->route('roles.index')
            ->with('success', 'Role created successfully');
    }

    public function show($id)
    {
        $role = \App\Models\Role::findOrFail($id);

        $groupedpermission = \App\Models\Permission::select('category', 'name', 'title', 'id')
            ->orderBy('id')
            ->get()
            ->groupBy(fn($p) => $p->getTranslation('category', app()->getLocale()));

        $rolePermissions = $role->permissions->pluck('name')->toArray();

        return view('roles.show', compact('role', 'rolePermissions', 'groupedpermission'));
    }

    public function edit($id)
    {
        $role = \App\Models\Role::findOrFail($id);

        $rolePermissions = \DB::table('role_has_permissions')
            ->where('role_id', $id)
            ->pluck('permission_id', 'permission_id')
            ->all();

        $groupedpermission = \App\Models\Permission::select('category', 'name', 'title', 'id')
            ->orderBy('id')
            ->get()
            ->groupBy(fn($p) => $p->getTranslation('category', app()->getLocale()));

        return view('roles.edit', compact('role', 'rolePermissions', 'groupedpermission'));
    }

    public function update(Request $request, $id)
    {
        $this->validate($request, [
            'name' => 'required',
            'permission' => 'nullable|array',
        ]);

        $role = Role::findOrFail($id);
        $role->name = $request->input('name');
        $role->save();

        $permissions = array_filter($request->input('permission', []));
        $role->syncPermissions($permissions);

        return redirect()->route('roles.index')
            ->with('success', 'Role updated successfully');
    }

    public function destroy($id)
    {
        Role::findOrFail($id)->delete();
        return redirect()->route('roles.index')
            ->with('success', 'Role deleted successfully');
    }
}
