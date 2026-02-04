<?php

namespace App\Http\Requests\coupons_offers;

use Illuminate\Foundation\Http\FormRequest;

class OfferRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name_ar' => 'nullable|string|max:255',
            'name_en' => 'nullable|string|max:255',
            'description_ar' => 'nullable|string',
            'description_en' => 'nullable|string',

            'applies_to' => 'required|in:any,subscription,sale,service',

            'discount_type' => 'required|in:percentage,fixed',
            'discount_value' => 'required|numeric|min:0',

            'min_amount' => 'nullable|numeric|min:0',
            'max_discount' => 'nullable|numeric|min:0',

            'start_at' => 'nullable|date',
            'end_at' => 'nullable|date|after_or_equal:start_at',

            'status' => 'required|in:active,disabled',
            'priority' => 'nullable|integer|min:0',

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
