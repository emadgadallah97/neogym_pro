<?php

namespace App\Http\Controllers\Application_settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

use App\Models\general\Branch;

class branchesController extends Controller
{
    public function index()
    {
        $Branches = Branch::orderBy('id', 'desc')->get();
        return view('settings.branches', compact('Branches'));
    }

    public function create()
    {
        return redirect()->route('branches.index');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name_ar' => 'required|string|max:255',
            'name_en' => 'required|string|max:255',

            'address' => 'nullable|string',
            'phone_1' => 'nullable|string|max:50',
            'phone_2' => 'nullable|string|max:50',
            'whatsapp' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',

            'notes' => 'nullable|string',
            'status' => 'sometimes|accepted',
        ]);

        DB::beginTransaction();
        try {

            Branch::create([
                'name' => [
                    'ar' => $request->name_ar,
                    'en' => $request->name_en,
                ],
                'address' => $request->address,
                'phone_1' => $request->phone_1,
                'phone_2' => $request->phone_2,
                'whatsapp' => $request->whatsapp,
                'email' => $request->email,
                'notes' => $request->notes,
                'status' => $request->boolean('status'),
                'user_add' => Auth::check() ? Auth::user()->id : null,
            ]);

            DB::commit();
            return redirect()->back()->with('success', trans('settings_trans.saved_success'));

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->withInput()->with('error', trans('settings_trans.saved_error'));
        }
    }

    public function show($id)
    {
        return redirect()->route('branches.index');
    }

    public function edit($id)
    {
        return redirect()->route('branches.index');
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'id' => 'required|exists:branches,id',

            'name_ar' => 'required|string|max:255',
            'name_en' => 'required|string|max:255',

            'address' => 'nullable|string',
            'phone_1' => 'nullable|string|max:50',
            'phone_2' => 'nullable|string|max:50',
            'whatsapp' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',

            'notes' => 'nullable|string',
            'status' => 'sometimes|accepted',
        ]);

        DB::beginTransaction();
        try {

            $Branch = Branch::findOrFail($request->id);

            $Branch->update([
                'name' => [
                    'ar' => $request->name_ar,
                    'en' => $request->name_en,
                ],
                'address' => $request->address,
                'phone_1' => $request->phone_1,
                'phone_2' => $request->phone_2,
                'whatsapp' => $request->whatsapp,
                'email' => $request->email,
                'notes' => $request->notes,
                'status' => $request->boolean('status'),
            ]);

            DB::commit();
            return redirect()->back()->with('success', trans('settings_trans.updated_success'));

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->withInput()->with('error', trans('settings_trans.updated_error'));
        }
    }

    public function destroy(Request $request, $id)
    {
        $request->validate([
            'id' => 'required|exists:branches,id',
        ]);

        DB::beginTransaction();
        try {

            $Branch = Branch::findOrFail($request->id);
            $Branch->delete();

            DB::commit();
            return redirect()->back()->with('success', trans('settings_trans.deleted_success'));

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', trans('settings_trans.deleted_error'));
        }
    }
}
