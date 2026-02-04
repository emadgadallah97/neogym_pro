@php
    $Coupon = $Coupon ?? null;

    $code = old('code', $Coupon->code ?? '');

    $nameAr = old('name_ar', $Coupon ? $Coupon->getTranslation('name','ar') : '');
    $nameEn = old('name_en', $Coupon ? $Coupon->getTranslation('name','en') : '');

    $descAr = old('description_ar', $Coupon ? $Coupon->getTranslation('description','ar') : '');
    $descEn = old('description_en', $Coupon ? $Coupon->getTranslation('description','en') : '');

    $appliesTo = old('applies_to', $Coupon->applies_to ?? 'subscription');
    $discountType = old('discount_type', $Coupon->discount_type ?? 'percentage');

    $discountValue = old('discount_value', $Coupon->discount_value ?? 0);
    $minAmount = old('min_amount', $Coupon->min_amount ?? '');
    $maxDiscount = old('max_discount', $Coupon->max_discount ?? '');

    $maxUsesTotal = old('max_uses_total', $Coupon->max_uses_total ?? '');
    $maxUsesPerMember = old('max_uses_per_member', $Coupon->max_uses_per_member ?? '');

    $memberId = old('member_id', $Coupon->member_id ?? '');

    $startAt = old('start_at', $Coupon && $Coupon->start_at ? $Coupon->start_at->format('Y-m-d\TH:i') : '');
    $endAt = old('end_at', $Coupon && $Coupon->end_at ? $Coupon->end_at->format('Y-m-d\TH:i') : '');

    $status = old('status', $Coupon->status ?? 'active');

    $selectedPlanIds = old('subscriptions_plan_ids', $Coupon && $Coupon->relationLoaded('plans') ? $Coupon->plans->pluck('id')->toArray() : []);
    $selectedTypeIds = old('subscriptions_type_ids', $Coupon && $Coupon->relationLoaded('types') ? $Coupon->types->pluck('id')->toArray() : []);
    $selectedBranchIds = old('branch_ids', $Coupon && $Coupon->relationLoaded('branches') ? $Coupon->branches->pluck('id')->toArray() : []);

    $existingDurations = $Coupon && $Coupon->relationLoaded('durations') ? $Coupon->durations : collect([]);
    $selectedDurationUnit = old('duration_unit', $existingDurations->first()->duration_unit ?? 'month');
    $selectedDurationValues = old('duration_values', $existingDurations->pluck('duration_value')->toArray());

    $jsonName = function ($nameJsonOrText) {
        $decoded = json_decode($nameJsonOrText, true);
        if (is_array($decoded)) {
            return $decoded[app()->getLocale()] ?? ($decoded['ar'] ?? ($decoded['en'] ?? ''));
        }
        return $nameJsonOrText;
    };
@endphp

<div class="row g-3">

    <div class="col-lg-4">
        <label class="form-label mb-1">{{ trans('coupons_offers.code') }}</label>
        <input type="text" name="code" class="form-control" value="{{ $code }}" placeholder="EX: NEWYEAR2026">
        <small class="text-muted d-block mt-1">سيتم حفظه تلقائيًا بحروف كبيرة.</small>
    </div>

    <div class="col-lg-4">
        <label class="form-label mb-1">{{ trans('coupons_offers.max_uses_total') }}</label>
        <input type="number" name="max_uses_total" class="form-control" value="{{ $maxUsesTotal }}">
    </div>

    <div class="col-lg-4">
        <label class="form-label mb-1">{{ trans('coupons_offers.max_uses_per_member') }}</label>
        <input type="number" name="max_uses_per_member" class="form-control" value="{{ $maxUsesPerMember }}">
    </div>

    <div class="col-lg-6">
        <label class="form-label mb-1">{{ trans('coupons_offers.name_ar') }}</label>
        <input type="text" name="name_ar" class="form-control" value="{{ $nameAr }}">
    </div>

    <div class="col-lg-6">
        <label class="form-label mb-1">{{ trans('coupons_offers.name_en') }}</label>
        <input type="text" name="name_en" class="form-control" value="{{ $nameEn }}">
    </div>

    <div class="col-lg-6">
        <label class="form-label mb-1">{{ trans('coupons_offers.description_ar') }}</label>
        <textarea name="description_ar" class="form-control" rows="3">{{ $descAr }}</textarea>
    </div>

    <div class="col-lg-6">
        <label class="form-label mb-1">{{ trans('coupons_offers.description_en') }}</label>
        <textarea name="description_en" class="form-control" rows="3">{{ $descEn }}</textarea>
    </div>

    <div class="col-lg-4">
        <label class="form-label mb-1">{{ trans('coupons_offers.applies_to') }}</label>
        <select name="applies_to" class="form-select">
            <option value="any" {{ $appliesTo=='any'?'selected':'' }}>{{ trans('coupons_offers.any') }}</option>
            <option value="subscription" {{ $appliesTo=='subscription'?'selected':'' }}>{{ trans('coupons_offers.subscription') }}</option>
            <option value="sale" {{ $appliesTo=='sale'?'selected':'' }}>{{ trans('coupons_offers.sale') }}</option>
            <option value="service" {{ $appliesTo=='service'?'selected':'' }}>{{ trans('coupons_offers.service') }}</option>
        </select>
    </div>

    <div class="col-lg-4">
        <label class="form-label mb-1">{{ trans('coupons_offers.discount_type') }}</label>
        <select name="discount_type" class="form-select">
            <option value="percentage" {{ $discountType=='percentage'?'selected':'' }}>{{ trans('coupons_offers.percentage') }}</option>
            <option value="fixed" {{ $discountType=='fixed'?'selected':'' }}>{{ trans('coupons_offers.fixed') }}</option>
        </select>
    </div>

    <div class="col-lg-4">
        <label class="form-label mb-1">{{ trans('coupons_offers.discount_value') }}</label>
        <input type="number" step="0.01" name="discount_value" class="form-control" value="{{ $discountValue }}">
    </div>

    <div class="col-lg-4">
        <label class="form-label mb-1">{{ trans('coupons_offers.min_amount') }}</label>
        <input type="number" step="0.01" name="min_amount" class="form-control" value="{{ $minAmount }}">
    </div>

    <div class="col-lg-4">
        <label class="form-label mb-1">{{ trans('coupons_offers.max_discount') }}</label>
        <input type="number" step="0.01" name="max_discount" class="form-control" value="{{ $maxDiscount }}">
    </div>

    <div class="col-lg-4">
        <label class="form-label mb-1">{{ trans('coupons_offers.member') }}</label>
        <select name="member_id" id="couponMember" class="form-select">
            <option value="">-</option>
            @foreach($Members as $m)
                <option value="{{ $m->id }}" {{ (string)$m->id === (string)$memberId ? 'selected' : '' }}>
                    {{ $m->name }}
                </option>
            @endforeach
        </select>
        <small class="text-muted d-block mt-1">اختياري (لو اخترت عضو، الكوبون لن يعمل لغيره).</small>
    </div>

    <div class="col-lg-6">
        <label class="form-label mb-1">{{ trans('coupons_offers.start_at') }}</label>
        <input type="datetime-local" name="start_at" class="form-control" value="{{ $startAt }}">
    </div>

    <div class="col-lg-6">
        <label class="form-label mb-1">{{ trans('coupons_offers.end_at') }}</label>
        <input type="datetime-local" name="end_at" class="form-control" value="{{ $endAt }}">
    </div>

    <div class="col-lg-4">
        <label class="form-label mb-1">{{ trans('coupons_offers.status') }}</label>
        <select name="status" class="form-select">
            <option value="active" {{ $status=='active'?'selected':'' }}>{{ trans('coupons_offers.active') }}</option>
            <option value="disabled" {{ $status=='disabled'?'selected':'' }}>{{ trans('coupons_offers.disabled') }}</option>
        </select>
    </div>

    <div class="col-12">
        <div class="alert alert-info mb-0">
            <strong>{{ trans('coupons_offers.constraints') }}</strong>
            <div class="text-muted mt-1">{{ trans('subscriptions.multi_select_hint') }}</div>
        </div>
    </div>

    <div class="col-lg-4">
        <label class="form-label mb-1">{{ trans('coupons_offers.branches') }}</label>
        <select name="branch_ids[]" id="couponBranches" class="form-select" multiple>
            @foreach($Branches as $b)
                <option value="{{ $b->id }}" {{ in_array($b->id, $selectedBranchIds) ? 'selected' : '' }}>
                    {{ $jsonName($b->name) }}
                </option>
            @endforeach
        </select>
        <small class="text-muted d-block mt-1">{{ trans('coupons_offers.branches') }} (اختياري)</small>
    </div>

    <div class="col-lg-4">
        <label class="form-label mb-1">{{ trans('coupons_offers.plans') }}</label>
        <select name="subscriptions_plan_ids[]" id="couponPlans" class="form-select" multiple>
            @foreach($Plans as $p)
                <option value="{{ $p->id }}" {{ in_array($p->id, $selectedPlanIds) ? 'selected' : '' }}>
                    {{ $p->code ? ('['.$p->code.'] ') : '' }}{{ $jsonName($p->name) }}
                </option>
            @endforeach
        </select>
        <small class="text-muted d-block mt-1">{{ trans('coupons_offers.plans') }} (اختياري)</small>
    </div>

    <div class="col-lg-4">
        <label class="form-label mb-1">{{ trans('coupons_offers.types') }}</label>
        <select name="subscriptions_type_ids[]" id="couponTypes" class="form-select" multiple>
            @foreach($Types as $t)
                <option value="{{ $t->id }}" {{ in_array($t->id, $selectedTypeIds) ? 'selected' : '' }}>
                    {{ $jsonName($t->name) }}
                </option>
            @endforeach
        </select>
        <small class="text-muted d-block mt-1">{{ trans('coupons_offers.types') }} (اختياري)</small>
    </div>

    <div class="col-lg-4">
        <label class="form-label mb-1">{{ trans('coupons_offers.duration_unit') }}</label>
        <select name="duration_unit" class="form-select">
            <option value="day" {{ $selectedDurationUnit=='day'?'selected':'' }}>{{ trans('coupons_offers.day') }}</option>
            <option value="month" {{ $selectedDurationUnit=='month'?'selected':'' }}>{{ trans('coupons_offers.month') }}</option>
            <option value="year" {{ $selectedDurationUnit=='year'?'selected':'' }}>{{ trans('coupons_offers.year') }}</option>
        </select>
    </div>

    <div class="col-lg-8">
        <label class="form-label mb-1">{{ trans('coupons_offers.durations') }}</label>
        <select name="duration_values[]" id="couponDurations" class="form-select" multiple>
            @foreach([1,2,3,6,9,12,18,24] as $dv)
                <option value="{{ $dv }}" {{ in_array($dv, $selectedDurationValues) ? 'selected' : '' }}>{{ $dv }}</option>
            @endforeach
        </select>
        <small class="text-muted d-block mt-1">اتركها فارغة لو الكوبون ينطبق على أي مدة.</small>
    </div>

</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        if (typeof $ === 'undefined') return;
        if (!$.fn || !$.fn.select2) return;

        var isRtl = $('html').attr('dir') === 'rtl';

        $('#couponMember').select2({
            width: '100%',
            placeholder: '{{ trans('coupons_offers.member') }}',
            allowClear: true,
            dir: isRtl ? 'rtl' : 'ltr'
        });

        $('#couponBranches').select2({
            width: '100%',
            placeholder: '{{ trans('coupons_offers.branches') }}',
            allowClear: true,
            closeOnSelect: false,
            dir: isRtl ? 'rtl' : 'ltr'
        });

        $('#couponPlans').select2({
            width: '100%',
            placeholder: '{{ trans('coupons_offers.plans') }}',
            allowClear: true,
            closeOnSelect: false,
            dir: isRtl ? 'rtl' : 'ltr'
        });

        $('#couponTypes').select2({
            width: '100%',
            placeholder: '{{ trans('coupons_offers.types') }}',
            allowClear: true,
            closeOnSelect: false,
            dir: isRtl ? 'rtl' : 'ltr'
        });

        $('#couponDurations').select2({
            width: '100%',
            placeholder: '{{ trans('coupons_offers.durations') }}',
            allowClear: true,
            closeOnSelect: false,
            tags: true,
            dir: isRtl ? 'rtl' : 'ltr'
        });
    });
</script>
