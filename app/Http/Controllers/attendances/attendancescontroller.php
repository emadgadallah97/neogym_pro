<?php

namespace App\Http\Controllers\attendances;

use App\Http\Controllers\Controller;
use App\Http\Requests\attendances\AttendanceGuestRequest;
use App\Http\Requests\attendances\AttendanceManualRequest;
use App\Http\Requests\attendances\AttendanceScanRequest;
use App\Models\attendances\attendance as Attendance;
use App\Models\general\Branch;
use App\Services\attendances\AttendanceService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class attendancescontroller extends Controller
{
    protected AttendanceService $service;

    public function __construct(AttendanceService $service)
    {
        $this->service = $service;
        $this->middleware('permission:attendance');
        $this->middleware('permission:attendance_cancel', ['only' => ['cancel']]);
        $this->middleware('permission:attendance_pt_cancel', ['only' => ['cancelPt']]);
        $this->middleware('permission:attendance_guest_add', ['only' => ['storeGuest']]);

    }
    // ─────────────────────────────────────────────────────────────
    // Helpers
    // ─────────────────────────────────────────────────────────────

    /**
     * الفرع الأساسي للموظف المرتبط بالمستخدم.
     * fallback: branch_id من جدول users (للتوافق مع القديم)
     */
    private function getPrimaryBranchId(): int
    {
        $user = Auth::user();

        if ($user->employee_id) {
            $primaryId = DB::table('employee_branch')
                ->where('employee_id', $user->employee_id)
                ->where('is_primary', 1)
                ->value('branch_id');

            if ($primaryId) {
                return (int)$primaryId;
            }
        }

        // fallback للتوافق مع المستخدمين غير المربوطين بموظف
        return (int)($user->branch_id ?? 0);
    }

    /**
     * جميع الفروع المرتبطة بالموظف (primary + أخرى) — للفلترة فقط.
     * لو المستخدم غير مربوط بموظف يرجع array فارغ (يعني لا قيد على الفروع).
     */
    private function getAccessibleBranchIds(): array
    {
        $user = Auth::user();

        if (!$user->employee_id) {
            return []; // لا قيد — يرى كل الفروع
        }

        $ids = DB::table('employee_branch')
            ->where('employee_id', $user->employee_id)
            ->pluck('branch_id')
            ->toArray();

        return array_map('intval', $ids);
    }

    /**
     * اسم الفرع من الـ Branch model (يراعي JSON name).
     */
    private function getBranchName(int $branchId): ?string
    {
        if ($branchId <= 0) return null;

        // withoutGlobalScope لأننا قد نكون خارج نطاق الفروع المتاحة
        $branch = Branch::withoutGlobalScope(\App\Models\Scopes\BranchAccessScope::class)
            ->find($branchId);

        if (!$branch) return null;

        $locale = app()->getLocale();
        $name = $branch->name;

        if (is_array($name)) {
            return $name[$locale] ?? ($name['ar'] ?? ($name['en'] ?? null));
        }

        if (is_string($name)) {
            $decoded = json_decode($name, true);
            if (is_array($decoded)) {
                return $decoded[$locale] ?? ($decoded['ar'] ?? ($decoded['en'] ?? $name));
            }
            return $name;
        }

        return null;
    }

    // ─────────────────────────────────────────────────────────────
    // Index
    // ─────────────────────────────────────────────────────────────

    public function index(Request $request)
    {
        // ✅ الفرع الأساسي للموظف هو فرع تسجيل الحضور
        $branchId = $this->getPrimaryBranchId();

        $dateFrom     = $request->get('date_from', Carbon::now()->format('Y-m-d'));
        $dateTo       = $request->get('date_to',   Carbon::now()->format('Y-m-d'));
        $memberSearch = trim((string)$request->get('member', ''));

        try {
            $from = Carbon::parse($dateFrom)->toDateString();
            $to   = Carbon::parse($dateTo)->toDateString();
            if ($from > $to) [$from, $to] = [$to, $from];
            $dateFrom = $from;
            $dateTo   = $to;
        } catch (\Throwable) {
            $dateFrom = Carbon::now()->format('Y-m-d');
            $dateTo   = Carbon::now()->format('Y-m-d');
        }

        $branchName = $this->getBranchName($branchId);

        return view('attendances.index', compact(
            'dateFrom',
            'dateTo',
            'branchId',
            'branchName',
            'memberSearch'
        ));
    }

    // ─────────────────────────────────────────────────────────────
    // DataTables server-side
    // ─────────────────────────────────────────────────────────────

    public function datatable(Request $request)
    {
        // ✅ الفرع الأساسي للعرض
        $branchId = $this->getPrimaryBranchId();

        // ✅ كل الفروع المتاحة للتحقق الإضافي (غير مستخدمة هنا لكن متاحة)
        $accessibleBranchIds = $this->getAccessibleBranchIds();

        $dateFrom     = $request->get('date_from', Carbon::now()->format('Y-m-d'));
        $dateTo       = $request->get('date_to',   Carbon::now()->format('Y-m-d'));
        $memberSearch = trim((string)$request->get('member', ''));

        try {
            $from = Carbon::parse($dateFrom)->toDateString();
            $to   = Carbon::parse($dateTo)->toDateString();
            if ($from > $to) [$from, $to] = [$to, $from];
            $dateFrom = $from;
            $dateTo   = $to;
        } catch (\Throwable) {
            $dateFrom = Carbon::now()->format('Y-m-d');
            $dateTo   = Carbon::now()->format('Y-m-d');
        }

        $draw   = (int)$request->input('draw', 1);
        $start  = (int)$request->input('start', 0);
        $length = (int)$request->input('length', 25);
        if ($length <= 0) $length = 25;

        $dtSearch = trim((string)$request->input('search.value', ''));

        $baseQuery = Attendance::query()
            ->with(['member', 'branch', 'subscription', 'ptAddon', 'guests'])
            ->whereBetween('attendance_date', [$dateFrom, $dateTo]);

        // ✅ فلترة الفروع:
        //    - لو المستخدم مربوط بموظف => فلتر على فرعه الأساسي فقط
        //    - لو غير مربوط (admin)    => يرى كل الفروع
        if ($branchId > 0) {
            $baseQuery->where('branch_id', $branchId);
        } elseif (!empty($accessibleBranchIds)) {
            $baseQuery->whereIn('branch_id', $accessibleBranchIds);
        }

        $recordsTotal = (clone $baseQuery)->count();

        $filtered = (clone $baseQuery);

        if ($memberSearch !== '') {
            $filtered->whereHas('member', function ($mq) use ($memberSearch) {
                $mq->where(function ($w) use ($memberSearch) {
                    $w->where('member_code', 'like', "%{$memberSearch}%")
                      ->orWhere('first_name',  'like', "%{$memberSearch}%")
                      ->orWhere('last_name',   'like', "%{$memberSearch}%")
                      ->orWhere('phone',       'like', "%{$memberSearch}%")
                      ->orWhere('phone2',      'like', "%{$memberSearch}%")
                      ->orWhere('whatsapp',    'like', "%{$memberSearch}%")
                      ->orWhere('email',       'like', "%{$memberSearch}%");
                });
            });
        }

        if ($dtSearch !== '') {
            $filtered->where(function ($q) use ($dtSearch) {
                $q->where('id', $dtSearch)
                  ->orWhere('checkin_method', 'like', "%{$dtSearch}%")
                  ->orWhereHas('member', function ($mq) use ($dtSearch) {
                      $mq->where(function ($w) use ($dtSearch) {
                          $w->where('member_code', 'like', "%{$dtSearch}%")
                            ->orWhere('first_name', 'like', "%{$dtSearch}%")
                            ->orWhere('last_name',  'like', "%{$dtSearch}%")
                            ->orWhere('phone',      'like', "%{$dtSearch}%")
                            ->orWhere('whatsapp',   'like', "%{$dtSearch}%");
                      });
                  });
            });
        }

        $recordsFiltered = (clone $filtered)->count();

        $orderColumnIdx = (int)$request->input('order.0.column', 0);
        $orderDir = strtolower((string)$request->input('order.0.dir', 'desc')) === 'asc' ? 'asc' : 'desc';

        $orderMap = [
            0 => 'id',
            1 => 'attendance_date',
            2 => 'attendance_time',
            4 => 'checkin_method',
            7 => 'is_cancelled',
        ];

        $orderCol = $orderMap[$orderColumnIdx] ?? 'id';

        $rows = $filtered
            ->orderBy($orderCol, $orderDir)
            ->skip($start)
            ->take($length)
            ->get();

        $data = [];

        foreach ($rows as $row) {
            $memberCode = $row->member?->member_code ?? (string)$row->member_id;
            $memberName = $row->member?->full_name ?? '';

            $memberHtml = '<div>' . e($memberCode) . '</div>'
                . '<small class="text-muted">' . e($memberName) . '</small>';

            $sub = $row->subscription;
            $pt  = $row->ptAddon;

            $baseTotal        = $sub->sessions_count     ?? null;
            $baseRemainingNow = $sub->sessions_remaining ?? null;

            $baseHtml = '<div>' . e((string)$row->base_sessions_before) . ' → ' . e((string)$row->base_sessions_after) . '</div>'
                . '<small class="text-muted">'
                . e(trans('attendances.total') ?: 'Total') . ': ' . e((string)($baseTotal ?? '-'))
                . ' | ' . e(trans('attendances.remaining') ?: 'Remaining') . ': ' . e((string)($baseRemainingNow ?? '-'))
                . '</small>';

            if ($row->pt_sessions_before !== null) {
                $ptTotal        = $pt->sessions_count     ?? null;
                $ptRemainingNow = $pt->sessions_remaining ?? null;

                $ptHtml = '<div>' . e((string)$row->pt_sessions_before) . ' → ' . e((string)$row->pt_sessions_after) . '</div>'
                    . '<small class="text-muted">'
                    . e(trans('attendances.total') ?: 'Total') . ': ' . e((string)($ptTotal ?? '-'))
                    . ' | ' . e(trans('attendances.remaining') ?: 'Remaining') . ': ' . e((string)($ptRemainingNow ?? '-'))
                    . '</small>';
            } else {
                $ptHtml = '-';
            }

            if ($row->is_cancelled) {
                $statusHtml = '<span class="badge bg-danger">' . e(trans('attendances.cancelled')) . '</span>';
            } else {
                $statusHtml = '<span class="badge bg-success">' . e(trans('attendances.active')) . '</span>';

                if ((int)$row->is_pt_deducted === 1) {
                    $statusHtml .= ' <span class="badge bg-info" style="margin-inline-start:4px;">'
                        . e(trans('attendances.pt_deducted') ?: 'PT deducted')
                        . '</span>';
                }
            }

            $guestsHtml = '-';
            if ($row->guests && $row->guests->isNotEmpty()) {
                $lines = '';
                foreach ($row->guests as $g) {
                    $lines .= '<div>'
                        . e($g->guest_name ?? '-')
                        . ($g->guest_phone
                            ? ' <small class="text-muted">(' . e($g->guest_phone) . ')</small>'
                            : '')
                        . '</div>';
                }
                $guestsHtml = $lines;
            }

            $actionsHtml = '-';
            if (!$row->is_cancelled) {
                $actions = '';

                // Cancel PT
                if ((int)$row->is_pt_deducted === 1 && !empty($row->pt_addon_id)) {
                    if (Auth::user()->can('attendance_pt_cancel')) {
                        $actions .= '<form method="POST" action="' . e(route('attendances.actions.cancel_pt', $row->id)) . '" style="display:inline;" onsubmit="return confirm(\'' . e(trans('attendances.confirm_cancel_pt') ?: 'Cancel PT deduction?') . '\');">'
                            . csrf_field()
                            . '<button type="submit" class="btn btn-sm btn-warning">' . e(trans('attendances.cancel_pt')) . '</button>'
                            . '</form> ';
                    }
                }

                // Cancel Attendance
                if (Auth::user()->can('attendance_cancel')) {
                    $actions .= '<form method="POST" action="' . e(route('attendances.actions.cancel', $row->id)) . '" style="display:inline;" onsubmit="return confirm(\'' . e(trans('attendances.confirm_cancel_attendance') ?: 'Cancel attendance?') . '\');">'
                        . csrf_field()
                        . '<button type="submit" class="btn btn-sm btn-danger">' . e(trans('attendances.cancel_attendance')) . '</button>'
                        . '</form> ';
                }

                // Add Guest
                if (Auth::user()->can('attendance_guest_add')) {
                    $actions .= '<button type="button" class="btn btn-sm btn-primary" onclick="var el=document.getElementById(\'guest_form_' . e((string)$row->id) . '\'); if(el){el.style.display=\'block\';}">'
                        . e(trans('attendances.add_guest'))
                        . '</button>';

                    $actions .= '<div id="guest_form_' . e((string)$row->id) . '" style="display:none; margin-top:6px;">'
                        . '<form method="POST" action="' . e(route('attendances.actions.guests.store', $row->id)) . '">'
                        . csrf_field()
                        . '<input type="text" name="guest_name" class="form-control form-control-sm mb-1" placeholder="' . e(trans('attendances.guest_name')) . '">'
                        . '<input type="text" name="guest_phone" class="form-control form-control-sm mb-1" placeholder="' . e(trans('attendances.guest_phone')) . '">'
                        . '<input type="text" name="notes" class="form-control form-control-sm mb-1" placeholder="' . e(trans('attendances.notes') ?: 'Notes') . '">'
                        . '<button class="btn btn-sm btn-success" type="submit">' . e(trans('attendances.save_guest')) . '</button>'
                        . '</form>'
                        . '</div>';
                }

                $actionsHtml = !empty($actions) ? $actions : '-';
            }

            $data[] = [
                'id'              => $row->id,
                'attendance_date' => optional($row->attendance_date)->format('Y-m-d'),
                'attendance_time' => $row->attendance_time,
                'member'          => $memberHtml,
                'checkin_method'  => e((string)$row->checkin_method),
                'base'            => $baseHtml,
                'pt'              => $ptHtml,
                'status'          => $statusHtml,
                'guests'          => $guestsHtml,
                'actions'         => $actionsHtml,
            ];
        }

        return response()->json([
            'draw'            => $draw,
            'recordsTotal'    => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data'            => $data,
        ]);
    }

    // ─────────────────────────────────────────────────────────────
    // Kiosk
    // ─────────────────────────────────────────────────────────────

    public function kiosk()
    {
        return view('attendances.kiosk');
    }

    // ─────────────────────────────────────────────────────────────
    // Manual check-in
    // ─────────────────────────────────────────────────────────────

    public function store(AttendanceManualRequest $request)
    {
        // ✅ فرع الحضور = الفرع الأساسي للموظف
        $branchId = $this->getPrimaryBranchId();

        if ($branchId <= 0) {
            return redirect()->back()->withInput()
                ->with('error', trans('attendances.user_branch_missing'));
        }

        $memberCode = $request->input('member_code');
        $deductPt   = $request->boolean('deduct_pt');

        $res = $this->service->manualCheckIn($memberCode, $branchId, (int)Auth::id(), $deductPt);

        if (!$res['ok']) {
            return redirect()->back()->withInput()->with('error', $res['message']);
        }

        return redirect()->route('attendances.index')->with('success', $res['message']);
    }

    // ─────────────────────────────────────────────────────────────
    // Barcode / Scan
    // ─────────────────────────────────────────────────────────────

    public function scan(AttendanceScanRequest $request)
    {
        // ✅ فرع الحضور = الفرع الأساسي للموظف
        $branchId = $this->getPrimaryBranchId();

        if ($branchId <= 0) {
            return response()->json([
                'ok'      => false,
                'message' => trans('attendances.user_branch_missing'),
            ], 422);
        }

        $memberCode = $request->input('member_code');
        $deductPt   = $request->boolean('deduct_pt');

        $res = $this->service->scanCheckIn($memberCode, $branchId, (int)Auth::id(), $deductPt);

        return response()->json($res, $res['ok'] ? 200 : 422);
    }

    // ─────────────────────────────────────────────────────────────
    // Actions
    // ─────────────────────────────────────────────────────────────

    public function cancel($attendance)
    {
        $res = $this->service->cancelAttendance((int)$attendance, (int)Auth::id());

        if (request()->expectsJson()) {
            return response()->json($res, $res['ok'] ? 200 : 422);
        }

        return redirect()->back()->with($res['ok'] ? 'success' : 'error', $res['message']);
    }

    public function cancelPt($attendance)
    {
        $res = $this->service->cancelPtOnly((int)$attendance, (int)Auth::id());

        if (request()->expectsJson()) {
            return response()->json($res, $res['ok'] ? 200 : 422);
        }

        return redirect()->back()->with($res['ok'] ? 'success' : 'error', $res['message']);
    }

    public function storeGuest(AttendanceGuestRequest $request, $attendance)
    {
        $res = $this->service->addGuest(
            (int)$attendance,
            (int)Auth::id(),
            $request->input('guest_name'),
            $request->input('guest_phone'),
            $request->input('notes')
        );

        if (request()->expectsJson()) {
            return response()->json($res, $res['ok'] ? 200 : 422);
        }

        return redirect()->back()->with($res['ok'] ? 'success' : 'error', $res['message']);
    }

    // ─────────────────────────────────────────────────────────────
    // Unused resource methods
    // ─────────────────────────────────────────────────────────────

    public function create()  { return redirect()->route('attendances.index'); }
    public function show($id) { return redirect()->route('attendances.index'); }
    public function edit($id) { return redirect()->route('attendances.index'); }
    public function update(Request $request, $id) { return redirect()->route('attendances.index'); }
    public function destroy($id) { return redirect()->route('attendances.index'); }
}
