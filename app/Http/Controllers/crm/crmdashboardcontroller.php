<?php
// app/Http/Controllers/crm/CrmDashboardController.php

namespace App\Http\Controllers\crm;

use App\Http\Controllers\Controller;
use App\Models\crm\CrmFollowup;
use App\Models\members\Member;
use App\Models\sales\MemberSubscription;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class CrmDashboardController extends Controller
{
    public function index()
    {
        $today = Carbon::today();

        // ══════════════════════════════════════════════════════
        //  Stats Cards
        // ══════════════════════════════════════════════════════

        // ✅ withoutGlobalScope لأن العداد يشمل الأعضاء الفعليين فقط (الـ scope يستثني prospects تلقائياً)
        $totalMembers  = Member::count();
        $activeMembers = Member::where('status', 'active')->count();

        // ✅ الأعضاء المحتملين — يحتاج scopeProspects لتجاوز الـ GlobalScope
        $prospectsCount = Member::prospects()->count();

        $expiring7 = DB::table('member_subscriptions')
            ->where('status', 'active')
            ->whereBetween('end_date', [
                $today->toDateString(),
                $today->copy()->addDays(7)->toDateString(),
            ])
            ->whereNull('deleted_at')
            ->distinct()
            ->count('member_id');

        $expiring30 = DB::table('member_subscriptions')
            ->where('status', 'active')
            ->whereBetween('end_date', [
                $today->copy()->addDays(8)->toDateString(),
                $today->copy()->addDays(30)->toDateString(),
            ])
            ->whereNull('deleted_at')
            ->distinct()
            ->count('member_id');

        $frozenMembers = Member::where('status', 'frozen')->count();

        $expiredCount = Member::whereHas('subscriptions', fn($q) => $q->where('status', 'expired'))
            ->whereDoesntHave('subscriptions', fn($q) => $q->where('status', 'active'))
            ->count();

        $newLast30 = Member::where('join_date', '>=', $today->copy()->subDays(30)->toDateString())
            ->count();

        $newThisMonth = Member::whereYear('join_date', now()->year)
            ->whereMonth('join_date', now()->month)
            ->count();

        $unpaidMembersCount = Member::whereHas('invoices', fn($q) => $q->where('status', 'unpaid'))
            ->count();

        $unpaidInvoicesCount = DB::table('invoices')
            ->where('status', 'unpaid')
            ->whereNull('deleted_at')
            ->count();

        // ── Inactive Members ──────────────────────────────────
        $recentAttendeeIds = DB::table('attendances')
            ->where('attendance_date', '>=', $today->copy()->subDays(14)->toDateString())
            ->where('is_cancelled', false)
            ->whereNull('deleted_at')
            ->distinct()
            ->pluck('member_id')
            ->toArray();

        $activeSubMemberIds = DB::table('member_subscriptions')
            ->where('status', 'active')
            ->where('end_date', '>=', $today->toDateString())
            ->whereNull('deleted_at')
            ->distinct()
            ->pluck('member_id')
            ->toArray();

        $inactiveCount = Member::where('status', 'active')
            ->whereIn('id', $activeSubMemberIds)
            ->whereNotIn('id', $recentAttendeeIds)
            ->count();

        // ── Follow-ups ────────────────────────────────────────
        $overdueCount          = CrmFollowup::overdue()->count();
        $todayCount            = CrmFollowup::dueToday()->count();
        $pendingFollowupsTotal = CrmFollowup::pending()->count();

        // ── Today + Overdue Follow-ups list ──────────────────
        $todayFollowups = CrmFollowup::with(['member', 'branch'])
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
        $expiringSoonList = MemberSubscription::with(['member'])
            ->where('status', 'active')
            ->whereBetween('end_date', [
                $today->toDateString(),
                $today->copy()->addDays(7)->toDateString(),
            ])
            ->whereNull('deleted_at')
            ->orderBy('end_date', 'asc')
            ->limit(10)
            ->get();

        // ══════════════════════════════════════════════════════
        //  Segments
        // ══════════════════════════════════════════════════════

        $segments = [
            [
                'key'   => 'all',
                'label' => __('crm.seg_all'),
                'count' => $totalMembers,
                'color' => 'primary',
                'icon'  => 'fa-users',
            ],
            [
                'key'   => 'expiring7',
                'label' => __('crm.seg_expiring7'),
                'count' => $expiring7,
                'color' => 'warning',
                'icon'  => 'fa-clock',
            ],
            [
                'key'   => 'expiring30',
                'label' => __('crm.seg_expiring30'),
                'count' => $expiring30,
                'color' => 'info',
                'icon'  => 'fa-calendar-alt',
            ],
            [
                'key'   => 'expired',
                'label' => __('crm.seg_expired'),
                'count' => $expiredCount,
                'color' => 'danger',
                'icon'  => 'fa-times-circle',
            ],
            [
                'key'   => 'frozen',
                'label' => __('crm.seg_frozen'),
                'count' => $frozenMembers,
                'color' => 'secondary',
                'icon'  => 'fa-snowflake',
            ],
            [
                'key'   => 'inactive',
                'label' => __('crm.seg_inactive'),
                'count' => $inactiveCount,
                'color' => 'dark',
                'icon'  => 'fa-user-slash',
            ],
            [
                'key'   => 'new',
                'label' => __('crm.seg_new'),
                'count' => $newLast30,
                'color' => 'success',
                'icon'  => 'fa-user-plus',
            ],
            [
                'key'   => 'debt',
                'label' => __('crm.seg_debt'),
                'count' => $unpaidMembersCount,
                'color' => 'danger',
                'icon'  => 'fa-file-invoice-dollar',
            ],
            // ✅ شريحة الأعضاء المحتملين
            [
                'key'   => 'prospects',
                'label' => __('crm.seg_prospects'),
                'count' => $prospectsCount,
                'color' => 'purple',
                'icon'  => 'fa-user-tag',
                'route' => 'crm.prospects.index', // route مستقل للـ prospects
            ],
        ];

        return view('crm.dashboard', compact(
            'activeMembers', 'expiring7', 'expiring30', 'frozenMembers',
            'newThisMonth', 'newLast30', 'pendingFollowupsTotal',
            'unpaidInvoicesCount', 'unpaidMembersCount', 'inactiveCount',
            'overdueCount', 'todayCount', 'totalMembers', 'prospectsCount',
            'segments', 'todayFollowups', 'expiringSoonList'
        ));
    }
}
