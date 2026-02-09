<div class="row">
    <div class="col-md-6 mb-3">
        <label class="form-label">{{ trans('sales.plan_price_without_trainer') ?? 'سعر الاشتراك (بدون مدرب)' }}</label>
        <input type="text" class="form-control" id="price_without_trainer_display" value="0.00" readonly>
        <small class="text-muted">
            {{ trans('sales.price_updates_by_branch_plan') ?? 'يتغير السعر حسب الفرع والخطة.' }}
        </small>
    </div>

    <div class="col-md-6 mb-3">
        <label class="form-label">{{ trans('sales.branch_coaches_note') ?? 'المدربين' }}</label>
        <div class="form-text" id="coachesHint">
            {{ trans('sales.choose_branch_to_load_coaches') ?? 'اختر الفرع لعرض المدربين المتاحين.' }}
        </div>
    </div>
</div>

<hr>

<div class="row">
    <div class="col-12 mb-2">
        <h6 class="mb-0">
            <i class="mdi mdi-plus-circle-outline"></i>
            {{ trans('sales.pt_addons_title') ?? 'جلسات مدرب إضافية (PT Add-ons)' }}
        </h6>
        <small class="text-muted">
            <span>{{ trans('sales.pt_addons_hint') ?? 'يمكن إضافة أكثر من باقة جلسات لكل اشتراك.' }}</span>
        </small>
    </div>

    <div class="col-12">
        <table class="table table-bordered align-middle" id="ptAddonsTable">
            <thead class="table-light">
                <tr>
                    <th style="width:60px;">#</th>
                    <th>{{ trans('sales.trainer') ?? 'المدرب' }}</th>
                    <th style="width:160px;">{{ trans('sales.session_price') ?? 'سعر الجلسة' }}</th>
                    <th style="width:160px;">{{ trans('sales.sessions_count') ?? 'عدد الجلسات' }}</th>
                    <th style="width:180px;">{{ trans('sales.pt_total') ?? 'الإجمالي' }}</th>
                    <th style="width:90px;">{{ trans('coupons_offers.actions') ?? 'العمليات' }}</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>

        <button type="button" class="btn btn-soft-primary btn-sm" id="btnAddPtAddon">
            <i class="ri-add-line"></i> {{ trans('sales.add_pt_addon') ?? 'إضافة باقة جلسات' }}
        </button>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {

    const TOKEN = document.querySelector('input[name="_token"]')?.value || '';

    const URL_BASE_PRICE = "{{ route('sales.ajax.plan_base_price') }}";
    const URL_COACHES_BY_BRANCH = "{{ route('sales.ajax.coaches_by_branch') }}";
    const URL_TRAINER_SESSION_PRICE = "{{ route('sales.ajax.trainer_session_price') }}";

    const branchSel = document.getElementById('branch_id');
    const planSel   = document.getElementById('subscriptions_plan_id');

    const priceWithoutTrainerDisplay = document.getElementById('price_without_trainer_display');
    const coachesHint = document.getElementById('coachesHint');

    const tableBody = document.querySelector('#ptAddonsTable tbody');
    const btnAdd = document.getElementById('btnAddPtAddon');

    let coachesCache = []; // [{id,text}, ...]
    let suppressPtEvents = false; // لمنع trigger change أثناء بناء الخيارات

    // AbortControllers لتقليل تكدس الطلبات
    let abortBasePrice = null;
    let abortCoaches = null;

    function debounce(fn, wait = 250) {
        let t = null;
        return function (...args) {
            clearTimeout(t);
            t = setTimeout(() => fn.apply(this, args), wait);
        };
    }

    function initSelect2IfNeeded(el) {
        if (!(typeof $ !== 'undefined' && $.fn && $.fn.select2)) return;
        const $el = $(el);
        if (!$el.length) return;
        if ($el.hasClass('select2-hidden-accessible')) return;

        $el.select2({
            placeholder: '{{ trans('settings_trans.choose') }}',
            allowClear: true,
            language: 'ar',
            dir: 'rtl',
            width: '100%'
        });
    }

    function destroySelect2IfNeeded(el) {
        if (!(typeof $ !== 'undefined' && $.fn && $.fn.select2)) return;
        const $el = $(el);
        if ($el.hasClass('select2-hidden-accessible')) $el.select2('destroy');
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
                'X-CSRF-TOKEN': TOKEN
            },
            body: JSON.stringify(payload || {}),
            signal: abortCtrl ? abortCtrl.signal : undefined
        });

        const json = await safeJson(res);
        if (!res.ok || !json || json.ok === false) {
            const msg = (json && (json.message || json.error)) ? (json.message || json.error) : '{{ trans('sales.ajax_error_try_again') }}';
            throw new Error(msg);
        }
        return json;
    }

    function renumberRows() {
        Array.from(tableBody.children).forEach((tr, idx) => {
            tr.querySelector('.row-index').textContent = idx + 1;
            const trainerSel = tr.querySelector('.pt-trainer');
            const countInp   = tr.querySelector('.pt-count');

            trainerSel.name = `pt_addons[${idx}][trainer_id]`;
            countInp.name   = `pt_addons[${idx}][sessions_count]`;
        });
    }

    function computeRowTotal(tr) {
        const price = parseFloat(tr.querySelector('.pt-price')?.value || '0');
        const count = parseInt(tr.querySelector('.pt-count')?.value || '0', 10);
        const total = Math.max(0, price * Math.max(0, count));
        const totalEl = tr.querySelector('.pt-total');
        if (totalEl) totalEl.value = total.toFixed(2);
    }

    function fillTrainerSelect(selectEl, selectedId = '') {
        suppressPtEvents = true;

        destroySelect2IfNeeded(selectEl);

        const current = selectedId || selectEl.value || '';
        selectEl.innerHTML = '';
        selectEl.appendChild(new Option("{{ trans('settings_trans.choose') }}", ""));

        coachesCache.forEach(c => {
            selectEl.appendChild(new Option(c.text, c.id));
        });

        const exists = Array.from(selectEl.options).some(o => String(o.value) === String(current));
        selectEl.value = exists ? current : '';

        initSelect2IfNeeded(selectEl);

        // تحديث UI فقط بدون trigger change (لتفادي تشغيل handler)
        if (typeof $ !== 'undefined') {
            $(selectEl).trigger('change.select2');
        }

        suppressPtEvents = false;
    }

    const refreshAll = debounce(function () {
        // ✅ نقطة واحدة فقط لتحديث preview+offers لمنع التكرار
        window.salesRefreshAll && window.salesRefreshAll();
    }, 200);

    async function loadBasePrice() {
        const branchId = branchSel ? branchSel.value : '';
        const planId   = planSel ? planSel.value : '';

        priceWithoutTrainerDisplay.value = '0.00';
        if (!branchId || !planId) return;

        try {
            if (abortBasePrice) abortBasePrice.abort();
            abortBasePrice = new AbortController();

            const json = await postJson(URL_BASE_PRICE, {
                branch_id: parseInt(branchId, 10),
                subscriptions_plan_id: parseInt(planId, 10)
            }, abortBasePrice);

            const price = parseFloat(json.data.price_without_trainer || 0);
            priceWithoutTrainerDisplay.value = price.toFixed(2);

            // ✅ لا تنادي preview/offers هنا
            refreshAll();
        } catch (e) {
            if (e && e.name === 'AbortError') return;
            console.error(e);
        }
    }

    async function loadCoachesByBranch() {
        const branchId = branchSel ? branchSel.value : '';
        coachesCache = [];

        if (!branchId) {
            coachesHint.textContent = "{{ trans('sales.choose_branch_to_load_coaches') ?? 'اختر الفرع لعرض المدربين المتاحين.' }}";
            Array.from(tableBody.querySelectorAll('.pt-trainer')).forEach(sel => fillTrainerSelect(sel));
            return;
        }

        try {
            if (abortCoaches) abortCoaches.abort();
            abortCoaches = new AbortController();

            const json = await postJson(URL_COACHES_BY_BRANCH, { branch_id: parseInt(branchId, 10) }, abortCoaches);
            coachesCache = json.data || [];

            coachesHint.textContent = (coachesCache.length === 0)
                ? "{{ trans('sales.no_coaches_in_branch') ?? 'لا يوجد مدربين مرتبطين بهذا الفرع.' }}"
                : "{{ trans('sales.coaches_loaded') ?? 'تم تحميل المدربين حسب الفرع المختار.' }}";

            Array.from(tableBody.querySelectorAll('.pt-trainer')).forEach(sel => fillTrainerSelect(sel));
        } catch (e) {
            if (e && e.name === 'AbortError') return;
            console.error(e);
        }
    }

    async function fetchTrainerSessionPrice(branchId, trainerId) {
        return await postJson(URL_TRAINER_SESSION_PRICE, {
            branch_id: parseInt(branchId, 10),
            trainer_id: parseInt(trainerId, 10)
        });
    }

    function addPtRow() {
        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td class="row-index">-</td>
            <td>
                <select class="form-select form-select-sm pt-trainer select2">
                    <option value="">{{ trans('settings_trans.choose') }}</option>
                </select>
            </td>
            <td>
                <input type="text" class="form-control form-control-sm pt-price" value="0.00" readonly>
            </td>
            <td>
                <input type="number" min="1" class="form-control form-control-sm pt-count" value="1">
            </td>
            <td>
                <input type="text" class="form-control form-control-sm pt-total" value="0.00" readonly>
            </td>
            <td class="text-center">
                <button type="button" class="btn btn-soft-danger btn-sm btn-remove-pt-row">
                    <i class="ri-delete-bin-line"></i>
                </button>
            </td>
        `;

        tableBody.appendChild(tr);
        renumberRows();

        const sel = tr.querySelector('.pt-trainer');
        fillTrainerSelect(sel);
        initSelect2IfNeeded(sel);

        refreshAll();
    }

    if (btnAdd) btnAdd.addEventListener('click', addPtRow);

    tableBody.addEventListener('click', function (e) {
        if (e.target.closest('.btn-remove-pt-row')) {
            e.target.closest('tr').remove();
            renumberRows();
            refreshAll();
        }
    });

    // trainer change (تجاهل أثناء build options)
    $(document).on('change', '.pt-trainer', async function () {
        if (suppressPtEvents) return;

        const tr = this.closest('tr');
        if (!tr) return;

        const branchId = branchSel ? branchSel.value : '';
        const trainerId = parseInt(this.value || '0', 10);

        if (!branchId || trainerId <= 0) {
            tr.querySelector('.pt-price').value = '0.00';
            computeRowTotal(tr);
            refreshAll();
            return;
        }

        try {
            const r = await fetchTrainerSessionPrice(branchId, trainerId);
            tr.querySelector('.pt-price').value = Number(r.data.session_price ?? 0).toFixed(2);
        } catch (e) {
            console.error(e);
            tr.querySelector('.pt-price').value = '0.00';
        }

        computeRowTotal(tr);
        refreshAll();
    });

    // sessions count change
    $(document).on('change', '.pt-count', function () {
        const tr = this.closest('tr');
        if (!tr) return;
        computeRowTotal(tr);
        refreshAll();
    });

    // ✅ branch/plan events: استخدم change فقط (بدون select2:select) لتجنب double triggers
    $(document).on('change', '#branch_id', function () {
        loadCoachesByBranch();
        // بعد تغيير الفرع، سعر الخطة يتأثر فقط لو plan محدد
        loadBasePrice();
    });

    $(document).on('change', '#subscriptions_plan_id', function () {
        loadBasePrice();
    });

    // initial
    loadCoachesByBranch();
    loadBasePrice();
});
</script>
