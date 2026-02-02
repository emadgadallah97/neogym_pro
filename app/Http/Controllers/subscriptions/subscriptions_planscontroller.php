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
            'daily',
            'weekly',
            'monthly',
            'quarterly',
            'semi_yearly',
            'yearly',
            'other',
        ];
    }

    private function weekDays()
    {
        return [
            'saturday',
            'sunday',
            'monday',
            'tuesday',
            'wednesday',
            'thursday',
            'friday',
        ];
    }

    public function index()
    {
        $SubscriptionsPlans = subscriptions_plan::with(['type'])->orderByDesc('id')->get();
        return view('subscriptions.programs.subscriptions_plans.index', compact('SubscriptionsPlans'));
    }

    public function create()
    {
        $SubscriptionsTypes = subscriptions_type::where('status', 1)->orderByDesc('id')->get();

        $Branches = DB::table('branches')
            ->where('status', 1)
            ->orderByDesc('id')
            ->get();

        $Coaches = DB::table('employees')
            ->where('status', 1)
            ->where('is_coach', 1)
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get();

        $PeriodTypes = $this->periodTypes();
        $WeekDays = $this->weekDays();

        return view('subscriptions.programs.subscriptions_plans.create', compact(
            'SubscriptionsTypes',
            'Branches',
            'Coaches',
            'PeriodTypes',
            'WeekDays'
        ));
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name_ar' => 'required|string|max:255',
            'name_en' => 'required|string|max:255',
            'subscriptions_type_id' => 'required|integer|exists:subscriptions_types,id',

            'sessions_period_type' => ['required', Rule::in($this->periodTypes())],
            'sessions_period_other_label' => 'nullable|string|max:255',

            'sessions_count' => 'required|integer|min:1',
            'duration_days' => 'required|integer|min:1',

            'allowed_training_days' => 'required|array|min:1',
            'allowed_training_days.*' => ['required', Rule::in($this->weekDays())],

            'allow_guest' => 'nullable',
            'guest_people_count' => 'nullable|integer|min:1',
            'guest_times_count' => 'nullable|integer|min:1',
            'guest_allowed_days' => 'nullable|array|min:1',
            'guest_allowed_days.*' => ['required', Rule::in($this->weekDays())],

            'notify_before_end' => 'nullable',
            'notify_days_before_end' => 'nullable|integer|min:1',

            'description' => 'nullable|string',
            'notes' => 'nullable|string',
            'status' => 'nullable',

            'branches' => 'required|array|min:1',
            'branches.*' => [
                'required',
                'integer',
                Rule::exists('branches', 'id')->where(function ($q) {
                    $q->where('status', 1);
                }),
            ],

            'pricing' => 'required|array',
        ]);

        $validator->after(function ($validator) use ($request) {

            if ($request->sessions_period_type == 'other' && empty($request->sessions_period_other_label)) {
                $validator->errors()->add('sessions_period_other_label', trans('subscriptions.sessions_period_other_required'));
            }

            $allow_guest = $request->has('allow_guest');
            if ($allow_guest) {
                if (empty($request->guest_people_count)) {
                    $validator->errors()->add('guest_people_count', trans('subscriptions.guest_people_count_required'));
                }
                if (empty($request->guest_times_count)) {
                    $validator->errors()->add('guest_times_count', trans('subscriptions.guest_times_count_required'));
                }
                if (empty($request->guest_allowed_days) || !is_array($request->guest_allowed_days)) {
                    $validator->errors()->add('guest_allowed_days', trans('subscriptions.guest_allowed_days_required'));
                }
            }

            $notify = $request->has('notify_before_end');
            if ($notify && empty($request->notify_days_before_end)) {
                $validator->errors()->add('notify_days_before_end', trans('subscriptions.notify_days_before_end_required'));
            }

            // Unique plan name (ar/en) inside JSON
            $exists_ar = subscriptions_plan::whereRaw(
                "json_unquote(json_extract(`name`, '$.\"ar\"')) = ?",
                [$request->name_ar]
            )->exists();

            if ($exists_ar) {
                $validator->errors()->add('name_ar', trans('subscriptions.name_ar_unique'));
            }

            $exists_en = subscriptions_plan::whereRaw(
                "json_unquote(json_extract(`name`, '$.\"en\"')) = ?",
                [$request->name_en]
            )->exists();

            if ($exists_en) {
                $validator->errors()->add('name_en', trans('subscriptions.name_en_unique'));
            }

            // Pricing validation per branch
            if (is_array($request->branches)) {
                foreach ($request->branches as $branch_id) {
                    $p = $request->pricing[$branch_id] ?? null;
                    if (!$p) {
                        $validator->errors()->add('pricing', trans('subscriptions.pricing_required_for_branch') . ' #' . $branch_id);
                        continue;
                    }

                    if (!isset($p['price_without_trainer']) || $p['price_without_trainer'] === '') {
                        $validator->errors()->add('pricing', trans('subscriptions.price_without_trainer_required') . ' #' . $branch_id);
                    }

                    if (!isset($p['trainer_pricing_mode']) || !in_array($p['trainer_pricing_mode'], ['uniform', 'per_trainer', 'exceptions'])) {
                        $validator->errors()->add('pricing', trans('subscriptions.trainer_pricing_mode_required') . ' #' . $branch_id);
                        continue;
                    }

                    if ($p['trainer_pricing_mode'] == 'uniform') {
                        if (!isset($p['trainer_uniform_price']) || $p['trainer_uniform_price'] === '') {
                            $validator->errors()->add('pricing', trans('subscriptions.trainer_uniform_price_required') . ' #' . $branch_id);
                        }
                    }

                    if ($p['trainer_pricing_mode'] == 'exceptions') {
                        if (!isset($p['trainer_default_price']) || $p['trainer_default_price'] === '') {
                            $validator->errors()->add('pricing', trans('subscriptions.trainer_default_price_required') . ' #' . $branch_id);
                        }
                    }

                    if ($p['trainer_pricing_mode'] == 'per_trainer') {
                        $coaches = $request->coaches[$branch_id] ?? [];
                        $has_included = false;

                        if (is_array($coaches)) {
                            foreach ($coaches as $coach_id => $row) {
                                $included = isset($row['is_included']) ? (int)$row['is_included'] : 0;
                                if ($included === 1) {
                                    $has_included = true;
                                    if (!isset($row['price']) || $row['price'] === '') {
                                        $validator->errors()->add(
                                            'pricing',
                                            trans('subscriptions.coach_price_required') . ' (branch #' . $branch_id . ', coach #' . $coach_id . ')'
                                        );
                                    }
                                }
                            }
                        }

                        if (!$has_included) {
                            $validator->errors()->add('pricing', trans('subscriptions.at_least_one_coach_required') . ' #' . $branch_id);
                        }
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

            // Generate next numeric code starting from 100
            $max_code = subscriptions_plan::withTrashed()->lockForUpdate()->max('code');
            $next_code = max((int)$max_code, 99) + 1;

            $Plan = subscriptions_plan::create([
                'code' => $next_code,
                'subscriptions_type_id' => $validated['subscriptions_type_id'],
                'name' => [
                    'ar' => $validated['name_ar'],
                    'en' => $validated['name_en'],
                ],
                'sessions_period_type' => $validated['sessions_period_type'],
                'sessions_period_other_label' => $validated['sessions_period_type'] == 'other' ? $validated['sessions_period_other_label'] : null,
                'sessions_count' => $validated['sessions_count'],
                'duration_days' => $validated['duration_days'],
                'allowed_training_days' => $validated['allowed_training_days'],

                'allow_guest' => $request->has('allow_guest') ? 1 : 0,
                'guest_people_count' => $request->has('allow_guest') ? $validated['guest_people_count'] : null,
                'guest_times_count' => $request->has('allow_guest') ? $validated['guest_times_count'] : null,
                'guest_allowed_days' => $request->has('allow_guest') ? ($validated['guest_allowed_days'] ?? []) : null,

                'notify_before_end' => $request->has('notify_before_end') ? 1 : 0,
                'notify_days_before_end' => $request->has('notify_before_end') ? $validated['notify_days_before_end'] : null,

                'description' => $validated['description'] ?? null,
                'notes' => $validated['notes'] ?? null,

                'status' => $request->has('status') ? 1 : 0,
                'created_by' => Auth::id(),
                'updated_by' => Auth::id(),
            ]);

            // Pivot branches
            foreach ($validated['branches'] as $branch_id) {
                subscriptions_plan_branch::create([
                    'subscriptions_plan_id' => $Plan->id,
                    'branch_id' => $branch_id,
                ]);
            }

            // Prices per branch + coach pricing per branch
            foreach ($validated['branches'] as $branch_id) {

                $p = $request->pricing[$branch_id];

                subscriptions_plan_branch_price::create([
                    'subscriptions_plan_id' => $Plan->id,
                    'branch_id' => $branch_id,
                    'price_without_trainer' => $p['price_without_trainer'],
                    'trainer_pricing_mode' => $p['trainer_pricing_mode'],
                    'trainer_uniform_price' => $p['trainer_pricing_mode'] == 'uniform' ? ($p['trainer_uniform_price'] ?? null) : null,
                    'trainer_default_price' => $p['trainer_pricing_mode'] == 'exceptions' ? ($p['trainer_default_price'] ?? null) : null,
                ]);

                $coaches = $request->coaches[$branch_id] ?? [];

                if (is_array($coaches)) {
                    foreach ($coaches as $coach_id => $row) {

                        $is_included = isset($row['is_included']) ? (int)$row['is_included'] : 0;
                        $price = $row['price'] ?? null;

                        // Exclusions always stored
                        if ($is_included === 0) {
                            subscriptions_plan_branch_coach_price::updateOrCreate(
                                [
                                    'subscriptions_plan_id' => $Plan->id,
                                    'branch_id' => $branch_id,
                                    'employee_id' => $coach_id,
                                ],
                                [
                                    'is_included' => 0,
                                    'price' => null,
                                ]
                            );
                            continue;
                        }

                        // Included coaches:
                        if ($p['trainer_pricing_mode'] == 'per_trainer') {
                            subscriptions_plan_branch_coach_price::updateOrCreate(
                                [
                                    'subscriptions_plan_id' => $Plan->id,
                                    'branch_id' => $branch_id,
                                    'employee_id' => $coach_id,
                                ],
                                [
                                    'is_included' => 1,
                                    'price' => $price,
                                ]
                            );
                        }

                        if ($p['trainer_pricing_mode'] == 'exceptions') {
                            // store only overrides or exclusions
                            if ($price !== null && $price !== '') {
                                subscriptions_plan_branch_coach_price::updateOrCreate(
                                    [
                                        'subscriptions_plan_id' => $Plan->id,
                                        'branch_id' => $branch_id,
                                        'employee_id' => $coach_id,
                                    ],
                                    [
                                        'is_included' => 1,
                                        'price' => $price,
                                    ]
                                );
                            }
                        }

                        // uniform: ignore included overrides, only exclusions are stored
                    }
                }
            }
        });

        return redirect()->route('subscriptions_plans.index')->with('success', trans('subscriptions.added_successfully'));
    }

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

        $coaches = DB::table('employees')
            ->where('status', 1)
            ->where('is_coach', 1)
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get();

        $coach_rows = subscriptions_plan_branch_coach_price::where('subscriptions_plan_id', $Plan->id)->get();
        $coach_map = [];
        foreach ($coach_rows as $r) {
            $coach_map[$r->branch_id][$r->employee_id] = [
                'is_included' => (int)$r->is_included,
                'price' => $r->price,
            ];
        }

        $Branches = [];
        foreach ($branches_db as $b) {
            $decoded = json_decode($b->name, true);
            $branch_name = is_array($decoded) ? $decoded : ['ar' => $b->name, 'en' => $b->name];

            $bp = $prices[$b->id] ?? null;
            $trainer_mode = $bp ? $bp->trainer_pricing_mode : 'uniform';

            $coaches_arr = [];
            foreach ($coaches as $c) {
                $saved = $coach_map[$b->id][$c->id] ?? null;

                $is_included = $saved ? (int)$saved['is_included'] : 1;
                $price = $saved ? $saved['price'] : null;

                $coaches_arr[] = [
                    'employee_id' => $c->id,
                    'name' => trim(($c->first_name ?? '') . ' ' . ($c->last_name ?? '')),
                    'is_included' => $is_included == 1,
                    'price' => $price,
                ];
            }

            $Branches[] = [
                'branch_id' => $b->id,
                'name' => $branch_name,
                'price_without_trainer' => $bp ? $bp->price_without_trainer : null,
                'trainer_pricing_mode' => $trainer_mode,
                'trainer_uniform_price' => $bp ? $bp->trainer_uniform_price : null,
                'trainer_default_price' => $bp ? $bp->trainer_default_price : null,
                'coaches' => $coaches_arr,
            ];
        }

        return view('subscriptions.programs.subscriptions_plans.show', compact('Plan', 'Branches'));
    }

    public function edit($id)
    {
        $Plan = subscriptions_plan::with(['type'])->findOrFail($id);

        $SubscriptionsTypes = subscriptions_type::where('status', 1)->orderByDesc('id')->get();
        $Branches = DB::table('branches')->where('status', 1)->orderByDesc('id')->get();
        $Coaches = DB::table('employees')
            ->where('status', 1)
            ->where('is_coach', 1)
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get();

        $SelectedBranches = DB::table('subscriptions_plan_branches')
            ->where('subscriptions_plan_id', $Plan->id)
            ->pluck('branch_id')
            ->toArray();

        $BranchPrices = subscriptions_plan_branch_price::where('subscriptions_plan_id', $Plan->id)
            ->get()
            ->keyBy('branch_id');

        $CoachPrices = subscriptions_plan_branch_coach_price::where('subscriptions_plan_id', $Plan->id)
            ->get();

        $CoachPricesMap = [];
        foreach ($CoachPrices as $row) {
            $CoachPricesMap[$row->branch_id][$row->employee_id] = [
                'is_included' => (int)$row->is_included,
                'price' => $row->price,
            ];
        }

        $PeriodTypes = $this->periodTypes();
        $WeekDays = $this->weekDays();

        return view('subscriptions.programs.subscriptions_plans.edit', compact(
            'Plan',
            'SubscriptionsTypes',
            'Branches',
            'Coaches',
            'SelectedBranches',
            'BranchPrices',
            'CoachPricesMap',
            'PeriodTypes',
            'WeekDays'
        ));
    }

    public function update(Request $request, $id)
    {
        // Fix wrong route param like /subscriptions_plans/test
        $plan_id = is_numeric($id) ? (int)$id : (int)$request->id;
        $Plan = subscriptions_plan::findOrFail($plan_id);

        $validator = Validator::make($request->all(), [
            'id' => 'required|integer|exists:subscriptions_plans,id',

            'name_ar' => 'required|string|max:255',
            'name_en' => 'required|string|max:255',
            'subscriptions_type_id' => 'required|integer|exists:subscriptions_types,id',

            'sessions_period_type' => ['required', Rule::in($this->periodTypes())],
            'sessions_period_other_label' => 'nullable|string|max:255',

            'sessions_count' => 'required|integer|min:1',
            'duration_days' => 'required|integer|min:1',

            'allowed_training_days' => 'required|array|min:1',
            'allowed_training_days.*' => ['required', Rule::in($this->weekDays())],

            'allow_guest' => 'nullable',
            'guest_people_count' => 'nullable|integer|min:1',
            'guest_times_count' => 'nullable|integer|min:1',
            'guest_allowed_days' => 'nullable|array|min:1',
            'guest_allowed_days.*' => ['required', Rule::in($this->weekDays())],

            'notify_before_end' => 'nullable',
            'notify_days_before_end' => 'nullable|integer|min:1',

            'description' => 'nullable|string',
            'notes' => 'nullable|string',
            'status' => 'nullable',

            'branches' => 'required|array|min:1',
            'branches.*' => [
                'required',
                'integer',
                Rule::exists('branches', 'id')->where(function ($q) {
                    $q->where('status', 1);
                }),
            ],

            'pricing' => 'required|array',
        ]);

        $validator->after(function ($validator) use ($request, $Plan) {

            if ($request->sessions_period_type == 'other' && empty($request->sessions_period_other_label)) {
                $validator->errors()->add('sessions_period_other_label', trans('subscriptions.sessions_period_other_required'));
            }

            $allow_guest = $request->has('allow_guest');
            if ($allow_guest) {
                if (empty($request->guest_people_count)) {
                    $validator->errors()->add('guest_people_count', trans('subscriptions.guest_people_count_required'));
                }
                if (empty($request->guest_times_count)) {
                    $validator->errors()->add('guest_times_count', trans('subscriptions.guest_times_count_required'));
                }
                if (empty($request->guest_allowed_days) || !is_array($request->guest_allowed_days)) {
                    $validator->errors()->add('guest_allowed_days', trans('subscriptions.guest_allowed_days_required'));
                }
            }

            $notify = $request->has('notify_before_end');
            if ($notify && empty($request->notify_days_before_end)) {
                $validator->errors()->add('notify_days_before_end', trans('subscriptions.notify_days_before_end_required'));
            }

            // Unique plan name (ar/en) inside JSON (ignore current)
            $exists_ar = subscriptions_plan::where('id', '!=', $Plan->id)
                ->whereRaw("json_unquote(json_extract(`name`, '$.\"ar\"')) = ?", [$request->name_ar])
                ->exists();
            if ($exists_ar) {
                $validator->errors()->add('name_ar', trans('subscriptions.name_ar_unique'));
            }

            $exists_en = subscriptions_plan::where('id', '!=', $Plan->id)
                ->whereRaw("json_unquote(json_extract(`name`, '$.\"en\"')) = ?", [$request->name_en])
                ->exists();
            if ($exists_en) {
                $validator->errors()->add('name_en', trans('subscriptions.name_en_unique'));
            }

            // Pricing validation per branch
            if (is_array($request->branches)) {
                foreach ($request->branches as $branch_id) {
                    $p = $request->pricing[$branch_id] ?? null;
                    if (!$p) {
                        $validator->errors()->add('pricing', trans('subscriptions.pricing_required_for_branch') . ' #' . $branch_id);
                        continue;
                    }

                    if (!isset($p['price_without_trainer']) || $p['price_without_trainer'] === '') {
                        $validator->errors()->add('pricing', trans('subscriptions.price_without_trainer_required') . ' #' . $branch_id);
                    }

                    if (!isset($p['trainer_pricing_mode']) || !in_array($p['trainer_pricing_mode'], ['uniform', 'per_trainer', 'exceptions'])) {
                        $validator->errors()->add('pricing', trans('subscriptions.trainer_pricing_mode_required') . ' #' . $branch_id);
                        continue;
                    }

                    if ($p['trainer_pricing_mode'] == 'uniform') {
                        if (!isset($p['trainer_uniform_price']) || $p['trainer_uniform_price'] === '') {
                            $validator->errors()->add('pricing', trans('subscriptions.trainer_uniform_price_required') . ' #' . $branch_id);
                        }
                    }

                    if ($p['trainer_pricing_mode'] == 'exceptions') {
                        if (!isset($p['trainer_default_price']) || $p['trainer_default_price'] === '') {
                            $validator->errors()->add('pricing', trans('subscriptions.trainer_default_price_required') . ' #' . $branch_id);
                        }
                    }

                    if ($p['trainer_pricing_mode'] == 'per_trainer') {
                        $coaches = $request->coaches[$branch_id] ?? [];
                        $has_included = false;
                        if (is_array($coaches)) {
                            foreach ($coaches as $coach_id => $row) {
                                $included = isset($row['is_included']) ? (int)$row['is_included'] : 0;
                                if ($included === 1) {
                                    $has_included = true;
                                    if (!isset($row['price']) || $row['price'] === '') {
                                        $validator->errors()->add('pricing', trans('subscriptions.coach_price_required') . ' (branch #' . $branch_id . ', coach #' . $coach_id . ')');
                                    }
                                }
                            }
                        }
                        if (!$has_included) {
                            $validator->errors()->add('pricing', trans('subscriptions.at_least_one_coach_required') . ' #' . $branch_id);
                        }
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

            $Plan->update([
                'subscriptions_type_id' => $validated['subscriptions_type_id'],
                'name' => [
                    'ar' => $validated['name_ar'],
                    'en' => $validated['name_en'],
                ],
                'sessions_period_type' => $validated['sessions_period_type'],
                'sessions_period_other_label' => $validated['sessions_period_type'] == 'other' ? $validated['sessions_period_other_label'] : null,
                'sessions_count' => $validated['sessions_count'],
                'duration_days' => $validated['duration_days'],
                'allowed_training_days' => $validated['allowed_training_days'],

                'allow_guest' => $request->has('allow_guest') ? 1 : 0,
                'guest_people_count' => $request->has('allow_guest') ? $validated['guest_people_count'] : null,
                'guest_times_count' => $request->has('allow_guest') ? $validated['guest_times_count'] : null,
                'guest_allowed_days' => $request->has('allow_guest') ? ($validated['guest_allowed_days'] ?? []) : null,

                'notify_before_end' => $request->has('notify_before_end') ? 1 : 0,
                'notify_days_before_end' => $request->has('notify_before_end') ? $validated['notify_days_before_end'] : null,

                'description' => $validated['description'] ?? null,
                'notes' => $validated['notes'] ?? null,

                'status' => $request->has('status') ? 1 : 0,
                'updated_by' => Auth::id(),
            ]);

            $new_branch_ids = $validated['branches'];
            $old_branch_ids = DB::table('subscriptions_plan_branches')
                ->where('subscriptions_plan_id', $Plan->id)
                ->pluck('branch_id')
                ->toArray();

            $removed = array_values(array_diff($old_branch_ids, $new_branch_ids));

            // remove pivot rows
            if (!empty($removed)) {
                DB::table('subscriptions_plan_branches')
                    ->where('subscriptions_plan_id', $Plan->id)
                    ->whereIn('branch_id', $removed)
                    ->delete();
            }

            // add new pivot rows
            $to_add = array_values(array_diff($new_branch_ids, $old_branch_ids));
            foreach ($to_add as $branch_id) {
                subscriptions_plan_branch::create([
                    'subscriptions_plan_id' => $Plan->id,
                    'branch_id' => $branch_id,
                ]);
            }

            // Soft delete prices for removed branches
            if (!empty($removed)) {
                subscriptions_plan_branch_price::where('subscriptions_plan_id', $Plan->id)
                    ->whereIn('branch_id', $removed)
                    ->delete();

                subscriptions_plan_branch_coach_price::where('subscriptions_plan_id', $Plan->id)
                    ->whereIn('branch_id', $removed)
                    ->delete();
            }

            // Upsert prices + coach pricing for current branches
            foreach ($new_branch_ids as $branch_id) {

                $p = $request->pricing[$branch_id];

                subscriptions_plan_branch_price::updateOrCreate(
                    [
                        'subscriptions_plan_id' => $Plan->id,
                        'branch_id' => $branch_id,
                    ],
                    [
                        'price_without_trainer' => $p['price_without_trainer'],
                        'trainer_pricing_mode' => $p['trainer_pricing_mode'],
                        'trainer_uniform_price' => $p['trainer_pricing_mode'] == 'uniform' ? ($p['trainer_uniform_price'] ?? null) : null,
                        'trainer_default_price' => $p['trainer_pricing_mode'] == 'exceptions' ? ($p['trainer_default_price'] ?? null) : null,
                    ]
                );

                $coaches = $request->coaches[$branch_id] ?? [];

                if (is_array($coaches)) {
                    foreach ($coaches as $coach_id => $row) {

                        $is_included = isset($row['is_included']) ? (int)$row['is_included'] : 0;
                        $price = $row['price'] ?? null;

                        if ($is_included === 0) {
                            subscriptions_plan_branch_coach_price::updateOrCreate(
                                [
                                    'subscriptions_plan_id' => $Plan->id,
                                    'branch_id' => $branch_id,
                                    'employee_id' => $coach_id,
                                ],
                                [
                                    'is_included' => 0,
                                    'price' => null,
                                ]
                            );
                            continue;
                        }

                        if ($p['trainer_pricing_mode'] == 'per_trainer') {
                            subscriptions_plan_branch_coach_price::updateOrCreate(
                                [
                                    'subscriptions_plan_id' => $Plan->id,
                                    'branch_id' => $branch_id,
                                    'employee_id' => $coach_id,
                                ],
                                [
                                    'is_included' => 1,
                                    'price' => $price,
                                ]
                            );
                        }

                        if ($p['trainer_pricing_mode'] == 'exceptions') {
                            if ($price !== null && $price !== '') {
                                subscriptions_plan_branch_coach_price::updateOrCreate(
                                    [
                                        'subscriptions_plan_id' => $Plan->id,
                                        'branch_id' => $branch_id,
                                        'employee_id' => $coach_id,
                                    ],
                                    [
                                        'is_included' => 1,
                                        'price' => $price,
                                    ]
                                );
                            } else {
                                $row_db = subscriptions_plan_branch_coach_price::where('subscriptions_plan_id', $Plan->id)
                                    ->where('branch_id', $branch_id)
                                    ->where('employee_id', $coach_id)
                                    ->first();

                                if ($row_db && (int)$row_db->is_included === 1) {
                                    $row_db->delete();
                                }
                            }
                        }

                        if ($p['trainer_pricing_mode'] == 'uniform') {
                            $row_db = subscriptions_plan_branch_coach_price::where('subscriptions_plan_id', $Plan->id)
                                ->where('branch_id', $branch_id)
                                ->where('employee_id', $coach_id)
                                ->first();

                            if ($row_db && (int)$row_db->is_included === 1) {
                                $row_db->delete();
                            }
                        }
                    }
                }
            }
        });

        return redirect()->route('subscriptions_plans.index')->with('success', trans('subscriptions.updated_successfully'));
    }

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
