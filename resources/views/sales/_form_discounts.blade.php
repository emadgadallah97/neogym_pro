<div class="row">
    <div class="col-md-6 mb-3">
        <div class="border rounded p-3 bg-light">
            <h6 class="mb-2">
                <i class="mdi mdi-sale"></i>
                {{ trans('sales.offer_section') ?? 'العروض' }}
            </h6>

            <label class="form-label mb-1">{{ trans('sales.offers_list') ?? 'قائمة العروض المتاحة' }}</label>
            <select class="form-select form-select-sm" id="offers_select">
                <option value="">{{ trans('sales.auto_best_offer') ?? 'تلقائي (أفضل عرض)' }}</option>
            </select>

            <input type="hidden" name="offer_id" id="offer_id">

            <div class="row g-2 mt-2">
                <div class="col-6">
                    <label class="form-label mb-1">{{ trans('sales.gross_amount') ?? 'إجمالي قبل الخصم' }}</label>
                    <input type="text" class="form-control form-control-sm" id="gross_amount_display" value="0.00" readonly>
                </div>
                <div class="col-6">
                    <label class="form-label mb-1">{{ trans('sales.amount_after_offer') ?? 'بعد العرض' }}</label>
                    <input type="text" class="form-control form-control-sm" id="amount_after_offer_display" value="0.00" readonly>
                </div>

                <div class="col-8">
                    <label class="form-label mb-1">{{ trans('sales.selected_offer') ?? 'العرض المختار' }}</label>
                    <input type="text" class="form-control form-control-sm" id="best_offer_name" value="-" readonly>
                </div>
                <div class="col-4">
                    <label class="form-label mb-1">{{ trans('sales.offer_discount') ?? 'خصم العرض' }}</label>
                    <input type="text" class="form-control form-control-sm" id="offer_discount_display" value="0.00" readonly>
                </div>
            </div>

            <small class="text-muted d-block mt-2">
                {{ trans('sales.offer_list_hint') ?? 'اختر عرض يدويًا أو اتركه تلقائي ليختار النظام أفضل عرض.' }}
            </small>
        </div>
    </div>

    <div class="col-md-6 mb-3">
        <div class="border rounded p-3 bg-white">
            <h6 class="mb-2">
                <i class="mdi mdi-ticket-percent"></i>
                {{ trans('coupons_offers.coupons') }}
            </h6>

            <label class="form-label">{{ trans('coupons_offers.code') }}</label>

            <div class="input-group input-group-sm">
                <input type="text"
                       name="coupon_code"
                       id="coupon_code"
                       class="form-control"
                       placeholder="EX: NEWYEAR2026"
                       value="{{ old('coupon_code') }}">

                <button type="button" class="btn btn-soft-primary" id="btnValidateCoupon">
                    <i class="mdi mdi-check-circle-outline"></i>
                    {{ trans('sales.validate_coupon') ?? 'تحقق وتطبيق' }}
                </button>
            </div>

            <div class="mt-2" id="couponStatusWrap" style="display:none;">
                <div class="alert py-2 mb-2" id="couponStatusAlert" role="alert">
                    <span class="fw-semibold" id="couponStatusTitle">-</span>
                    <span class="ms-2" id="couponStatusText"></span>
                </div>

                <div class="row g-2">
                    <div class="col-6">
                        <label class="form-label mb-1">{{ trans('sales.coupon_discount') ?? 'خصم الكوبون' }}</label>
                        <input type="text" class="form-control form-control-sm" id="coupon_discount_display" value="0.00" readonly>
                    </div>
                    <div class="col-6">
                        <label class="form-label mb-1">{{ trans('sales.amount_after_coupon') ?? 'بعد الكوبون' }}</label>
                        <input type="text" class="form-control form-control-sm" id="amount_after_coupon_display" value="0.00" readonly>
                    </div>
                </div>
            </div>

            <small class="text-muted d-block mt-2">
                {{ trans('coupons_offers.coupon_hint') ?? 'اختياري؛ سيتم التحقق من صلاحية الكوبون عند الحفظ' }}
            </small>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {

    function debounce(fn, wait = 250) {
        let t = null;
        return function (...args) {
            clearTimeout(t);
            t = setTimeout(() => fn.apply(this, args), wait);
        };
    }

    function getToken() {
        return document.querySelector('input[name="_token"]')?.value || '';
    }

    async function safeJson(res) {
        const ct = (res.headers.get('content-type') || '').toLowerCase();
        if (ct.includes('application/json')) return await res.json();
        const text = await res.text();
        throw new Error(text.substring(0, 200));
    }

    async function postJson(url, payload, abortCtrl = null) {
        const res = await fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': getToken()
            },
            body: JSON.stringify(payload || {}),
            signal: abortCtrl ? abortCtrl.signal : undefined
        });

        const json = await safeJson(res);
        return { res, json };
    }

    function getPtAddonsPayload() {
        const ptRows = Array.from(document.querySelectorAll('#ptAddonsTable tbody tr'));
        return ptRows.map(tr => {
            const trainerId = tr.querySelector('.pt-trainer')?.value || '';
            const count = tr.querySelector('.pt-count')?.value || '';
            return { trainer_id: trainerId, sessions_count: count };
        }).filter(x => x.trainer_id && x.sessions_count);
    }

    function extractName(name) {
        if (!name) return '-';
        if (typeof name === 'object') {
            return name.ar || name.en || Object.values(name)[0] || '-';
        }
        return String(name);
    }

    async function fetchOffersList(payload, abortCtrl = null) {
        const { res, json } = await postJson(`{{ route('sales.ajax.offers_list') }}`, payload, abortCtrl);
        if (!res.ok || !json || !json.ok) throw new Error(json?.message || '{{ trans('sales.ajax_error_try_again') }}');
        return json;
    }

    async function validateCoupon(payload) {
        const { res, json } = await postJson(`{{ route('sales.ajax.validate_coupon') }}`, payload);
        if (!res.ok || !json || !json.ok) throw new Error(json?.message || '{{ trans('sales.coupon_invalid') }}');
        return json;
    }

    function resetCouponUI() {
        const wrap = document.getElementById('couponStatusWrap');
        const disc = document.getElementById('coupon_discount_display');
        const after = document.getElementById('amount_after_coupon_display');
        if (wrap) wrap.style.display = 'none';
        if (disc) disc.value = '0.00';
        if (after) after.value = '0.00';
    }

    function showCouponStatus(ok, title, text) {
        const wrap = document.getElementById('couponStatusWrap');
        const alert = document.getElementById('couponStatusAlert');
        const ttl = document.getElementById('couponStatusTitle');
        const msg = document.getElementById('couponStatusText');

        if (!wrap || !alert || !ttl || !msg) return;

        wrap.style.display = 'block';
        ttl.textContent = title || '-';
        msg.textContent = text || '';

        alert.classList.remove('alert-success','alert-danger','alert-warning');
        alert.classList.add(ok ? 'alert-success' : 'alert-danger');
    }

    function setOfferSnapshotUI(d) {
        const gross = document.getElementById('gross_amount_display');
        const afterOffer = document.getElementById('amount_after_offer_display');
        const offerDisc = document.getElementById('offer_discount_display');
        const offerName = document.getElementById('best_offer_name');

        if (gross) gross.value = Number(d.gross_amount || 0).toFixed(2);
        if (afterOffer) afterOffer.value = Number(d.amount_after_offer || 0).toFixed(2);
        if (offerDisc) offerDisc.value = Number(d.offer_discount || 0).toFixed(2);

        // server قد يرجع offer_name مباشرة (في validateCoupon) أو object داخل selected_offer (في preview)
        const nameTxt =
            extractName(d.offer_name) ||
            extractName(d.selected_offer?.offer?.name) ||
            extractName(d.best_offer?.offer?.name);

        if (offerName) offerName.value = nameTxt || '-';
    }

    // =========================================================
    // ✅ offers loader: تحميل القائمة فقط (بدون preview/refresh)
    // =========================================================
    let offersAbort = null;

    window.salesLoadOffers = debounce(async function () {
        // أثناء Hydration (ملء select2) لا تحمل عروض
        if (window.__salesHydrating) return;

        const branchId = document.getElementById('branch_id')?.value || '';
        const planId   = document.getElementById('subscriptions_plan_id')?.value || '';
        const typeId   = document.getElementById('subscriptions_type_id')?.value || '';

        if (!branchId || !planId) return;

        const payload = {
            branch_id: branchId,
            subscriptions_plan_id: planId,
            subscriptions_type_id: typeId || null,
            pt_addons: getPtAddonsPayload()
        };

        try {
            if (offersAbort) offersAbort.abort();
            offersAbort = new AbortController();

            const json = await fetchOffersList(payload, offersAbort);
            const offers = (json.data.offers || []);

            const sel = document.getElementById('offers_select');
            if (!sel) return;

            const oldVal = sel.value;

            sel.innerHTML = `<option value="">{{ trans('sales.auto_best_offer') ?? 'تلقائي (أفضل عرض)' }}</option>`;

            offers.forEach(o => {
                const nameTxt = extractName(o.name);
                const text = `${nameTxt} | -${Number(o.discount_amount).toFixed(2)} | ${Number(o.amount_after).toFixed(2)}`;
                const opt = document.createElement('option');
                opt.value = o.offer_id;
                opt.textContent = text;
                sel.appendChild(opt);
            });

            // الحفاظ على اختيار المستخدم إن كان مازال متاحًا
            if (oldVal && Array.from(sel.options).some(x => String(x.value) === String(oldVal))) {
                sel.value = oldVal;
            }

            // لأن المبالغ قد تتغير بعد أي تحديث (PT/plan/branch) نعيد تصفير UI للكوبون فقط
            resetCouponUI();

            // ✅ ممنوع استدعاء salesPreviewPricing هنا لتجنب loop
        } catch (e) {
            if (e && e.name === 'AbortError') return;
            console.error(e);
        }
    }, 250);

    // =========================================================
    // Offer selection change => preview فقط (بدون إعادة تحميل offers)
    // =========================================================
    const offersSelect = document.getElementById('offers_select');
    const offerIdInput = document.getElementById('offer_id');

    if (offersSelect) {
        offersSelect.addEventListener('change', function () {
            if (offerIdInput) offerIdInput.value = offersSelect.value || '';
            resetCouponUI();

            // ✅ هنا نحتاج preview فقط لأن العرض تغيّر
            window.salesPreviewPricing && window.salesPreviewPricing();
        });
    }

    // =========================================================
    // Coupon
    // =========================================================
    const btnValidate = document.getElementById('btnValidateCoupon');
    const couponInput = document.getElementById('coupon_code');

    if (couponInput) {
        couponInput.addEventListener('input', function () {
            resetCouponUI();
        });
    }

    if (btnValidate) {
        btnValidate.addEventListener('click', async function () {
            const code = (couponInput?.value || '').trim();
            if (!code) {
                showCouponStatus(false, '{{ trans('sales.coupon_invalid') }}', '{{ trans('sales.coupon_empty') }}');
                return;
            }

            const branchId = document.getElementById('branch_id')?.value || '';
            const planId   = document.getElementById('subscriptions_plan_id')?.value || '';
            const typeId   = document.getElementById('subscriptions_type_id')?.value || '';
            const offerId  = document.getElementById('offer_id')?.value || '';
            const memberId = document.getElementById('member_id')?.value || '';

            if (!branchId || !planId) {
                showCouponStatus(false, '{{ trans('sales.coupon_invalid') }}', '{{ trans('sales.choose_branch_first') ?? 'اختر الفرع أولاً' }}');
                return;
            }

            btnValidate.disabled = true;
            btnValidate.innerHTML = `<i class="mdi mdi-loading mdi-spin"></i> {{ trans('sales.validating') ?? 'جارِ التحقق...' }}`;

            try {
                const couponPayload = {
                    branch_id: branchId,
                    subscriptions_plan_id: planId,
                    subscriptions_type_id: typeId || null,
                    offer_id: offerId || null,
                    pt_addons: getPtAddonsPayload(),
                    coupon_code: code,
                    member_id: memberId || null
                };

                const json = await validateCoupon(couponPayload);
                const d = json.data || {};

                // تحديث snapshot من رد validate (أدق لأنه يحسب offer + coupon معًا)
                setOfferSnapshotUI(d);

                document.getElementById('coupon_discount_display').value = Number(d.coupon_discount || 0).toFixed(2);
                document.getElementById('amount_after_coupon_display').value = Number(d.amount_after_coupon || 0).toFixed(2);

                showCouponStatus(true, '{{ trans('sales.coupon_valid') }}', d.message || '');
            } catch (e) {
                console.error(e);
                document.getElementById('coupon_discount_display').value = '0.00';
                document.getElementById('amount_after_coupon_display').value = '0.00';
                showCouponStatus(false, '{{ trans('sales.coupon_invalid') }}', e.message || '');
            } finally {
                btnValidate.disabled = false;
                btnValidate.innerHTML = `<i class="mdi mdi-check-circle-outline"></i> {{ trans('sales.validate_coupon') ?? 'تحقق وتطبيق' }}`;
            }
        });
    }
});
</script>
