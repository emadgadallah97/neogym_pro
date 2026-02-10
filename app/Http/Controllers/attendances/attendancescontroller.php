<?php

namespace App\Http\Controllers\attendances;

use App\Http\Controllers\Controller;
use App\Http\Requests\attendances\AttendanceGuestRequest;
use App\Http\Requests\attendances\AttendanceManualRequest;
use App\Http\Requests\attendances\AttendanceScanRequest;
use App\Models\attendances\attendance;
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

        $rows = attendance::query()
            ->with(['member', 'branch'])
            ->when($branchId > 0, function ($q) use ($branchId) {
                $q->where('branch_id', $branchId);
            })
            ->whereBetween('attendance_date', [$dateFrom, $dateTo])
            ->orderByDesc('id')
            ->limit(500)
            ->get();

        return view('attendances.index', compact('rows', 'dateFrom', 'dateTo', 'branchId'));
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
        $deductPt = $request->boolean('deduct_pt', true);

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
        $deductPt = $request->boolean('deduct_pt', true);

        $res = $this->service->scanCheckIn($memberCode, $branchId, (int)Auth::id(), $deductPt);

        return response()->json($res, $res['ok'] ? 200 : 422);
    }

    public function cancel($attendance)
    {
        $res = $this->service->cancelAttendance((int)$attendance, (int)Auth::id());
        return redirect()->back()->with($res['ok'] ? 'success' : 'error', $res['message']);
    }

    public function cancelPt($attendance)
    {
        $res = $this->service->cancelPtOnly((int)$attendance, (int)Auth::id());
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

        return redirect()->back()->with($res['ok'] ? 'success' : 'error', $res['message']);
    }

    // Not used now (resource requires)
    public function create() { return redirect()->route('attendances.index'); }
    public function show($id) { return redirect()->route('attendances.index'); }
    public function edit($id) { return redirect()->route('attendances.index'); }
    public function update(Request $request, $id) { return redirect()->route('attendances.index'); }
    public function destroy($id) { return redirect()->route('attendances.index'); }
}
