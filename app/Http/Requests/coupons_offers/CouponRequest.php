<?php

namespace App\Http\Requests\coupons_offers;

use Illuminate\Foundation\Http\FormRequest;

class CouponRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $couponId = $this->route('coupon') ? $this->route('coupon') : null;

        return [
            'code' => 'required|string|max:60|unique:coupons,code,' . $couponId,

            'name_ar' => 'nullable|string|max:255',
            'name_en' => 'nullable|string|max:255',
            'description_ar' => 'nullable|string',
            'description_en' => 'nullable|string',

            'applies_to' => 'required|in:any,subscription,sale,service',

            'discount_type' => 'required|in:percentage,fixed',
            'discount_value' => 'required|numeric|min:0',

            'min_amount' => 'nullable|numeric|min:0',
            'max_discount' => 'nullable|numeric|min:0',

            'max_uses_total' => 'nullable|integer|min:1',
            'max_uses_per_member' => 'nullable|integer|min:1',

            'member_id' => 'nullable|integer',

            'start_at' => 'nullable|date',
            'end_at' => 'nullable|date|after_or_equal:start_at',

            'status' => 'required|in:active,disabled',

            'subscriptions_plan_ids' => 'nullable|array',
            'subscriptions_plan_ids.*' => 'integer',

            'subscriptions_type_ids' => 'nullable|array',
            'subscriptions_type_ids.*' => 'integer',

            'duration_values' => 'nullable|array',
            'duration_values.*' => 'integer|min:1',

            'duration_unit' => 'nullable|in:day,month,year',
        ];
    }
}
