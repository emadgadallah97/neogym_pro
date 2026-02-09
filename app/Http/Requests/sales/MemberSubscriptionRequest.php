<?php

namespace App\Http\Requests\sales;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

use App\Models\subscriptions\subscriptions_plan_branch_price;
use App\Models\employee\Employee;

class MemberSubscriptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'branch_id' => ['required', 'integer', 'exists:branches,id'],
            'member_id' => ['required', 'integer', 'exists:members,id'],

            'subscriptions_plan_id' => ['required', 'integer', 'exists:subscriptions_plans,id'],
            'subscriptions_type_id' => ['nullable', 'integer', 'exists:subscriptions_types,id'],

            'start_date' => ['required', 'date'],

            // ✅ تم تجاهل الاشتراك مع مدرب بالكامل
            'with_trainer' => ['nullable'],
            'main_trainer_id' => ['nullable'],

            'pt_addons' => ['nullable', 'array'],
            'pt_addons.*.trainer_id' => ['required_with:pt_addons', 'integer', 'exists:employees,id'],
            'pt_addons.*.sessions_count' => ['required_with:pt_addons', 'integer', 'min:1'],

            'offer_id' => ['nullable', 'integer', 'exists:offers,id'],
            'coupon_code' => ['nullable', 'string', 'max:60'],

            'sales_employee_id' => ['nullable', 'integer', 'exists:employees,id'],

            'allow_all_branches' => ['nullable', 'boolean'],
            'source' => ['nullable', 'string', Rule::in([
                'website', 'reception', 'mobile', 'call_center', 'partner', 'other',
            ])],

            'payment_method' => ['required', 'string', Rule::in([
                'cash', 'card', 'transfer', 'instapay', 'ewallet', 'cheque', 'other',
            ])],

            'notes' => ['nullable', 'string'],
        ];
    }

    public function withValidator(Validator $validator)
    {
        $validator->after(function ($v) {

            $branchId = (int)$this->input('branch_id');
            $planId   = (int)$this->input('subscriptions_plan_id');

            if ($branchId <= 0 || $planId <= 0) {
                return;
            }

            // ✅ لازم سعر أساسي للخطة في هذا الفرع
            $bp = subscriptions_plan_branch_price::query()
                ->where('subscriptions_plan_id', $planId)
                ->where('branch_id', $branchId)
                ->first();

            if (!$bp || $bp->price_without_trainer === null) {
                $v->errors()->add('subscriptions_plan_id', trans('sales.base_price_not_found'));
            }

            // ✅ تحقق جلسات PT: المدرب يجب أن يكون مرتبط بالفرع (employee_branch)
            $addons = $this->input('pt_addons', []);
            if (!is_array($addons) || empty($addons)) {
                return;
            }

            foreach ($addons as $idx => $row) {
                $trainerId = isset($row['trainer_id']) ? (int)$row['trainer_id'] : 0;
                if ($trainerId <= 0) {
                    continue;
                }

                $belongs = Employee::query()
                    ->where('id', $trainerId)
                    ->where('is_coach', 1)
                    ->whereHas('branches', function ($q) use ($branchId) {
                        $q->where('branches.id', $branchId);
                    })
                    ->exists();

                if (!$belongs) {
                    $v->errors()->add("pt_addons.$idx.trainer_id", trans('sales.coach_not_in_branch'));
                }
            }
        });
    }
}
