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
    public function index()
    {
        $Expenses = Expense::with(['type', 'branch', 'disburserEmployee', 'creator'])
            ->orderByDesc('id')
            ->get();

        // 1) استثناء غير النشط من الأنواع في الاختيار/الفلاتر
        $ExpensesTypes = ExpensesType::where('status', 1)->orderByDesc('id')->get();

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

    private function employeeBelongsToBranch(?int $employeeId, int $branchId): bool
    {
        if (!$employeeId) return true;

        return Employee::where('id', $employeeId)
            ->whereHas('branches', function ($q) use ($branchId) {
                $q->where('branches.id', $branchId);
            })
            ->exists();
    }

    public function store(Request $request)
    {
        $request->validate([
            'branchid'       => ['required', 'integer', 'exists:branches,id'],
            'expensestypeid' => ['required', 'integer', 'exists:expenses_types,id'],
            'expensedate'    => ['required', 'date'],
            'amount'         => ['required', 'numeric', 'min:0.01'],

            'recipientname'       => ['required', 'string', 'max:255'],
            'recipientphone'      => ['nullable', 'string', 'max:50'],
            'recipientnationalid' => ['nullable', 'string', 'max:100'],

            'disbursedbyemployeeid' => ['nullable', 'integer', 'exists:employees,id'],

            'description' => ['nullable', 'string'],
            'notes'       => ['nullable', 'string'],

            // iscancelled لا نفتحها في الإنشاء عادة
        ]);

        $branchId = (int) $request->branchid;
        $employeeId = $request->disbursedbyemployeeid ? (int) $request->disbursedbyemployeeid : null;

        // 2 + 5) القائم بالصرف من نفس الفرع
        if (!$this->employeeBelongsToBranch($employeeId, $branchId)) {
            return redirect()->back()->withInput()->with('error', trans('accounting.employee_not_in_branch'));
        }

        // 1) منع اختيار نوع غير نشط (تحقق إضافي)
        $typeIsActive = ExpensesType::where('id', (int)$request->expensestypeid)->where('status', 1)->exists();
        if (!$typeIsActive) {
            return redirect()->back()->withInput()->with('error', trans('accounting.expense_type_inactive'));
        }

        DB::beginTransaction();
        try {
            Expense::create([
                'branchid'       => $branchId,
                'expensestypeid' => (int) $request->expensestypeid,
                'expensedate'    => $request->expensedate,
                'amount'         => $request->amount,

                'recipientname'       => trim((string) $request->recipientname),
                'recipientphone'      => $request->recipientphone ? trim((string) $request->recipientphone) : null,
                'recipientnationalid' => $request->recipientnationalid ? trim((string) $request->recipientnationalid) : null,

                'disbursedbyemployeeid' => $employeeId,

                'description' => $request->description,
                'notes'       => $request->notes,

                // 3) بديل status
                'iscancelled' => false,
                'cancelledat' => null,
                'cancelledby' => null,

                'useradd'    => Auth::check() ? Auth::user()->id : null,
                'userupdate' => null,
            ]);

            DB::commit();
            return redirect()->back()->with('success', trans('accounting.saved_success'));
        } catch (\Throwable $e) {
            DB::rollBack();
            return redirect()->back()->withInput()->with('error', trans('accounting.saved_error'));
        }
    }

    public function show($id)
    {
        return redirect()->route('expenses.index');
    }

    public function edit($id)
    {
        return redirect()->route('expenses.index');
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'id'            => ['required', 'integer', 'exists:expenses,id'],
            'branchid'       => ['required', 'integer', 'exists:branches,id'],
            'expensestypeid' => ['required', 'integer', 'exists:expenses_types,id'],
            'expensedate'    => ['required', 'date'],
            'amount'         => ['required', 'numeric', 'min:0.01'],

            'recipientname'       => ['required', 'string', 'max:255'],
            'recipientphone'      => ['nullable', 'string', 'max:50'],
            'recipientnationalid' => ['nullable', 'string', 'max:100'],

            'disbursedbyemployeeid' => ['nullable', 'integer', 'exists:employees,id'],

            'description' => ['nullable', 'string'],
            'notes'       => ['nullable', 'string'],

            // 3) ملغي
            'iscancelled' => ['nullable', 'boolean'],
        ]);

        $Expense = Expense::findOrFail((int) $request->id);

        $branchId = (int) $request->branchid;
        $employeeId = $request->disbursedbyemployeeid ? (int) $request->disbursedbyemployeeid : null;

        if (!$this->employeeBelongsToBranch($employeeId, $branchId)) {
            return redirect()->back()->withInput()->with('error', trans('accounting.employee_not_in_branch'));
        }

        // منع اختيار نوع غير نشط
        $typeIsActive = ExpensesType::where('id', (int)$request->expensestypeid)->where('status', 1)->exists();
        if (!$typeIsActive) {
            return redirect()->back()->withInput()->with('error', trans('accounting.expense_type_inactive'));
        }

        $isCancelled = (bool) $request->iscancelled;

        DB::beginTransaction();
        try {
            $Expense->update([
                'branchid'       => $branchId,
                'expensestypeid' => (int) $request->expensestypeid,
                'expensedate'    => $request->expensedate,
                'amount'         => $request->amount,

                'recipientname'       => trim((string) $request->recipientname),
                'recipientphone'      => $request->recipientphone ? trim((string) $request->recipientphone) : null,
                'recipientnationalid' => $request->recipientnationalid ? trim((string) $request->recipientnationalid) : null,

                'disbursedbyemployeeid' => $employeeId,

                'description' => $request->description,
                'notes'       => $request->notes,

                'iscancelled' => $isCancelled,
                'cancelledat' => $isCancelled ? ($Expense->cancelledat ?? Carbon::now()) : null,
                'cancelledby' => $isCancelled ? (Auth::check() ? Auth::user()->id : null) : null,

                'userupdate' => Auth::check() ? Auth::user()->id : null,
            ]);

            DB::commit();
            return redirect()->back()->with('success', trans('accounting.updated_success'));
        } catch (\Throwable $e) {
            DB::rollBack();
            return redirect()->back()->withInput()->with('error', trans('accounting.updated_error'));
        }
    }

    public function destroy(Request $request, $id)
    {
        $request->validate([
            'id' => ['required', 'integer', 'exists:expenses,id'],
        ]);

        DB::beginTransaction();
        try {
            $Expense = Expense::findOrFail((int) $request->id);
            $Expense->delete();

            DB::commit();
            return redirect()->back()->with('success', trans('accounting.deleted_success'));
        } catch (\Throwable $e) {
            DB::rollBack();
            return redirect()->back()->with('error', trans('accounting.deleted_error'));
        }
    }

    // AJAX: employees by branch
    public function ajaxEmployeesByBranch(Request $request)
    {
        $request->validate([
            'branchid' => ['required', 'integer', 'exists:branches,id'],
        ]);

        $branchId = (int) $request->branchid;

        $rows = Employee::query()
            ->whereHas('branches', function ($q) use ($branchId) {
                $q->where('branches.id', $branchId);
            })
            ->orderByDesc('id')
            ->get()
            ->map(function ($e) {
                return [
                    'id' => (int) $e->id,
                    'text' => $e->full_name ?? trim(($e->first_name ?? '') . ' ' . ($e->last_name ?? '')),
                ];
            })
            ->values();

        return response()->json([
            'ok' => true,
            'data' => $rows,
        ]);
    }
}
