<?php

namespace App\Services\coupons_offers;

use App\Models\coupons_offers\Coupon;

class CouponEngine
{
    public function __construct(private DiscountService $discountService)
    {
    }

    /**
     * Context keys:
     * - code: string
     * - applies_to: any|subscription|sale|service
     * - subscriptions_plan_id: int|null
     * - subscriptions_type_id: int|null
     * - duration_value: int|null
     * - duration_unit: day|month|year|null
     * - amount: float
     * - member_id: int|null
     */
    public function validateAndCompute(array $context): array
    {
        $code = strtoupper(trim((string)($context['code'] ?? '')));
        $amount = (float)($context['amount'] ?? 0);
        $memberId = $context['member_id'] ?? null;

        if ($code === '') {
            return ['ok' => false, 'message' => trans('coupons_offers.coupon_code_required')];
        }

        $appliesTo = $context['applies_to'] ?? 'subscription';

        $planId = $context['subscriptions_plan_id'] ?? null;
        $typeId = $context['subscriptions_type_id'] ?? null;

        $durationValue = $context['duration_value'] ?? null;
        $durationUnit = $context['duration_unit'] ?? null;

        $coupon = Coupon::query()
            ->validNow()
            ->where('code', $code)
            ->where(function ($q) use ($appliesTo) {
                $q->where('applies_to', 'any')->orWhere('applies_to', $appliesTo);
            })
            ->first();

        if (!$coupon) {
            return ['ok' => false, 'message' => trans('coupons_offers.coupon_not_found_or_inactive')];
        }

        if ($coupon->member_id && (int)$coupon->member_id !== (int)$memberId) {
            return ['ok' => false, 'message' => trans('coupons_offers.coupon_not_allowed_for_member')];
        }

        if (!is_null($coupon->min_amount) && $amount < (float)$coupon->min_amount) {
            return ['ok' => false, 'message' => trans('coupons_offers.coupon_min_amount_not_met')];
        }

        // Plan constraint
        $planIds = $coupon->plans()->pluck('subscriptions_plans.id')->toArray();
        if (!empty($planIds)) {
            if (!$planId || !in_array((int)$planId, array_map('intval', $planIds), true)) {
                return ['ok' => false, 'message' => trans('coupons_offers.coupon_not_applicable')];
            }
        }

        // Type constraint
        $typeIds = $coupon->types()->pluck('subscriptions_types.id')->toArray();
        if (!empty($typeIds)) {
            if (!$typeId || !in_array((int)$typeId, array_map('intval', $typeIds), true)) {
                return ['ok' => false, 'message' => trans('coupons_offers.coupon_not_applicable')];
            }
        }

        // Duration constraint
        $durations = $coupon->durations()->get();
        if ($durations->count() > 0) {
            if (!$durationValue || !$durationUnit) {
                return ['ok' => false, 'message' => trans('coupons_offers.coupon_not_applicable')];
            }

            $matched = $durations->first(function ($d) use ($durationValue, $durationUnit) {
                return (int)$d->duration_value === (int)$durationValue && (string)$d->duration_unit === (string)$durationUnit;
            });

            if (!$matched) {
                return ['ok' => false, 'message' => trans('coupons_offers.coupon_not_applicable')];
            }
        }

        // Usage limits
        if (!is_null($coupon->max_uses_total)) {
            $used = $coupon->usages()->count();
            if ($used >= (int)$coupon->max_uses_total) {
                return ['ok' => false, 'message' => trans('coupons_offers.coupon_usage_limit_reached')];
            }
        }

        if (!is_null($coupon->max_uses_per_member) && $memberId) {
            $usedByMember = $coupon->usages()->where('member_id', $memberId)->count();
            if ($usedByMember >= (int)$coupon->max_uses_per_member) {
                return ['ok' => false, 'message' => trans('coupons_offers.coupon_member_usage_limit_reached')];
            }
        }

        $discount = $this->discountService->calculateDiscountAmount(
            $amount,
            (string)$coupon->discount_type,
            (float)$coupon->discount_value,
            is_null($coupon->max_discount) ? null : (float)$coupon->max_discount
        );

        $applied = $this->discountService->applyDiscount($amount, $discount);

        return [
            'ok' => true,
            'message' => trans('coupons_offers.coupon_valid'),
            'coupon_id' => $coupon->id,
            'coupon' => $coupon,
            'amount_before' => $applied['amount_before'],
            'discount_amount' => $applied['discount_amount'],
            'amount_after' => $applied['amount_after'],
        ];
    }
}
