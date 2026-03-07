<?php

namespace App\Http\Controllers\subscriptions;

use App\Http\Controllers\Controller;
use App\Models\subscriptions\subscriptions_plan;
use App\Models\subscriptions\subscriptions_plan_branch;
use App\Models\subscriptions\subscriptions_plan_branch_price;
use App\Models\subscriptions\subscriptions_plan_branch_coach_price;
use App\Models\subscriptions\subscriptions_type;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class subscriptions_planscontroller extends Controller
{
    private function periodTypes()
    {
        return [
            'daily', 'weekly', 'monthly',
            'quarterly', 'semi_yearly', 'yearly', 'other',
        ];
    }

    private function weekDays()
    {
        return [
            'saturday', 'sunday', 'monday', 'tuesday',
            'wednesday', 'thursday', 'friday',
        ];
    }

    private function loadBranchCoachesMap($branchIds)
    {
        $branchIds = array_values(array_filter(array_map('intval', (array)$branchIds)));
        if (empty($branchIds)) return [];

        $rows = DB::table('employee_branch')
            ->join('employees', 'employees.id', '=', 'employee_branch.employee_id')
            ->whereIn('employee_branch.branch_id', $branchIds)
            ->whereNull('employees.deleted_at')
            ->where('employees.status', 1)
            ->where('employees.is_coach', 1)
            ->select([
                'employee_branch.branch_id',
                'employees.id',
                'employees.first_name',
                'employees.last_name',
            ])
            ->orderBy('employees.first_name')
            ->orderBy('employees.last_name')
            ->get();

        $map = [];
        foreach ($rows as $r) {
            $map[$r->branch_id][] = [
                'id'         => $r->id,
                'first_name' => $r->first_name,
                'last_name'  => $r->last_name,
            ];
        }

        foreach ($branchIds as $bid) {
            if (!isset($map[$bid])) $map[$bid] = [];
        }

        return $map;
    }

    // ─────────────────────────────────────────────────────────────
    // Index
    // ─────────────────────────────────────────────────────────────
    public function index()
    {
        $SubscriptionsPlans = subscriptions_plan::with(['type'])->orderByDesc('id')->get();
        return view('subscriptions.programs.subscriptions_plans.index', compact('SubscriptionsPlans'));
    }

    // ─────────────────────────────────────────────────────────────
    // Create
    // ─────────────────────────────────────────────────────────────
    public function create()
    {
        $SubscriptionsTypes = subscriptions_type::where('status', 1)->orderByDesc('id')->get();

        $Branches = DB::table('branches')
            ->where('status', 1)
            ->orderByDesc('id')
            ->get();

        $BranchCoachesMap = $this->loadBranchCoachesMap($Branches->pluck('id')->toArray());
        $PeriodTypes      = $this->periodTypes();
        $WeekDays         = $this->weekDays();

        return view('subscriptions.programs.subscriptions_plans.create', compact(
            'SubscriptionsTypes',
            'Branches',
            'BranchCoachesMap',
            'PeriodTypes',
            'WeekDays'
        ));
    }

    // ─────────────────────────────────────────────────────────────
    // Store
    // ─────────────────────────────────────────────────────────────
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name_ar'                    => 'required|string|max:255',
            'name_en'                    => 'required|string|max:255',
            'subscriptions_type_id'      => 'required|integer|exists:subscriptions_types,id',
            'sessions_period_type'       => ['required', Rule::in($this->periodTypes())],
            'sessions_period_other_label'=> 'nullable|string|max:255',
            'sessions_count'             => 'required|integer|min:1',
            'duration_days'              => 'required|integer|min:1',
            'allowed_training_days'      => 'required|array|min:1',
            'allowed_training_days.*'    => ['required', Rule::in($this->weekDays())],
            'allow_guest'                => 'nullable',
            'guest_people_count'         => 'nullable|integer|min:1',
            'guest_times_count'          => 'nullable|integer|min:1',
            'guest_allowed_days'         => 'nullable|array|min:1',
            'guest_allowed_days.*'       => ['required', Rule::in($this->weekDays())],
            'notify_before_end'          => 'nullable',
            'notify_days_before_end'     => 'nullable|integer|min:1',
            'allow_freeze'               => 'nullable',
            'max_freeze_days'            => 'nullable|integer|min:1|max:65535',
            'description'                => 'nullable|string',
            'notes'                      => 'nullable|string',
            'status'                     => 'nullable',
            'branches'                   => 'required|array|min:1',
            'branches.*'                 => [
                'required', 'integer',
                Rule::exists('branches', 'id')->where(fn($q) => $q->where('status', 1)),
            ],
            'pricing'                    => 'required|array',
        ]);

        $validator->after(function ($validator) use ($request) {

            if ($request->sessions_period_type == 'other' && empty($request->sessions_period_other_label)) {
                $validator->errors()->add('sessions_period_other_label', trans('subscriptions.sessions_period_other_required'));
            }

            $allow_guest = $request->has('allow_guest');
            if ($allow_guest) {
                if (empty($request->guest_people_count))
                    $validator->errors()->add('guest_people_count', trans('subscriptions.guest_people_count_required'));
                if (empty($request->guest_times_count))
                    $validator->errors()->add('guest_times_count', trans('subscriptions.guest_times_count_required'));
                if (empty($request->guest_allowed_days) || !is_array($request->guest_allowed_days))
                    $validator->errors()->add('guest_allowed_days', trans('subscriptions.guest_allowed_days_required'));
            }

            if ($request->has('notify_before_end') && empty($request->notify_days_before_end)) {
                $validator->errors()->add('notify_days_before_end', trans('subscriptions.notify_days_before_end_required'));
            }

            if ($request->has('allow_freeze') && empty($request->max_freeze_days)) {
                $validator->errors()->add('max_freeze_days', trans('subscriptions.max_freeze_days_required'));
            }

            // Unique name
            if (subscriptions_plan::whereRaw("json_unquote(json_extract(`name`, '$.\"ar\"')) = ?", [$request->name_ar])->exists()) {
                $validator->errors()->add('name_ar', trans('subscriptions.name_ar_unique'));
            }
            if (subscriptions_plan::whereRaw("json_unquote(json_extract(`name`, '$.\"en\"')) = ?", [$request->name_en])->exists()) {
                $validator->errors()->add('name_en', trans('subscriptions.name_en_unique'));
            }

            // ✅ التحقق من السعر الأساسي فقط لكل فرع
            if (is_array($request->branches)) {
                foreach ($request->branches as $branch_id) {
                    $p = $request->pricing[$branch_id] ?? null;

                    if (!$p) {
                        $validator->errors()->add('pricing', trans('subscriptions.pricing_required_for_branch') . ' #' . $branch_id);
                        continue;
                    }

                    if (!isset($p['base_price']) || $p['base_price'] === '') {
                        $validator->errors()->add('pricing', trans('subscriptions.price_without_trainer_required') . ' #' . $branch_id);
                    }
                }
            }
        });

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput()
                ->with('error', $validator->errors()->first());
        }

        $validated = $validator->validated();

        DB::transaction(function () use ($request, $validated) {

            $max_code  = subscriptions_plan::withTrashed()->lockForUpdate()->max('code');
            $next_code = max((int)$max_code, 99) + 1;

            $allow_guest  = $request->has('allow_guest');
            $notify       = $request->has('notify_before_end');
            $allow_freeze = $request->has('allow_freeze');

            $Plan = subscriptions_plan::create([
                'code'                        => $next_code,
                'subscriptions_type_id'       => $validated['subscriptions_type_id'],
                'name'                        => ['ar' => $validated['name_ar'], 'en' => $validated['name_en']],
                'sessions_period_type'        => $validated['sessions_period_type'],
                'sessions_period_other_label' => $validated['sessions_period_type'] == 'other' ? ($validated['sessions_period_other_label'] ?? null) : null,
                'sessions_count'              => $validated['sessions_count'],
                'duration_days'               => $validated['duration_days'],
                'allowed_training_days'       => $validated['allowed_training_days'],
                'allow_guest'                 => $allow_guest ? 1 : 0,
                'guest_people_count'          => $allow_guest ? ($validated['guest_people_count'] ?? null) : null,
                'guest_times_count'           => $allow_guest ? ($validated['guest_times_count'] ?? null) : null,
                'guest_allowed_days'          => $allow_guest ? ($validated['guest_allowed_days'] ?? []) : null,
                'notify_before_end'           => $notify ? 1 : 0,
                'notify_days_before_end'      => $notify ? ($validated['notify_days_before_end'] ?? null) : null,
                'allow_freeze'                => $allow_freeze ? 1 : 0,
                'max_freeze_days'             => $allow_freeze ? ($validated['max_freeze_days'] ?? null) : null,
                'description'                 => $validated['description'] ?? null,
                'notes'                       => $validated['notes'] ?? null,
                'status'                      => $request->has('status') ? 1 : 0,
                'created_by'                  => Auth::id(),
                'updated_by'                  => Auth::id(),
            ]);

            foreach ($validated['branches'] as $branch_id) {
                subscriptions_plan_branch::create([
                    'subscriptions_plan_id' => $Plan->id,
                    'branch_id'             => $branch_id,
                ]);
            }

            // ✅ حفظ السعر الأساسي فقط — بدون أي بيانات مدرب
            foreach ($validated['branches'] as $branch_id) {
                $p = $request->pricing[$branch_id];

                subscriptions_plan_branch_price::create([
                    'subscriptions_plan_id' => $Plan->id,
                    'branch_id'             => $branch_id,
                    'price_without_trainer' => $p['base_price'],   // ✅ map base_price → عمود DB
                    'is_private_coach'      => 0,
                    'trainer_pricing_mode'  => null,
                    'trainer_uniform_price' => null,
                    'trainer_default_price' => null,
                ]);
            }
        });

        return redirect()->route('subscriptions_plans.index')
            ->with('success', trans('subscriptions.added_successfully'));
    }

    // ─────────────────────────────────────────────────────────────
    // Show
    // ─────────────────────────────────────────────────────────────
    public function show($id)
    {
        $Plan = subscriptions_plan::with(['type'])->findOrFail($id);

        $branch_ids = DB::table('subscriptions_plan_branches')
            ->where('subscriptions_plan_id', $Plan->id)
            ->pluck('branch_id')
            ->toArray();

        $branches_db = DB::table('branches')
            ->whereIn('id', $branch_ids)
            ->orderBy('id')
            ->get();

        $prices = subscriptions_plan_branch_price::where('subscriptions_plan_id', $Plan->id)
            ->get()
            ->keyBy('branch_id');

        $BranchCoachesMap = $this->loadBranchCoachesMap($branch_ids);

        $coach_rows = subscriptions_plan_branch_coach_price::where('subscriptions_plan_id', $Plan->id)->get();
        $coach_map  = [];
        foreach ($coach_rows as $r) {
            $coach_map[$r->branch_id][$r->employee_id] = [
                'is_included' => (int)$r->is_included,
                'price'       => $r->price,
            ];
        }

        $Branches = [];
        foreach ($branches_db as $b) {
            $decoded     = json_decode($b->name, true);
            $branch_name = is_array($decoded) ? $decoded : ['ar' => $b->name, 'en' => $b->name];

            $bp          = $prices[$b->id] ?? null;
            $is_private  = $bp ? (int)($bp->is_private_coach ?? 0) : 0;
            $trainer_mode = $bp ? $bp->trainer_pricing_mode : null;

            $coaches_arr = [];
            foreach (($BranchCoachesMap[$b->id] ?? []) as $c) {
                $saved    = $coach_map[$b->id][$c['id']] ?? null;
                $coaches_arr[] = [
                    'employee_id' => $c['id'],
                    'name'        => trim(($c['first_name'] ?? '') . ' ' . ($c['last_name'] ?? '')),
                    'is_included' => $saved ? ((int)$saved['is_included'] == 1) : true,
                    'price'       => $saved ? $saved['price'] : null,
                ];
            }

            $Branches[] = [
                'branch_id'             => $b->id,
                'name'                  => $branch_name,
                'price_without_trainer' => $bp ? $bp->price_without_trainer : null,
                'is_private_coach'      => $is_private,
                'trainer_pricing_mode'  => $trainer_mode,
                'trainer_uniform_price' => $bp ? $bp->trainer_uniform_price : null,
                'trainer_default_price' => $bp ? $bp->trainer_default_price : null,
                'coaches'               => $coaches_arr,
            ];
        }

        return view('subscriptions.programs.subscriptions_plans.show', compact('Plan', 'Branches'));
    }

    // ─────────────────────────────────────────────────────────────
    // Edit
    // ─────────────────────────────────────────────────────────────
    public function edit($id)
    {
        $Plan = subscriptions_plan::with(['type'])->findOrFail($id);

        $SubscriptionsTypes = subscriptions_type::where('status', 1)->orderByDesc('id')->get();
        $Branches           = DB::table('branches')->where('status', 1)->orderByDesc('id')->get();

        $SelectedBranches = DB::table('subscriptions_plan_branches')
            ->where('subscriptions_plan_id', $Plan->id)
            ->pluck('branch_id')
            ->toArray();

        // ✅ BranchPrices يحتوي على base_price مُعاد تسميته لتوافق الفورم
        $BranchPricesRaw = subscriptions_plan_branch_price::where('subscriptions_plan_id', $Plan->id)
            ->get()
            ->keyBy('branch_id');

        // نحوّل price_without_trainer → base_price ليتوافق مع الفورم الجديد
        $BranchPrices = [];
        foreach ($BranchPricesRaw as $branchId => $row) {
            $BranchPrices[$branchId] = [
                'base_price' => $row->price_without_trainer,
            ];
        }

        $CoachPrices    = subscriptions_plan_branch_coach_price::where('subscriptions_plan_id', $Plan->id)->get();
        $CoachPricesMap = [];
        foreach ($CoachPrices as $row) {
            $CoachPricesMap[$row->branch_id][$row->employee_id] = [
                'is_included' => (int)$row->is_included,
                'price'       => $row->price,
            ];
        }

        $BranchCoachesMap = $this->loadBranchCoachesMap($SelectedBranches);
        $PeriodTypes      = $this->periodTypes();
        $WeekDays         = $this->weekDays();

        return view('subscriptions.programs.subscriptions_plans.edit', compact(
            'Plan',
            'SubscriptionsTypes',
            'Branches',
            'SelectedBranches',
            'BranchPrices',
            'CoachPricesMap',
            'BranchCoachesMap',
            'PeriodTypes',
            'WeekDays'
        ));
    }

    // ─────────────────────────────────────────────────────────────
    // Update
    // ─────────────────────────────────────────────────────────────
    public function update(Request $request, $id)
    {
        $plan_id = is_numeric($id) ? (int)$id : (int)$request->id;
        $Plan    = subscriptions_plan::findOrFail($plan_id);

        $validator = Validator::make($request->all(), [
            'id'                         => 'required|integer|exists:subscriptions_plans,id',
            'name_ar'                    => 'required|string|max:255',
            'name_en'                    => 'required|string|max:255',
            'subscriptions_type_id'      => 'required|integer|exists:subscriptions_types,id',
            'sessions_period_type'       => ['required', Rule::in($this->periodTypes())],
            'sessions_period_other_label'=> 'nullable|string|max:255',
            'sessions_count'             => 'required|integer|min:1',
            'duration_days'              => 'required|integer|min:1',
            'allowed_training_days'      => 'required|array|min:1',
            'allowed_training_days.*'    => ['required', Rule::in($this->weekDays())],
            'allow_guest'                => 'nullable',
            'guest_people_count'         => 'nullable|integer|min:1',
            'guest_times_count'          => 'nullable|integer|min:1',
            'guest_allowed_days'         => 'nullable|array|min:1',
            'guest_allowed_days.*'       => ['required', Rule::in($this->weekDays())],
            'notify_before_end'          => 'nullable',
            'notify_days_before_end'     => 'nullable|integer|min:1',
            'allow_freeze'               => 'nullable',
            'max_freeze_days'            => 'nullable|integer|min:1|max:65535',
            'description'                => 'nullable|string',
            'notes'                      => 'nullable|string',
            'status'                     => 'nullable',
            'branches'                   => 'required|array|min:1',
            'branches.*'                 => [
                'required', 'integer',
                Rule::exists('branches', 'id')->where(fn($q) => $q->where('status', 1)),
            ],
            'pricing'                    => 'required|array',
        ]);

        $validator->after(function ($validator) use ($request, $Plan) {

            if ($request->sessions_period_type == 'other' && empty($request->sessions_period_other_label)) {
                $validator->errors()->add('sessions_period_other_label', trans('subscriptions.sessions_period_other_required'));
            }

            $allow_guest = $request->has('allow_guest');
            if ($allow_guest) {
                if (empty($request->guest_people_count))
                    $validator->errors()->add('guest_people_count', trans('subscriptions.guest_people_count_required'));
                if (empty($request->guest_times_count))
                    $validator->errors()->add('guest_times_count', trans('subscriptions.guest_times_count_required'));
                if (empty($request->guest_allowed_days) || !is_array($request->guest_allowed_days))
                    $validator->errors()->add('guest_allowed_days', trans('subscriptions.guest_allowed_days_required'));
            }

            if ($request->has('notify_before_end') && empty($request->notify_days_before_end)) {
                $validator->errors()->add('notify_days_before_end', trans('subscriptions.notify_days_before_end_required'));
            }

            if ($request->has('allow_freeze') && empty($request->max_freeze_days)) {
                $validator->errors()->add('max_freeze_days', trans('subscriptions.max_freeze_days_required'));
            }

            // Unique name (ignore current)
            if (subscriptions_plan::where('id', '!=', $Plan->id)
                ->whereRaw("json_unquote(json_extract(`name`, '$.\"ar\"')) = ?", [$request->name_ar])
                ->exists()) {
                $validator->errors()->add('name_ar', trans('subscriptions.name_ar_unique'));
            }
            if (subscriptions_plan::where('id', '!=', $Plan->id)
                ->whereRaw("json_unquote(json_extract(`name`, '$.\"en\"')) = ?", [$request->name_en])
                ->exists()) {
                $validator->errors()->add('name_en', trans('subscriptions.name_en_unique'));
            }

            // ✅ التحقق من السعر الأساسي فقط
            if (is_array($request->branches)) {
                foreach ($request->branches as $branch_id) {
                    $p = $request->pricing[$branch_id] ?? null;

                    if (!$p) {
                        $validator->errors()->add('pricing', trans('subscriptions.pricing_required_for_branch') . ' #' . $branch_id);
                        continue;
                    }

                    if (!isset($p['base_price']) || $p['base_price'] === '') {
                        $validator->errors()->add('pricing', trans('subscriptions.price_without_trainer_required') . ' #' . $branch_id);
                    }
                }
            }
        });

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput()
                ->with('error', $validator->errors()->first());
        }

        $validated = $validator->validated();

        DB::transaction(function () use ($request, $validated, $Plan) {

            $allow_guest  = $request->has('allow_guest');
            $notify       = $request->has('notify_before_end');
            $allow_freeze = $request->has('allow_freeze');

            $Plan->update([
                'subscriptions_type_id'       => $validated['subscriptions_type_id'],
                'name'                        => ['ar' => $validated['name_ar'], 'en' => $validated['name_en']],
                'sessions_period_type'        => $validated['sessions_period_type'],
                'sessions_period_other_label' => $validated['sessions_period_type'] == 'other' ? ($validated['sessions_period_other_label'] ?? null) : null,
                'sessions_count'              => $validated['sessions_count'],
                'duration_days'               => $validated['duration_days'],
                'allowed_training_days'       => $validated['allowed_training_days'],
                'allow_guest'                 => $allow_guest ? 1 : 0,
                'guest_people_count'          => $allow_guest ? ($validated['guest_people_count'] ?? null) : null,
                'guest_times_count'           => $allow_guest ? ($validated['guest_times_count'] ?? null) : null,
                'guest_allowed_days'          => $allow_guest ? ($validated['guest_allowed_days'] ?? []) : null,
                'notify_before_end'           => $notify ? 1 : 0,
                'notify_days_before_end'      => $notify ? ($validated['notify_days_before_end'] ?? null) : null,
                'allow_freeze'                => $allow_freeze ? 1 : 0,
                'max_freeze_days'             => $allow_freeze ? ($validated['max_freeze_days'] ?? null) : null,
                'description'                 => $validated['description'] ?? null,
                'notes'                       => $validated['notes'] ?? null,
                'status'                      => $request->has('status') ? 1 : 0,
                'updated_by'                  => Auth::id(),
            ]);

            $new_branch_ids = $validated['branches'];
            $old_branch_ids = DB::table('subscriptions_plan_branches')
                ->where('subscriptions_plan_id', $Plan->id)
                ->pluck('branch_id')
                ->toArray();

            $removed = array_values(array_diff($old_branch_ids, $new_branch_ids));
            $to_add  = array_values(array_diff($new_branch_ids, $old_branch_ids));

            if (!empty($removed)) {
                DB::table('subscriptions_plan_branches')
                    ->where('subscriptions_plan_id', $Plan->id)
                    ->whereIn('branch_id', $removed)
                    ->delete();

                subscriptions_plan_branch_price::where('subscriptions_plan_id', $Plan->id)
                    ->whereIn('branch_id', $removed)->delete();

                subscriptions_plan_branch_coach_price::where('subscriptions_plan_id', $Plan->id)
                    ->whereIn('branch_id', $removed)->delete();
            }

            foreach ($to_add as $branch_id) {
                subscriptions_plan_branch::create([
                    'subscriptions_plan_id' => $Plan->id,
                    'branch_id'             => $branch_id,
                ]);
            }

            // ✅ حفظ السعر الأساسي فقط — بدون أي بيانات مدرب
            foreach ($new_branch_ids as $branch_id) {
                $p = $request->pricing[$branch_id];

                subscriptions_plan_branch_price::updateOrCreate(
                    [
                        'subscriptions_plan_id' => $Plan->id,
                        'branch_id'             => $branch_id,
                    ],
                    [
                        'price_without_trainer' => $p['base_price'],   // ✅ map base_price → عمود DB
                        'is_private_coach'      => 0,
                        'trainer_pricing_mode'  => null,
                        'trainer_uniform_price' => null,
                        'trainer_default_price' => null,
                    ]
                );

                // ✅ حذف أي أسعار مدربين قديمة إذا وُجدت
                subscriptions_plan_branch_coach_price::where('subscriptions_plan_id', $Plan->id)
                    ->where('branch_id', $branch_id)
                    ->delete();
            }
        });

        return redirect()->route('subscriptions_plans.index')
            ->with('success', trans('subscriptions.updated_successfully'));
    }

    // ─────────────────────────────────────────────────────────────
    // Destroy
    // ─────────────────────────────────────────────────────────────
    public function destroy(Request $request, $id)
    {
        $request->validate([
            'id' => 'required|integer|exists:subscriptions_plans,id',
        ]);

        $Plan = subscriptions_plan::findOrFail($request->id);

        DB::transaction(function () use ($Plan) {
            $Plan->delete();
            subscriptions_plan_branch_price::where('subscriptions_plan_id', $Plan->id)->delete();
            subscriptions_plan_branch_coach_price::where('subscriptions_plan_id', $Plan->id)->delete();
            DB::table('subscriptions_plan_branches')->where('subscriptions_plan_id', $Plan->id)->delete();
        });

        return redirect()->back()->with('success', trans('subscriptions.deleted_successfully'));
    }
}
