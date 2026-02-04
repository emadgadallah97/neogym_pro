<?php

namespace App\Http\Controllers\coupons_offers;

use App\Http\Controllers\Controller;
use App\Http\Requests\coupons_offers\CouponRequest;
use App\Models\coupons_offers\Coupon;
use App\Models\coupons_offers\CouponDuration;
use App\Services\coupons_offers\CouponEngine;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class couponscontroller extends Controller
{
    public function __construct()
    {
        // permissions...
    }

    public function index()
    {
        $Coupons = Coupon::query()
            ->withCount('usages')
            ->orderByDesc('id')
            ->get();

        return view('coupons_offers.coupons.index', compact('Coupons'));
    }

    private function membersList()
    {
        return DB::table('members')
            ->select([
                'id',
                DB::raw("CONCAT_WS(' ', member_code, '-', first_name, last_name) as name"),
            ])
            ->orderByDesc('id')
            ->limit(300)
            ->get();
    }

    private function activePlansList()
    {
        $q = DB::table('subscriptions_plans')->select(['id', 'name', 'code'])->orderByDesc('id');

        if (Schema::hasColumn('subscriptions_plans', 'deleted_at')) {
            $q->whereNull('deleted_at');
        }

        if (Schema::hasColumn('subscriptions_plans', 'status')) {
            $q->where('status', 1);
        }

        return $q->get();
    }

    private function activeTypesList()
    {
        $q = DB::table('subscriptions_types')->select(['id', 'name'])->orderByDesc('id');

        if (Schema::hasColumn('subscriptions_types', 'deleted_at')) {
            $q->whereNull('deleted_at');
        }

        if (Schema::hasColumn('subscriptions_types', 'status')) {
            $q->where('status', 1);
        }

        return $q->get();
    }

    private function activeBranchesList()
    {
        $q = DB::table('branches')->select(['id', 'name'])->orderByDesc('id');

        if (Schema::hasColumn('branches', 'deleted_at')) {
            $q->whereNull('deleted_at');
        }

        if (Schema::hasColumn('branches', 'status')) {
            $q->where('status', 1);
        }

        return $q->get();
    }

    public function create()
    {
        $Plans = $this->activePlansList();
        $Types = $this->activeTypesList();
        $Branches = $this->activeBranchesList();
        $Members = $this->membersList();

        return view('coupons_offers.coupons.create', compact('Plans', 'Types', 'Branches', 'Members'));
    }

    public function store(CouponRequest $request)
    {
        DB::beginTransaction();

        try {
            $coupon = new Coupon();

            $coupon->code = strtoupper(trim($request->code));

            $coupon->name = [
                'ar' => $request->name_ar,
                'en' => $request->name_en,
            ];

            $coupon->description = [
                'ar' => $request->description_ar,
                'en' => $request->description_en,
            ];

            $coupon->applies_to = $request->applies_to;

            $coupon->discount_type = $request->discount_type;
            $coupon->discount_value = $request->discount_value;

            $coupon->min_amount = $request->min_amount;
            $coupon->max_discount = $request->max_discount;

            $coupon->max_uses_total = $request->max_uses_total;
            $coupon->max_uses_per_member = $request->max_uses_per_member;

            $coupon->member_id = $request->member_id;

            $coupon->start_at = $request->start_at;
            $coupon->end_at = $request->end_at;

            $coupon->status = $request->status;

            $coupon->created_by = Auth::id();

            $coupon->save();

            $planIds = $request->subscriptions_plan_ids ?? [];
            $typeIds = $request->subscriptions_type_ids ?? [];
            $branchIds = $request->branch_ids ?? [];

            $coupon->plans()->sync($planIds);
            $coupon->types()->sync($typeIds);
            $coupon->branches()->sync($branchIds);

            $durationValues = $request->duration_values ?? [];
            $durationUnit = $request->duration_unit ?? null;

            CouponDuration::where('coupon_id', $coupon->id)->delete();

            if (!empty($durationValues) && $durationUnit) {
                foreach ($durationValues as $v) {
                    CouponDuration::create([
                        'coupon_id' => $coupon->id,
                        'duration_value' => (int)$v,
                        'duration_unit' => $durationUnit,
                    ]);
                }
            }

            DB::commit();

            return redirect()->route('coupons.index')->with('success', trans('coupons_offers.saved_successfully'));
        } catch (\Throwable $e) {
            DB::rollBack();
            return redirect()->back()->withInput()->with('error', trans('coupons_offers.something_went_wrong'));
        }
    }

    public function show($id)
    {
        $Coupon = Coupon::with(['plans', 'types', 'branches', 'durations', 'usages'])->findOrFail($id);
        return view('coupons_offers.coupons.show', compact('Coupon'));
    }

    public function edit($id)
    {
        $Coupon = Coupon::with(['plans', 'types', 'branches', 'durations'])->findOrFail($id);

        $Plans = $this->activePlansList();
        $Types = $this->activeTypesList();
        $Branches = $this->activeBranchesList();
        $Members = $this->membersList();

        return view('coupons_offers.coupons.edit', compact('Coupon', 'Plans', 'Types', 'Branches', 'Members'));
    }

    public function update(CouponRequest $request, $id)
    {
        DB::beginTransaction();

        try {
            $coupon = Coupon::findOrFail($id);

            $coupon->code = strtoupper(trim($request->code));

            $coupon->name = [
                'ar' => $request->name_ar,
                'en' => $request->name_en,
            ];

            $coupon->description = [
                'ar' => $request->description_ar,
                'en' => $request->description_en,
            ];

            $coupon->applies_to = $request->applies_to;

            $coupon->discount_type = $request->discount_type;
            $coupon->discount_value = $request->discount_value;

            $coupon->min_amount = $request->min_amount;
            $coupon->max_discount = $request->max_discount;

            $coupon->max_uses_total = $request->max_uses_total;
            $coupon->max_uses_per_member = $request->max_uses_per_member;

            $coupon->member_id = $request->member_id;

            $coupon->start_at = $request->start_at;
            $coupon->end_at = $request->end_at;

            $coupon->status = $request->status;

            $coupon->save();

            $planIds = $request->subscriptions_plan_ids ?? [];
            $typeIds = $request->subscriptions_type_ids ?? [];
            $branchIds = $request->branch_ids ?? [];

            $coupon->plans()->sync($planIds);
            $coupon->types()->sync($typeIds);
            $coupon->branches()->sync($branchIds);

            $durationValues = $request->duration_values ?? [];
            $durationUnit = $request->duration_unit ?? null;

            CouponDuration::where('coupon_id', $coupon->id)->delete();

            if (!empty($durationValues) && $durationUnit) {
                foreach ($durationValues as $v) {
                    CouponDuration::create([
                        'coupon_id' => $coupon->id,
                        'duration_value' => (int)$v,
                        'duration_unit' => $durationUnit,
                    ]);
                }
            }

            DB::commit();

            return redirect()->route('coupons.index')->with('success', trans('coupons_offers.updated_successfully'));
        } catch (\Throwable $e) {
            DB::rollBack();
            return redirect()->back()->withInput()->with('error', trans('coupons_offers.something_went_wrong'));
        }
    }

    public function destroy($id)
    {
        try {
            $Coupon = Coupon::findOrFail($id);
            $Coupon->delete();

            return redirect()->route('coupons.index')->with('success', trans('coupons_offers.deleted_successfully'));
        } catch (\Throwable $e) {
            return redirect()->route('coupons.index')->with('error', trans('coupons_offers.something_went_wrong'));
        }
    }

    public function validateCoupon(Request $request, CouponEngine $engine)
    {
        $result = $engine->validateAndCompute([
            'code' => $request->get('code'),
            'applies_to' => $request->get('applies_to', 'subscription'),
            'subscriptions_plan_id' => $request->get('subscriptions_plan_id'),
            'subscriptions_type_id' => $request->get('subscriptions_type_id'),
            'branch_id' => $request->get('branch_id'),
            'duration_value' => $request->get('duration_value'),
            'duration_unit' => $request->get('duration_unit'),
            'amount' => (float)$request->get('amount', 0),
            'member_id' => $request->get('member_id'),
        ]);

        return response()->json($result);
    }
}
