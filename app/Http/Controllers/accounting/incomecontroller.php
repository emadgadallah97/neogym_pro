<?php

namespace App\Http\Controllers\accounting;

use App\Models\employee\employee as Employee;
use App\Models\general\Branch;
use App\Models\accounting\Income;
use App\Models\accounting\IncomeType;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;

class incomecontroller extends Controller
{
    public function index()
    {
        $BranchesList = Branch::orderByDesc('id')->get();

        $IncomeTypesList = IncomeType::where('status', 1)
            ->orderByDesc('id')
            ->get();

        $Incomes = Income::with(['type', 'branch', 'receivedByEmployee'])
            ->orderByDesc('id')
            ->get();

        return view('accounting.programs.income.index', compact('BranchesList', 'IncomeTypesList', 'Incomes'));
    }

    public function create()
    {
        return redirect()->route('income.index');
    }

    private function employeeBelongsToBranch(int $employeeId, int $branchId): bool
    {
        if ($employeeId <= 0 || $branchId <= 0) return false;

        return Employee::query()
            ->where('id', $employeeId)
            ->where('status', 1)
            ->whereHas('branches', function ($q) use ($branchId) {
                $q->where('branches.id', $branchId);
            })
            ->exists();
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'branchid' => 'required|integer|exists:branches,id',
            'income_type_id' => 'required|integer|exists:income_types,id',
            'incomedate' => 'required|date',
            'amount' => 'required|numeric|min:0.01',
            'paymentmethod' => 'required|string|in:cash,card,transfer,instapay,ewallet,cheque,other',
            'receivedbyemployeeid' => 'nullable|integer|exists:employees,id',
            'payername' => 'nullable|string|max:150',
            'payerphone' => 'nullable|string|max:50',
            'description' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        $branchId = (int)$data['branchid'];
        $receivedEmpId = !empty($data['receivedbyemployeeid']) ? (int)$data['receivedbyemployeeid'] : 0;

        // تحسين: تأكد الموظف المستلم تابع للفرع
        if ($receivedEmpId > 0 && !$this->employeeBelongsToBranch($receivedEmpId, $branchId)) {
            return redirect()->back()
                ->withInput()
                ->with('error', trans('accounting.employee_not_in_branch'));
        }

        $row = new Income();
        $row->branchid = $branchId;
        $row->income_type_id = (int)$data['income_type_id'];
        $row->incomedate = $data['incomedate'];
        $row->amount = $data['amount'];
        $row->paymentmethod = $data['paymentmethod'];
        $row->receivedbyemployeeid = $receivedEmpId > 0 ? $receivedEmpId : null;

        $row->payername = $data['payername'] ?? null;
        $row->payerphone = $data['payerphone'] ?? null;
        $row->description = $data['description'] ?? null;
        $row->notes = $data['notes'] ?? null;

        $row->iscancelled = false;
        $row->useradd = Auth::id();
        $row->save();

        return redirect()->route('income.index')->with('success', trans('accounting.saved_successfully'));
    }

    public function show($id)
    {
        return redirect()->route('income.index');
    }

    public function edit($id)
    {
        return redirect()->route('income.index');
    }

    public function update(Request $request, $id)
    {
        $row = Income::findOrFail($id);

        $data = $request->validate([
            'branchid' => 'required|integer|exists:branches,id',
            'income_type_id' => 'required|integer|exists:income_types,id',
            'incomedate' => 'required|date',
            'amount' => 'required|numeric|min:0.01',
            'paymentmethod' => 'required|string|in:cash,card,transfer,instapay,ewallet,cheque,other',
            'receivedbyemployeeid' => 'nullable|integer|exists:employees,id',
            'payername' => 'nullable|string|max:150',
            'payerphone' => 'nullable|string|max:50',
            'description' => 'nullable|string',
            'notes' => 'nullable|string',

            'iscancelled' => 'nullable|boolean',
            'cancelreason' => 'nullable|string',
        ]);

        $branchId = (int)$data['branchid'];
        $receivedEmpId = !empty($data['receivedbyemployeeid']) ? (int)$data['receivedbyemployeeid'] : 0;

        // تحسين: تأكد الموظف المستلم تابع للفرع
        if ($receivedEmpId > 0 && !$this->employeeBelongsToBranch($receivedEmpId, $branchId)) {
            return redirect()->back()
                ->withInput()
                ->with('error', trans('accounting.employee_not_in_branch'));
        }

        $row->branchid = $branchId;
        $row->income_type_id = (int)$data['income_type_id'];
        $row->incomedate = $data['incomedate'];
        $row->amount = $data['amount'];
        $row->paymentmethod = $data['paymentmethod'];
        $row->receivedbyemployeeid = $receivedEmpId > 0 ? $receivedEmpId : null;

        $row->payername = $data['payername'] ?? null;
        $row->payerphone = $data['payerphone'] ?? null;
        $row->description = $data['description'] ?? null;
        $row->notes = $data['notes'] ?? null;

        $isCancelled = (bool)($data['iscancelled'] ?? 0);

        if ($isCancelled) {
            if (empty($data['cancelreason'])) {
                return redirect()->back()->withInput()->with('error', trans('accounting.cancel_reason_required'));
            }

            $row->iscancelled = true;
            $row->cancelreason = $data['cancelreason'];

            if (empty($row->cancelledat)) {
                $row->cancelledat = Carbon::now();
            }
            if (empty($row->usercancel)) {
                $row->usercancel = Auth::id();
            }
        } else {
            $row->iscancelled = false;
            $row->usercancel = null;
            $row->cancelledat = null;
            $row->cancelreason = null;
        }

        $row->userupdate = Auth::id();
        $row->save();

        return redirect()->route('income.index')->with('success', trans('accounting.updated_successfully'));
    }

    public function destroy($id)
    {
        $row = Income::findOrFail($id);
        $row->userupdate = Auth::id();
        $row->save();
        $row->delete();

        return redirect()->route('income.index')->with('success', trans('accounting.deleted_successfully'));
    }

    // AJAX: employees by branch (Pivot employee_branch)
    public function employees_by_branch(Request $request)
    {
        try {
            $data = $request->validate([
                'branchid' => 'required|integer|exists:branches,id',
            ]);

            $branchId = (int)$data['branchid'];

            // تحسينات:
            // - select أعمدة قليلة
            // - orderBy بالاسم
            // - limit لحماية الأداء
            $rows = Employee::query()
                ->select(['id', 'code', 'first_name', 'last_name', 'status'])
                ->where('status', 1)
                ->whereHas('branches', function ($q) use ($branchId) {
                    $q->where('branches.id', $branchId);
                })
                ->orderBy('first_name')
                ->orderBy('last_name')
                ->orderBy('id')
                ->limit(500)
                ->get();

            $out = $rows->map(function ($e) {
                $fullName = trim(($e->first_name ?? '') . ' ' . ($e->last_name ?? ''));
                $text = trim(($e->code ? $e->code . ' - ' : '') . ($fullName ?: ('#' . $e->id)));

                return [
                    'id' => (int)$e->id,
                    'text' => $text,
                ];
            })->values();

            return response()->json(['ok' => true, 'data' => $out]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'ok' => false,
                'message' => $e->getMessage(),
                'errors' => $e->errors(),
            ], 422);
        } catch (\Throwable $e) {
            return response()->json([
                'ok' => false,
                'message' => trans('accounting.ajax_error_try_again'),
            ], 500);
        }
    }
}
