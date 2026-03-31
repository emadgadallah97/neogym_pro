<?php

namespace App\Http\Controllers\accounting;

use App\Http\Controllers\Controller;
use App\Models\accounting\TreasuryPeriod;
use App\Models\accounting\TreasuryTransaction;
use App\Models\general\Branch;
use App\Services\TreasuryPeriodService;
use App\Services\TreasuryService;
use App\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\TreasuryTransactionsExport;

class TreasuryController extends Controller
{
    protected TreasuryService       $treasuryService;
    protected TreasuryPeriodService $periodService;

    public function __construct(TreasuryService $treasuryService, TreasuryPeriodService $periodService)
    {
        $this->treasuryService = $treasuryService;
        $this->periodService   = $periodService;
    }

    // ─────────────────────────────────────────────────────────────
    // Helpers
    // ─────────────────────────────────────────────────────────────

    private function accessibleBranchIds(): ?array
    {
        $user = Auth::user();
        if (!$user->employee_id) return null; // Admin with global access

        return DB::table('employee_branch')
            ->where('employee_id', $user->employee_id)
            ->pluck('branch_id')
            ->map(fn($id) => (int)$id)
            ->toArray();
    }

    private function userCanAccessBranch(int $branchId): bool
    {
        $ids = $this->accessibleBranchIds();
        if ($ids === null) return true;
        return in_array($branchId, $ids);
    }

    private function resolveBranchId(Request $request): int
    {
        if ($request->filled('branch_id') && $this->userCanAccessBranch((int)$request->branch_id)) {
            return (int)$request->branch_id;
        }

        $branchIds = $this->accessibleBranchIds();

        if ($branchIds === null) {
            // Global access
            return Branch::where('status', 1)->value('id') ?? 0;
        }

        // Single-branch user → auto-resolve
        if (count($branchIds) === 1) {
            return $branchIds[0];
        }

        // Fallback to first accessible branch, or 0 if none
        return count($branchIds) > 0 ? $branchIds[0] : 0;
    }

    // ─────────────────────────────────────────────────────────────
    // Index
    // ─────────────────────────────────────────────────────────────

    public function index(Request $request)
    {
        $this->authorize('treasury.view');

        $selectedBranchId = $this->resolveBranchId($request);
        
        if ($selectedBranchId === 0) {
            abort(403, 'لا تملك صلاحيات على أي فرع لفتح الخزينة.');
        }

        // BranchAccessScope will automatically filter this based on accessible branches
        $branches = Branch::where('status', 1)->orderBy('name')->get();

        $selectedBranchId = $this->resolveBranchId($request);
        $balance          = $this->treasuryService->getBalance($selectedBranchId);
        $openPeriod       = $balance['period'];

        // Last closed period (for carried_forward hint in open modal)
        $lastClosedPeriod = TreasuryPeriod::where('branch_id', $selectedBranchId)
            ->where('status', 'closed')
            ->latest('id')
            ->first();

        // Users list for filter
        $users = User::select('id', 'name')->orderBy('name')->get();

        return view('accounting.programs.treasury.index', compact(
            'branches',
            'selectedBranchId',
            'balance',
            'openPeriod',
            'lastClosedPeriod',
            'users'
        ));
    }

    // ─────────────────────────────────────────────────────────────
    // Ajax: paginated + filtered transactions
    // ─────────────────────────────────────────────────────────────

    public function transactions(Request $request)
    {
        $this->authorize('treasury.view');

        $branchId = $request->filled('branch_id') ? (int)$request->branch_id : 0;

        if ($branchId && !$this->userCanAccessBranch($branchId)) {
            return response()->json(['ok' => false, 'message' => 'Unauthorized'], 403);
        }

        $query = TreasuryTransaction::with(['period', 'user'])
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId));

        // If period_id specified (for period detail view)
        if ($request->filled('period_id')) {
            $query->where('period_id', (int)$request->period_id);
        } elseif ($branchId) {
            // Default: current open period
            $period = TreasuryPeriod::where('branch_id', $branchId)
                ->where('status', 'open')
                ->latest('id')
                ->first();
            if ($period) {
                $query->where('period_id', $period->id);
            }
        }

        // Filters
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }
        if ($request->filled('source_type')) {
            $query->where('source_type', $request->source_type);
        }
        if ($request->filled('user_id')) {
            $query->where('user_id', (int)$request->user_id);
        }
        if ($request->filled('date_from')) {
            $query->where('transaction_date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->where('transaction_date', '<=', $request->date_to);
        }

        // Calculate filter totals
        $filterTotals = [
            'in'  => (clone $query)->where('type', 'in')->sum('amount'),
            'out' => (clone $query)->where('type', 'out')->sum('amount'),
        ];
        $filterTotals['net'] = $filterTotals['in'] - $filterTotals['out'];

        $transactions = $query->orderByDesc('id')->paginate(20);

        $rows = $transactions->getCollection()->map(function (TreasuryTransaction $tx) {
            return [
                'id'               => $tx->id,
                'transaction_date' => optional($tx->transaction_date)->format('Y-m-d'),
                'type'             => $tx->type,
                'type_label'       => $tx->type === 'in' ? trans('accounting.treasury_in') : trans('accounting.treasury_out'),
                'amount'           => number_format((float)$tx->amount, 2),
                'source_type'      => $tx->source_type,
                'source_type_label'=> trans('accounting.treasury_source_' . ($tx->source_type ?? 'manual')),
                'source_id'        => $tx->source_id,
                'description'      => $tx->description,
                'user_name'        => optional($tx->user)->name ?? '-',
                'is_reversal'      => $tx->is_reversal,
                'reversal_of'      => $tx->reversal_of,
                'period_name'      => optional($tx->period)->name,
            ];
        });

        return response()->json([
            'ok'          => true,
            'data'        => $rows,
            'total'       => $transactions->total(),
            'totals'      => $filterTotals,
            'current_page'=> $transactions->currentPage(),
            'last_page'   => $transactions->lastPage(),
        ]);
    }

    // ─────────────────────────────────────────────────────────────
    // Open Period
    // ─────────────────────────────────────────────────────────────

    public function openPeriod(Request $request)
    {
        $this->authorize('treasury.open');

        $data = $request->validate([
            'branch_id'       => 'required|integer|exists:branches,id',
            'name'            => 'required|string|max:150',
            'start_date'      => 'required|date',
            'opening_balance' => 'nullable|numeric|min:0',
        ]);

        if (!$this->userCanAccessBranch((int)$data['branch_id'])) {
            return back()->with('error', trans('accounting.branch_not_allowed'));
        }

        try {
            $this->periodService->open(
                (int)$data['branch_id'],
                $data['name'],
                $data['start_date'],
                isset($data['opening_balance']) ? (float)$data['opening_balance'] : null
            );

            return back()->with('success', trans('accounting.treasury_period_opened'));
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        } catch (\Throwable $e) {
            return back()->with('error', trans('accounting.saved_error'))->withInput();
        }
    }

    // ─────────────────────────────────────────────────────────────
    // Close Period
    // ─────────────────────────────────────────────────────────────

    public function closePeriod(Request $request)
    {
        $this->authorize('treasury.close');

        $data = $request->validate([
            'period_id'   => 'required|integer|exists:treasury_periods,id',
            'end_date'    => 'required|date',
            'handed_over' => 'required|numeric|min:0',
            'close_notes' => 'nullable|string|max:1000',
        ]);

        try {
            $this->periodService->close(
                (int)$data['period_id'],
                $data['end_date'],
                (float)$data['handed_over'],
                $data['close_notes'] ?? null
            );

            return back()->with('success', trans('accounting.treasury_period_closed'));
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        } catch (\Throwable $e) {
            return back()->with('error', trans('accounting.saved_error'))->withInput();
        }
    }

    // ─────────────────────────────────────────────────────────────
    // Manual Transaction
    // ─────────────────────────────────────────────────────────────

    public function manualTransaction(Request $request)
    {
        $this->authorize('treasury.manual');

        $data = $request->validate([
            'branch_id'        => 'required|integer|exists:branches,id',
            'type'             => 'required|in:in,out',
            'amount'           => 'required|numeric|min:0.01',
            'category'         => 'nullable|string|max:100',
            'description'      => 'nullable|string|max:500',
            'transaction_date' => 'required|date',
        ]);

        if (!$this->userCanAccessBranch((int)$data['branch_id'])) {
            return back()->with('error', trans('accounting.branch_not_allowed'));
        }

        $tx = $this->treasuryService->record(
            type:            $data['type'],
            amount:          (float)$data['amount'],
            branchId:        (int)$data['branch_id'],
            sourceType:      'manual',
            sourceId:        null,
            description:     $data['description'] ?? null,
            transactionDate: $data['transaction_date'],
            category:        $data['category'] ?? null
        );

        if (!$tx) {
            return back()->with('error', trans('accounting.treasury_no_open_period'));
        }

        return back()->with('success', trans('accounting.treasury_manual_recorded'));
    }

    // ─────────────────────────────────────────────────────────────
    // Periods List (review)
    // ─────────────────────────────────────────────────────────────

    public function periods(Request $request)
    {
        $this->authorize('treasury.review');

        $selectedBranchId = $this->resolveBranchId($request);
        
        if ($selectedBranchId === 0) {
            abort(403, 'لا تملك صلاحيات على أي فرع.');
        }

        // BranchAccessScope will automatically filter this based on accessible branches
        $branches = Branch::where('status', 1)->orderBy('name')->get();

        $selectedBranchId = $this->resolveBranchId($request);

        $periodsQuery = TreasuryPeriod::with(['openedBy', 'closedBy', 'branch'])
            ->when($selectedBranchId, fn($q) => $q->where('branch_id', $selectedBranchId))
            ->when($request->filled('status'), fn($q) => $q->where('status', $request->status))
            ->when($request->filled('date_from'), fn($q) => $q->where('start_date', '>=', $request->date_from))
            ->when($request->filled('date_to'), fn($q) => $q->where('start_date', '<=', $request->date_to));

        // Calculate totals per period
        $periods = $periodsQuery->orderByDesc('id')->paginate(25)->through(function (TreasuryPeriod $p) {
            $p->total_in  = (float) $p->transactions()->where('type', 'in')->sum('amount');
            $p->total_out = (float) $p->transactions()->where('type', 'out')->sum('amount');
            return $p;
        });

        return view('accounting.programs.treasury.periods', compact(
            'branches',
            'selectedBranchId',
            'periods'
        ));
    }

    // ─────────────────────────────────────────────────────────────
    // Period Detail — read-only transactions
    // ─────────────────────────────────────────────────────────────

    public function periodTransactions(TreasuryPeriod $period, Request $request)
    {
        $this->authorize('treasury.review');

        if (!$this->userCanAccessBranch((int)$period->branch_id)) {
            abort(403);
        }

        // If Ajax request
        if ($request->ajax() || $request->filled('ajax')) {
            $request->merge(['period_id' => $period->id, 'branch_id' => $period->branch_id]);
            return $this->transactions($request);
        }

        // BranchAccessScope will automatically filter this based on accessible branches
        $branches = Branch::where('status', 1)->orderBy('name')->get();

        $balance = [
            'opening'   => (float) $period->opening_balance,
            'total_in'  => (float) $period->transactions()->where('type', 'in')->sum('amount'),
            'total_out' => (float) $period->transactions()->where('type', 'out')->sum('amount'),
            'balance'   => 0,
            'period'    => $period,
        ];
        $balance['balance'] = $balance['opening'] + $balance['total_in'] - $balance['total_out'];

        $users = User::select('id', 'name')->orderBy('name')->get();

        return view('accounting.programs.treasury.index', [
            'branches'         => $branches,
            'selectedBranchId' => $period->branch_id,
            'balance'          => $balance,
            'openPeriod'       => $period->isOpen() ? $period : null,
            'viewPeriod'       => $period,
            'lastClosedPeriod' => null,
            'users'            => $users,
            'readOnly'         => !$period->isOpen(),
        ]);
    }

    // ─────────────────────────────────────────────────────────────
    // Excel Export
    // ─────────────────────────────────────────────────────────────

    public function export(Request $request)
    {
        $this->authorize('treasury.view');

        $branchId = $request->filled('branch_id') ? (int)$request->branch_id : 0;
        $periodId = $request->filled('period_id') ? (int)$request->period_id : null;

        return Excel::download(
            new TreasuryTransactionsExport($branchId, $periodId, $request->all()),
            'treasury_transactions_' . now()->format('Y_m_d_His') . '.xlsx'
        );
    }
}
