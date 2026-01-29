<?php

namespace App\Http\Controllers\employees;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

use App\Models\employee\Employee;
use App\Models\employee\Job;
use App\Models\general\Branch;

use App\Traits\store\file_storage;

class employeescontroller extends Controller
{
    use file_storage;

    private function generateEmployeeCode($id): string
    {
        return 'EMP-' . str_pad((string)$id, 6, '0', STR_PAD_LEFT);
    }

    private function deletePublicFileIfExists(?string $relativePath): void
    {
        if (!empty($relativePath)) {
            $full = public_path($relativePath);
            if (file_exists($full)) {
                @unlink($full);
            }
        }
    }

    public function index()
    {
        $Employees = Employee::with(['job', 'branches'])->orderBy('id', 'desc')->get();
        $Jobs = Job::orderBy('id', 'desc')->get();
        $Branches = Branch::orderBy('id', 'desc')->get();

        return view('employees.index', compact('Employees', 'Jobs', 'Branches'));
    }

    public function create()
    {
        return redirect()->route('employees.index');
    }

    public function store(Request $request)
    {
        $request->validate([
            'first_name' => 'required|string|max:150',
            'last_name' => 'required|string|max:150',

            'job_id' => 'nullable|exists:jobs,id',

            'gender' => 'nullable|in:male,female',
            'birth_date' => 'nullable|date',

            'phone_1' => 'nullable|string|max:50',
            'phone_2' => 'nullable|string|max:50',
            'whatsapp' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',

            'specialization' => 'nullable|string|max:255',
            'years_experience' => 'nullable|integer|min:0|max:80',

            'bio' => 'nullable|string',

            'compensation_type' => 'required|in:salary_only,commission_only,salary_and_commission',
            'base_salary' => 'nullable|numeric|min:0',

            'commission_percent' => 'nullable|numeric|min:0|max:100',
            'commission_fixed' => 'nullable|numeric|min:0',

            'salary_transfer_method' => 'nullable|in:cash,ewallet,bank_transfer,instapay,credit_card,cheque,other',
            'salary_transfer_details' => 'nullable|string',

            'branches' => 'required|array|min:1',
            'branches.*' => 'required|exists:branches,id',
            'primary_branch_id' => 'required|exists:branches,id',

            'status' => 'sometimes|accepted',

            'photo' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
        ]);

        // Ensure primary branch is inside selected branches
        if (!in_array($request->primary_branch_id, $request->branches)) {
            return redirect()->back()->withInput()->with('error', trans('employees.primary_branch_must_be_selected'));
        }

        // Compensation logic checks
        $type = $request->compensation_type;

        if (in_array($type, ['salary_only', 'salary_and_commission']) && ($request->base_salary === null || $request->base_salary === '')) {
            return redirect()->back()->withInput()->with('error', trans('employees.base_salary_required'));
        }

        if (in_array($type, ['commission_only', 'salary_and_commission'])) {
            $hasPercent = ($request->commission_percent !== null && $request->commission_percent !== '');
            $hasFixed = ($request->commission_fixed !== null && $request->commission_fixed !== '');
            if (!$hasPercent && !$hasFixed) {
                return redirect()->back()->withInput()->with('error', trans('employees.commission_required'));
            }
        }

        if (in_array($type, ['salary_only', 'salary_and_commission']) && empty($request->salary_transfer_method)) {
            return redirect()->back()->withInput()->with('error', trans('employees.transfer_method_required'));
        }

        DB::beginTransaction();
        try {
            $data = [
                'code' => null, // will be generated after create
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'job_id' => $request->job_id,

                'gender' => $request->gender,
                'birth_date' => $request->birth_date,

                'phone_1' => $request->phone_1,
                'phone_2' => $request->phone_2,
                'whatsapp' => $request->whatsapp,
                'email' => $request->email,

                'specialization' => $request->specialization,
                'years_experience' => $request->years_experience,

                'bio' => $request->bio,

                'compensation_type' => $request->compensation_type,
                'base_salary' => $request->base_salary,
                'commission_percent' => $request->commission_percent,
                'commission_fixed' => $request->commission_fixed,

                'salary_transfer_method' => $request->salary_transfer_method,
                'salary_transfer_details' => $request->salary_transfer_details,

                'status' => $request->boolean('status'),
                'user_add' => Auth::check() ? Auth::user()->id : null,
            ];

            if ($request->hasFile('photo')) {
                $data['photo'] = $this->file_storage($request->file('photo'), 'employees/photo');
            }

            $Employee = Employee::create($data);

            // Generate unique code based on id
            $Employee->update([
                'code' => $this->generateEmployeeCode($Employee->id),
            ]);

            // Attach branches + set primary
            $pivot = [];
            foreach ($request->branches as $branchId) {
                $pivot[$branchId] = [
                    'is_primary' => ((string)$branchId === (string)$request->primary_branch_id),
                ];
            }
            $Employee->branches()->sync($pivot);

            DB::commit();
            return redirect()->back()->with('success', trans('employees.saved_success'));

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->withInput()->with('error', trans('employees.saved_error'));
        }
    }

    public function show($id)
    {
        return redirect()->route('employees.index');
    }

    public function edit($id)
    {
        return redirect()->route('employees.index');
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'id' => 'required|exists:employees,id',

            'first_name' => 'required|string|max:150',
            'last_name' => 'required|string|max:150',

            'job_id' => 'nullable|exists:jobs,id',

            'gender' => 'nullable|in:male,female',
            'birth_date' => 'nullable|date',

            'phone_1' => 'nullable|string|max:50',
            'phone_2' => 'nullable|string|max:50',
            'whatsapp' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',

            'specialization' => 'nullable|string|max:255',
            'years_experience' => 'nullable|integer|min:0|max:80',

            'bio' => 'nullable|string',

            'compensation_type' => 'required|in:salary_only,commission_only,salary_and_commission',
            'base_salary' => 'nullable|numeric|min:0',

            'commission_percent' => 'nullable|numeric|min:0|max:100',
            'commission_fixed' => 'nullable|numeric|min:0',

            'salary_transfer_method' => 'nullable|in:cash,ewallet,bank_transfer,instapay,credit_card,cheque,other',
            'salary_transfer_details' => 'nullable|string',

            'branches' => 'required|array|min:1',
            'branches.*' => 'required|exists:branches,id',
            'primary_branch_id' => 'required|exists:branches,id',

            'status' => 'sometimes|accepted',

            'photo' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
        ]);

        if (!in_array($request->primary_branch_id, $request->branches)) {
            return redirect()->back()->withInput()->with('error', trans('employees.primary_branch_must_be_selected'));
        }

        $type = $request->compensation_type;

        if (in_array($type, ['salary_only', 'salary_and_commission']) && ($request->base_salary === null || $request->base_salary === '')) {
            return redirect()->back()->withInput()->with('error', trans('employees.base_salary_required'));
        }

        if (in_array($type, ['commission_only', 'salary_and_commission'])) {
            $hasPercent = ($request->commission_percent !== null && $request->commission_percent !== '');
            $hasFixed = ($request->commission_fixed !== null && $request->commission_fixed !== '');
            if (!$hasPercent && !$hasFixed) {
                return redirect()->back()->withInput()->with('error', trans('employees.commission_required'));
            }
        }

        if (in_array($type, ['salary_only', 'salary_and_commission']) && empty($request->salary_transfer_method)) {
            return redirect()->back()->withInput()->with('error', trans('employees.transfer_method_required'));
        }

        DB::beginTransaction();
        try {
            $Employee = Employee::with('branches')->findOrFail($request->id);

            $data = [
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'job_id' => $request->job_id,

                'gender' => $request->gender,
                'birth_date' => $request->birth_date,

                'phone_1' => $request->phone_1,
                'phone_2' => $request->phone_2,
                'whatsapp' => $request->whatsapp,
                'email' => $request->email,

                'specialization' => $request->specialization,
                'years_experience' => $request->years_experience,

                'bio' => $request->bio,

                'compensation_type' => $request->compensation_type,
                'base_salary' => $request->base_salary,
                'commission_percent' => $request->commission_percent,
                'commission_fixed' => $request->commission_fixed,

                'salary_transfer_method' => $request->salary_transfer_method,
                'salary_transfer_details' => $request->salary_transfer_details,

                'status' => $request->boolean('status'),
            ];

            if ($request->hasFile('photo')) {
                // delete old photo from public/attachments/...
                $this->deletePublicFileIfExists($Employee->photo);

                $data['photo'] = $this->file_storage($request->file('photo'), 'employees/photo');
            }

            $Employee->update($data);

            // sync branches + set primary
            $pivot = [];
            foreach ($request->branches as $branchId) {
                $pivot[$branchId] = [
                    'is_primary' => ((string)$branchId === (string)$request->primary_branch_id),
                ];
            }
            $Employee->branches()->sync($pivot);

            DB::commit();
            return redirect()->back()->with('success', trans('employees.updated_success'));

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->withInput()->with('error', trans('employees.updated_error'));
        }
    }

    public function destroy(Request $request, $id)
    {
        $request->validate([
            'id' => 'required|exists:employees,id',
        ]);

        DB::beginTransaction();
        try {
            $Employee = Employee::findOrFail($request->id);

            // NOTE: نترك الصورة بدون حذف عند delete لو تحب؛ أو احذفها هنا لو تريد.
             $this->deletePublicFileIfExists($Employee->photo);

            $Employee->delete();

            DB::commit();
            return redirect()->back()->with('success', trans('employees.deleted_success'));

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', trans('employees.deleted_error'));
        }
    }
}
