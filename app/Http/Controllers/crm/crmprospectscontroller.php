<?php

namespace App\Http\Controllers\crm;

use App\Http\Controllers\Controller;
use App\Models\crm\CrmFollowup;
use App\Models\members\Member;
use App\Models\Scopes\ExcludeProspectsScope;
use App\Models\general\Branch;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\ProspectsImport;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class CrmProspectsController extends Controller
{
    // ══════════════════════════════════════════════════════
    //  INDEX
    // ══════════════════════════════════════════════════════

public function index(Request $request)
{
    $query = Member::prospects()
        ->with(['branch', 'followups' => fn($q) => $q->latest()->limit(1)])
        ->withCount('followups');

    if ($request->filled('search')) {
        $s = $request->search;
        $query->where(function ($q) use ($s) {
            $q->where('first_name', 'like', "%{$s}%")
              ->orWhere('last_name',  'like', "%{$s}%")
              ->orWhere('phone',      'like', "%{$s}%")
              ->orWhere('email',      'like', "%{$s}%");
        });
    }

    if ($request->filled('branch_id')) {
        $query->where('branch_id', $request->branch_id);
    }

    if ($request->filled('gender')) {
        $query->where('gender', $request->gender);
    }

    // ✅ فلتر تاريخ الإضافة (created_at) — من / إلى
    if ($request->filled('created_from') || $request->filled('created_to')) {

        $from = $request->filled('created_from')
            ? Carbon::createFromFormat('Y-m-d', $request->created_from)->startOfDay()
            : null;

        $to = $request->filled('created_to')
            ? Carbon::createFromFormat('Y-m-d', $request->created_to)->endOfDay()
            : null;

        if ($from && $to) {
            $query->whereBetween('created_at', [$from, $to]);
        } elseif ($from) {
            $query->where('created_at', '>=', $from);
        } elseif ($to) {
            $query->where('created_at', '<=', $to);
        }
    }

    if ($request->filled('followup_status')) {
        match ($request->followup_status) {
            'no_followup' => $query->whereDoesntHave('followups'),
            'pending'     => $query->whereHas('followups', fn($q) => $q->where('status', 'pending')),
            'overdue'     => $query->whereHas('followups', fn($q) =>
                                $q->where('status', 'pending')
                                  ->whereNotNull('next_action_at')
                                  ->whereDate('next_action_at', '<', Carbon::today())
                             ),
            default       => null,
        };
    }

    $prospects = $query->orderBy('created_at', 'desc')->paginate(20)->withQueryString();
    $branches  = Branch::orderBy('id')->get();

    return view('crm.prospects.index', compact('prospects', 'branches'));
}


    // ══════════════════════════════════════════════════════
    //  CREATE
    // ══════════════════════════════════════════════════════

    public function create()
    {
        $branches      = Branch::orderBy('id')->get();
        $followupTypes = CrmFollowup::getTypes();

        return view('crm.prospects.create', compact('branches', 'followupTypes'));
    }

    // ══════════════════════════════════════════════════════
    //  STORE
    // ══════════════════════════════════════════════════════

    public function store(Request $request)
    {
        $validated = $request->validate([
            'branch_id'       => 'required|exists:branches,id',
            'first_name'      => 'required|string|max:100',
            'last_name'       => 'required|string|max:100',
            'phone'           => 'required|string|max:20',
            'address'         => 'required|string|max:255',
            'phone2'          => 'nullable|string|max:20',
            'whatsapp'        => 'nullable|string|max:20',
            'email'           => 'nullable|email|max:150',
            'gender'          => 'nullable|in:male,female',
            'notes'           => 'nullable|string|max:1000',

            // متابعة اختيارية (datetime-local)
            'create_followup'   => 'nullable|boolean',
            'followup_type'     => [
                'nullable',
                'required_if:create_followup,1',
                Rule::in(array_keys(CrmFollowup::typeLabels())),
            ],
            'followup_notes'    => 'nullable|string|max:500',
            'followup_datetime' => 'nullable|required_if:create_followup,1|date_format:Y-m-d\TH:i|after:now',
        ]);

        $prospect = Member::withoutGlobalScope(ExcludeProspectsScope::class)->create([
            'branch_id'   => $validated['branch_id'],
            'first_name'  => $validated['first_name'],
            'last_name'   => $validated['last_name'],
            'phone'       => $validated['phone'],
            'address'     => $validated['address'],
            'phone2'      => $validated['phone2']   ?? null,
            'whatsapp'    => $validated['whatsapp'] ?? null,
            'email'       => $validated['email']    ?? null,
            'gender'      => $validated['gender']   ?? null,
            'notes'       => $validated['notes']    ?? null,
            'type'        => 'prospect',
            'status'      => 'active',
            'join_date'   => Carbon::today(),
            'user_add'    => Auth::id(),
            'user_update' => Auth::id(),
        ]);

        if ($request->boolean('create_followup') && $request->filled('followup_type')) {
            $followupAt = Carbon::createFromFormat('Y-m-d\TH:i', $request->followup_datetime);

            CrmFollowup::create([
                'member_id'      => $prospect->id,
                'branch_id'      => $prospect->branch_id,
                'type'           => $request->followup_type,
                'status'         => 'pending',
                'priority'       => 'medium',
                'notes'          => $request->followup_notes ?? 'متابعة أولى - عضو محتمل جديد',
                'next_action_at' => $followupAt,
                'created_by'     => Auth::id(),
                'updated_by'     => Auth::id(),
            ]);

            return redirect()
                ->route('crm.prospects.show', $prospect->id)
                ->with('success', 'تم إضافة العضو المحتمل وجدولة المتابعة بنجاح');
        }

        return redirect()
            ->route('crm.prospects.show', $prospect->id)
            ->with('success', 'تم إضافة العضو المحتمل بنجاح');
    }

    // ══════════════════════════════════════════════════════
    //  SHOW
    // ══════════════════════════════════════════════════════

public function show(int $id)
{
    $prospect = Member::withoutGlobalScope(ExcludeProspectsScope::class)
        ->where('type', 'prospect')
        ->with(['branch', 'followups' => fn($q) => $q->orderBy('created_at', 'desc')])
        ->findOrFail($id);

    // ✅ مطلوب لموديل إضافة المتابعة
    $branches = Branch::where('status', 1)->get();

    return view('crm.prospects.show', compact('prospect', 'branches'));
}


    // ══════════════════════════════════════════════════════
    //  EDIT
    // ══════════════════════════════════════════════════════

    public function edit(int $id)
    {
        $prospect = Member::withoutGlobalScope(ExcludeProspectsScope::class)
            ->where('type', 'prospect')
            ->findOrFail($id);

        $branches      = Branch::orderBy('id')->get();
        $followupTypes = CrmFollowup::getTypes();

        return view('crm.prospects.edit', compact('prospect', 'branches', 'followupTypes'));
    }

    // ══════════════════════════════════════════════════════
    //  UPDATE
    // ══════════════════════════════════════════════════════

    public function update(Request $request, int $id)
    {
        $prospect = Member::withoutGlobalScope(ExcludeProspectsScope::class)
            ->where('type', 'prospect')
            ->findOrFail($id);

        $validated = $request->validate([
            'branch_id'       => 'required|exists:branches,id',
            'first_name'      => 'required|string|max:100',
            'last_name'       => 'required|string|max:100',
            'phone'           => 'required|string|max:20',
            'address'         => 'required|string|max:255',
            'phone2'          => 'nullable|string|max:20',
            'whatsapp'        => 'nullable|string|max:20',
            'email'           => 'nullable|email|max:150',
            'gender'          => 'nullable|in:male,female',
            'notes'           => 'nullable|string|max:1000',

            // متابعة اختيارية (datetime-local)
            'create_followup'   => 'nullable|boolean',
            'followup_type'     => [
                'nullable',
                'required_if:create_followup,1',
                Rule::in(array_keys(CrmFollowup::typeLabels())),
            ],
            'followup_notes'    => 'nullable|string|max:500',
            'followup_datetime' => 'nullable|required_if:create_followup,1|date_format:Y-m-d\TH:i|after:now',
        ]);

        $prospect->update([
            'branch_id'   => $validated['branch_id'],
            'first_name'  => $validated['first_name'],
            'last_name'   => $validated['last_name'],
            'phone'       => $validated['phone'],
            'address'     => $validated['address'],
            'phone2'      => $validated['phone2']   ?? null,
            'whatsapp'    => $validated['whatsapp'] ?? null,
            'email'       => $validated['email']    ?? null,
            'gender'      => $validated['gender']   ?? null,
            'notes'       => $validated['notes']    ?? null,
            'user_update' => Auth::id(),
        ]);

        if ($request->boolean('create_followup') && $request->filled('followup_type')) {
            $followupAt = Carbon::createFromFormat('Y-m-d\TH:i', $request->followup_datetime);

            CrmFollowup::create([
                'member_id'      => $prospect->id,
                'branch_id'      => $prospect->branch_id,
                'type'           => $request->followup_type,
                'status'         => 'pending',
                'priority'       => 'medium',
                'notes'          => $request->followup_notes ?? 'متابعة مضافة عند التعديل',
                'next_action_at' => $followupAt,
                'created_by'     => Auth::id(),
                'updated_by'     => Auth::id(),
            ]);

            return redirect()
                ->route('crm.prospects.show', $prospect->id)
                ->with('success', 'تم تحديث البيانات وجدولة المتابعة بنجاح');
        }

        return redirect()
            ->route('crm.prospects.show', $prospect->id)
            ->with('success', 'تم تحديث بيانات العضو المحتمل بنجاح');
    }

    // ══════════════════════════════════════════════════════
    //  DESTROY
    // ══════════════════════════════════════════════════════

    public function destroy(int $id)
    {
        $prospect = Member::withoutGlobalScope(ExcludeProspectsScope::class)
            ->where('type', 'prospect')
            ->findOrFail($id);

        $prospect->delete();

        return redirect()
            ->route('crm.prospects.index')
            ->with('success', 'تم حذف العضو المحتمل بنجاح');
    }

    // ══════════════════════════════════════════════════════
    //  CONVERT → MEMBER
    // ══════════════════════════════════════════════════════

    public function convert(int $id)
    {
        $prospect = Member::withoutGlobalScope(ExcludeProspectsScope::class)
            ->where('type', 'prospect')
            ->findOrFail($id);

        DB::transaction(function () use ($prospect) {
            $prospect->convertToMember();

            $prospect->update([
                'member_code' => $this->generateMemberCode($prospect),
                'user_update' => Auth::id(),
            ]);

            CrmFollowup::where('member_id', $prospect->id)
                ->where('status', 'pending')
                ->update([
                    'status'     => 'done',
                    'result'     => 'converted',
                    'updated_by' => Auth::id(),
                    'updated_at' => now(),
                ]);

            $firstType = collect(CrmFollowup::getTypes())->keys()->first();

            CrmFollowup::create([
                'member_id'      => $prospect->id,
                'branch_id'      => $prospect->branch_id,
                'type'           => $firstType,
                'status'         => 'done',
                'priority'       => 'medium',
                'result'         => 'converted',
                'notes'          => 'تم تحويل العضو المحتمل إلى عضو فعلي بنجاح',
                'next_action_at' => now(),
                'created_by'     => Auth::id(),
                'updated_by'     => Auth::id(),
            ]);
        });

        if (request()->expectsJson()) {
            return response()->json([
                'success'  => true,
                'message'  => "تم تحويل {$prospect->full_name} إلى عضو فعلي بنجاح",
                'redirect' => route('crm.members.show', $prospect->id),
            ]);
        }

        return redirect()
            ->route('crm.members.show', $prospect->id)
            ->with('success', "تم تحويل {$prospect->full_name} إلى عضو فعلي — يمكنك الآن إضافة اشتراك");
    }

    // ══════════════════════════════════════════════════════
    //  DISQUALIFY
    // ══════════════════════════════════════════════════════

    public function disqualify(Request $request, int $id)
    {
        $prospect = Member::withoutGlobalScope(ExcludeProspectsScope::class)
            ->where('type', 'prospect')
            ->findOrFail($id);

        $request->validate([
            'reason' => 'nullable|string|max:500',
        ]);

        DB::transaction(function () use ($prospect, $request) {
            $reason = $request->reason ?? 'غير محدد';

            CrmFollowup::where('member_id', $prospect->id)
                ->where('status', 'pending')
                ->update([
                    'status'     => 'cancelled',
                    'result'     => 'not_interested',
                    'updated_by' => Auth::id(),
                    'updated_at' => now(),
                ]);

            $prospect->update([
                'notes'       => trim(($prospect->notes ?? '') . "\nسبب الإلغاء: {$reason}"),
                'user_update' => Auth::id(),
            ]);

            $prospect->delete();
        });

        return redirect()
            ->route('crm.prospects.index')
            ->with('info', 'تم إلغاء العضو المحتمل وإغلاق متابعاته');
    }

    // ══════════════════════════════════════════════════════
    //  IMPORT FORM
    // ══════════════════════════════════════════════════════

    public function importForm()
    {
        $followupTypes = CrmFollowup::typeLabels();
        return view('crm.prospects.import', compact('followupTypes'));
    }

    // ══════════════════════════════════════════════════════
    //  IMPORT STORE
    // ══════════════════════════════════════════════════════

    public function importStore(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls|max:5120',

            'create_followup'   => 'nullable|boolean',
            'followup_type'     => [
                'nullable',
                'required_if:create_followup,1',
                Rule::in(array_keys(CrmFollowup::typeLabels())),
            ],
            'followup_datetime' => 'nullable|required_if:create_followup,1|date_format:Y-m-d\TH:i|after:now',
        ]);

        try {
            $import = new ProspectsImport(
                $request->boolean('create_followup'),
                $request->input('followup_type'),
                $request->input('followup_datetime'),
                Auth::id()
            );

            Excel::import($import, $request->file('file'));

            $stats = $import->getStats();

            return redirect()
                ->route('crm.prospects.index')
                ->with('success', "تم رفع {$stats['success']} عضو محتمل بنجاح. تم تجاهل {$stats['skipped']} صف.");
        } catch (\Exception $e) {
            return back()->with('error', 'حدث خطأ أثناء معالجة الملف: ' . $e->getMessage());
        }
    }

    // ══════════════════════════════════════════════════════
    //  DOWNLOAD TEMPLATE
    // ══════════════════════════════════════════════════════

    public function downloadTemplate()
    {
        $headers = [
            'branch_id', 'first_name', 'last_name', 'phone', 'address',
            'phone2', 'whatsapp', 'email', 'gender', 'notes',
        ];

        $sampleData = [
            [1, 'أحمد', 'محمد', '01012345678', 'المعادي، القاهرة', '01098765432', '01012345678', 'ahmed@example.com', 'male', 'عرف عنا من الفيسبوك'],
            [1, 'فاطمة', 'علي', '01123456789', 'المهندسين، الجيزة', '', '01123456789', 'fatma@example.com', 'female', ''],
        ];

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $sheet->fromArray($headers, null, 'A1');
        $sheet->getStyle('A1:J1')->getFont()->setBold(true);
        $sheet->getStyle('A1:J1')->getFill()
            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
            ->getStartColor()->setARGB('FFE0E0E0');

        $sheet->fromArray($sampleData, null, 'A2');

        foreach (range('A', 'J') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $writer   = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $fileName = 'prospects_template_' . date('Y-m-d') . '.xlsx';

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $fileName . '"');
        header('Cache-Control: max-age=0');

        $writer->save('php://output');
        exit;
    }

    // ══════════════════════════════════════════════════════
    //  PRIVATE — توليد كود العضو عند التحويل فقط
    // ══════════════════════════════════════════════════════

    private function generateMemberCode(Member $member): string
    {
        $time = $member->created_at
            ? $member->created_at->format('His')
            : Carbon::now()->format('His');

        return intval($member->branch_id) . intval($member->id) . $time;
    }
}
