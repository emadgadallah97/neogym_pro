<?php

namespace App\Http\Controllers\hr;

use App\Http\Controllers\Controller;
use App\Models\general\Branch;
use App\Models\hr\HrEmployeeShift;
use App\Models\hr\HrShift;
use App\Models\employee\employee;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class employee_shiftscontroller extends Controller
{
    // ─────────────────────────────────────────
    // Helpers — Branch Access
    // ─────────────────────────────────────────

    private function accessibleBranchIds(): array
    {
        $user = Auth::user();
        if (!$user->employee_id) return [];

        return DB::table('employee_branch')
            ->where('employee_id', $user->employee_id)
            ->pluck('branch_id')
            ->map(fn($id) => (int)$id)
            ->toArray();
    }

    private function userCanAccessBranch(int $branchId): bool
    {
        $ids = $this->accessibleBranchIds();
        return empty($ids) || in_array($branchId, $ids);
    }

    // ─────────────────────────────────────────
    // Index
    // ─────────────────────────────────────────

    public function index(Request $request)
    {
        $branchIds = $this->accessibleBranchIds();

        // ✅ Scope يعمل تلقائياً — فروع المستخدم فقط
        $branches = Branch::where('status', 1)->orderBy('id')->get();

        $defaultBranch = !empty($branchIds) ? $branchIds[0] : 0;
        $branchId      = (int)($request->get('branch_id', $defaultBranch));
        $employeeId    = (int)($request->get('employee_id', 0));

        // ✅ التحقق أن الفرع المطلوب ضمن فروع المستخدم
        if ($branchId > 0 && !$this->userCanAccessBranch($branchId)) {
            $branchId = $defaultBranch;
        }

        $employees = $this->getEmployeesByPrimaryBranch($branchId);

        // ✅ الورديات إعدادات — بدون تقييد
        $shifts = HrShift::where('status', 1)->orderBy('id')->get();

        $q = HrEmployeeShift::with([
            'employee',
            // ✅ withoutGlobalScope لضمان ظهور اسم الفرع دائماً
            'branch' => fn($q) => $q->withoutGlobalScope(\App\Models\Scopes\BranchAccessScope::class),
            'shift',
        ])->orderByDesc('id');

        if ($branchId > 0) {
            $q->where('branch_id', $branchId);
            $primaryEmployeeIds = $employees->pluck('id')->toArray();
            $q->whereIn('employee_id', $primaryEmployeeIds);
        } else {
            $q->whereRaw('1=0');
        }

        if ($employeeId > 0) $q->where('employee_id', $employeeId);

        $rows = $q->get();

        return view('hr.employee_shifts.index', compact(
            'branches', 'branchId', 'employees', 'employeeId', 'shifts', 'rows'
        ));
    }

    public function create() { return redirect()->route('employee_shifts.index'); }
    public function edit($id) { return redirect()->route('employee_shifts.index'); }

    // ─────────────────────────────────────────
    // Show
    // ─────────────────────────────────────────

    public function show($id)
    {
        $row = HrEmployeeShift::with([
            'employee',
            'branch' => fn($q) => $q->withoutGlobalScope(\App\Models\Scopes\BranchAccessScope::class),
            'shift',
        ])->findOrFail($id);

        return response()->json(['success' => true, 'data' => $this->dto($row)]);
    }

    // ─────────────────────────────────────────
    // Store
    // ─────────────────────────────────────────

    public function store(Request $request)
    {
        // ✅ التحقق أن الفرع ضمن فروع المستخدم قبل الـ validation
        $branchId = (int)$request->input('branch_id', 0);
        if ($branchId > 0 && !$this->userCanAccessBranch($branchId)) {
            return response()->json(['success' => false, 'message' => trans('accounting.branch_not_allowed')], 403);
        }

        $data = $this->validateEmployeeShift($request);

        try {
            $row              = new HrEmployeeShift();
            $row->employee_id = (int)$data['employee_id'];
            $row->branch_id   = (int)$data['branch_id'];
            $row->shift_id    = (int)$data['shift_id'];
            $row->start_date  = $data['start_date'];
            $row->end_date    = $data['end_date'];
            $row->status      = (bool)$data['status'];
            $row->user_add    = Auth::id();
            $row->save();

            $row->load([
                'employee',
                'branch' => fn($q) => $q->withoutGlobalScope(\App\Models\Scopes\BranchAccessScope::class),
                'shift',
            ]);

            return response()->json([
                'success' => true,
                'message' => trans('hr.employee_shift_saved_success') ?? 'تم حفظ وردية الموظف بنجاح',
                'data'    => $this->dto($row),
            ]);
        } catch (\Throwable $e) {
            Log::error('employee_shifts.store error', ['msg' => $e->getMessage(), 'file' => $e->getFile(), 'line' => $e->getLine()]);
            return response()->json(['success' => false, 'message' => config('app.debug') ? $e->getMessage() : (trans('hr.error_occurred') ?? 'حدث خطأ')], 500);
        }
    }

    // ─────────────────────────────────────────
    // Update
    // ─────────────────────────────────────────

    public function update(Request $request, $id)
    {
        $row = HrEmployeeShift::findOrFail($id);

        // ✅ التحقق أن الفرع ضمن فروع المستخدم
        $branchId = (int)$request->input('branch_id', 0);
        if ($branchId > 0 && !$this->userCanAccessBranch($branchId)) {
            return response()->json(['success' => false, 'message' => trans('accounting.branch_not_allowed')], 403);
        }

        $data = $this->validateEmployeeShift($request, $id);

        try {
            $row->employee_id = (int)$data['employee_id'];
            $row->branch_id   = (int)$data['branch_id'];
            $row->shift_id    = (int)$data['shift_id'];
            $row->start_date  = $data['start_date'];
            $row->end_date    = $data['end_date'];
            $row->status      = (bool)$data['status'];
            $row->save();

            $row->load([
                'employee',
                'branch' => fn($q) => $q->withoutGlobalScope(\App\Models\Scopes\BranchAccessScope::class),
                'shift',
            ]);

            return response()->json([
                'success' => true,
                'message' => trans('hr.employee_shift_updated_success') ?? 'تم تحديث وردية الموظف بنجاح',
                'data'    => $this->dto($row),
            ]);
        } catch (\Throwable $e) {
            Log::error('employee_shifts.update error', ['msg' => $e->getMessage(), 'file' => $e->getFile(), 'line' => $e->getLine()]);
            return response()->json(['success' => false, 'message' => config('app.debug') ? $e->getMessage() : (trans('hr.error_occurred') ?? 'حدث خطأ')], 500);
        }
    }

    // ─────────────────────────────────────────
    // Destroy
    // ─────────────────────────────────────────

    public function destroy($id)
    {
        $row = HrEmployeeShift::findOrFail($id);

        try {
            $row->delete();

            return response()->json([
                'success' => true,
                'message' => trans('hr.employee_shift_deleted_success') ?? 'تم حذف وردية الموظف بنجاح',
                'data'    => ['id' => $id],
            ]);
        } catch (\Throwable $e) {
            Log::error('employee_shifts.destroy error', ['msg' => $e->getMessage(), 'file' => $e->getFile(), 'line' => $e->getLine()]);
            return response()->json(['success' => false, 'message' => config('app.debug') ? $e->getMessage() : (trans('hr.error_occurred') ?? 'حدث خطأ')], 500);
        }
    }

    // ─────────────────────────────────────────
    // Validation
    // ─────────────────────────────────────────

    private function validateEmployeeShift(Request $request, ?int $ignoreId = null): array
    {
        $validator = Validator::make($request->all(), [
            'branch_id'   => 'required|exists:branches,id',
            'employee_id' => 'required|exists:employees,id',
            'shift_id'    => [
                'required',
                Rule::exists('hr_shifts', 'id')->where(fn($q) => $q->where('status', 1)),
            ],
            'start_date'  => 'required|date',
            'end_date'    => 'required|date|after_or_equal:start_date',
            'status'      => 'required|boolean',
        ], [
            'end_date.after_or_equal' => trans('hr.end_date_after_or_equal_start') ?? 'تاريخ النهاية يجب أن يكون بعد/يساوي تاريخ البداية',
        ]);

        $validator->after(function ($v) use ($request, $ignoreId) {
            $branchId   = (int)$request->input('branch_id');
            $employeeId = (int)$request->input('employee_id');
            $startDate  = $request->input('start_date');
            $endDate    = $request->input('end_date');
            $status     = (int)$request->input('status', 1);

            if (!$branchId || !$employeeId || !$startDate || !$endDate) return;

            $start = Carbon::parse($startDate)->toDateString();
            $end   = Carbon::parse($endDate)->toDateString();

            if (!$this->isEmployeePrimaryInBranch($employeeId, $branchId)) {
                $v->errors()->add('employee_id', trans('hr.employee_not_in_branch') ?? 'الموظف غير تابع لهذا الفرع (Primary)');
                return;
            }

            if ($status === 1) {
                $q = HrEmployeeShift::where('employee_id', $employeeId)
                    ->where('branch_id', $branchId)
                    ->where('status', 1)
                    ->whereDate('start_date', '<=', $end)
                    ->whereDate('end_date', '>=', $start);

                if ($ignoreId) $q->where('id', '!=', $ignoreId);

                if ($q->exists()) {
                    $msg = trans('hr.employee_shift_overlap') ?? 'يوجد تعارض مع وردية فعّالة أخرى لنفس الموظف والفرع ضمن نفس الفترة';
                    $v->errors()->add('start_date', $msg);
                    $v->errors()->add('end_date', $msg);
                }
            }
        });

        $validated = $validator->validate();

        $validated['branch_id']   = (int)$validated['branch_id'];
        $validated['employee_id'] = (int)$validated['employee_id'];
        $validated['shift_id']    = (int)$validated['shift_id'];
        $validated['status']      = (bool)$validated['status'];
        $validated['start_date']  = Carbon::parse($validated['start_date'])->toDateString();
        $validated['end_date']    = Carbon::parse($validated['end_date'])->toDateString();

        return $validated;
    }

    // ─────────────────────────────────────────
    // DTO + Private Helpers
    // ─────────────────────────────────────────

    private function dto(HrEmployeeShift $r): array
    {
        return [
            'id'            => $r->id,
            'employee_id'   => $r->employee_id,
            'employee_name' => $r->employee?->full_name ?? ($r->employee?->getFullNameAttribute() ?? ''),
            'employee_code' => $r->employee?->code ?? '',
            'branch_id'     => $r->branch_id,
            'branch_name'   => $r->branch?->name ?? '',
            'shift_id'      => $r->shift_id,
            'shift_name'    => $r->shift?->name ?? '',
            'start_date'    => $r->start_date ? Carbon::parse($r->start_date)->toDateString() : null,
            'end_date'      => $r->end_date   ? Carbon::parse($r->end_date)->toDateString()   : null,
            'status'        => (int)$r->status,
            'created_at'    => $r->created_at ? $r->created_at->toDateTimeString() : null,
        ];
    }

    private function getEmployeesByPrimaryBranch(int $branchId)
    {
        if ($branchId <= 0) return collect([]);

        return employee::whereHas('branches', function ($q) use ($branchId) {
            $q->where('branches.id', $branchId)
              ->where('employee_branch.is_primary', 1);
        })
        ->where('status', 1)
        ->orderBy('id')
        ->get();
    }

    private function isEmployeePrimaryInBranch(int $employeeId, int $branchId): bool
    {
        return DB::table('employee_branch')
            ->where('employee_id', $employeeId)
            ->where('branch_id', $branchId)
            ->where('is_primary', 1)
            ->exists();
    }
}
