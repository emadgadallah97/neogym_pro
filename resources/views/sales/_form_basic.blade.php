@php
    $Branches = $Branches ?? [];
    $Members  = $Members ?? [];
    $Plans    = $Plans ?? [];
    $Types    = $Types ?? collect(); // مهم عشان map() ما تعملش error لو Types null
@endphp

<div class="row">
    {{-- الفرع --}}
    <div class="col-md-4 mb-3">
        <label class="form-label">{{ trans('settings_trans.branches') ?? 'الفرع' }}</label>
        <select name="branch_id" class="form-select select2" id="branch_id" required>
            <option value="">{{ trans('settings_trans.choose') }}</option>
            @foreach($Branches as $Branch)
                <option value="{{ $Branch->id }}" {{ old('branch_id') == $Branch->id ? 'selected' : '' }}>
                    {{ $Branch->getTranslation('name','ar') }}
                </option>
            @endforeach
        </select>
    </div>

    {{-- العضو --}}
    <div class="col-md-4 mb-3">
        <label class="form-label">{{ trans('members.members') ?? 'العضو' }}</label>
        <select name="member_id" class="form-select select2" id="member_id" required disabled>
            <option value="">{{ trans('sales.choose_branch_first') ?? 'اختر الفرع أولاً' }}</option>
        </select>
        <small class="text-muted">{{ trans('sales.members_hint') ?? 'سيتم تحميل الأعضاء بعد اختيار الفرع' }}</small>
    </div>

    {{-- الخطة --}}
    <div class="col-md-4 mb-3">
        <label class="form-label">{{ trans('subscriptions.subscriptions_plans') }}</label>
        <select name="subscriptions_plan_id" class="form-select select2" id="subscriptions_plan_id" required disabled>
            <option value="">{{ trans('sales.choose_branch_first') ?? 'اختر الفرع أولاً' }}</option>
        </select>
        <small class="text-muted">{{ trans('sales.plans_hint') ?? 'سيتم تحميل الخطط بعد اختيار الفرع' }}</small>
    </div>

    {{-- نوع الاشتراك (عرض فقط) --}}
    <div class="col-md-4 mb-3">
        <label class="form-label">{{ trans('subscriptions.subscriptions_types') ?? 'نوع الاشتراك' }}</label>
        <input type="text" class="form-control" id="subscription_type_display" readonly>
        <input type="hidden" name="subscriptions_type_id" id="subscriptions_type_id" value="{{ old('subscriptions_type_id') }}">
    </div>

    {{-- تاريخ البداية --}}
    <div class="col-md-4 mb-3">
        <label class="form-label">{{ trans('members.date_join') ?? 'تاريخ البداية' }}</label>
        <input type="date" class="form-control" name="start_date" id="start_date" value="{{ old('start_date', date('Y-m-d')) }}" required>
    </div>

    {{-- تاريخ النهاية (يُحسب تلقائياً) --}}
    <div class="col-md-4 mb-3">
        <label class="form-label">{{ trans('subscriptions.end_date') ?? 'تاريخ النهاية' }}</label>
        <input type="date" class="form-control" id="end_date" readonly>
    </div>

    {{-- عدد الحصص (عرض فقط) --}}
    <div class="col-md-4 mb-3">
        <label class="form-label">{{ trans('subscriptions.sessions_count') ?? 'عدد الحصص' }}</label>
        <input type="number" class="form-control" id="sessions_count_display" readonly>
    </div>

    {{-- الحضور من أي فرع --}}
    <div class="col-md-4 mb-3">
        <label class="form-label d-block">{{ trans('sales.allow_all_branches') ?? 'الحضور من أي فرع' }}</label>
        <div class="form-check form-switch">
            <input class="form-check-input" type="checkbox" name="allow_all_branches" id="allow_all_branches" value="1" {{ old('allow_all_branches') ? 'checked' : '' }}>
            <label class="form-check-label" for="allow_all_branches">
                {{ trans('sales.allow_all_branches_label') ?? 'نعم، يمكنه الحضور من أي فرع' }}
            </label>
        </div>
    </div>

    {{-- قناة الاشتراك --}}
    <div class="col-md-4 mb-3">
        <label class="form-label">{{ trans('sales.source') ?? 'قناة الاشتراك' }}</label>
        <select name="source" class="form-select select2" id="source">
            <option value="reception" {{ old('source') == 'reception' ? 'selected' : '' }}>{{ trans('sales.source_reception') ?? 'الاستقبال' }}</option>
            <option value="website" {{ old('source') == 'website' ? 'selected' : '' }}>{{ trans('sales.source_website') ?? 'الموقع' }}</option>
            <option value="mobile" {{ old('source') == 'mobile' ? 'selected' : '' }}>{{ trans('sales.source_mobile') ?? 'موبايل' }}</option>
            <option value="call_center" {{ old('source') == 'call_center' ? 'selected' : '' }}>{{ trans('sales.source_call_center') ?? 'مركز اتصال' }}</option>
            <option value="partner" {{ old('source') == 'partner' ? 'selected' : '' }}>{{ trans('sales.source_partner') ?? 'شريك' }}</option>
            <option value="other" {{ old('source') == 'other' ? 'selected' : '' }}>{{ trans('sales.source_other') ?? 'أخرى' }}</option>
        </select>
    </div>

    {{-- ملاحظات --}}
    <div class="col-12 mb-3">
        <label class="form-label">{{ trans('settings_trans.notes') }}</label>
        <textarea name="notes" rows="2" class="form-control">{{ old('notes') }}</textarea>
    </div>
</div>

@php
    $typesForJs = $Types->map(function($t) {
        return [
            'id'   => $t->id,
            'name' => $t->getTranslation('name', app()->getLocale()),
        ];
    })->toArray();

    $oldMemberId = old('member_id');
    $oldPlanId   = old('subscriptions_plan_id');
@endphp

<script>
document.addEventListener('DOMContentLoaded', function () {

    const MEMBERS_URL = "{{ route('sales.ajax.members_by_branch') }}";
    const PLANS_URL   = "{{ route('sales.ajax.plans_by_branch') }}";

    const types = @json($typesForJs);

    const oldMemberId = @json($oldMemberId);
    const oldPlanId   = @json($oldPlanId);

    const token = document.querySelector('input[name="_token"]')?.value || '';

    const $branch = $('#branch_id');
    const $member = $('#member_id');
    const $plan   = $('#subscriptions_plan_id');

    const typeDisplay     = document.getElementById('subscription_type_display');
    const typeHidden      = document.getElementById('subscriptions_type_id');
    const sessionsDisplay = document.getElementById('sessions_count_display');

    function safeSelect2Init() {
        if (!(typeof $ !== 'undefined' && $.fn && $.fn.select2)) return;

        $('.select2').each(function () {
            const $el = $(this);
            if ($el.hasClass('select2-hidden-accessible')) return;
            $el.select2({
                placeholder: "{{ trans('settings_trans.choose') }}",
                allowClear: true,
                language: 'ar',
                dir: 'rtl',
                width: '100%'
            });
        });
    }

    async function safeJson(res) {
        const ct = (res.headers.get('content-type') || '').toLowerCase();
        if (ct.includes('application/json')) return await res.json();
        const text = await res.text();
        throw new Error(text.substring(0, 200));
    }

    function resetMemberAndPlan() {
        window.__salesHydrating = true;

        $member.prop('disabled', true);
        $plan.prop('disabled', true);

        $member.empty().append(new Option("{{ trans('sales.choose_branch_first') ?? 'اختر الفرع أولاً' }}", ""));
        $plan.empty().append(new Option("{{ trans('sales.choose_branch_first') ?? 'اختر الفرع أولاً' }}", ""));

        // تحديث select2 UI فقط بدون مناداة preview هنا
        $member.trigger('change.select2');
        $plan.trigger('change.select2');

        if (typeHidden) typeHidden.value = '';
        if (typeDisplay) typeDisplay.value = '';
        if (sessionsDisplay) sessionsDisplay.value = '';

        window.__salesHydrating = false;
    }

    function setLoadingState() {
        window.__salesHydrating = true;

        $member.prop('disabled', true);
        $plan.prop('disabled', true);

        $member.empty().append(new Option("Loading...", ""));
        $plan.empty().append(new Option("Loading...", ""));

        $member.trigger('change.select2');
        $plan.trigger('change.select2');

        window.__salesHydrating = false;
    }

    function updateTypeAndSessionsFromSelectedPlan() {
        const planSelect = document.getElementById('subscriptions_plan_id');
        if (!planSelect) return;

        const opt = planSelect.options[planSelect.selectedIndex];
        if (!opt || !opt.value) {
            if (typeHidden) typeHidden.value = '';
            if (typeDisplay) typeDisplay.value = '';
            if (sessionsDisplay) sessionsDisplay.value = '';
            return;
        }

        const typeId = opt.getAttribute('data-type-id') || '';
        const sessionsCount = opt.getAttribute('data-sessions-count') || '';

        if (typeHidden) typeHidden.value = typeId;

        const t = types.find(x => String(x.id) === String(typeId));
        if (typeDisplay) typeDisplay.value = t ? t.name : '';

        if (sessionsDisplay) sessionsDisplay.value = sessionsCount;
    }

    let membersAbort = null;
    let plansAbort = null;

    async function loadMembersAndPlans(branchId) {
        if (!branchId) {
            resetMemberAndPlan();
            // بعد reset خلّي المنسق يحدث الباقي مرة واحدة
            window.salesRefreshAll && window.salesRefreshAll();
            return;
        }

        setLoadingState();

        // نوقف أي طلبات سابقة
        try { membersAbort && membersAbort.abort(); } catch (e) {}
        try { plansAbort && plansAbort.abort(); } catch (e) {}

        membersAbort = new AbortController();
        plansAbort = new AbortController();

        window.__salesHydrating = true;

        // Members
        const membersRes = await fetch(MEMBERS_URL, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': token
            },
            body: JSON.stringify({ branch_id: branchId }),
            signal: membersAbort.signal
        });

        const membersJson = await safeJson(membersRes);

        $member.empty().append(new Option("{{ trans('settings_trans.choose') }}", ""));
        if (membersJson && membersJson.ok) {
            (membersJson.data || []).forEach(m => {
                const opt = new Option(m.text, m.id);
                $member.append(opt);
            });
        }

        if (oldMemberId) {
            $member.val(String(oldMemberId));
        }

        $member.prop('disabled', false).trigger('change.select2');

        // Plans
        const plansRes = await fetch(PLANS_URL, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': token
            },
            body: JSON.stringify({ branch_id: branchId }),
            signal: plansAbort.signal
        });

        const plansJson = await safeJson(plansRes);

        $plan.empty().append(new Option("{{ trans('settings_trans.choose') }}", ""));
        if (plansJson && plansJson.ok) {
            (plansJson.data || []).forEach(p => {
                const opt = new Option(p.text, p.id);
                opt.setAttribute('data-type-id', p.type_id || '');
                opt.setAttribute('data-duration-days', p.duration_days || 0);
                opt.setAttribute('data-sessions-count', p.sessions_count || 0);
                $plan.append(opt);
            });
        }

        if (oldPlanId) {
            $plan.val(String(oldPlanId));
        }

        $plan.prop('disabled', false).trigger('change.select2');

        updateTypeAndSessionsFromSelectedPlan();

        window.__salesHydrating = false;

        // ✅ تحديث واحد فقط (Preview + Offers) من المنسق في index
        window.salesRefreshAll && window.salesRefreshAll();
    }

    // Init select2 once
    safeSelect2Init();

    // Branch change: استخدم change فقط لتجنب double calls (select2 يطلق change أيضًا) [web:34]
    $(document).on('change', '#branch_id', function () {
        const branchId = $(this).val();
        loadMembersAndPlans(branchId);
    });

    // Plan change: لا تستدعي preview/offers هنا، فقط حدث الحقول ثم دع المنسق يتولى
    $(document).on('change', '#subscriptions_plan_id', function () {
        if (window.__salesHydrating) return;

        updateTypeAndSessionsFromSelectedPlan();
        window.salesRefreshAll && window.salesRefreshAll();
    });

    // Initial
    const initialBranchId = $branch.val();
    if (initialBranchId) {
        loadMembersAndPlans(initialBranchId);
    } else {
        resetMemberAndPlan();
        window.salesRefreshAll && window.salesRefreshAll();
    }
});
</script>
