<?php

namespace App\Http\Controllers\coupons_offers;

use App\Http\Controllers\Controller;
use App\Http\Requests\coupons_offers\OfferRequest;
use App\Models\coupons_offers\Offer;
use App\Models\coupons_offers\OfferDuration;
use App\Services\coupons_offers\OfferEngine;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class offerscontroller extends Controller
{
    public function __construct()
    {
        // permissions...
    }

    public function index()
    {
        $Offers = Offer::query()
            ->orderByDesc('id')
            ->get();

        return view('coupons_offers.offers.index', compact('Offers'));
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

        return view('coupons_offers.offers.create', compact('Plans', 'Types', 'Branches'));
    }

    public function store(OfferRequest $request)
    {
        DB::beginTransaction();

        try {
            $offer = new Offer();

            $offer->name = [
                'ar' => $request->name_ar,
                'en' => $request->name_en,
            ];

            $offer->description = [
                'ar' => $request->description_ar,
                'en' => $request->description_en,
            ];

            $offer->applies_to = $request->applies_to;

            $offer->discount_type = $request->discount_type;
            $offer->discount_value = $request->discount_value;

            $offer->min_amount = $request->min_amount;
            $offer->max_discount = $request->max_discount;

            $offer->start_at = $request->start_at;
            $offer->end_at = $request->end_at;

            $offer->status = $request->status;
            $offer->priority = (int)($request->priority ?? 0);

            $offer->created_by = Auth::id();

            $offer->save();

            $planIds = $request->subscriptions_plan_ids ?? [];
            $typeIds = $request->subscriptions_type_ids ?? [];
            $branchIds = $request->branch_ids ?? [];

            $offer->plans()->sync($planIds);
            $offer->types()->sync($typeIds);
            $offer->branches()->sync($branchIds);

            $durationValues = $request->duration_values ?? [];
            $durationUnit = $request->duration_unit ?? null;

            OfferDuration::where('offer_id', $offer->id)->delete();

            if (!empty($durationValues) && $durationUnit) {
                foreach ($durationValues as $v) {
                    OfferDuration::create([
                        'offer_id' => $offer->id,
                        'duration_value' => (int)$v,
                        'duration_unit' => $durationUnit,
                    ]);
                }
            }

            DB::commit();

            return redirect()->route('offers.index')->with('success', trans('coupons_offers.saved_successfully'));
        } catch (\Throwable $e) {
            DB::rollBack();
            return redirect()->back()->withInput()->with('error', trans('coupons_offers.something_went_wrong'));
        }
    }

    public function show($id)
    {
        $Offer = Offer::with(['plans', 'types', 'branches', 'durations'])->findOrFail($id);
        return view('coupons_offers.offers.show', compact('Offer'));
    }

    public function edit($id)
    {
        $Offer = Offer::with(['plans', 'types', 'branches', 'durations'])->findOrFail($id);

        $Plans = $this->activePlansList();
        $Types = $this->activeTypesList();
        $Branches = $this->activeBranchesList();

        return view('coupons_offers.offers.edit', compact('Offer', 'Plans', 'Types', 'Branches'));
    }

    public function update(OfferRequest $request, $id)
    {
        DB::beginTransaction();

        try {
            $offer = Offer::findOrFail($id);

            $offer->name = [
                'ar' => $request->name_ar,
                'en' => $request->name_en,
            ];

            $offer->description = [
                'ar' => $request->description_ar,
                'en' => $request->description_en,
            ];

            $offer->applies_to = $request->applies_to;

            $offer->discount_type = $request->discount_type;
            $offer->discount_value = $request->discount_value;

            $offer->min_amount = $request->min_amount;
            $offer->max_discount = $request->max_discount;

            $offer->start_at = $request->start_at;
            $offer->end_at = $request->end_at;

            $offer->status = $request->status;
            $offer->priority = (int)($request->priority ?? 0);

            $offer->save();

            $planIds = $request->subscriptions_plan_ids ?? [];
            $typeIds = $request->subscriptions_type_ids ?? [];
            $branchIds = $request->branch_ids ?? [];

            $offer->plans()->sync($planIds);
            $offer->types()->sync($typeIds);
            $offer->branches()->sync($branchIds);

            $durationValues = $request->duration_values ?? [];
            $durationUnit = $request->duration_unit ?? null;

            OfferDuration::where('offer_id', $offer->id)->delete();

            if (!empty($durationValues) && $durationUnit) {
                foreach ($durationValues as $v) {
                    OfferDuration::create([
                        'offer_id' => $offer->id,
                        'duration_value' => (int)$v,
                        'duration_unit' => $durationUnit,
                    ]);
                }
            }

            DB::commit();

            return redirect()->route('offers.index')->with('success', trans('coupons_offers.updated_successfully'));
        } catch (\Throwable $e) {
            DB::rollBack();
            return redirect()->back()->withInput()->with('error', trans('coupons_offers.something_went_wrong'));
        }
    }

    public function destroy($id)
    {
        try {
            $Offer = Offer::findOrFail($id);
            $Offer->delete();

            return redirect()->route('offers.index')->with('success', trans('coupons_offers.deleted_successfully'));
        } catch (\Throwable $e) {
            return redirect()->route('offers.index')->with('error', trans('coupons_offers.something_went_wrong'));
        }
    }

    public function best(Request $request, OfferEngine $engine)
    {
        $data = $engine->getBestOffer([
            'applies_to' => $request->get('applies_to', 'subscription'),
            'subscriptions_plan_id' => $request->get('subscriptions_plan_id'),
            'subscriptions_type_id' => $request->get('subscriptions_type_id'),
            'branch_id' => $request->get('branch_id'),
            'duration_value' => $request->get('duration_value'),
            'duration_unit' => $request->get('duration_unit'),
            'amount' => (float)$request->get('amount', 0),
        ]);

        return response()->json([
            'ok' => (bool)$data,
            'data' => $data,
        ]);
    }
}
