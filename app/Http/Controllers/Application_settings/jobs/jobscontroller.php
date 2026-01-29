<?php

namespace App\Http\Controllers\Application_settings\jobs;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

use App\Models\employee\Job;

class jobscontroller extends Controller
{
    public function index()
    {
        $Jobs = Job::orderBy('id', 'desc')->get();
        return view('settings.jobs.index', compact('Jobs'));
    }

    public function create()
    {
        return redirect()->route('jobs.index');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name_ar' => 'required|string|max:255',
            'name_en' => 'required|string|max:255',

            'code' => 'nullable|string|max:50|unique:jobs,code',
            'description' => 'nullable|string',
            'notes' => 'nullable|string',

            'status' => 'sometimes|accepted',
        ]);

        // منع تكرار نفس الاسم (عربي/إنجليزي) كمنطق أساسي
        $exists = Job::where('name->ar', $request->name_ar)
            ->orWhere('name->en', $request->name_en)
            ->exists();

        if ($exists) {
            return redirect()->back()->withInput()->with('error', trans('settings_trans.job_exists'));
        }

        DB::beginTransaction();
        try {

            Job::create([
                'name' => [
                    'ar' => $request->name_ar,
                    'en' => $request->name_en,
                ],
                'code' => $request->code,
                'description' => $request->description,
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
        return redirect()->route('jobs.index');
    }

    public function edit($id)
    {
        return redirect()->route('jobs.index');
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'id' => 'required|exists:jobs,id',

            'name_ar' => 'required|string|max:255',
            'name_en' => 'required|string|max:255',

            'code' => [
                'nullable',
                'string',
                'max:50',
                Rule::unique('jobs', 'code')->ignore($request->id),
            ],

            'description' => 'nullable|string',
            'notes' => 'nullable|string',

            'status' => 'sometimes|accepted',
        ]);

        // منع تكرار نفس الاسم (مع استثناء السجل الحالي)
        $exists = Job::where('id', '!=', $request->id)
            ->where(function ($q) use ($request) {
                $q->where('name->ar', $request->name_ar)
                  ->orWhere('name->en', $request->name_en);
            })
            ->exists();

        if ($exists) {
            return redirect()->back()->withInput()->with('error', trans('settings_trans.job_exists'));
        }

        DB::beginTransaction();
        try {

            $Job = Job::findOrFail($request->id);

            $Job->update([
                'name' => [
                    'ar' => $request->name_ar,
                    'en' => $request->name_en,
                ],
                'code' => $request->code,
                'description' => $request->description,
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
            'id' => 'required|exists:jobs,id',
        ]);

        DB::beginTransaction();
        try {

            $Job = Job::findOrFail($request->id);

            // لاحقًا: هنا نضيف شرط "لا يمكن حذف الوظيفة لأنها مرتبطة بموظفين"
            $Job->delete();

            DB::commit();
            return redirect()->back()->with('success', trans('settings_trans.deleted_success'));

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', trans('settings_trans.deleted_error'));
        }
    }
}
