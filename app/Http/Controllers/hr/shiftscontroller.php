<?php

namespace App\Http\Controllers\hr;

use App\Http\Controllers\Controller;
use App\Models\hr\HrShift;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class shiftscontroller extends Controller
{
    public function index()
    {
        $shifts = HrShift::orderByDesc('id')->get();
        return view('hr.shifts.index', compact('shifts'));
    }

    public function create()
    {
        return redirect()->route('shifts.index');
    }

    public function edit($id)
    {
        return redirect()->route('shifts.index');
    }

    public function show($id)
    {
        $shift = HrShift::findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $this->shiftDto($shift),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validateShift($request);

        try {
            $shift = new HrShift();
            $shift->name           = $data['name'];
            $shift->start_time     = $data['start_time'];
            $shift->end_time       = $data['end_time'];
            $shift->grace_minutes  = $data['grace_minutes'];
            $shift->min_half_hours = $data['min_half_hours'];
            $shift->min_full_hours = $data['min_full_hours'];

            $shift->sun = $data['sun'];
            $shift->mon = $data['mon'];
            $shift->tue = $data['tue'];
            $shift->wed = $data['wed'];
            $shift->thu = $data['thu'];
            $shift->fri = $data['fri'];
            $shift->sat = $data['sat'];

            $shift->status   = $data['status'];
            $shift->user_add = Auth::id();

            $shift->save();

            return response()->json([
                'success' => true,
                'message' => trans('hr.shift_saved_success') ?? 'تم حفظ الوردية بنجاح',
                'data'    => $this->shiftDto($shift),
            ]);
        } catch (\Throwable $e) {
            Log::error('shifts.store error', ['msg' => $e->getMessage(), 'file' => $e->getFile(), 'line' => $e->getLine()]);
            $msg = trans('hr.error_occurred') ?? 'حدث خطأ، يرجى المحاولة مجدداً';
            if (config('app.debug')) $msg = $e->getMessage();
            return response()->json(['success' => false, 'message' => $msg], 500);
        }
    }

    public function update(Request $request, $id)
    {
        $shift = HrShift::findOrFail($id);
        $data = $this->validateShift($request, $id);

        try {
            $shift->name           = $data['name'];
            $shift->start_time     = $data['start_time'];
            $shift->end_time       = $data['end_time'];
            $shift->grace_minutes  = $data['grace_minutes'];
            $shift->min_half_hours = $data['min_half_hours'];
            $shift->min_full_hours = $data['min_full_hours'];

            $shift->sun = $data['sun'];
            $shift->mon = $data['mon'];
            $shift->tue = $data['tue'];
            $shift->wed = $data['wed'];
            $shift->thu = $data['thu'];
            $shift->fri = $data['fri'];
            $shift->sat = $data['sat'];

            $shift->status = $data['status'];

            $shift->save();

            return response()->json([
                'success' => true,
                'message' => trans('hr.shift_updated_success') ?? 'تم تحديث الوردية بنجاح',
                'data'    => $this->shiftDto($shift),
            ]);
        } catch (\Throwable $e) {
            Log::error('shifts.update error', ['msg' => $e->getMessage(), 'file' => $e->getFile(), 'line' => $e->getLine()]);
            $msg = trans('hr.error_occurred') ?? 'حدث خطأ، يرجى المحاولة مجدداً';
            if (config('app.debug')) $msg = $e->getMessage();
            return response()->json(['success' => false, 'message' => $msg], 500);
        }
    }

    public function destroy($id)
    {
        $shift = HrShift::findOrFail($id);

        try {
            $shift->delete();

            return response()->json([
                'success' => true,
                'message' => trans('hr.shift_deleted_success') ?? 'تم حذف الوردية بنجاح',
                'data'    => ['id' => $id],
            ]);
        } catch (\Throwable $e) {
            Log::error('shifts.destroy error', ['msg' => $e->getMessage(), 'file' => $e->getFile(), 'line' => $e->getLine()]);
            $msg = trans('hr.error_occurred') ?? 'حدث خطأ، يرجى المحاولة مجدداً';
            if (config('app.debug')) $msg = $e->getMessage();
            return response()->json(['success' => false, 'message' => $msg], 500);
        }
    }

    // ───────────────────────────────

    private function validateShift(Request $request, $id = null): array
    {
        // ✅ لاحظ: أزلنا after:start_time لدعم الورديات الليلية، واستبدلناه بتحقق مخصص [web:154][web:162]
        $validator = Validator::make($request->all(), [
            'name'           => 'required|string|max:190',
            'start_time'     => 'required|date_format:H:i',
            'end_time'       => 'required|date_format:H:i',
            'grace_minutes'  => 'nullable|integer|min:0|max:1440',
            'min_half_hours' => 'nullable|numeric|min:0|max:24',
            'min_full_hours' => 'nullable|numeric|min:0|max:24|gte:min_half_hours',

            'sun' => 'nullable|boolean',
            'mon' => 'nullable|boolean',
            'tue' => 'nullable|boolean',
            'wed' => 'nullable|boolean',
            'thu' => 'nullable|boolean',
            'fri' => 'nullable|boolean',
            'sat' => 'nullable|boolean',

            'status' => 'required|boolean',
        ]);

        $validator->after(function ($v) use ($request) {
            $start = $request->input('start_time');
            $end   = $request->input('end_time');

            if (!$start || !$end) return;

            $durationMinutes = $this->shiftDurationMinutes($start, $end);

            // منع تساوي الوقتين (مدة 0)
            if ($durationMinutes <= 0) {
                $v->errors()->add('end_time', trans('hr.shift_time_invalid') ?? 'وقت النهاية غير صحيح');
                return;
            }

            // منع 24 ساعة كاملة (اختياري): نخليها أقل من 24 ساعة
            if ($durationMinutes >= 24 * 60) {
                $v->errors()->add('end_time', trans('hr.shift_duration_too_long') ?? 'مدة الوردية لا يجب أن تكون 24 ساعة أو أكثر');
                return;
            }

            $durationHours = round($durationMinutes / 60, 2);

            $minHalf = (float)($request->input('min_half_hours', 4));
            $minFull = (float)($request->input('min_full_hours', 8));

            if ($minHalf > $durationHours) {
                $v->errors()->add('min_half_hours', (trans('hr.min_half_exceeds_shift') ?? 'حد نصف يوم أكبر من مدة الوردية') . ' (' . $durationHours . 'h)');
            }

            if ($minFull > $durationHours) {
                $v->errors()->add('min_full_hours', (trans('hr.min_full_exceeds_shift') ?? 'حد اليوم الكامل أكبر من مدة الوردية') . ' (' . $durationHours . 'h)');
            }
        });

        $validated = $validator->validate();

        // defaults
        $validated['grace_minutes']  = (int)($validated['grace_minutes'] ?? 0);
        $validated['min_half_hours'] = (float)($validated['min_half_hours'] ?? 4);
        $validated['min_full_hours'] = (float)($validated['min_full_hours'] ?? 8);

        foreach (['sun','mon','tue','wed','thu','fri','sat'] as $d) {
            $validated[$d] = (bool)($request->input($d, 0));
        }

        $validated['status'] = (bool)$validated['status'];

        return $validated;
    }

    private function shiftDto(HrShift $s): array
    {
        $start = $this->formatTime($s->start_time);
        $end   = $this->formatTime($s->end_time);

        $durationMinutes = $this->shiftDurationMinutes($start, $end);
        $durationHours   = $durationMinutes > 0 ? round($durationMinutes / 60, 2) : 0;
        $isOvernight     = $this->isOvernight($start, $end) ? 1 : 0;

        return [
            'id'             => $s->id,
            'name'           => $s->name,
            'start_time'     => $start,
            'end_time'       => $end,
            'is_overnight'   => $isOvernight,
            'duration_hours' => $durationHours,

            'grace_minutes'  => (int)$s->grace_minutes,
            'min_half_hours' => (float)$s->min_half_hours,
            'min_full_hours' => (float)$s->min_full_hours,

            'sun' => (int)$s->sun, 'mon' => (int)$s->mon, 'tue' => (int)$s->tue, 'wed' => (int)$s->wed,
            'thu' => (int)$s->thu, 'fri' => (int)$s->fri, 'sat' => (int)$s->sat,

            'status'         => (int)$s->status,
            'created_at'     => $s->created_at ? $s->created_at->toDateTimeString() : null,
        ];
    }

    private function formatTime($value): string
    {
        if (!$value) return '';
        try {
            return Carbon::parse($value)->format('H:i');
        } catch (\Throwable $e) {
            return (string)$value;
        }
    }

    private function isOvernight(string $start, string $end): bool
    {
        if (!$start || !$end) return false;

        $s = Carbon::createFromFormat('H:i', $start);
        $e = Carbon::createFromFormat('H:i', $end);

        return $e->lessThanOrEqualTo($s);
    }

    private function shiftDurationMinutes(string $start, string $end): int
    {
        if (!$start || !$end) return 0;

        $s = Carbon::createFromFormat('H:i', $start);
        $e = Carbon::createFromFormat('H:i', $end);

        if ($e->lessThanOrEqualTo($s)) {
            $e->addDay(); // ✅ وردية ليلية: النهاية في اليوم التالي
        }

        return $e->diffInMinutes($s);
    }
}
