@php
$Employees = $Employees ?? [];

$commissionBeforeDiscounts = isset($commission_before_discounts)
? (bool)$commission_before_discounts
: (bool)(\Illuminate\Support\Facades\DB::table('commission_settings')->where('id', 1)->value('calculate_commission_before_discounts') ?? 0);
@endphp

<input type="hidden" id="commission_before_discounts" value="{{ $commissionBeforeDiscounts ? 1 : 0 }}">

{{-- هذه الحقول مخفية لأن الـ JS يحتاجها لحساب الملخص --}}
<input type="hidden" id="price_plan_display" value="0.00">
<input type="hidden" id="price_pt_addons_display" value="0.00">
<input type="hidden" id="total_amount_display" value="0.00">

{{-- ✅ Card 1: ملخص الفاتورة + طريقة الدفع --}}
<div class="card shadow-sm mb-3 border-primary border-opacity-25">
    <div class="card-header bg-primary bg-opacity-10 border-0 py-2">
        <h6 class="card-title mb-0 text-primary">
            <i class="mdi mdi-receipt-text-outline me-1"></i>
            {{ trans('sales.invoice_summary') ?? 'ملخص الفاتورة' }}
        </h6>
    </div>
    <div class="card-body p-0">
        <table class="table table-sm table-borderless align-middle mb-0">
            <tbody>
                <tr>
                    <td class="text-muted ps-3">{{ trans('sales.item_plan') ?? 'الخطة' }}</td>
                    <td class="text-end pe-3 fw-semibold"><span id="sum_plan_price">0.00</span></td>
                </tr>
                <tr>
                    <td class="text-muted ps-3">{{ trans('sales.item_pt_addons') ?? 'جلسات المدرب (PT)' }}</td>
                    <td class="text-end pe-3 fw-semibold"><span id="sum_pt_price">0.00</span></td>
                </tr>
                <tr class="border-top">
                    <td class="text-muted ps-3">{{ trans('sales.subtotal_gross') ?? 'الإجمالي قبل الخصم' }}</td>
                    <td class="text-end pe-3 fw-semibold"><span id="sum_gross">0.00</span></td>
                </tr>
                <tr>
                    <td class="text-muted ps-3">
                        {{ trans('sales.discount_offer') ?? 'خصم العرض' }}
                        <small class="d-block text-muted" id="sum_offer_name">-</small>
                    </td>
                    <td class="text-end pe-3 text-danger">-<span id="sum_offer_discount">0.00</span></td>
                </tr>
                <tr>
                    <td class="text-muted ps-3">
                        {{ trans('sales.discount_coupon') ?? 'خصم الكوبون' }}
                        <small class="d-block text-muted" id="sum_coupon_code">-</small>
                    </td>
                    <td class="text-end pe-3 text-danger">-<span id="sum_coupon_discount">0.00</span></td>
                </tr>
                <tr class="border-top">
                    <td class="text-muted ps-3">{{ trans('sales.total_discount') ?? 'إجمالي الخصومات' }}</td>
                    <td class="text-end pe-3 text-danger fw-semibold">-<span id="sum_total_discount">0.00</span></td>
                </tr>
                <tr class="bg-primary bg-opacity-10">
                    <td class="ps-3 fw-bold">{{ trans('sales.net_total') ?? 'الإجمالي المستحق' }}</td>
                    <td class="text-end pe-3 fw-bold fs-5"><span id="sum_net_total">0.00</span></td>
                </tr>
            </tbody>
        </table>

        {{-- طريقة الدفع --}}
        <div class="px-3 pb-3 pt-2 border-top">
            <label class="form-label mb-1 fw-semibold">
                <i class="mdi mdi-credit-card-outline me-1"></i>
                {{ trans('sales.payment_method') ?? 'طريقة الدفع' }}
            </label>
            <select name="payment_method" class="form-select form-select-sm" required>
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
</div>

{{-- ✅ Card 2: موظف المبيعات + العمولة (مدمج) --}}
<div class="card shadow-sm mb-3">
    <div class="card-header bg-white border-0 py-2">
        <h6 class="card-title mb-0">
            <i class="mdi mdi-account-cash-outline me-1"></i>
            {{ trans('sales.sales_employee') ?? 'موظف المبيعات' }} & {{ trans('sales.commission_section') ?? 'العمولة' }}
        </h6>
    </div>
    <div class="card-body pt-1">
        <select name="sales_employee_id" class="form-select form-select-sm mb-2" id="sales_employee_id">
            <option value="">{{ trans('settings_trans.choose') }}</option>
            @foreach($Employees as $Emp)
            @php
            $empName = $Emp->full_name ?? trim(($Emp->first_name ?? '').' '.($Emp->last_name ?? ''));
            $ctype = $Emp->commission_value_type ?? '';
            $cpercent = $Emp->commission_percent ?? 0;
            $cfixed = $Emp->commission_fixed ?? 0;
            @endphp
            <option
                value="{{ $Emp->id }}"
                data-commission-type="{{ $ctype }}"
                data-commission-percent="{{ $cpercent }}"
                data-commission-fixed="{{ $cfixed }}">
                {{ $empName }}
            </option>
            @endforeach
        </select>

        <div class="row g-2" id="commissionPreviewWrap" style="display:none;">
            <div class="col-6">
                <small class="text-muted d-block">{{ trans('sales.commission_value_type') ?? 'النوع' }}</small>
                <span class="fw-semibold" id="commission_value_type_display">-</span>
            </div>
            <div class="col-6">
                <small class="text-muted d-block">{{ trans('sales.commission_value') ?? 'القيمة' }}</small>
                <span class="fw-semibold" id="commission_value_display">-</span>
            </div>
            <div class="col-6">
                <small class="text-muted d-block">{{ trans('sales.commission_base_amount') ?? 'الأساس' }}</small>
                <span class="fw-semibold" id="commission_base_amount_display">0.00</span>
            </div>
            <div class="col-6">
                <small class="text-muted d-block">{{ trans('sales.commission_estimated') ?? 'العمولة' }}</small>
                <span class="fw-bold text-success" id="commission_amount_display">0.00</span>
            </div>
            <div class="col-12">
                <small class="text-muted" id="commission_calculated_on_hint">-</small>
            </div>
        </div>

        {{-- Hidden inputs for backward compat --}}
        <input type="hidden" id="commission_employee_name" value="-">
        <input type="hidden" id="commission_net_amount_display" value="0.00">
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {

        function toMoney(val) {
            const n = parseFloat(String(val ?? '0').replace(/,/g, '').trim());
            if (Number.isNaN(n)) return 0;
            return Math.max(0, n);
        }

        function readMoneyById(id, fallback = 0) {
            const el = document.getElementById(id);
            if (!el) return fallback;
            const raw = (el.value !== undefined && el.value !== '') ? el.value : el.textContent;
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
                type: (opt.dataset.commissionType || '').toString().trim(),
                percent: toMoney(opt.dataset.commissionPercent || 0),
                fixed: toMoney(opt.dataset.commissionFixed || 0)
            };
        }

        function isCouponApplied() {
            const wrap = document.getElementById('couponStatusWrap') || document.getElementById('coupon_status_wrap');
            if (!wrap) return false;
            return (wrap.style.display !== 'none');
        }

        function updateCommissionPreview(gross, netTotal) {
            const emp = getSelectedEmployeeData();
            const wrap = document.getElementById('commissionPreviewWrap');

            setInputValue('commission_net_amount_display', netTotal.toFixed(2));

            const base = getCommissionBeforeDiscountsFlag() ? gross : netTotal;
            setInputValue('commission_base_amount_display', base.toFixed(2));

            const baseHint = getCommissionBeforeDiscountsFlag() ?
                '{{ trans("sales.commission_base_gross") ?? "Base = Gross (قبل الخصومات)" }}' :
                '{{ trans("sales.commission_base_net") ?? "Base = Net (بعد الخصومات)" }}';
            setText('commission_calculated_on_hint', baseHint);

            if (!emp) {
                if (wrap) wrap.style.display = 'none';
                setInputValue('commission_employee_name', '-');
                return;
            }

            if (wrap) wrap.style.display = '';
            setInputValue('commission_employee_name', emp.name);

            let amount = 0;
            let valueTypeLabel = '-';
            let valueLabel = '-';

            if (emp.type === 'percent') {
                valueTypeLabel = '{{ trans("sales.commission_type_percent") ?? "نسبة %" }}';
                valueLabel = emp.percent.toFixed(2) + ' %';
                amount = base * (emp.percent / 100.0);
            } else if (emp.type === 'fixed') {
                valueTypeLabel = '{{ trans("sales.commission_type_fixed") ?? "مبلغ ثابت" }}';
                valueLabel = emp.fixed.toFixed(2);
                amount = emp.fixed;
            }

            setText('commission_value_type_display', valueTypeLabel);
            setText('commission_value_display', valueLabel);
            setText('commission_base_amount_display', base.toFixed(2));
            setText('commission_amount_display', Math.max(0, amount).toFixed(2));
        }

        function updateInvoiceSummary() {
            const planPrice = readMoneyById('price_plan_display', 0);
            const ptPrice = readMoneyById('price_pt_addons_display', 0);

            const grossFromUI = readMoneyAny(['gross_amount_display', 'grossamountdisplay'], 0);
            const gross = (grossFromUI > 0) ? grossFromUI : (planPrice + ptPrice);

            const offerDiscount = readMoneyAny(['offer_discount_display', 'offerdiscountdisplay'], 0);
            const afterOfferUI = readMoneyAny(['amount_after_offer_display', 'amountafterofferdisplay'], 0);
            const afterOffer = (afterOfferUI > 0) ? afterOfferUI : Math.max(0, gross - offerDiscount);

            let couponDiscount = 0;
            let afterCoupon = afterOffer;

            if (isCouponApplied()) {
                couponDiscount = readMoneyAny(['coupon_discount_display', 'coupondiscountdisplay'], 0);
                const afterCouponUI = readMoneyAny(['amount_after_coupon_display', 'amountaftercoupondisplay'], 0);
                afterCoupon = (afterCouponUI > 0) ? afterCouponUI : Math.max(0, afterOffer - couponDiscount);
            }

            const totalDiscount = Math.max(0, offerDiscount + couponDiscount);
            const netTotal = Math.max(0, gross - totalDiscount);

            setText('sum_plan_price', planPrice.toFixed(2));
            setText('sum_pt_price', ptPrice.toFixed(2));
            setText('sum_gross', gross.toFixed(2));

            setText('sum_offer_discount', offerDiscount.toFixed(2));
            setText('sum_coupon_discount', couponDiscount.toFixed(2));
            setText('sum_total_discount', totalDiscount.toFixed(2));
            setText('sum_net_total', netTotal.toFixed(2));

            const offerNameEl = document.getElementById('best_offer_name') || document.getElementById('bestoffername');
            const offerName = (offerNameEl?.value || offerNameEl?.textContent || '').toString().trim();
            setText('sum_offer_name', offerName ? offerName : '-');

            const couponCodeEl = document.getElementById('coupon_code') || document.getElementById('couponcode');
            const couponCode = (couponCodeEl?.value || couponCodeEl?.textContent || '').toString().trim();
            setText('sum_coupon_code', couponCode ? couponCode : '-');

            const totalDisplay = document.getElementById('total_amount_display');
            if (totalDisplay) {
                totalDisplay.value = (isCouponApplied() ? afterCoupon : afterOffer).toFixed(2);
            }

            updateCommissionPreview(gross, netTotal);
        }

        const empSel = document.getElementById('sales_employee_id');
        if (empSel) {
            empSel.addEventListener('change', updateInvoiceSummary);
        }

        let lastSignature = '';
        setInterval(function() {
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