<?php
// app/Http/Controllers/crm/CrmMembersController.php

namespace App\Http\Controllers\crm;

use App\Http\Controllers\Controller;
use App\Models\crm\crmfollowup;
use App\Models\general\Branch;
use App\Models\members\Member;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Scopes\ExcludeProspectsScope;
class CrmMembersController extends Controller
{
    protected function segmentsMetaData(): array
    {
        return [
            'expiring7'  => ['label' => trans('crm.seg_expiring7'),  'color' => 'warning',   'icon' => 'fa-clock'],
            'expiring30' => ['label' => trans('crm.seg_expiring30'), 'color' => 'info',      'icon' => 'fa-calendar-alt'],
            'expired'    => ['label' => trans('crm.seg_expired'),    'color' => 'danger',    'icon' => 'fa-times-circle'],
            'frozen'     => ['label' => trans('crm.seg_frozen'),     'color' => 'secondary', 'icon' => 'fa-snowflake'],
            'inactive'   => ['label' => trans('crm.seg_inactive'),   'color' => 'dark',      'icon' => 'fa-user-slash'],
            'new'        => ['label' => trans('crm.seg_new'),        'color' => 'success',   'icon' => 'fa-user-plus'],
            'debt'       => ['label' => trans('crm.seg_debt'),       'color' => 'danger',    'icon' => 'fa-file-invoice'],
        ];
    }

    // ══════════════════════════════════════════════════════
    //  INDEX
    // ══════════════════════════════════════════════════════

    public function index(Request $request)
    {
        // ✅ [تعديل] قبول 'all' بجانب باقي الـ segments
        $segmentsMeta = $this->segmentsMetaData();
        $validSegments = array_merge(['all'], array_keys($segmentsMeta));
        $segment  = in_array($request->segment, $validSegments)
                    ? $request->segment
                    : 'expiring7';

        $search   = $request->search;
        $branchId = $request->branch_id;
        $today    = Carbon::today();

        $query = $this->buildSegmentQuery($segment, $today);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('first_name',    'like', "%$search%")
                  ->orWhere('last_name',   'like', "%$search%")
                  ->orWhere('member_code', 'like', "%$search%")
                  ->orWhere('phone',       'like', "%$search%");
            });
        }

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        $members   = $query->with('branch')->paginate(20)->withQueryString();
        $memberIds = $members->pluck('id')->toArray();

        $latestSubs      = $this->getLatestSubscriptions($memberIds, $segment, $today);
        $lastAttendances = $this->getLastAttendances($memberIds);
        $unpaidAmounts   = ($segment === 'debt') ? $this->getUnpaidAmounts($memberIds) : collect();
        $segmentCounts   = $this->getSegmentCounts($today);
        $branches        = Branch::where('status', 1)->get();
        $segmentsMeta    = $this->segmentsMetaData();

        return view('crm.members.index', compact(
            'members', 'segment', 'segmentCounts', 'branches',
            'search', 'branchId', 'latestSubs', 'lastAttendances',
            'unpaidAmounts', 'segmentsMeta'
        ));
    }

    // ══════════════════════════════════════════════════════
    //  SHOW — Member 360°
    // ══════════════════════════════════════════════════════

    public function show($id)
    {
        $member = Member::with('branch')->findOrFail($id);
        $today  = Carbon::today();

        // ── 1) الاشتراك الحالي + PT Addon ──────────────────
        $activeSub = DB::table('member_subscriptions')
            ->where('member_id', $id)
            ->where('status', 'active')
            ->whereNull('deleted_at')
            ->orderByDesc('end_date')
            ->first();

        if ($activeSub) {
            $activeSub->plan_name_display = $this->parsePlanName($activeSub->plan_name);

            $ptAddon = DB::table('member_subscription_pt_addons')
                ->where('member_subscription_id', $activeSub->id)
                ->whereNull('deleted_at')
                ->first();

            if ($ptAddon && $ptAddon->trainer_id) {
                $trainer = DB::table('employees')
                    ->where('id', $ptAddon->trainer_id)
                    ->first();
                $ptAddon->trainer_name = $trainer
                    ? trim(($trainer->first_name ?? '') . ' ' . ($trainer->last_name ?? ''))
                    : '—';
            }
        } else {
            $ptAddon = null;
        }

        // ── 2) إحصائيات الحضور ─────────────────────────────
        $attendanceStats = $this->getAttendanceStats($id, $today);

        // ── 3) الماليات ────────────────────────────────────
        $financialStats = $this->getFinancialStats($id);

        // ── 4) تاريخ الاشتراكات ────────────────────────────
        $allSubscriptions = DB::table('member_subscriptions')
            ->where('member_id', $id)
            ->whereNull('deleted_at')
            ->orderByDesc('start_date')
            ->get()
            ->map(function ($sub) {
                $sub->plan_name_display = $this->parsePlanName($sub->plan_name);
                return $sub;
            });

        // ── 5) سجل المتابعات CRM ───────────────────────────
        $followups = crmfollowup::where('member_id', $id)
            ->orderByDesc('created_at')
            ->get();

        // ── 6) آخر 30 حضور ─────────────────────────────────
        $recentAttendances = DB::table('attendances')
            ->where('member_id', $id)
            ->where('is_cancelled', false)
            ->whereNull('deleted_at')
            ->orderByDesc('attendance_date')
            ->limit(30)
            ->get();

        // ── 7) الفواتير المعلقة ─────────────────────────────
        $unpaidInvoices = DB::table('invoices')
            ->where('member_id', $id)
            ->where('status', 'unpaid')
            ->whereNull('deleted_at')
            ->orderByDesc('created_at')
            ->get();

        $segmentsMeta = $this->segmentsMetaData();

        return view('crm.members.show', compact(
            'member',
            'activeSub',
            'ptAddon',
            'attendanceStats',
            'financialStats',
            'allSubscriptions',
            'followups',
            'recentAttendances',
            'unpaidInvoices',
            'segmentsMeta'
        ));
    }

    // ══════════════════════════════════════════════════════
    //  BUILD QUERY PER SEGMENT
    // ══════════════════════════════════════════════════════

    private function buildSegmentQuery(string $segment, Carbon $today)
    {
        switch ($segment) {

            // ✅ [تعديل] تاب الكل — بدون فلترة
            case 'all':
                return Member::query()->orderByDesc('id');

            case 'expiring7':
                return Member::whereHas('subscriptions', function ($q) use ($today) {
                    $q->where('status', 'active')
                      ->whereBetween('end_date', [
                          $today->toDateString(),
                          $today->copy()->addDays(7)->toDateString(),
                      ]);
                });

            case 'expiring30':
                return Member::whereHas('subscriptions', function ($q) use ($today) {
                    $q->where('status', 'active')
                      ->whereBetween('end_date', [
                          $today->copy()->addDays(8)->toDateString(),
                          $today->copy()->addDays(30)->toDateString(),
                      ]);
                });

            case 'expired':
                return Member::whereHas('subscriptions', function ($q) {
                    $q->where('status', 'expired');
                })->whereDoesntHave('subscriptions', function ($q) {
                    $q->where('status', 'active');
                });

            case 'frozen':
                return Member::where('status', 'frozen');

            case 'inactive':
                $recentIds = DB::table('attendances')
                    ->where('attendance_date', '>=', $today->copy()->subDays(14)->toDateString())
                    ->where('is_cancelled', false)
                    ->whereNull('deleted_at')
                    ->distinct()->pluck('member_id');

                $activeSubIds = DB::table('member_subscriptions')
                    ->where('status', 'active')
                    ->where('end_date', '>=', $today->toDateString())
                    ->whereNull('deleted_at')
                    ->distinct()->pluck('member_id');

                return Member::where('status', 'active')
                    ->whereIn('id', $activeSubIds)
                    ->whereNotIn('id', $recentIds);

            case 'new':
                return Member::where('join_date', '>=',
                    $today->copy()->subDays(30)->toDateString()
                );

            case 'debt':
                return Member::whereHas('invoices', function ($q) {
                    $q->where('status', 'unpaid');
                });

            default:
                return Member::query()->orderByDesc('id');
        }
    }

    // ══════════════════════════════════════════════════════
    //  HELPERS — INDEX
    // ══════════════════════════════════════════════════════

    private function getLatestSubscriptions(array $ids, string $segment, Carbon $today): \Illuminate\Support\Collection
    {
        if (empty($ids)) return collect();

        $q = DB::table('member_subscriptions')
            ->whereIn('member_id', $ids)
            ->whereNull('deleted_at');

        // ✅ [تعديل] للـ 'all' نجلب الاشتراك النشط فقط لعرضه في العمود
        if (in_array($segment, ['expiring7', 'expiring30', 'inactive', 'all'])) {
            $q->where('status', 'active');
        }

        return $q->orderBy('end_date', 'desc')
            ->get()
            ->groupBy('member_id')
            ->map(fn($s) => $s->first())
            ->map(function ($sub) {
                $sub->plan_name_display = $this->parsePlanName($sub->plan_name);
                return $sub;
            });
    }

    private function getLastAttendances(array $ids): \Illuminate\Support\Collection
    {
        if (empty($ids)) return collect();

        return DB::table('attendances')
            ->whereIn('member_id', $ids)
            ->where('is_cancelled', false)
            ->whereNull('deleted_at')
            ->orderBy('attendance_date', 'desc')
            ->get()
            ->groupBy('member_id')
            ->map(fn($a) => $a->first());
    }

    private function getUnpaidAmounts(array $ids): \Illuminate\Support\Collection
    {
        if (empty($ids)) return collect();

        return DB::table('invoices')
            ->whereIn('member_id', $ids)
            ->where('status', 'unpaid')
            ->whereNull('deleted_at')
            ->groupBy('member_id')
            ->select(
                'member_id',
                DB::raw('SUM(total) as unpaid_total'),
                DB::raw('COUNT(*) as unpaid_count')
            )
            ->get()
            ->keyBy('member_id');
    }

    private function getSegmentCounts(Carbon $today): array
    {
        $recentIds = DB::table('attendances')
            ->where('attendance_date', '>=', $today->copy()->subDays(14)->toDateString())
            ->where('is_cancelled', false)->whereNull('deleted_at')
            ->distinct()->pluck('member_id');

        $activeSubIds = DB::table('member_subscriptions')
            ->where('status', 'active')
            ->where('end_date', '>=', $today->toDateString())
            ->whereNull('deleted_at')->distinct()->pluck('member_id');

        return [
            // ✅ [تعديل] إضافة عداد تاب الكل
            'all' => Member::count(),

            'expiring7' => DB::table('member_subscriptions')
                ->where('status', 'active')
                ->whereBetween('end_date', [
                    $today->toDateString(),
                    $today->copy()->addDays(7)->toDateString(),
                ])
                ->whereNull('deleted_at')->distinct()->count('member_id'),

            'expiring30' => DB::table('member_subscriptions')
                ->where('status', 'active')
                ->whereBetween('end_date', [
                    $today->copy()->addDays(8)->toDateString(),
                    $today->copy()->addDays(30)->toDateString(),
                ])
                ->whereNull('deleted_at')->distinct()->count('member_id'),

            'expired' => Member::whereHas('subscriptions', fn($q) => $q->where('status', 'expired'))
                ->whereDoesntHave('subscriptions', fn($q) => $q->where('status', 'active'))
                ->count(),

            'frozen' => Member::where('status', 'frozen')->count(),

            'inactive' => Member::where('status', 'active')
                ->whereIn('id', $activeSubIds)
                ->whereNotIn('id', $recentIds)
                ->count(),

            'new'  => Member::where('join_date', '>=', $today->copy()->subDays(30)->toDateString())->count(),

            'debt' => Member::whereHas('invoices', fn($q) => $q->where('status', 'unpaid'))->count(),
        ];
    }

    // ══════════════════════════════════════════════════════
    //  HELPERS — SHOW
    // ══════════════════════════════════════════════════════

    private function getAttendanceStats(int $memberId, Carbon $today): object
    {
        $rows = DB::table('attendances')
            ->where('member_id', $memberId)
            ->where('is_cancelled', false)
            ->whereNull('deleted_at')
            ->orderByDesc('attendance_date')
            ->get();

        $totalDays = $rows->count();
        $lastVisit = $rows->first()?->attendance_date;

        $last90 = $rows->filter(
            fn($r) => Carbon::parse($r->attendance_date)->gte($today->copy()->subDays(90))
        )->count();
        $avgPerWeek = $last90 > 0 ? round($last90 / (90 / 7), 1) : 0;

        $thisMonth = $rows->filter(
            fn($r) => Carbon::parse($r->attendance_date)->month === $today->month
                   && Carbon::parse($r->attendance_date)->year  === $today->year
        )->count();

        return (object) [
            'total_days'   => $totalDays,
            'last_visit'   => $lastVisit,
            'avg_per_week' => $avgPerWeek,
            'this_month'   => $thisMonth,
        ];
    }

    private function getFinancialStats(int $memberId): object
    {
        $totalPaid = DB::table('payments')
            ->where('member_id', $memberId)
            ->where('status', 'paid')
            ->whereNull('deleted_at')
            ->sum('amount');

        $unpaid = DB::table('invoices')
            ->where('member_id', $memberId)
            ->where('status', 'unpaid')
            ->whereNull('deleted_at')
            ->selectRaw('COUNT(*) as cnt, COALESCE(SUM(total),0) as total')
            ->first();

        return (object) [
            'total_paid'    => (float) $totalPaid,
            'unpaid_total'  => (float) ($unpaid->total ?? 0),
            'unpaid_count'  => (int)   ($unpaid->cnt   ?? 0),
        ];
    }

    // ══════════════════════════════════════════════════════
    //  PARSE PLAN NAME
    // ══════════════════════════════════════════════════════

    private function parsePlanName($planName): string
    {
        if (empty($planName)) return '—';

        if (is_array($planName)) {
            return $planName[app()->getLocale()]
                ?? $planName['ar']
                ?? $planName['en']
                ?? (count($planName) ? array_values($planName)[0] : '—');
        }

        if (is_string($planName)) {
            $decoded = json_decode($planName, true);

            if (is_array($decoded)) {
                return $decoded[app()->getLocale()]
                    ?? $decoded['ar']
                    ?? $decoded['en']
                    ?? (count($decoded) ? array_values($decoded)[0] : '—');
            }

            if (is_string($decoded) && !empty($decoded)) {
                return $decoded;
            }

            return $planName;
        }

        return '—';
    }

    // ══════════════════════════════════════════════════════
    //  AJAX — Member Search for Select2
    // ══════════════════════════════════════════════════════

public function searchAjax(Request $request): \Illuminate\Http\JsonResponse
{
    $q             = trim((string) $request->get('q', ''));
    $branchId      = $request->get('branch_id');

    // ✅ إظهار الأعضاء المحتملين عند الطلب (من موديل المتابعات)
    $withProspects = $request->boolean('with_prospects');

    $query = Member::query()
        ->select('id', 'first_name', 'last_name', 'member_code', 'phone', 'branch_id', 'type');

    // ✅ رفع ExcludeProspectsScope إذا طُلب، وإلا أعضاء عاديون فقط
    if ($withProspects) {
        $query->withoutGlobalScope(ExcludeProspectsScope::class);
    }

    if (!empty($branchId)) {
        $query->where('branch_id', $branchId);
    }

    if ($q !== '') {
        $query->where(function ($sq) use ($q) {
            $sq->where('first_name',    'like', "%{$q}%")
               ->orWhere('last_name',   'like', "%{$q}%")
               ->orWhere('member_code', 'like', "%{$q}%")
               ->orWhere('phone',       'like', "%{$q}%");
        });
    }

    $members = $query->limit(20)->get()->map(fn($m) => [
        'id'        => $m->id,

        // ✅ إضافة (محتمل) بجانب اسم العضو المحتمل للتمييز
        'text'      => trim("{$m->first_name} {$m->last_name}")
                        . ($m->member_code ? " — {$m->member_code}" : '')
                        . ($m->type === 'prospect' ? ' 🟢 ' . trans('crm.prospect_member') : ''),

        'phone'     => $m->phone ?? '',
        'branch_id' => $m->branch_id,
        'type'      => $m->type ?? 'member',
    ]);

    return response()->json($members);
}

}
