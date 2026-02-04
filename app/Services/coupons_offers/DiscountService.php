<?php

namespace App\Services\coupons_offers;

class DiscountService
{
    public function calculateDiscountAmount(
        float $amount,
        string $discountType,
        float $discountValue,
        ?float $maxDiscount = null
    ): float {
        $amount = max(0, $amount);
        $discountValue = max(0, $discountValue);

        if ($amount <= 0) {
            return 0.0;
        }

        if ($discountType === 'percentage') {
            $d = ($amount * $discountValue) / 100.0;
        } else {
            $d = $discountValue;
        }

        $d = min($d, $amount);

        if (!is_null($maxDiscount)) {
            $d = min($d, max(0, $maxDiscount));
        }

        return round($d, 2);
    }

    public function applyDiscount(float $amount, float $discountAmount): array
    {
        $amount = max(0, $amount);
        $discountAmount = min(max(0, $discountAmount), $amount);

        return [
            'amount_before' => round($amount, 2),
            'discount_amount' => round($discountAmount, 2),
            'amount_after' => round($amount - $discountAmount, 2),
        ];
    }
}
