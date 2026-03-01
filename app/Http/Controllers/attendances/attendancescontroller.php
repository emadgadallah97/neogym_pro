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

class attendancescontroller extends Controller
{
    protected AttendanceService $service;

    public function __construct(AttendanceService $service)
    {
        $this->service = $service;
    }

    public function index(Request $request)
    {
        $user = Auth::user();
        $branchId = (int)($user->branch_id ?? 0);

        $dateFrom = $request->get('date_from', Carbon::now()->format('Y-m-d'));
        $dateTo   = $request->get('date_to', Carbon::now()->format('Y-m-d'));
        $memberSearch = trim((string)$request->get('member', ''));

        try {
            $from = Carbon::parse($dateFrom)->toDateString();
            $to   = Carbon::parse($dateTo)->toDateString();
            if ($from > $to) {
                [$from, $to] = [$to, $from];
            }
            $dateFrom = $from;
            $dateTo = $to;
        } catch (\Throwable $e) {
            $dateFrom = Carbon::now()->format('Y-m-d');
            $dateTo   = Carbon::now()->format('Y-m-d');
        }

        $branchName = null;
        if ($branchId > 0) {
            $branch = Branch::query()->find($branchId);
            $branchName = $branch->name ?? $branch->branch_name ?? null;
        }

        return view('attendances.index', compact(
            'dateFrom',
            'dateTo',
            'branchId',
            'branchName',
            'memberSearch'
        ));
    }

    // DataTables server-side
    public function datatable(Request $request)
    {
        $user = Auth::user();
        $branchId = (int)($user->branch_id ?? 0);

        $dateFrom = $request->get('date_from', Carbon::now()->format('Y-m-d'));
        $dateTo   = $request->get('date_to', Carbon::now()->format('Y-m-d'));
        $memberSearch = trim((string)$request->get('member', ''));

        try {
            $from = Carbon::parse($dateFrom)->toDateString();
            $to   = Carbon::parse($dateTo)->toDateString();
            if ($from > $to) {
                [$from, $to] = [$to, $from];
            }
            $dateFrom = $from;
            $dateTo = $to;
        } catch (\Throwable $e) {
            $dateFrom = Carbon::now()->format('Y-m-d');
            $dateTo   = Carbon::now()->format('Y-m-d');
        }

        $draw   = (int)$request->input('draw', 1);
        $start  = (int)$request->input('start', 0);
        $length = (int)$request->input('length', 25);
        if ($length <= 0) {
            $length = 25;
        }

        $dtSearch = trim((string)$request->input('search.value', ''));

        // ① أضفنا guests في with()
        $baseQuery = Attendance::query()
            ->with(['member', 'branch', 'subscription', 'ptAddon', 'guests'])
            ->when($branchId > 0, function ($q) use ($branchId) {
                $q->where('branch_id', $branchId);
            })
            ->whereBetween('attendance_date', [$dateFrom, $dateTo]);

        $recordsTotal = (clone $baseQuery)->count();

        $filtered = (clone $baseQuery);

        if ($memberSearch !== '') {
            $filtered->whereHas('member', function ($mq) use ($memberSearch) {
                $mq->where(function ($w) use ($memberSearch) {
                    $w->where('member_code', 'like', "%{$memberSearch}%")
                        ->orWhere('first_name', 'like', "%{$memberSearch}%")
                        ->orWhere('last_name', 'like', "%{$memberSearch}%")
                        ->orWhere('phone', 'like', "%{$memberSearch}%")
                        ->orWhere('phone2', 'like', "%{$memberSearch}%")
                        ->orWhere('whatsapp', 'like', "%{$memberSearch}%")
                        ->orWhere('email', 'like', "%{$memberSearch}%");
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
                            ->orWhere('last_name', 'like', "%{$dtSearch}%")
                            ->orWhere('phone', 'like', "%{$dtSearch}%")
                            ->orWhere('whatsapp', 'like', "%{$dtSearch}%");
                      });
                  });
            });
        }

        $recordsFiltered = (clone $filtered)->count();

        $orderColumnIdx = (int)$request->input('order.0.column', 0);
        $orderDir = strtolower((string)$request->input('order.0.dir', 'desc')) === 'asc' ? 'asc' : 'desc';

        // ② تحديث orderMap بعد إضافة عمود guests (index 8)، actions أصبح index 9
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

            $baseTotal        = $sub->sessions_count ?? null;
            $baseRemainingNow = $sub->sessions_remaining ?? null;

            $baseHtml = '<div>' . e((string)$row->base_sessions_before) . ' → ' . e((string)$row->base_sessions_after) . '</div>'
                . '<small class="text-muted">'
                . e(trans('attendances.total') ?: 'Total') . ': ' . e((string)($baseTotal ?? '-'))
                . ' | ' . e(trans('attendances.remaining') ?: 'Remaining') . ': ' . e((string)($baseRemainingNow ?? '-'))
                . '</small>';

            if ($row->pt_sessions_before !== null) {
                $ptTotal        = $pt->sessions_count ?? null;
                $ptRemainingNow = $pt->sessions_remaining ?? null;

                $ptHtml = '<div>' . e((string)$row->pt_sessions_before) . ' → ' . e((string)$row->pt_sessions_after) . '</div>'
                    . '<small class="text-muted">'
                    . e(trans('attendances.total') ?: 'Total') . ': ' . e((string)($ptTotal ?? '-'))
                    . ' | ' . e(trans('attendances.remaining') ?: 'Remaining') . ': ' . e((string)($ptRemainingNow ?? '-'))
                    . '</small>';
            } else {
                $ptHtml = '-';
            }

            // ③ status + badge PT deducted
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

            // ④ guests column
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

            // Actions
            $actionsHtml = '-';
            if (!$row->is_cancelled) {
                $actions = '';

                if ((int)$row->is_pt_deducted === 1 && !empty($row->pt_addon_id)) {
                    $actions .= '<form method="POST" action="' . e(route('attendances.actions.cancel_pt', $row->id)) . '" style="display:inline;" onsubmit="return confirm(\'' . e(trans('attendances.confirm_cancel_pt') ?: 'Cancel PT deduction?') . '\');">'
                        . csrf_field()
                        . '<button type="submit" class="btn btn-sm btn-warning">' . e(trans('attendances.cancel_pt')) . '</button>'
                        . '</form> ';
                }

                $actions .= '<form method="POST" action="' . e(route('attendances.actions.cancel', $row->id)) . '" style="display:inline;" onsubmit="return confirm(\'' . e(trans('attendances.confirm_cancel_attendance') ?: 'Cancel attendance?') . '\');">'
                    . csrf_field()
                    . '<button type="submit" class="btn btn-sm btn-danger">' . e(trans('attendances.cancel_attendance')) . '</button>'
                    . '</form> ';

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

                $actionsHtml = $actions;
            }

            // ⑤ أضفنا guests في $data[]
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

    public function kiosk()
    {
        return view('attendances.kiosk');
    }

    // Manual check-in (from admin screen)
    public function store(AttendanceManualRequest $request)
    {
        $user = Auth::user();
        $branchId = (int)($user->branch_id ?? 0);

        if ($branchId <= 0) {
            return redirect()->back()->withInput()->with('error', trans('attendances.user_branch_missing'));
        }

        $memberCode = $request->input('member_code');

        // Checkbox unchecked should be false (view sends hidden 0)
        $deductPt = $request->boolean('deduct_pt');

        $res = $this->service->manualCheckIn($memberCode, $branchId, (int)Auth::id(), $deductPt);
        if (!$res['ok']) {
            return redirect()->back()->withInput()->with('error', $res['message']);
        }

        return redirect()->route('attendances.index')->with('success', $res['message']);
    }

    // Barcode / global scan (JSON fast)
    public function scan(AttendanceScanRequest $request)
    {
        $user = Auth::user();
        $branchId = (int)($user->branch_id ?? 0);

        if ($branchId <= 0) {
            return response()->json([
                'ok' => false,
                'message' => trans('attendances.user_branch_missing'),
            ], 422);
        }

        $memberCode = $request->input('member_code');
        $deductPt = $request->boolean('deduct_pt');

        $res = $this->service->scanCheckIn($memberCode, $branchId, (int)Auth::id(), $deductPt);

        return response()->json($res, $res['ok'] ? 200 : 422);
    }

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

    // Not used now (resource requires)
    public function create() { return redirect()->route('attendances.index'); }
    public function show($id) { return redirect()->route('attendances.index'); }
    public function edit($id) { return redirect()->route('attendances.index'); }
    public function update(Request $request, $id) { return redirect()->route('attendances.index'); }
    public function destroy($id) { return redirect()->route('attendances.index'); }
}
