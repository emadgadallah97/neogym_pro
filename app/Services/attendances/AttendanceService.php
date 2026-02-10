<?php

namespace App\Services\attendances;

use App\Models\attendances\Attendance;
use App\Models\attendances\AttendanceGuest;
use App\Models\members\Member;
use App\Models\sales\MemberSubscription;
use App\Models\sales\MemberSubscriptionPtAddon;
use App\Models\subscriptions\subscriptions_plan;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

class AttendanceService
{
    public function scanCheckIn(string $memberCode, int $branchId, int $userId, bool $deductPt = true): array
    {
        $memberCode = trim($memberCode);

        if ($memberCode === '') {
            return ['ok' => false, 'message' => trans('attendances.member_code_required')];
        }

        $now = Carbon::now();
        $today = $now->toDateString();
        $dayKey = strtolower($now->format('l')); // monday...

        try {
            return DB::transaction(function () use ($memberCode, $branchId, $userId, $deductPt, $now, $today, $dayKey) {

                $member = Member::query()
                    ->where('member_code', $memberCode)
                    ->first();

                if (!$member) {
                    return ['ok' => false, 'message' => trans('attendances.member_not_found')];
                }

                // ✅ Prevent multiple check-in per day (active only)
                $already = Attendance::query()
                    ->where('member_id', $member->id)
                    ->where('attendance_date', $today)
                    ->where('is_cancelled', 0)
                    ->exists();

                if ($already) {
                    return ['ok' => false, 'message' => trans('attendances.already_checked_in_today')];
                }

                // -----------------------------
                // ✅ Subscription checks (fixed column names)
                // -----------------------------
                $baseQ = MemberSubscription::query()
                    ->where('member_id', $member->id);

                if (!(clone $baseQ)->exists()) {
                    return ['ok' => false, 'message' => trans('attendances.member_has_no_subscriptions')];
                }

                $activeQ = (clone $baseQ)->where('status', 'active');

                if (!(clone $activeQ)->exists()) {
                    return ['ok' => false, 'message' => trans('attendances.subscription_status_not_active')];
                }

                // ✅ Date range check
                $inDateQ = (clone $activeQ)
                    ->whereNotNull('start_date')
                    ->whereNotNull('end_date')
                    ->whereDate('start_date', '<=', $today)
                    ->whereDate('end_date', '>=', $today);

                if (!(clone $inDateQ)->exists()) {
                    return ['ok' => false, 'message' => trans('attendances.subscription_out_of_date')];
                }

                $withSessionsQ = (clone $inDateQ)->where('sessions_remaining', '>', 0);

                if (!(clone $withSessionsQ)->exists()) {
                    return ['ok' => false, 'message' => trans('attendances.subscription_sessions_finished')];
                }

                $allowedBranchQ = (clone $withSessionsQ)->where(function ($q) use ($branchId) {
                    $q->where('allow_all_branches', 1)
                      ->orWhere('branch_id', $branchId);
                });

                if (!(clone $allowedBranchQ)->exists()) {
                    return ['ok' => false, 'message' => trans('attendances.subscription_branch_not_allowed')];
                }

                // ✅ Pick the nearest end date (and lock it)
                $sub = (clone $allowedBranchQ)
                    ->orderBy('end_date', 'asc')
                    ->lockForUpdate()
                    ->first();

                if (!$sub) {
                    return ['ok' => false, 'message' => trans('attendances.no_active_subscription')];
                }

                // ✅ Plan check
                $planId = (int)($sub->subscriptions_plan_id ?? 0);
                $plan = subscriptions_plan::query()->where('id', $planId)->first();

                if (!$plan) {
                    return ['ok' => false, 'message' => trans('attendances.plan_not_found')];
                }

                // ✅ Allowed training days check
                $allowedDays = $this->normalizeDays($plan->allowed_training_days ?? null);
                if (!empty($allowedDays) && !in_array($dayKey, $allowedDays, true)) {
                    return [
                        'ok' => false,
                        'message' => trans('attendances.day_not_allowed'),
                        'data' => [
                            'allowed_days' => $allowedDays,
                            'today' => $dayKey,
                        ]
                    ];
                }

                // ✅ Deduct base
                $baseBefore = (int)($sub->sessions_remaining ?? 0);
                if ($baseBefore <= 0) {
                    return ['ok' => false, 'message' => trans('attendances.subscription_sessions_finished')];
                }

                $sub->sessions_remaining = $baseBefore - 1;
                $sub->save();

                $baseAfter = (int)($sub->sessions_remaining ?? 0);

                // ✅ PT deduction (optional, best-effort)
                $ptAddonId = null;
                $ptBefore = null;
                $ptAfter = null;
                $isPtDeducted = false;

                if ($deductPt) {
                    $ptAddon = MemberSubscriptionPtAddon::query()
                        ->where('member_subscription_id', $sub->id)
                        ->where('sessions_remaining', '>', 0)
                        ->orderBy('id', 'asc')
                        ->lockForUpdate()
                        ->first();

                    if ($ptAddon) {
                        $ptBefore = (int)($ptAddon->sessions_remaining ?? 0);
                        $ptAddon->sessions_remaining = $ptBefore - 1;
                        $ptAddon->save();

                        $ptAfter = (int)($ptAddon->sessions_remaining ?? 0);
                        $ptAddonId = $ptAddon->id;
                        $isPtDeducted = true;
                    }
                }

                // ✅ Create attendance row
                try {
                    $attendance = Attendance::create([
                        'branch_id' => $branchId,
                        'member_id' => $member->id,
                        'attendance_date' => $today,
                        'attendance_time' => $now->format('H:i:s'),
                        'day_key' => $dayKey,

                        'member_subscription_id' => $sub->id,
                        'pt_addon_id' => $ptAddonId,

                        'is_base_deducted' => 1,
                        'is_pt_deducted' => $isPtDeducted ? 1 : 0,

                        'base_sessions_before' => $baseBefore,
                        'base_sessions_after' => $baseAfter,
                        'pt_sessions_before' => $ptBefore,
                        'pt_sessions_after' => $ptAfter,

                        'checkin_method' => 'barcode',
                        'recorded_by' => null,

                        'user_add' => $userId,
                    ]);
                } catch (QueryException $e) {
                    throw $e;
                }

                $msg = trans('attendances.scan_success');
                if ($deductPt && !$isPtDeducted) {
                    $msg = trans('attendances.scan_success_without_pt');
                }

                return [
                    'ok' => true,
                    'message' => $msg,
                    'data' => [
                        'attendance_id' => $attendance->id,
                        'member_id' => $member->id,
                        'member_code' => $member->member_code,
                        'member_name' => $member->full_name ?? null,
                        'base_before' => $baseBefore,
                        'base_after' => $baseAfter,
                        'pt_deducted' => $isPtDeducted ? 1 : 0,
                        'pt_before' => $ptBefore,
                        'pt_after' => $ptAfter,
                    ]
                ];
            }, 3);
        } catch (QueryException $e) {
            return ['ok' => false, 'message' => trans('attendances.already_checked_in_today')];
        } catch (\Throwable $e) {
            return ['ok' => false, 'message' => trans('attendances.something_went_wrong')];
        }
    }

    public function manualCheckIn(string $memberCode, int $branchId, int $userId, bool $deductPt = true): array
    {
        $result = $this->scanCheckIn($memberCode, $branchId, $userId, $deductPt);
        if (!$result['ok']) {
            return $result;
        }

        Attendance::query()
            ->where('id', $result['data']['attendance_id'])
            ->update([
                'checkin_method' => 'manual',
                'recorded_by' => $userId,
            ]);

        return $result;
    }

    public function cancelAttendance(int $attendanceId, int $userId): array
    {
        try {
            return DB::transaction(function () use ($attendanceId, $userId) {

                $att = Attendance::query()->lockForUpdate()->find($attendanceId);
                if (!$att) {
                    return ['ok' => false, 'message' => trans('attendances.attendance_not_found')];
                }
                if ((bool)$att->is_cancelled) {
                    return ['ok' => false, 'message' => trans('attendances.already_cancelled')];
                }

                if ((bool)$att->is_base_deducted && $att->member_subscription_id) {
                    $sub = MemberSubscription::query()
                        ->where('id', $att->member_subscription_id)
                        ->lockForUpdate()
                        ->first();

                    if ($sub) {
                        $sub->sessions_remaining = (int)($sub->sessions_remaining ?? 0) + 1;
                        $sub->save();
                    }
                }

                if ((bool)$att->is_pt_deducted && $att->pt_addon_id) {
                    $pt = MemberSubscriptionPtAddon::query()
                        ->where('id', $att->pt_addon_id)
                        ->lockForUpdate()
                        ->first();

                    if ($pt) {
                        $pt->sessions_remaining = (int)($pt->sessions_remaining ?? 0) + 1;
                        $pt->save();
                    }
                }

                $att->is_cancelled = 1;
                $att->cancelled_at = Carbon::now();
                $att->cancelled_by = $userId;
                $att->user_update = $userId;
                $att->save();

                return ['ok' => true, 'message' => trans('attendances.cancelled_success')];
            }, 3);
        } catch (\Throwable $e) {
            return ['ok' => false, 'message' => trans('attendances.something_went_wrong')];
        }
    }

    public function cancelPtOnly(int $attendanceId, int $userId): array
    {
        try {
            return DB::transaction(function () use ($attendanceId, $userId) {

                $att = Attendance::query()->lockForUpdate()->find($attendanceId);
                if (!$att) {
                    return ['ok' => false, 'message' => trans('attendances.attendance_not_found')];
                }
                if ((bool)$att->is_cancelled) {
                    return ['ok' => false, 'message' => trans('attendances.cannot_edit_cancelled')];
                }
                if (!(bool)$att->is_pt_deducted || !$att->pt_addon_id) {
                    return ['ok' => false, 'message' => trans('attendances.pt_not_deducted')];
                }

                $pt = MemberSubscriptionPtAddon::query()
                    ->where('id', $att->pt_addon_id)
                    ->lockForUpdate()
                    ->first();

                if (!$pt) {
                    return ['ok' => false, 'message' => trans('attendances.pt_addon_not_found')];
                }

                $pt->sessions_remaining = (int)($pt->sessions_remaining ?? 0) + 1;
                $pt->save();

                $att->is_pt_deducted = 0;
                $att->pt_refunded_at = Carbon::now();
                $att->pt_refunded_by = $userId;
                $att->user_update = $userId;
                $att->save();

                return ['ok' => true, 'message' => trans('attendances.pt_cancelled_success')];
            }, 3);
        } catch (\Throwable $e) {
            return ['ok' => false, 'message' => trans('attendances.something_went_wrong')];
        }
    }

    public function addGuest(int $attendanceId, int $userId, ?string $name, ?string $phone, ?string $notes = null): array
    {
        try {
            return DB::transaction(function () use ($attendanceId, $userId, $name, $phone, $notes) {

                $att = Attendance::query()->with('subscription')->lockForUpdate()->find($attendanceId);
                if (!$att) {
                    return ['ok' => false, 'message' => trans('attendances.attendance_not_found')];
                }
                if ((bool)$att->is_cancelled) {
                    return ['ok' => false, 'message' => trans('attendances.cannot_edit_cancelled')];
                }
                if (!$att->member_subscription_id || !$att->subscription) {
                    return ['ok' => false, 'message' => trans('attendances.no_active_subscription')];
                }

                $sub = $att->subscription;

                $planId = (int)($sub->subscriptions_plan_id ?? 0);
                $plan = subscriptions_plan::query()->where('id', $planId)->first();

                if (!$plan) {
                    return ['ok' => false, 'message' => trans('attendances.plan_not_found')];
                }

                if (!(bool)($plan->allow_guest ?? false)) {
                    return ['ok' => false, 'message' => trans('attendances.guests_not_allowed')];
                }

                $now = Carbon::now();
                $todayDay = strtolower($now->format('l'));

                $guestAllowedDays = $this->normalizeDays($plan->guest_allowed_days ?? null);
                if (!empty($guestAllowedDays) && !in_array($todayDay, $guestAllowedDays, true)) {
                    return ['ok' => false, 'message' => trans('attendances.guest_day_not_allowed')];
                }

                $maxPeople = (int)($plan->guest_people_count ?? 0);
                if ($maxPeople > 0) {
                    $currentCount = AttendanceGuest::query()
                        ->where('attendance_id', $att->id)
                        ->count();

                    if ($currentCount >= $maxPeople) {
                        return ['ok' => false, 'message' => trans('attendances.guest_people_limit_reached')];
                    }
                }

                $maxTimes = (int)($plan->guest_times_count ?? 0);
                if ($maxTimes > 0) {
                    $usedTimes = Attendance::query()
                        ->where('member_subscription_id', $sub->id)
                        ->where('is_cancelled', 0)
                        ->whereHas('guests')
                        ->distinct()
                        ->count('id');

                    if ($usedTimes >= $maxTimes) {
                        return ['ok' => false, 'message' => trans('attendances.guest_times_limit_reached')];
                    }
                }

                AttendanceGuest::create([
                    'attendance_id' => $att->id,
                    'guest_name' => $name ? trim($name) : null,
                    'guest_phone' => $phone ? trim($phone) : null,
                    'notes' => $notes,
                    'user_add' => $userId,
                ]);

                return ['ok' => true, 'message' => trans('attendances.guest_added_success')];
            }, 3);
        } catch (\Throwable $e) {
            return ['ok' => false, 'message' => trans('attendances.something_went_wrong')];
        }
    }

    private function normalizeDays($value): array
    {
        if (is_array($value)) {
            return array_values(array_filter(array_map('strtolower', $value)));
        }

        if (is_string($value) && trim($value) !== '') {
            $decoded = json_decode($value, true);
            if (is_array($decoded)) {
                return array_values(array_filter(array_map('strtolower', $decoded)));
            }
        }

        return [];
    }
}
