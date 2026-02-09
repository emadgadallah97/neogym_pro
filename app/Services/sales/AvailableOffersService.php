<?php

namespace App\Services\sales;

use App\Models\coupons_offers\Offer;
use App\Services\coupons_offers\DiscountService;

class AvailableOffersService
{
    protected DiscountService $discountService;

    public function __construct(DiscountService $discountService)
    {
        $this->discountService = $discountService;
    }

    /**
     * يرجع العروض الصالحة (validNow + applies_to) والتي تنطبق على القيود
     * مع حساب الخصم لكل عرض على amount.
     */
    public function listOffersWithDiscount(array $context): array
    {
        $amount       = (float)($context['amount'] ?? 0);
        $appliesTo    = $context['applies_to'] ?? 'subscription';
        $planId       = $context['subscriptions_plan_id'] ?? null;
        $typeId       = $context['subscriptions_type_id'] ?? null;
        $branchId     = $context['branch_id'] ?? null;
        $durationValue= $context['duration_value'] ?? null;
        $durationUnit = $context['duration_unit'] ?? null;

        $offers = Offer::query()
            ->validNow()
            ->where(function ($q) use ($appliesTo) {
                $q->where('applies_to', 'any')
                  ->orWhere('applies_to', $appliesTo);
            })
            ->with(['plans', 'types', 'branches', 'durations'])
            ->orderByDesc('id')
            ->get();

        $rows = [];

        foreach ($offers as $offer) {
            if (!$this->matchesConstraints($offer, $planId, $typeId, $branchId, $durationValue, $durationUnit)) {
                continue;
            }

            if (!is_null($offer->min_amount) && $amount < (float)$offer->min_amount) {
                continue;
            }

            $discount = $this->discountService->calculateDiscountAmount(
                $amount,
                (string)$offer->discount_type,
                (float)$offer->discount_value,
                is_null($offer->max_discount) ? null : (float)$offer->max_discount
            );

            $applied = $this->discountService->applyDiscount($amount, $discount);

            $rows[] = [
                'offer_id'        => $offer->id,
                'name'            => $offer->name, // JSON
                'discount_type'   => $offer->discount_type,
                'discount_value'  => (float)$offer->discount_value,
                'min_amount'      => $offer->min_amount,
                'max_discount'    => $offer->max_discount,
                'priority'        => (int)($offer->priority ?? 0),

                'amount_before'   => (float)$applied['amount_before'],
                'discount_amount' => (float)$applied['discount_amount'],
                'amount_after'    => (float)$applied['amount_after'],
            ];
        }

        // ترتيب: أعلى خصم ثم أعلى priority ثم أحدث id (مثل منطق OfferEngine) [file:17]
        usort($rows, function ($a, $b) {
            if ($a['discount_amount'] != $b['discount_amount']) {
                return $b['discount_amount'] <=> $a['discount_amount'];
            }
            if ($a['priority'] != $b['priority']) {
                return $b['priority'] <=> $a['priority'];
            }
            return $b['offer_id'] <=> $a['offer_id'];
        });

        return $rows;
    }

    public function computeSingleOfferDiscount(Offer $offer, array $context): ?array
    {
        $amount        = (float)($context['amount'] ?? 0);
        $planId        = $context['subscriptions_plan_id'] ?? null;
        $typeId        = $context['subscriptions_type_id'] ?? null;
        $branchId      = $context['branch_id'] ?? null;
        $durationValue = $context['duration_value'] ?? null;
        $durationUnit  = $context['duration_unit'] ?? null;

        if (!$offer->scopeValidNow($offer->newQuery())->where('id', $offer->id)->exists()) {
            return null;
        }

        if (!$this->matchesConstraints($offer->loadMissing(['plans','types','branches','durations']), $planId, $typeId, $branchId, $durationValue, $durationUnit)) {
            return null;
        }

        if (!is_null($offer->min_amount) && $amount < (float)$offer->min_amount) {
            return null;
        }

        $discount = $this->discountService->calculateDiscountAmount(
            $amount,
            (string)$offer->discount_type,
            (float)$offer->discount_value,
            is_null($offer->max_discount) ? null : (float)$offer->max_discount
        );

        $applied = $this->discountService->applyDiscount($amount, $discount);

        return [
            'offer_id'        => $offer->id,
            'offer'           => $offer,
            'discount_amount' => (float)$applied['discount_amount'],
            'amount_before'   => (float)$applied['amount_before'],
            'amount_after'    => (float)$applied['amount_after'],
        ];
    }

    private function matchesConstraints($offer, $planId, $typeId, $branchId, $durationValue, $durationUnit): bool
    {
        $planIds = $offer->plans?->pluck('id')->toArray() ?? [];
        if (!empty($planIds)) {
            if (!$planId || !in_array((int)$planId, array_map('intval', $planIds), true)) return false;
        }

        $typeIds = $offer->types?->pluck('id')->toArray() ?? [];
        if (!empty($typeIds)) {
            if (!$typeId || !in_array((int)$typeId, array_map('intval', $typeIds), true)) return false;
        }

        // عندك branch constraints في offers حسب التصميم [file:17]
        $branchIds = $offer->branches?->pluck('id')->toArray() ?? [];
        if (!empty($branchIds)) {
            if (!$branchId || !in_array((int)$branchId, array_map('intval', $branchIds), true)) return false;
        }

        $durations = $offer->durations ?? collect();
        if ($durations->count() === 0) {
            // لو مفيش durations قيود: نعتبرها صالحة لأي مدة
            return true;
        }

        if (!$durationValue || !$durationUnit) return false;

        $matched = $durations->first(function ($d) use ($durationValue, $durationUnit) {
            return (int)$d->duration_value === (int)$durationValue && (string)$d->duration_unit === (string)$durationUnit;
        });

        return (bool)$matched;
    }
}
