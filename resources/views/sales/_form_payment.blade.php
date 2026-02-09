@php
    $Employees = $Employees ?? [];

    // الأفضل تمريرها من الكنترولر كمتغير: $commission_before_discounts
    // لو غير متوفر، هنستخدم fallback آمن (لكن الأفضل عدم الاستعلام من داخل الفيو).
    $commissionBeforeDiscounts = isset($commission_before_discounts)
        ? (bool)$commission_before_discounts
        : (bool)(\Illuminate\Support\Facades\DB::table('commission_settings')->where('id', 1)->value('calculate_commission_before_discounts') ?? 0);
@endphp

<input type="hidden" id="commission_before_discounts" value="{{ $commissionBeforeDiscounts ? 1 : 0 }}">

<div class="row">
    <div class="col-md-6 mb-3">
        <label class="form-label">{{ trans('sales.sales_employee') ?? 'موظف المبيعات' }}</label>
        <select name="sales_employee_id" class="form-select" id="sales_employee_id">
            <option value="">{{ trans('settings_trans.choose') }}</option>
            @foreach($Employees as $Emp)
                @php
                    $empName = $Emp->full_name ?? trim(($Emp->first_name ?? '').' '.($Emp->last_name ?? ''));
                    $ctype = $Emp->commission_value_type ?? ''; // percent | fixed
                    $cpercent = $Emp->commission_percent ?? 0;
                    $cfixed = $Emp->commission_fixed ?? 0;
                @endphp
                <option
                    value="{{ $Emp->id }}"
                    data-commission-type="{{ $ctype }}"
                    data-commission-percent="{{ $cpercent }}"
                    data-commission-fixed="{{ $cfixed }}"
                >
                    {{ $empName }}
                </option>
            @endforeach
        </select>
        <small class="text-muted">
            {{ trans('sales.sales_employee_hint') ?? 'اختياري؛ سيتم حساب العمولة حسب إعدادات الموظف.' }}
        </small>
    </div>

    <div class="col-md-6 mb-3">
        <label class="form-label">{{ trans('sales.payment_method') ?? 'طريقة الدفع' }}</label>
        <select name="payment_method" class="form-select" required>
            <option value="cash">{{ trans('sales.cash') ?? 'نقدي' }}</option>
            <option value="card">{{ trans('sales.card') ?? 'بطاقة' }}</option>
            <option value="transfer">{{ trans('sales.transfer') ?? 'تحويل' }}</option>
            <option value="instapay">{{ trans('sales.instapay') ?? 'InstaPay' }}</option>
            <option value="ewallet">{{ trans('sales.ewallet') ?? 'محفظة' }}</option>
            <option value="cheque">{{ trans('sales.cheque') ?? 'شيك' }}</option>
            <option value="other">{{ trans('sales.payment_other') ?? 'أخرى' }}</option>
        </select>
    </div>
</div>

<div class="row">
    <div class="col-md-4 mb-3">
        <label class="form-label">{{ trans('sales.price_plan') ?? 'سعر الخطة' }}</label>
        <input type="text" class="form-control form-control-sm" id="price_plan_display" value="0.00" readonly>
    </div>
    <div class="col-md-4 mb-3">
        <label class="form-label">{{ trans('sales.price_pt_addons') ?? 'سعر جلسات المدرب' }}</label>
        <input type="text" class="form-control form-control-sm" id="price_pt_addons_display" value="0.00" readonly>
    </div>
    <div class="col-md-4 mb-3">
        <label class="form-label">{{ trans('sales.total_amount') ?? 'الإجمالي النهائي' }}</label>
        <input type="text" class="form-control form-control-sm" id="total_amount_display" value="0.00" readonly>
    </div>
</div>

<small class="text-muted d-block">
    {{ trans('sales.totals_preview_hint') ?? 'هذه القيم للعرض فقط (Preview) ويتم اعتماد الحساب النهائي عند الحفظ.' }}
</small>

<hr class="my-3">

{{-- ✅ ملخص الفاتورة والخصومات --}}
<div class="row">
    <div class="col-lg-7 mb-3">
        <div class="border rounded p-3 bg-light">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                <h6 class="mb-0">
                    <i class="mdi mdi-receipt-text-outline"></i>
                    {{ trans('sales.invoice_summary') ?? 'ملخص الفاتورة' }}
                </h6>
                <small class="text-muted">
                    {{ trans('sales.preview_only') ?? 'Preview فقط' }}
                </small>
            </div>

            <div class="table-responsive mt-2">
                <table class="table table-sm table-bordered align-middle mb-0">
                    <tbody>
                        <tr>
                            <td class="text-muted">{{ trans('sales.item_plan') ?? 'الخطة' }}</td>
                            <td class="text-end"><span id="sum_plan_price">0.00</span></td>
                        </tr>
                        <tr>
                            <td class="text-muted">{{ trans('sales.item_pt_addons') ?? 'جلسات المدرب (PT)' }}</td>
                            <td class="text-end"><span id="sum_pt_price">0.00</span></td>
                        </tr>
                        <tr>
                            <td class="text-muted">{{ trans('sales.subtotal_gross') ?? 'الإجمالي قبل الخصم' }}</td>
                            <td class="text-end"><span id="sum_gross">0.00</span></td>
                        </tr>
                        <tr>
                            <td class="text-muted">
                                {{ trans('sales.discount_offer') ?? 'خصم العرض' }}
                                <small class="d-block text-muted" id="sum_offer_name">-</small>
                            </td>
                            <td class="text-end text-danger">-<span id="sum_offer_discount">0.00</span></td>
                        </tr>
                        <tr>
                            <td class="text-muted">
                                {{ trans('sales.discount_coupon') ?? 'خصم الكوبون' }}
                                <small class="d-block text-muted" id="sum_coupon_code">-</small>
                            </td>
                            <td class="text-end text-danger">-<span id="sum_coupon_discount">0.00</span></td>
                        </tr>
                        <tr>
                            <td class="text-muted">{{ trans('sales.total_discount') ?? 'إجمالي الخصومات' }}</td>
                            <td class="text-end text-danger">-<span id="sum_total_discount">0.00</span></td>
                        </tr>
                        <tr class="table-primary">
                            <td class="fw-semibold">{{ trans('sales.net_total') ?? 'الإجمالي المستحق' }}</td>
                            <td class="text-end fw-semibold"><span id="sum_net_total">0.00</span></td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <small class="text-muted d-block mt-2">
                {{ trans('sales.summary_hint') ?? 'يتم تحديث الملخص تلقائيًا حسب الخطة/جلسات PT/العرض/الكوبون.' }}
            </small>
        </div>
    </div>

    <div class="col-lg-5 mb-3">
        <div class="border rounded p-3 bg-white">
            <h6 class="mb-2">
                <i class="mdi mdi-cash-multiple"></i>
                {{ trans('sales.commission_section') ?? 'العمولة' }}
            </h6>

            <div class="row g-2">
                <div class="col-12">
                    <label class="form-label mb-1">{{ trans('sales.commission_employee') ?? 'موظف المبيعات' }}</label>
                    <input type="text" class="form-control form-control-sm" id="commission_employee_name" value="-" readonly>
                </div>

                <div class="col-6">
                    <label class="form-label mb-1">{{ trans('sales.commission_base_amount') ?? 'أساس العمولة' }}</label>
                    <input type="text" class="form-control form-control-sm" id="commission_base_amount_display" value="0.00" readonly>
                </div>

                <div class="col-6">
                    <label class="form-label mb-1">{{ trans('sales.commission_net_amount') ?? 'صافي المبلغ' }}</label>
                    <input type="text" class="form-control form-control-sm" id="commission_net_amount_display" value="0.00" readonly>
                </div>

                <div class="col-6">
                    <label class="form-label mb-1">{{ trans('sales.commission_value_type') ?? 'نوع العمولة' }}</label>
                    <input type="text" class="form-control form-control-sm" id="commission_value_type_display" value="-" readonly>
                </div>

                <div class="col-6">
                    <label class="form-label mb-1">{{ trans('sales.commission_value') ?? 'قيمة النسبة/المبلغ' }}</label>
                    <input type="text" class="form-control form-control-sm" id="commission_value_display" value="-" readonly>
                </div>

                <div class="col-12">
                    <label class="form-label mb-1">{{ trans('sales.commission_estimated') ?? 'قيمة العمولة' }}</label>
                    <input type="text" class="form-control form-control-sm" id="commission_amount_display" value="-" readonly>
                    <small class="text-muted d-block mt-1">
                        {{ trans('sales.commission_calculated_on_save') ?? 'سيتم حساب العمولة النهائية عند الحفظ حسب إعدادات الموظف.' }}
                    </small>
                    <small class="text-muted d-block" id="commission_calculated_on_hint">
                        -
                    </small>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {

    function toMoney(val) {
        const n = parseFloat(String(val ?? '0').replace(/,/g,'').trim());
        if (Number.isNaN(n)) return 0;
        return Math.max(0, n);
    }

    function readMoneyById(id, fallback = 0) {
        const el = document.getElementById(id);
        if (!el) return fallback;
        const raw = (el.value !== undefined) ? el.value : el.textContent;
        return toMoney(raw);
    }

    function readMoneyAny(ids, fallback = 0) {
        for (const id of ids) {
            const el = document.getElementById(id);
            if (el) return readMoneyById(id, fallback);
        }
        return fallback;
    }

    function setText(id, txt) {
        const el = document.getElementById(id);
        if (el) el.textContent = txt;
    }

    function setInputValue(id, txt) {
        const el = document.getElementById(id);
        if (el) el.value = txt;
    }

    function getCommissionBeforeDiscountsFlag() {
        return (document.getElementById('commission_before_discounts')?.value ?? '0') === '1';
    }

    function getSelectedEmployeeData() {
        const sel = document.getElementById('sales_employee_id');
        if (!sel || sel.selectedIndex < 0) return null;

        const opt = sel.options[sel.selectedIndex];
        const empId = (opt?.value || '').toString().trim();
        if (!empId) return null;

        return {
            id: empId,
            name: (opt.textContent || '').trim() || '-',
            type: (opt.dataset.commissionType || '').toString().trim(), // percent | fixed
            percent: toMoney(opt.dataset.commissionPercent || 0),
            fixed: toMoney(opt.dataset.commissionFixed || 0)
        };
    }

    function isCouponApplied() {
        // لو UI الكوبون ظاهر، نعتبره مطبّق (validated)
        const wrap = document.getElementById('couponStatusWrap') || document.getElementById('coupon_status_wrap');
        if (!wrap) return false;
        return (wrap.style.display !== 'none');
    }

    function updateCommissionPreview(gross, netTotal) {
        const emp = getSelectedEmployeeData();

        setInputValue('commission_net_amount_display', netTotal.toFixed(2));

        // أساس العمولة حسب الإعداد
        const base = getCommissionBeforeDiscountsFlag() ? gross : netTotal;
        setInputValue('commission_base_amount_display', base.toFixed(2));

        const baseHint = getCommissionBeforeDiscountsFlag()
            ? 'Base = Gross (قبل الخصومات)'
            : 'Base = Net (بعد الخصومات)';
        setText('commission_calculated_on_hint', baseHint);

        if (!emp) {
            setInputValue('commission_employee_name', '-');
            setInputValue('commission_value_type_display', '-');
            setInputValue('commission_value_display', '-');
            setInputValue('commission_amount_display', '-');
            return;
        }

        setInputValue('commission_employee_name', emp.name);

        let amount = 0;
        let valueTypeLabel = '-';
        let valueLabel = '-';

        if (emp.type === 'percent') {
            valueTypeLabel = 'percent';
            valueLabel = emp.percent.toFixed(2) + ' %';
            amount = base * (emp.percent / 100.0);
        } else if (emp.type === 'fixed') {
            valueTypeLabel = 'fixed';
            valueLabel = emp.fixed.toFixed(2);
            amount = emp.fixed;
        } else {
            valueTypeLabel = '-';
            valueLabel = '-';
            amount = 0;
        }

        setInputValue('commission_value_type_display', valueTypeLabel);
        setInputValue('commission_value_display', valueLabel);
        setInputValue('commission_amount_display', Math.max(0, amount).toFixed(2));
    }

    function updateInvoiceSummary() {
        const planPrice = readMoneyById('price_plan_display', 0);
        const ptPrice   = readMoneyById('price_pt_addons_display', 0);

        // gross: لو موجود في تاب العروض استخدمه، وإلا plan+pt
        const grossFromUI = readMoneyAny(['gross_amount_display','grossamountdisplay'], 0);
        const gross = (grossFromUI > 0) ? grossFromUI : (planPrice + ptPrice);

        // offer
        const offerDiscount = readMoneyAny(['offer_discount_display','offerdiscountdisplay'], 0);
        const afterOfferUI  = readMoneyAny(['amount_after_offer_display','amountafterofferdisplay'], 0);
        const afterOffer    = (afterOfferUI > 0) ? afterOfferUI : Math.max(0, gross - offerDiscount);

        // coupon
        let couponDiscount = 0;
        let afterCoupon = afterOffer;

        if (isCouponApplied()) {
            couponDiscount = readMoneyAny(['coupon_discount_display','coupondiscountdisplay'], 0);
            const afterCouponUI = readMoneyAny(['amount_after_coupon_display','amountaftercoupondisplay'], 0);
            afterCoupon = (afterCouponUI > 0) ? afterCouponUI : Math.max(0, afterOffer - couponDiscount);
        }

        const totalDiscount = Math.max(0, offerDiscount + couponDiscount);
        const netTotal = Math.max(0, gross - totalDiscount);

        // summary
        setText('sum_plan_price', planPrice.toFixed(2));
        setText('sum_pt_price', ptPrice.toFixed(2));
        setText('sum_gross', gross.toFixed(2));

        setText('sum_offer_discount', offerDiscount.toFixed(2));
        setText('sum_coupon_discount', couponDiscount.toFixed(2));
        setText('sum_total_discount', totalDiscount.toFixed(2));
        setText('sum_net_total', netTotal.toFixed(2));

        // offer/coupon labels (دعم أكثر من ID حسب باقي التابات)
        const offerNameEl = document.getElementById('best_offer_name') || document.getElementById('bestoffername');
        const offerName = (offerNameEl?.value || offerNameEl?.textContent || '').toString().trim();
        setText('sum_offer_name', offerName ? offerName : '-');

        const couponCodeEl = document.getElementById('coupon_code') || document.getElementById('couponcode');
        const couponCode = (couponCodeEl?.value || couponCodeEl?.textContent || '').toString().trim();
        setText('sum_coupon_code', couponCode ? couponCode : '-');

        // total amount display
        const totalDisplay = document.getElementById('total_amount_display');
        if (totalDisplay) {
            totalDisplay.value = (isCouponApplied() ? afterCoupon : afterOffer).toFixed(2);
        }

        // commission
        updateCommissionPreview(gross, netTotal);
    }

    const empSel = document.getElementById('sales_employee_id');
    if (empSel) {
        empSel.addEventListener('change', updateInvoiceSummary);
    }

    // تحديث دوري خفيف لأن القيم تتغير برمجيًا من تابات أخرى
    let lastSignature = '';
    setInterval(function () {
        const signature = [
            document.getElementById('price_plan_display')?.value,
            document.getElementById('price_pt_addons_display')?.value,
            document.getElementById('gross_amount_display')?.value,
            document.getElementById('offer_discount_display')?.value,
            document.getElementById('amount_after_offer_display')?.value,
            document.getElementById('coupon_discount_display')?.value,
            document.getElementById('amount_after_coupon_display')?.value,
            document.getElementById('best_offer_name')?.value,
            document.getElementById('coupon_code')?.value,
            (document.getElementById('couponStatusWrap') || document.getElementById('coupon_status_wrap'))?.style?.display,
            document.getElementById('sales_employee_id')?.value
        ].join('|');

        if (signature !== lastSignature) {
            lastSignature = signature;
            updateInvoiceSummary();
        }
    }, 350);

    updateInvoiceSummary();
});
</script>
