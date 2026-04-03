<?php

namespace App\Http\Controllers\users;

use App\Http\Controllers\Controller;
use App\Models\employee\employee;
use App\Models\general\Branch;
use App\Models\Role;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class userscontroller extends Controller
{
            public function __construct()
    {
        $this->middleware('permission:users_view', ['only' => ['index', 'show']]);
        $this->middleware('permission:users_create', ['only' => ['create', 'store']]);
        $this->middleware('permission:users_edit', ['only' => ['edit', 'update']]);
        $this->middleware('permission:users_password_edit', ['only' => ['updatePassword']]);
        $this->middleware('permission:users_delete', ['only' => ['destroy']]);
    }
    public function index(Request $request)
    {
        $users = User::with([
            'branch'   => fn($q) => $q->withoutGlobalScopes(),
            'employee',
            'roles',
        ])->latest()->get();

        return view('users.index', compact('users'));
    }

    public function create()
    {
        $branches  = Branch::withoutGlobalScopes()->where('status', 1)->orderBy('id')->get();
        $employees = employee::where('status', 1)->get();
        $roles     = \App\Models\Role::pluck('name', 'name')->all();

        return view('users.create', compact('branches', 'employees', 'roles'));
    }

    public function store(Request $request)
    {
        $this->validate($request, [
            'name'       => 'required',
            'email'      => 'required|email|unique:users,email',
            'password'   => 'required|same:confirm-password',
            'roles_name' => 'required|array',
            'Status'     => 'required',
            'branch_id'  => 'required',
        ]);

        $input             = $request->all();
        $input['password'] = Hash::make($input['password']);

        $user = User::create($input);

        if ($request->has('roles_name')) {
            $user->assignRole($request->input('roles_name'));
        }

        return redirect()->route('users.index')
            ->with('success', trans('users.created_successfully'));
    }

    public function show($id)
    {
        $user = User::with([
            'branch'          => fn($q) => $q->withoutGlobalScopes(),
            'employee.branches',
            'roles',
        ])->find($id);

        if (!$user) abort(404);

        return view('users.show', compact('user'));
    }

    public function edit($id)
    {
        $user = User::find($id);
        if (!$user) abort(404);

        $branches  = Branch::withoutGlobalScopes()->where('status', 1)->orderBy('id')->get();
        $employees = employee::where('status', 1)->get();
        $roles     = \App\Models\Role::pluck('name', 'name')->all();
        $userRole  = $user->roles->pluck('name', 'name')->all();

        return view('users.edit', compact('user', 'branches', 'employees', 'roles', 'userRole'));
    }

    public function update(Request $request, $id)
    {
        $this->validate($request, [
            'name'       => 'required',
            'email'      => 'required|email|unique:users,email,' . $id,
            'roles_name' => 'required|array',
            'Status'     => 'required',
            'branch_id'  => 'required',
        ]);

        $input = $request->except(['password', 'confirm-password']);

        $user = User::find($id);
        $user->update($input);

        DB::table('model_has_roles')->where('model_id', $id)->delete();

        if ($request->has('roles_name')) {
            $user->assignRole($request->input('roles_name'));
        }

        return redirect()->route('users.index')
            ->with('success', trans('users.updated_successfully'));
    }

    public function updatePassword(Request $request, $id)
    {
        $this->validate($request, [
            'password' => 'required|min:6|same:confirm-password',
        ]);

        $user = User::find($id);
        if (!$user) abort(404);

        $user->update([
            'password' => Hash::make($request->password),
        ]);

        return redirect()->back()
            ->with('success', trans('users.password_updated_successfully'));
    }

    public function destroy($id)
    {
        User::find($id)->delete();

        return redirect()->route('users.index')
            ->with('success', trans('users.deleted_successfully'));
    }
}
