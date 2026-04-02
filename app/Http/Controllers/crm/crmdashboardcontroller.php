<?php

namespace App\Http\Controllers\crm;

use App\Http\Controllers\Controller;
use App\Models\crm\CrmFollowup;
use App\Models\general\Branch;
use App\Models\members\Member;
use App\Models\sales\MemberSubscription;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CrmDashboardController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:crm_dashboard_view');
    }
    public function index(Request $request)
    {
        $today    = Carbon::today();
        $branchId = (int)$request->get('branch_id', 0); // ✅ 0 = كل الفروع

        // ✅ كل الفروع النشطة — withoutGlobalScopes لتجاوز BranchAccessScope
        $branches = Branch::withoutGlobalScopes()
            ->where('status', 1)
            ->orderBy('id')
            ->get();

        // ══════════════════════════════════════════════════════
        //  Stats Cards
        // ══════════════════════════════════════════════════════

        $totalMembers   = $this->memberQuery($branchId)->count();
        $activeMembers  = $this->memberQuery($branchId)->where('status', 'active')->count();
        $prospectsCount = $this->prospectQuery($branchId)->count();

        $expiring7 = $this->subscriptionQuery($branchId)
            ->where('status', 'active')
            ->whereBetween('end_date', [
                $today->toDateString(),
                $today->copy()->addDays(7)->toDateString(),
            ])
            ->distinct()->count('member_id');

        $expiring30 = $this->subscriptionQuery($branchId)
            ->where('status', 'active')
            ->whereBetween('end_date', [
                $today->copy()->addDays(8)->toDateString(),
                $today->copy()->addDays(30)->toDateString(),
            ])
            ->distinct()->count('member_id');

        $frozenMembers = $this->memberQuery($branchId)->where('status', 'frozen')->count();

        $expiredCount = $this->memberQuery($branchId)
            ->whereHas('subscriptions', fn($q) => $q->where('status', 'expired'))
            ->whereDoesntHave('subscriptions', fn($q) => $q->where('status', 'active'))
            ->count();

        $newLast30 = $this->memberQuery($branchId)
            ->where('join_date', '>=', $today->copy()->subDays(30)->toDateString())
            ->count();

        $newThisMonth = $this->memberQuery($branchId)
            ->whereYear('join_date', now()->year)
            ->whereMonth('join_date', now()->month)
            ->count();

        $unpaidMembersCount = $this->memberQuery($branchId)
            ->whereHas('invoices', fn($q) => $q->where('status', 'unpaid'))
            ->count();

        $unpaidInvoicesCount = $this->invoiceQuery($branchId)
            ->where('status', 'unpaid')
            ->count();

        // ── Inactive Members ──────────────────────────────────
        $recentAttendeeIds = $this->attendanceQuery($branchId)
            ->where('attendance_date', '>=', $today->copy()->subDays(14)->toDateString())
            ->where('is_cancelled', false)
            ->distinct()->pluck('member_id')->toArray();

        $activeSubMemberIds = $this->subscriptionQuery($branchId)
            ->where('status', 'active')
            ->where('end_date', '>=', $today->toDateString())
            ->distinct()->pluck('member_id')->toArray();

        $inactiveCount = $this->memberQuery($branchId)
            ->where('status', 'active')
            ->whereIn('id', $activeSubMemberIds)
            ->whereNotIn('id', $recentAttendeeIds)
            ->count();

        // ── Follow-ups ────────────────────────────────────────
        $followupBase = $branchId > 0
            ? CrmFollowup::where('branch_id', $branchId)
            : CrmFollowup::query();

        $overdueCount          = (clone $followupBase)->overdue()->count();
        $todayCount            = (clone $followupBase)->dueToday()->count();
        $pendingFollowupsTotal = (clone $followupBase)->pending()->count();

        // ── Today + Overdue Follow-ups list ──────────────────
        $todayFollowups = (clone $followupBase)
            ->with([
                'member',
                // ✅ branch يظهر دائماً بغض النظر عن GlobalScope
                'branch' => fn($bq) => $bq->withoutGlobalScopes(),
            ])
            ->where('status', 'pending')
            ->where(function ($q) use ($today) {
                $q->whereDate('next_action_at', $today)
                  ->orWhere(function ($q2) use ($today) {
                      $q2->whereNotNull('next_action_at')
                         ->whereDate('next_action_at', '<', $today);
                  });
            })
            ->orderByRaw("FIELD(priority, 'high', 'medium', 'low')")
            ->orderBy('next_action_at', 'asc')
            ->limit(15)
            ->get();

        // ── Expiring soon list ────────────────────────────────
        $expiringSoonList = $this->subscriptionModelQuery($branchId)
            ->with(['member'])
            ->where('status', 'active')
            ->whereBetween('end_date', [
                $today->toDateString(),
                $today->copy()->addDays(7)->toDateString(),
            ])
            ->orderBy('end_date', 'asc')
            ->limit(10)
            ->get();

        // ══════════════════════════════════════════════════════
        //  Segments
        // ══════════════════════════════════════════════════════

        $segments = [
            ['key' => 'all',       'label' => __('crm.seg_all'),       'count' => $totalMembers,       'color' => 'primary',   'icon' => 'fa-users'],
            ['key' => 'expiring7', 'label' => __('crm.seg_expiring7'), 'count' => $expiring7,          'color' => 'warning',   'icon' => 'fa-clock'],
            ['key' => 'expiring30','label' => __('crm.seg_expiring30'),'count' => $expiring30,         'color' => 'info',      'icon' => 'fa-calendar-alt'],
            ['key' => 'expired',   'label' => __('crm.seg_expired'),   'count' => $expiredCount,       'color' => 'danger',    'icon' => 'fa-times-circle'],
            ['key' => 'frozen',    'label' => __('crm.seg_frozen'),    'count' => $frozenMembers,      'color' => 'secondary', 'icon' => 'fa-snowflake'],
            ['key' => 'inactive',  'label' => __('crm.seg_inactive'),  'count' => $inactiveCount,      'color' => 'dark',      'icon' => 'fa-user-slash'],
            ['key' => 'new',       'label' => __('crm.seg_new'),       'count' => $newLast30,          'color' => 'success',   'icon' => 'fa-user-plus'],
            ['key' => 'debt',      'label' => __('crm.seg_debt'),      'count' => $unpaidMembersCount, 'color' => 'danger',    'icon' => 'fa-file-invoice-dollar'],
            [
                'key'   => 'prospects',
                'label' => __('crm.seg_prospects'),
                'count' => $prospectsCount,
                'color' => 'purple',
                'icon'  => 'fa-user-tag',
                'route' => 'crm.prospects.index',
            ],
        ];

        return view('crm.dashboard', compact(
            'branches', 'branchId',
            'activeMembers', 'expiring7', 'expiring30', 'frozenMembers',
            'newThisMonth', 'newLast30', 'pendingFollowupsTotal',
            'unpaidInvoicesCount', 'unpaidMembersCount', 'inactiveCount',
            'overdueCount', 'todayCount', 'totalMembers', 'prospectsCount',
            'segments', 'todayFollowups', 'expiringSoonList'
        ));
    }

    // ─────────────────────────────────────────
    // Query Helpers — Branch Filter
    // ─────────────────────────────────────────

    private function memberQuery(int $branchId)
    {
        $q = Member::query();
        if ($branchId > 0) $q->where('branch_id', $branchId);
        return $q;
    }

    private function prospectQuery(int $branchId)
    {
        $q = Member::prospects();
        if ($branchId > 0) $q->where('branch_id', $branchId);
        return $q;
    }

    private function subscriptionQuery(int $branchId)
    {
        $q = DB::table('member_subscriptions')->whereNull('deleted_at');
        if ($branchId > 0) $q->where('branch_id', $branchId);
        return $q;
    }

    private function subscriptionModelQuery(int $branchId)
    {
        $q = MemberSubscription::query();
        if ($branchId > 0) $q->where('branch_id', $branchId);
        return $q;
    }

    private function attendanceQuery(int $branchId)
    {
        $q = DB::table('attendances')->whereNull('deleted_at');
        if ($branchId > 0) $q->where('branch_id', $branchId);
        return $q;
    }

    private function invoiceQuery(int $branchId)
    {
        $q = DB::table('invoices')->whereNull('deleted_at');
        if ($branchId > 0) $q->where('branch_id', $branchId);
        return $q;
    }
}
