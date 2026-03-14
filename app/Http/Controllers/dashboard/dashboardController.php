<?php

namespace App\Http\Controllers\dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\Models\members\Member;
use App\Models\sales\MemberSubscription;
use App\Models\attendances\attendance;
use App\Models\accounting\income;
use App\Models\accounting\Expense;
use App\Models\employee\employee;
use App\Models\sales\Invoice;
use App\Models\general\Branch;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class dashboardController extends Controller
{
    function __construct()
    {
        $this->middleware('permission:dashboard');
    }

    public function index(Request $request)
    {
        $branchId = $request->input('branch_id');

        $cacheKey = 'dashboard_kpis_' . ($branchId ?? 'all');
        $data = Cache::remember($cacheKey, 300, function () use ($branchId) {
            $today = Carbon::today();
            $thisMonthStart = Carbon::today()->startOfMonth();
            $lastMonthStart = Carbon::today()->subMonth()->startOfMonth();
            $lastMonthEnd = Carbon::today()->subMonth()->endOfMonth();

            // 1. KPI Cards
            $kpis = [];
            
            // Total Active Members
            $mQuery = Member::where('status', 'active');
            if ($branchId) $mQuery->where('branch_id', $branchId);
            $kpis['total_active_members'] = $mQuery->count();

            // Today's Attendance
            $attQuery = attendance::whereDate('attendance_date', $today)->where('is_cancelled', 0);
            if ($branchId) $attQuery->where('branch_id', $branchId);
            $kpis['today_attendance'] = $attQuery->count();

            // Today's Revenue
            $revQuery = income::whereDate('incomedate', $today)->where('iscancelled', 0);
            if ($branchId) $revQuery->where('branchid', $branchId);
            $kpis['today_revenue'] = $revQuery->sum('amount');

            // Today's Expenses
            $expQuery = Expense::whereDate('expensedate', $today)->where('iscancelled', 0);
            if ($branchId) $expQuery->where('branchid', $branchId);
            $kpis['today_expenses'] = $expQuery->sum('amount');

            // Active Subscriptions
            $subQuery = MemberSubscription::where('status', 'active');
            if ($branchId) $subQuery->where('branch_id', $branchId);
            $kpis['active_subscriptions'] = $subQuery->count();

            // Pending Renewals (Next 7 days)
            $renewQuery = MemberSubscription::whereIn('status', ['active', 'expired', 'inactive'])
                ->whereBetween('end_date', [$today, $today->copy()->addDays(7)]);
            if ($branchId) $renewQuery->where('branch_id', $branchId);
            $kpis['pending_renewals'] = $renewQuery->count();

            // Total Employees
            $empQuery = employee::where('status', 1);
            if ($branchId) {
                $empQuery->whereHas('branches', function ($q) use ($branchId) {
                    $q->where('branch_id', $branchId);
                });
            }
            $kpis['total_employees'] = $empQuery->count();

            // Invoices Summary
            $invMonthQuery = Invoice::whereMonth('issued_at', $thisMonthStart->month)
                ->whereYear('issued_at', $thisMonthStart->year);
            if ($branchId) $invMonthQuery->where('branch_id', $branchId);
            $kpis['total_invoices_month'] = (clone $invMonthQuery)->count();
            $kpis['total_invoiced_amount'] = (clone $invMonthQuery)->sum('total');

            // 2. Branch Comparison Table
            $branchesData = [];
            $allBranches = Branch::where('status', 1)->get();
            foreach ($allBranches as $b) {
                $bId = $b->id;
                $activeM = Member::where('status', 'active')->where('branch_id', $bId)->count();
                $tdAtt = attendance::whereDate('attendance_date', $today)->where('is_cancelled', 0)->where('branch_id', $bId)->count();
                $mtdRev = income::whereBetween('incomedate', [$thisMonthStart, $today])->where('iscancelled', 0)->where('branchid', $bId)->sum('amount');
                $mtdExp = Expense::whereBetween('expensedate', [$thisMonthStart, $today])->where('iscancelled', 0)->where('branchid', $bId)->sum('amount');

                $branchesData[] = [
                    'name' => $b->name,
                    'active_members' => $activeM,
                    'today_attendance' => $tdAtt,
                    'mtd_revenue' => $mtdRev,
                    'mtd_expenses' => $mtdExp,
                    'net' => $mtdRev - $mtdExp
                ];
            }

            // 3. Members by Status & New Members
            $membersByStatus = Member::select('status', DB::raw('count(*) as count'))
                ->when($branchId, function($q) use($branchId) { return $q->where('branch_id', $branchId); })
                ->groupBy('status')
                ->pluck('count', 'status')->toArray();

            $newMembersThisMonth = Member::whereBetween('join_date', [$thisMonthStart, $today])
                ->when($branchId, function($q) use($branchId) { return $q->where('branch_id', $branchId); })->count();
            
            $newMembersLastMonth = Member::whereBetween('join_date', [$lastMonthStart, $lastMonthEnd])
                ->when($branchId, function($q) use($branchId) { return $q->where('branch_id', $branchId); })->count();

            return compact('kpis', 'branchesData', 'membersByStatus', 'newMembersThisMonth', 'newMembersLastMonth');
        });

        if ($request->ajax()) {
            return response()->json($data['kpis']);
        }

        $branches = Branch::where('status', 1)->get();
        // Make sure data is accessible easily avoiding nested arrays when extracting
        return view('dashboard.index', array_merge($data, ['branches' => $branches, 'selectedBranch' => $branchId]));
    }

    public function ajaxCharts(Request $request)
    {
        $branchId = $request->input('branch_id');
        $today = Carbon::today();
        
        $cacheKey = 'dashboard_charts_' . ($branchId ?? 'all');
        $chartsData = Cache::remember($cacheKey, 300, function () use ($branchId, $today) {
            
            // 1. Revenue vs Expenses (Last 12 Months)
            $twelveMonthsAgo = $today->copy()->subMonths(11)->startOfMonth();
            
            $revDataQuery = income::select(
                DB::raw('YEAR(incomedate) as year, MONTH(incomedate) as month'),
                DB::raw('SUM(amount) as total')
            )->where('iscancelled', 0)->where('incomedate', '>=', $twelveMonthsAgo);
            if ($branchId) $revDataQuery->where('branchid', $branchId);
            $revData = $revDataQuery->groupBy('year', 'month')->get();

            $expDataQuery = Expense::select(
                DB::raw('YEAR(expensedate) as year, MONTH(expensedate) as month'),
                DB::raw('SUM(amount) as total')
            )->where('iscancelled', 0)->where('expensedate', '>=', $twelveMonthsAgo);
            if ($branchId) $expDataQuery->where('branchid', $branchId);
            $expData = $expDataQuery->groupBy('year', 'month')->get();

            $months = [];
            $revSeries = [];
            $expSeries = [];
            
            for ($i = 0; $i < 12; $i++) {
                $m = $today->copy()->subMonths(11 - $i);
                $year = $m->year;
                $month = $m->month;
                $months[] = $m->format('M Y');
                
                $r = $revData->first(function($item) use ($year, $month) {
                    return $item->year == $year && $item->month == $month;
                });
                $revSeries[] = $r ? (float) $r->total : 0;
                
                $e = $expData->first(function($item) use ($year, $month) {
                    return $item->year == $year && $item->month == $month;
                });
                $expSeries[] = $e ? (float) $e->total : 0;
            }

            // 2. Attendance (Last 30 days)
            $thirtyDaysAgo = $today->copy()->subDays(29);
            $attDataQuery = attendance::select(
                DB::raw('DATE(attendance_date) as date'),
                DB::raw('COUNT(*) as total')
            )->where('is_cancelled', 0)->where('attendance_date', '>=', $thirtyDaysAgo);
            if ($branchId) $attDataQuery->where('branch_id', $branchId);
            $attData = $attDataQuery->groupBy('date')->orderBy('date')->get();

            $attDates = [];
            $attSeries = [];
            for ($i = 0; $i < 30; $i++) {
                $d = $thirtyDaysAgo->copy()->addDays($i);
                $dateStr = $d->format('Y-m-d');
                $attDates[] = $d->format('d M');
                
                $a = $attData->first(function($item) use ($dateStr) {
                    return $item->date == $dateStr;
                });
                $attSeries[] = $a ? (int) $a->total : 0;
            }

            // 3. Subscription Type Distribution
            $subTypeQuery = MemberSubscription::select(
                'subscriptions_types.name',
                DB::raw('COUNT(*) as total')
            )
            ->join('subscriptions_types', 'member_subscriptions.subscriptions_type_id', '=', 'subscriptions_types.id')
            ->where('member_subscriptions.status', 'active');
            if ($branchId) $subTypeQuery->where('member_subscriptions.branch_id', $branchId);
            
            $subTypes = $subTypeQuery->groupBy('subscriptions_types.id', 'subscriptions_types.name')->get();
            
            $subTypeLabels = [];
            $subTypeSeries = [];
            foreach ($subTypes as $st) {
                // Name might be JSON for translations, assume decode if possible or fallback
                $name = json_decode($st->name, true);
                if (is_array($name)) {
                    $locale = app()->getLocale();
                    $name = $name[$locale] ?? array_values($name)[0] ?? 'Unknown';
                } else {
                    $name = $st->name;
                }
                $subTypeLabels[] = $name;
                $subTypeSeries[] = (int) $st->total;
            }

            // 4. Top 5 Branches by Revenue (This Month)
            $thisMonthStart = $today->copy()->startOfMonth();
            $topBranchesDataQuery = income::select('branchid', DB::raw('SUM(amount) as total'))
                ->where('iscancelled', 0)
                ->whereBetween('incomedate', [$thisMonthStart, $today])
                ->groupBy('branchid')
                ->orderBy('total', 'desc')
                ->limit(5)
                ->with('branch');
            $topBranchesData = $topBranchesDataQuery->get()->map(function ($q) {
                    return [
                        'name' => $q->branch ? $q->branch->name : 'Unknown',
                        'total' => $q->total
                    ];
                });

            // 5. Best Selling Packages (This Month)
            $topPackagesData = MemberSubscription::select('plan_name', DB::raw('COUNT(*) as total'))
                ->whereBetween('start_date', [$thisMonthStart, $today])
                ->when($branchId, function($q) use($branchId) { return $q->where('branch_id', $branchId); })
                ->groupBy('plan_name')
                ->orderBy('total', 'desc')
                ->limit(5)
                ->get()
                ->map(function ($q) {
                    $name = is_array($q->plan_name) ? ($q->plan_name[app()->getLocale()] ?? array_values($q->plan_name)[0] ?? 'Unknown') : $q->plan_name;
                    return [
                        'name' => $name,
                        'total' => $q->total
                    ];
                });

            return [
                'revenue_expenses' => [
                    'months' => $months,
                    'revenue' => $revSeries,
                    'expenses' => $expSeries
                ],
                'attendance' => [
                    'dates' => $attDates,
                    'totals' => $attSeries
                ],
                'subscription_types' => [
                    'labels' => $subTypeLabels,
                    'series' => $subTypeSeries
                ],
                'top_branches' => $topBranchesData,
                'top_packages' => $topPackagesData
            ];
        });

        return response()->json($chartsData);
    }
}
