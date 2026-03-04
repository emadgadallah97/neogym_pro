<?php
// app/Http/Controllers/crm/CrmFollowupsController.php

namespace App\Http\Controllers\crm;

use App\Http\Controllers\Controller;
use App\Models\crm\crmfollowup;
use App\Models\general\Branch;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CrmFollowupsController extends Controller
{
public function index(Request $request)
{
    $quick      = $request->get('quick', 'all');
    $search     = trim((string) $request->get('search', ''));
    $branchId   = $request->get('branch_id');
    $nextFrom   = $request->get('next_from');
    $nextTo     = $request->get('next_to');

    // ✅ فلتر متابعة محددة (من صفحة show الخاصة بالعضو المحتمل)
    $followupId = $request->get('followup_id');

    $type = $request->get('type');
    if ($quick === 'prospect') {
        $type = 'prospect';
    }

    $status   = $request->get('status');
    $priority = $request->get('priority');

    $typeLabels     = CrmFollowup::typeLabels();
    $priorityLabels = CrmFollowup::priorityLabels();
    $statusLabels   = CrmFollowup::statusLabels();

    $q = CrmFollowup::query()
        ->with([
            'member' => function ($mq) {
                $mq->withoutGlobalScope(\App\Models\Scopes\ExcludeProspectsScope::class);
            },
            'branch',
        ])
        ->orderByDesc('created_at');

    // ── Quick tabs ────────────────────────────────────────────
    if ($quick === 'pending') {
        $q->where('status', 'pending');
    } elseif ($quick === 'done') {
        $q->where('status', 'done');
    } elseif ($quick === 'cancelled') {
        $q->where('status', 'cancelled');
    } elseif ($quick === 'overdue') {
        $q->where('status', 'pending')
          ->whereNotNull('next_action_at')
          ->where('next_action_at', '<', now());
    } elseif ($quick === 'today') {
        $q->dueToday();
    } elseif ($quick === 'prospect') {
        $q->where('type', 'prospect');
    }

    // ── Filters ───────────────────────────────────────────────
    if (!empty($followupId)) $q->where('id', $followupId);
    if (!empty($branchId))   $q->where('branch_id', $branchId);
    if (!empty($type))       $q->where('type', $type);
    if (!empty($status))     $q->where('status', $status);
    if (!empty($priority))   $q->where('priority', $priority);

    // ── فلتر موعد المتابعة (next_action_at) ──────────────────
    if (!empty($nextFrom) || !empty($nextTo)) {
        $from = !empty($nextFrom)
            ? Carbon::createFromFormat('Y-m-d', $nextFrom)->startOfDay()
            : null;
        $to = !empty($nextTo)
            ? Carbon::createFromFormat('Y-m-d', $nextTo)->endOfDay()
            : null;

        $q->whereNotNull('next_action_at');
        if ($from && $to)  $q->whereBetween('next_action_at', [$from, $to]);
        elseif ($from)     $q->where('next_action_at', '>=', $from);
        elseif ($to)       $q->where('next_action_at', '<=', $to);
    }

    // ── بحث نصي ──────────────────────────────────────────────
    if ($search !== '') {
        $q->whereHas('member', function ($mq) use ($search) {
            $mq->withoutGlobalScope(\App\Models\Scopes\ExcludeProspectsScope::class)
               ->where(function ($sq) use ($search) {
                   $sq->where('first_name',    'like', "%{$search}%")
                      ->orWhere('last_name',   'like', "%{$search}%")
                      ->orWhere('member_code', 'like', "%{$search}%")
                      ->orWhere('phone',       'like', "%{$search}%")
                      ->orWhere('whatsapp',    'like', "%{$search}%");
               });
        });
    }

    $followups = $q->paginate(20)->withQueryString();

    // ── Interactions ──────────────────────────────────────────
    $followupIds            = $followups->pluck('id')->toArray();
    $interactionsByFollowup = collect();
    $interactionCounts      = [];

    if (!empty($followupIds)) {
        $interactionsByFollowup = DB::table('crm_interactions')
            ->whereIn('followup_id', $followupIds)
            ->orderByDesc('interacted_at')
            ->orderByDesc('id')
            ->get()
            ->groupBy('followup_id')
            ->map(fn($items) => $items->take(5));

        $interactionCounts = DB::table('crm_interactions')
            ->whereIn('followup_id', $followupIds)
            ->select('followup_id', DB::raw('COUNT(*) as cnt'))
            ->groupBy('followup_id')
            ->pluck('cnt', 'followup_id')
            ->toArray();
    }

    $branches = Branch::where('status', 1)->get();

    // ── Tabs counts ───────────────────────────────────────────
    $counts = [
        'all'       => CrmFollowup::count(),
        'pending'   => CrmFollowup::where('status', 'pending')->count(),
        'done'      => CrmFollowup::where('status', 'done')->count(),
        'cancelled' => CrmFollowup::where('status', 'cancelled')->count(),
        'overdue'   => CrmFollowup::where('status', 'pending')
                            ->whereNotNull('next_action_at')
                            ->where('next_action_at', '<', now())
                            ->count(),
        'today'     => CrmFollowup::dueToday()->count(),
        'prospect'  => CrmFollowup::where('type', 'prospect')->count(),
    ];

    // ── AJAX partial ──────────────────────────────────────────
    if ($request->ajax() && (string)$request->get('partial') === '1') {
        $tabsHtml  = view('crm.followups._tabs', compact('counts', 'quick'))->render();
        $tableHtml = view('crm.followups._table', compact(
            'followups',
            'interactionsByFollowup',
            'interactionCounts'
        ))->render();

        return response()->json([
            'tabs_html'  => $tabsHtml,
            'table_html' => $tableHtml,
        ]);
    }

    return view('crm.followups.index', compact(
        'followups', 'branches', 'counts',
        'quick', 'search', 'branchId',
        'type', 'status', 'priority',
        'typeLabels', 'priorityLabels', 'statusLabels',
        'interactionsByFollowup', 'interactionCounts',
        'nextFrom', 'nextTo',
        'followupId'   // ✅ جديد
    ));
}




    public function create(){ return redirect()->route('crm.followups.index'); }
    public function show($id){ return redirect()->route('crm.followups.index'); }
    public function edit($id){ return redirect()->route('crm.followups.index'); }

public function store(Request $request)
{
    $data = $request->validate([
        'member_id'      => 'required|integer|exists:members,id',
        'branch_id'      => 'required|integer|exists:branches,id',

        // ✅ أضفنا prospect
        'type'           => 'required|in:renewal,freeze,inactive,debt,general,prospect',

        'status'         => 'required|in:pending,done,cancelled',
        'priority'       => 'required|in:high,medium,low',
        'notes'          => 'nullable|string',
        'next_action_at' => 'nullable|date',
        'result'         => 'nullable|string',
    ]);

    $data['created_by'] = Auth::id();
    $data['updated_by'] = Auth::id();

    $fu = CrmFollowup::create($data);

    if ($request->ajax()) {
        return response()->json([
            'success' => true,
            'message' => 'تم حفظ المتابعة بنجاح',
            'id'      => $fu->id,

            // ✅ إرجاع بيانات المتابعة للـ timeline في show
            'followup' => [
                'id'              => $fu->id,
                'type'            => $fu->type,
                'status'          => $fu->status,
                'priority'        => $fu->priority,
                'notes'           => $fu->notes,
                'result'          => $fu->result,
                'next_action_at'  => $fu->next_action_at?->format('Y-m-d\TH:i'),
                'created_at_human'=> $fu->created_at->diffForHumans(),
            ],
        ]);
    }

    return redirect()->back()->with('success', 'تم حفظ المتابعة بنجاح');
}

public function update(Request $request, $id)
{
    $fu = CrmFollowup::findOrFail($id);

    $data = $request->validate([
        'member_id'      => 'sometimes|required|integer|exists:members,id',
        'branch_id'      => 'sometimes|required|integer|exists:branches,id',

        // ✅ أضفنا prospect
        'type'           => 'sometimes|required|in:renewal,freeze,inactive,debt,general,prospect',

        'status'         => 'sometimes|required|in:pending,done,cancelled',
        'priority'       => 'sometimes|required|in:high,medium,low',
        'notes'          => 'nullable|string',
        'next_action_at' => 'nullable|date',
        'result'         => 'nullable|string',
    ]);

    $data['updated_by'] = Auth::id();
    $fu->update($data);

    if ($request->ajax()) {
        return response()->json([
            'success' => true,
            'message' => 'تم تحديث المتابعة',
        ]);
    }

    return redirect()->back()->with('success', 'تم تحديث المتابعة');
}


    public function destroy(Request $request, $id)
    {
        $fu = crmfollowup::findOrFail($id);
        $fu->delete();

        if ($request->ajax()) {
            return response()->json(['success' => true, 'message' => 'تم حذف المتابعة']);
        }

        return redirect()->back()->with('success', 'تم حذف المتابعة');
    }

    public function markDone(Request $request, $id)
    {
        $fu = crmfollowup::findOrFail($id);

        $fu->status     = 'done';
        $fu->updated_by = Auth::id();
        $fu->save();

        if ($request->ajax()) {
            return response()->json(['success' => true, 'message' => 'تم إنهاء المتابعة']);
        }

        return redirect()->back()->with('success', 'تم إنهاء المتابعة');
    }
}
