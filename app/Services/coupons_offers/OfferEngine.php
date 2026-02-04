<?php

namespace App\Services\coupons_offers;

use App\Models\coupons_offers\Offer;

class OfferEngine
{
    public function __construct(private DiscountService $discountService)
    {
    }

    /**
     * Context keys:
     * - applies_to: any|subscription|sale|service
     * - subscriptions_plan_id: int|null
     * - subscriptions_type_id: int|null
     * - duration_value: int|null
     * - duration_unit: day|month|year|null
     * - amount: float
     */
    public function getBestOffer(array $context): ?array
    {
        $amount = (float) ($context['amount'] ?? 0);
        $appliesTo = $context['applies_to'] ?? 'subscription';

        $planId = $context['subscriptions_plan_id'] ?? null;
        $typeId = $context['subscriptions_type_id'] ?? null;

        $durationValue = $context['duration_value'] ?? null;
        $durationUnit = $context['duration_unit'] ?? null;

        $offers = Offer::query()
            ->validNow()
            ->where(function ($q) use ($appliesTo) {
                $q->where('applies_to', 'any')->orWhere('applies_to', $appliesTo);
            })
            ->get();

        $best = null;

        foreach ($offers as $offer) {
            if (!$this->matchesConstraints($offer, $planId, $typeId, $durationValue, $durationUnit)) {
                continue;
            }

            if (!is_null($offer->min_amount) && $amount < (float) $offer->min_amount) {
                continue;
            }

            $discount = $this->discountService->calculateDiscountAmount(
                $amount,
                (string) $offer->discount_type,
                (float) $offer->discount_value,
                is_null($offer->max_discount) ? null : (float) $offer->max_discount
            );

            if (is_null($best)) {
                $best = ['offer' => $offer, 'discount_amount' => $discount];
                continue;
            }

            // Choose highest discount; if tie choose higher priority then latest id
            if ($discount > $best['discount_amount']) {
                $best = ['offer' => $offer, 'discount_amount' => $discount];
            } elseif ($discount == $best['discount_amount']) {
                if ((int) $offer->priority > (int) $best['offer']->priority) {
                    $best = ['offer' => $offer, 'discount_amount' => $discount];
                } elseif ((int) $offer->priority === (int) $best['offer']->priority && $offer->id > $best['offer']->id) {
                    $best = ['offer' => $offer, 'discount_amount' => $discount];
                }
            }
        }

        if (!$best) {
            return null;
        }

        $applied = $this->discountService->applyDiscount($amount, (float) $best['discount_amount']);

        return [
            'offer_id' => $best['offer']->id,
            'offer' => $best['offer'],
            'discount_amount' => $applied['discount_amount'],
            'amount_before' => $applied['amount_before'],
            'amount_after' => $applied['amount_after'],
        ];
    }

    private function matchesConstraints(Offer $offer, $planId, $typeId, $durationValue, $durationUnit): bool
    {
        // Plan constraint
        $planIds = $offer->plans()->pluck('subscriptions_plans.id')->toArray();
        if (!empty($planIds)) {
            if (!$planId || !in_array((int)$planId, array_map('intval', $planIds), true)) {
                return false;
            }
        }

        // Type constraint
        $typeIds = $offer->types()->pluck('subscriptions_types.id')->toArray();
        if (!empty($typeIds)) {
            if (!$typeId || !in_array((int)$typeId, array_map('intval', $typeIds), true)) {
                return false;
            }
        }

        // Duration constraint
        $durations = $offer->durations()->get();
        if ($durations->count() > 0) {
            if (!$durationValue || !$durationUnit) {
                return false;
            }

            $matched = $durations->first(function ($d) use ($durationValue, $durationUnit) {
                return (int)$d->duration_value === (int)$durationValue && (string)$d->duration_unit === (string)$durationUnit;
            });

            if (!$matched) {
                return false;
            }
        }

        return true;
    }
}
