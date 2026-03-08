<?php

namespace App\Http\Controllers\accounting;

use App\Http\Controllers\Controller;
use App\Models\accounting\Expense;
use App\Models\accounting\ExpensesType;
use App\Models\employee\employee as Employee;
use App\Models\general\Branch;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class expensescontroller extends Controller
{
    // ─────────────────────────────────────────────────────────────
    // Helpers
    // ─────────────────────────────────────────────────────────────

    /**
     * فروع المستخدم الحالي — فارغة = admin يرى الكل
     */
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

    /**
     * التحقق أن الفرع المختار ضمن فروع المستخدم
     */
    private function userCanAccessBranch(int $branchId): bool
    {
        $ids = $this->accessibleBranchIds();
        return empty($ids) || in_array($branchId, $ids);
    }

    private function employeeBelongsToBranch(?int $employeeId, int $branchId): bool
    {
        if (!$employeeId) return true;

        return Employee::where('id', $employeeId)
            ->whereHas('branches', function ($q) use ($branchId) {
                $q->where('branches.id', $branchId);
            })
            ->exists();
    }

    // ─────────────────────────────────────────────────────────────
    // Index
    // ─────────────────────────────────────────────────────────────

    public function index()
    {
        $branchIds = $this->accessibleBranchIds();

        // ✅ عرض مصروفات فروع المستخدم فقط
        $Expenses = Expense::with([
            'type',
            // ✅ withoutGlobalScope لإظهار اسم الفرع دائماً
            'branch'            => fn($q) => $q->withoutGlobalScope(\App\Models\Scopes\BranchAccessScope::class),
            'disburserEmployee',
            'creator',
        ])
        ->when(!empty($branchIds), fn($q) => $q->whereIn('branchid', $branchIds))
        ->orderByDesc('id')
        ->get();

        $ExpensesTypes = ExpensesType::where('status', 1)->orderByDesc('id')->get();

        // ✅ القائمة المنسدلة — فروع المستخدم فقط (Scope يعمل تلقائياً)
        $BranchesList = Branch::select(['id', 'name'])
            ->where('status', 1)
            ->orderByDesc('id')
            ->get();

        return view('accounting.programs.expenses.index', compact('Expenses', 'ExpensesTypes', 'BranchesList'));
    }

    public function create()
    {
        return redirect()->route('expenses.index');
    }

    // ─────────────────────────────────────────────────────────────
    // Store
    // ─────────────────────────────────────────────────────────────

    public function store(Request $request)
    {
        $request->validate([
            'branchid'              => ['required', 'integer', 'exists:branches,id'],
            'expensestypeid'        => ['required', 'integer', 'exists:expenses_types,id'],
            'expensedate'           => ['required', 'date'],
            'amount'                => ['required', 'numeric', 'min:0.01'],
            'recipientname'         => ['required', 'string', 'max:255'],
            'recipientphone'        => ['nullable', 'string', 'max:50'],
            'recipientnationalid'   => ['nullable', 'string', 'max:100'],
            'disbursedbyemployeeid' => ['nullable', 'integer', 'exists:employees,id'],
            'description'           => ['nullable', 'string'],
            'notes'                 => ['nullable', 'string'],
        ]);

        $branchId   = (int)$request->branchid;
        $employeeId = $request->disbursedbyemployeeid ? (int)$request->disbursedbyemployeeid : null;

        // ✅ التحقق أن الفرع ضمن فروع المستخدم
        if (!$this->userCanAccessBranch($branchId)) {
            return redirect()->back()->withInput()->with('error', trans('accounting.branch_not_allowed'));
        }

        if (!$this->employeeBelongsToBranch($employeeId, $branchId)) {
            return redirect()->back()->withInput()->with('error', trans('accounting.employee_not_in_branch'));
        }

        $typeIsActive = ExpensesType::where('id', (int)$request->expensestypeid)->where('status', 1)->exists();
        if (!$typeIsActive) {
            return redirect()->back()->withInput()->with('error', trans('accounting.expense_type_inactive'));
        }

        DB::beginTransaction();
        try {
            Expense::create([
                'branchid'              => $branchId,
                'expensestypeid'        => (int)$request->expensestypeid,
                'expensedate'           => $request->expensedate,
                'amount'                => $request->amount,
                'recipientname'         => trim((string)$request->recipientname),
                'recipientphone'        => $request->recipientphone        ? trim((string)$request->recipientphone)        : null,
                'recipientnationalid'   => $request->recipientnationalid   ? trim((string)$request->recipientnationalid)   : null,
                'disbursedbyemployeeid' => $employeeId,
                'description'           => $request->description,
                'notes'                 => $request->notes,
                'iscancelled'           => false,
                'cancelledat'           => null,
                'cancelledby'           => null,
                'useradd'               => Auth::check() ? Auth::user()->id : null,
                'userupdate'            => null,
            ]);

            DB::commit();
            return redirect()->back()->with('success', trans('accounting.saved_success'));
        } catch (\Throwable $e) {
            DB::rollBack();
            return redirect()->back()->withInput()->with('error', trans('accounting.saved_error'));
        }
    }

    public function show($id)  { return redirect()->route('expenses.index'); }
    public function edit($id)  { return redirect()->route('expenses.index'); }

    // ─────────────────────────────────────────────────────────────
    // Update
    // ─────────────────────────────────────────────────────────────

    public function update(Request $request, $id)
    {
        $request->validate([
            'id'                    => ['required', 'integer', 'exists:expenses,id'],
            'branchid'              => ['required', 'integer', 'exists:branches,id'],
            'expensestypeid'        => ['required', 'integer', 'exists:expenses_types,id'],
            'expensedate'           => ['required', 'date'],
            'amount'                => ['required', 'numeric', 'min:0.01'],
            'recipientname'         => ['required', 'string', 'max:255'],
            'recipientphone'        => ['nullable', 'string', 'max:50'],
            'recipientnationalid'   => ['nullable', 'string', 'max:100'],
            'disbursedbyemployeeid' => ['nullable', 'integer', 'exists:employees,id'],
            'description'           => ['nullable', 'string'],
            'notes'                 => ['nullable', 'string'],
            'iscancelled'           => ['nullable', 'boolean'],
        ]);

        $Expense    = Expense::findOrFail((int)$request->id);
        $branchId   = (int)$request->branchid;
        $employeeId = $request->disbursedbyemployeeid ? (int)$request->disbursedbyemployeeid : null;

        // ✅ التحقق أن الفرع ضمن فروع المستخدم
        if (!$this->userCanAccessBranch($branchId)) {
            return redirect()->back()->withInput()->with('error', trans('accounting.branch_not_allowed'));
        }

        if (!$this->employeeBelongsToBranch($employeeId, $branchId)) {
            return redirect()->back()->withInput()->with('error', trans('accounting.employee_not_in_branch'));
        }

        $typeIsActive = ExpensesType::where('id', (int)$request->expensestypeid)->where('status', 1)->exists();
        if (!$typeIsActive) {
            return redirect()->back()->withInput()->with('error', trans('accounting.expense_type_inactive'));
        }

        $isCancelled = (bool)$request->iscancelled;

        DB::beginTransaction();
        try {
            $Expense->update([
                'branchid'              => $branchId,
                'expensestypeid'        => (int)$request->expensestypeid,
                'expensedate'           => $request->expensedate,
                'amount'                => $request->amount,
                'recipientname'         => trim((string)$request->recipientname),
                'recipientphone'        => $request->recipientphone        ? trim((string)$request->recipientphone)        : null,
                'recipientnationalid'   => $request->recipientnationalid   ? trim((string)$request->recipientnationalid)   : null,
                'disbursedbyemployeeid' => $employeeId,
                'description'           => $request->description,
                'notes'                 => $request->notes,
                'iscancelled'           => $isCancelled,
                'cancelledat'           => $isCancelled ? ($Expense->cancelledat ?? Carbon::now()) : null,
                'cancelledby'           => $isCancelled ? (Auth::check() ? Auth::user()->id : null) : null,
                'userupdate'            => Auth::check() ? Auth::user()->id : null,
            ]);

            DB::commit();
            return redirect()->back()->with('success', trans('accounting.updated_success'));
        } catch (\Throwable $e) {
            DB::rollBack();
            return redirect()->back()->withInput()->with('error', trans('accounting.updated_error'));
        }
    }

    // ─────────────────────────────────────────────────────────────
    // Destroy
    // ─────────────────────────────────────────────────────────────

    public function destroy(Request $request, $id)
    {
        $request->validate([
            'id' => ['required', 'integer', 'exists:expenses,id'],
        ]);

        DB::beginTransaction();
        try {
            Expense::findOrFail((int)$request->id)->delete();

            DB::commit();
            return redirect()->back()->with('success', trans('accounting.deleted_success'));
        } catch (\Throwable $e) {
            DB::rollBack();
            return redirect()->back()->with('error', trans('accounting.deleted_error'));
        }
    }

    // ─────────────────────────────────────────────────────────────
    // AJAX
    // ─────────────────────────────────────────────────────────────

    public function ajaxEmployeesByBranch(Request $request)
    {
        $request->validate([
            'branchid' => ['required', 'integer', 'exists:branches,id'],
        ]);

        $branchId = (int)$request->branchid;

        // ✅ منع جلب موظفي فرع لا يملكه المستخدم
        if (!$this->userCanAccessBranch($branchId)) {
            return response()->json(['ok' => false, 'data' => []]);
        }

        $rows = Employee::query()
            ->whereHas('branches', fn($q) => $q->where('branches.id', $branchId))
            ->orderByDesc('id')
            ->get()
            ->map(fn($e) => [
                'id'   => (int)$e->id,
                'text' => $e->full_name ?? trim(($e->first_name ?? '') . ' ' . ($e->last_name ?? '')),
            ])
            ->values();

        return response()->json(['ok' => true, 'data' => $rows]);
    }
}
