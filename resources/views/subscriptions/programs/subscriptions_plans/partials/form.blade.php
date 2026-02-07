@php
    $is_edit = isset($Plan);
    $selected_branches = $is_edit ? ($SelectedBranches ?? []) : (old('branches', []) ?? []);
    $locale = app()->getLocale();
@endphp

{{-- Section: Basic information --}}
<div class="card border mb-3">
    <div class="card-header bg-soft-primary">
        <div class="d-flex align-items-center justify-content-between">
            <h6 class="mb-0">
                <i class="ri-information-line align-bottom me-1"></i> {{ trans('subscriptions.section_basic_info') }}
            </h6>
        </div>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-4">
                <div class="mb-3">
                    <label for="name_ar" class="form-label">{{ trans('subscriptions.name_ar') }} <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="name_ar" name="name_ar" value="{{ $is_edit ? $Plan->getTranslation('name','ar') : old('name_ar') }}" placeholder="{{ trans('subscriptions.name_ar') }}">
                    @error('name_ar')
                        <div class="text-danger small mt-1">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div class="col-md-4">
                <div class="mb-3">
                    <label for="name_en" class="form-label">{{ trans('subscriptions.name_en') }} <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="name_en" name="name_en" value="{{ $is_edit ? $Plan->getTranslation('name','en') : old('name_en') }}" placeholder="{{ trans('subscriptions.name_en') }}">
                    @error('name_en')
                        <div class="text-danger small mt-1">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div class="col-md-4">
                <div class="mb-3">
                    <label class="form-label">{{ trans('subscriptions.subscriptions_type') }} <span class="text-danger">*</span></label>
                    <select class="form-select" name="subscriptions_type_id" id="subscriptions_type_id">
                        <option value="">{{ trans('subscriptions.choose') }}</option>
                        @foreach($SubscriptionsTypes as $Type)
                            <option value="{{ $Type->id }}" {{ ($is_edit ? $Plan->subscriptions_type_id : old('subscriptions_type_id')) == $Type->id ? 'selected' : '' }}>
                                {{ $Type->getTranslation('name', $locale) }}
                            </option>
                        @endforeach
                    </select>
                    @error('subscriptions_type_id')
                        <div class="text-danger small mt-1">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div class="col-md-6">
                <div class="mb-3">
                    <label class="form-label">{{ trans('subscriptions.description') }}</label>
                    <textarea class="form-control" name="description" rows="2" placeholder="{{ trans('subscriptions.description') }}">{{ $is_edit ? $Plan->description : old('description') }}</textarea>
                    @error('description')
                        <div class="text-danger small mt-1">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div class="col-md-6">
                <div class="mb-3">
                    <label class="form-label">{{ trans('subscriptions.notes') }}</label>
                    <textarea class="form-control" name="notes" rows="2" placeholder="{{ trans('subscriptions.notes') }}">{{ $is_edit ? $Plan->notes : old('notes') }}</textarea>
                    @error('notes')
                        <div class="text-danger small mt-1">{{ $message }}</div>
                    @enderror
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Section: Sessions & duration --}}
<div class="card border mb-3">
    <div class="card-header bg-soft-info">
        <h6 class="mb-0">
            <i class="ri-timer-line align-bottom me-1"></i> {{ trans('subscriptions.section_schedule') }}
        </h6>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-4">
                <div class="mb-3">
                    <label class="form-label">{{ trans('subscriptions.sessions_period_type') }}</label>
                    <select class="form-select" name="sessions_period_type" id="sessions_period_type">
                        @foreach($PeriodTypes as $t)
                            <option value="{{ $t }}" {{ ($is_edit ? $Plan->sessions_period_type : old('sessions_period_type')) == $t ? 'selected' : '' }}>
                                {{ trans('subscriptions.period_' . $t) }}
                            </option>
                        @endforeach
                    </select>
                    @error('sessions_period_type')
                        <div class="text-danger small mt-1">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div class="col-md-4" id="period_other_wrap" style="display:none;">
                <div class="mb-3">
                    <label class="form-label">{{ trans('subscriptions.sessions_period_other_label') }}</label>
                    <input type="text" class="form-control" name="sessions_period_other_label" value="{{ $is_edit ? $Plan->sessions_period_other_label : old('sessions_period_other_label') }}">
                    @error('sessions_period_other_label')
                        <div class="text-danger small mt-1">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div class="col-md-2">
                <div class="mb-3">
                    <label class="form-label">{{ trans('subscriptions.sessions_count') }}</label>
                    <input type="number" class="form-control" name="sessions_count" min="1" value="{{ $is_edit ? $Plan->sessions_count : old('sessions_count', 1) }}">
                    @error('sessions_count')
                        <div class="text-danger small mt-1">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div class="col-md-2">
                <div class="mb-3">
                    <label class="form-label">{{ trans('subscriptions.duration_days') }}</label>
                    <input type="number" class="form-control" name="duration_days" min="1" value="{{ $is_edit ? $Plan->duration_days : old('duration_days', 30) }}">
                    @error('duration_days')
                        <div class="text-danger small mt-1">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div class="col-md-12">
                <div class="mb-0">
                    <label class="form-label">{{ trans('subscriptions.allowed_training_days') }}</label>
                    <select class="form-control select2" name="allowed_training_days[]" id="allowed_training_days" multiple>
                        @foreach($WeekDays as $d)
                            @php
                                $selected_days = $is_edit ? ($Plan->allowed_training_days ?? []) : (old('allowed_training_days', []) ?? []);
                            @endphp
                            <option value="{{ $d }}" {{ in_array($d, $selected_days) ? 'selected' : '' }}>{{ trans('subscriptions.day_' . $d) }}</option>
                        @endforeach
                    </select>
                    @error('allowed_training_days')
                        <div class="text-danger small mt-1">{{ $message }}</div>
                    @enderror
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Section: Status & notifications --}}
<div class="card border mb-3">
    <div class="card-header bg-soft-success">
        <h6 class="mb-0">
            <i class="ri-notification-3-line align-bottom me-1"></i> {{ trans('subscriptions.section_status_notifications') }}
        </h6>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-4">
                <div class="form-check mt-1">
                    @php $st = $is_edit ? $Plan->status : old('status', 1); @endphp
                    <input class="form-check-input" type="checkbox" name="status" value="1" id="statuss" {{ $st ? 'checked' : '' }}>
                    <label class="form-check-label" for="status">{{ trans('subscriptions.status_active') }}</label>
                </div>
            </div>

            <div class="col-md-4">
                <div class="form-check mt-1">
                    @php $nb = $is_edit ? $Plan->notify_before_end : old('notify_before_end', 0); @endphp
                    <input class="form-check-input" type="checkbox" name="notify_before_end" value="1" id="notify_before_end" {{ $nb ? 'checked' : '' }}>
                    <label class="form-check-label" for="notify_before_end">{{ trans('subscriptions.notify_before_end') }}</label>
                </div>
            </div>

            <div class="col-md-4" id="notify_days_wrap" style="display:none;">
                <div class="mb-0">
                    <label class="form-label">{{ trans('subscriptions.notify_days_before_end') }}</label>
                    <input type="number" class="form-control" name="notify_days_before_end" min="1" value="{{ $is_edit ? $Plan->notify_days_before_end : old('notify_days_before_end') }}">
                    @error('notify_days_before_end')
                        <div class="text-danger small mt-1">{{ $message }}</div>
                    @enderror
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Section: Freeze --}}
<div class="card border mb-3">
    <div class="card-header bg-soft-dark">
        <h6 class="mb-0">
            <i class="ri-snowy-line align-bottom me-1"></i> {{ trans('subscriptions.allow_freeze') }}
        </h6>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-4">
                <div class="form-check mt-1">
                    @php $af = $is_edit ? ($Plan->allow_freeze ?? 0) : old('allow_freeze', 0); @endphp
                    <input class="form-check-input" type="checkbox" name="allow_freeze" value="1" id="allow_freeze" {{ $af ? 'checked' : '' }}>
                    <label class="form-check-label" for="allow_freeze">{{ trans('subscriptions.allow_freeze') }}</label>
                </div>
            </div>

            <div class="col-md-4" id="freeze_days_wrap" style="display:none;">
                <div class="mb-0">
                    <label class="form-label">{{ trans('subscriptions.max_freeze_days') }} <span class="text-danger">*</span></label>
                    <input type="number" class="form-control" name="max_freeze_days" min="1" value="{{ $is_edit ? $Plan->max_freeze_days : old('max_freeze_days') }}">
                    @error('max_freeze_days')
                        <div class="text-danger small mt-1">{{ $message }}</div>
                    @enderror
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Section: Guests --}}
<div class="card border mb-3">
    <div class="card-header bg-soft-warning">
        <h6 class="mb-0">
            <i class="ri-user-add-line align-bottom me-1"></i> {{ trans('subscriptions.section_guest') }}
        </h6>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-12">
                <div class="form-check mt-1">
                    @php $ag = $is_edit ? $Plan->allow_guest : old('allow_guest', 0); @endphp
                    <input class="form-check-input" type="checkbox" name="allow_guest" value="1" id="allow_guest" {{ $ag ? 'checked' : '' }}>
                    <label class="form-check-label" for="allow_guest">{{ trans('subscriptions.allow_guest') }}</label>
                </div>
            </div>

            <div class="col-md-3 guest_wrap" style="display:none;">
                <div class="mb-3 mt-2">
                    <label class="form-label">{{ trans('subscriptions.guest_people_count') }}</label>
                    <input type="number" class="form-control" name="guest_people_count" min="1" value="{{ $is_edit ? $Plan->guest_people_count : old('guest_people_count') }}">
                    @error('guest_people_count')
                        <div class="text-danger small mt-1">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div class="col-md-3 guest_wrap" style="display:none;">
                <div class="mb-3 mt-2">
                    <label class="form-label">{{ trans('subscriptions.guest_times_count') }}</label>
                    <input type="number" class="form-control" name="guest_times_count" min="1" value="{{ $is_edit ? $Plan->guest_times_count : old('guest_times_count') }}">
                    @error('guest_times_count')
                        <div class="text-danger small mt-1">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div class="col-md-6 guest_wrap" style="display:none;">
                <div class="mb-0 mt-2">
                    <label class="form-label">{{ trans('subscriptions.guest_allowed_days') }}</label>
                    <select class="form-control select2" name="guest_allowed_days[]" id="guest_allowed_days" multiple>
                        @foreach($WeekDays as $d)
                            @php
                                $selected_guest_days = $is_edit ? ($Plan->guest_allowed_days ?? []) : (old('guest_allowed_days', []) ?? []);
                            @endphp
                            <option value="{{ $d }}" {{ in_array($d, $selected_guest_days) ? 'selected' : '' }}>{{ trans('subscriptions.day_' . $d) }}</option>
                        @endforeach
                    </select>
                    @error('guest_allowed_days')
                        <div class="text-danger small mt-1">{{ $message }}</div>
                    @enderror
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Section: Branches & pricing --}}
<div class="card border mb-3">
    <div class="card-header bg-soft-secondary">
        <h6 class="mb-0">
            <i class="ri-store-2-line align-bottom me-1"></i> {{ trans('subscriptions.section_branches_pricing') }}
        </h6>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-12">
                <div class="mb-0">
                    <label class="form-label">{{ trans('subscriptions.branches') }} <span class="text-danger">*</span></label>
                    <select class="form-control select2" name="branches[]" id="branches" multiple>
                        @foreach($Branches as $Branch)
                            @php
                                $branch_name = $Branch->name;
                                if (is_string($branch_name)) {
                                    $decoded = json_decode($branch_name, true);
                                    if (is_array($decoded)) {
                                        $branch_name = $decoded[$locale] ?? ($decoded['ar'] ?? ($decoded['en'] ?? $branch_name));
                                    }
                                } elseif (is_array($Branch->name)) {
                                    $branch_name = $Branch->name[$locale] ?? ($Branch->name['ar'] ?? ($Branch->name['en'] ?? ''));
                                }
                            @endphp
                            <option value="{{ $Branch->id }}" {{ in_array($Branch->id, $selected_branches) ? 'selected' : '' }}>
                                {{ $branch_name }}
                            </option>
                        @endforeach
                    </select>
                    @error('branches')
                        <div class="text-danger small mt-1">{{ $message }}</div>
                    @enderror
                    <small class="text-muted d-block mt-1">{{ trans('subscriptions.branches_select_hint') }}</small>
                </div>
            </div>
        </div>
    </div>
</div>

<div id="pricing_container"></div>

<script>
    const ALL_BRANCHES = @json($Branches);

    // Per-branch coaches only
    const BRANCH_COACHES_MAP = @json(isset($BranchCoachesMap) ? $BranchCoachesMap : []);

    const BRANCH_PRICES = @json(isset($BranchPrices) ? $BranchPrices : []);
    const COACH_PRICES_MAP = @json(isset($CoachPricesMap) ? $CoachPricesMap : []);

    const OLD_PRICING = @json(old('pricing', []));
    const OLD_COACHES = @json(old('coaches', []));

    const COACHES_PER_PAGE = 5;
    const COACH_STATE = window.__COACH_STATE__ || (window.__COACH_STATE__ = {}); // [branchId][coachId] => {is_included:0|1, price:''}
    const COACH_UI = window.__COACH_UI__ || (window.__COACH_UI__ = {});         // [branchId] => {q:'', page:1}

    function coachName(c){
        return ((c.first_name ?? '') + ' ' + (c.last_name ?? '')).trim();
    }

    function normalizeStr(s){
        return String(s ?? '').toLowerCase().trim();
    }

    function parseJsonMaybe(v){
        if (v === null || v === undefined) return null;
        if (typeof v === 'object') return v;
        if (typeof v === 'string') {
            try { return JSON.parse(v); } catch(e) { return null; }
        }
        return null;
    }

    function getBranchName(b){
        if (!b) return '';
        const obj = parseJsonMaybe(b.name);
        if (obj && obj['{{ app()->getLocale() }}']) return obj['{{ app()->getLocale() }}'];
        if (obj && obj.ar) return obj.ar;
        if (obj && obj.en) return obj.en;
        return 'Branch #' + b.id;
    }

    function getSavedBranchPrice(branchId){
        if (OLD_PRICING && OLD_PRICING[branchId]) return OLD_PRICING[branchId];
        if (BRANCH_PRICES && BRANCH_PRICES[branchId]) return BRANCH_PRICES[branchId];
        return null;
    }

    function getSavedCoach(branchId, coachId){
        if (OLD_COACHES && OLD_COACHES[branchId] && OLD_COACHES[branchId][coachId]) return OLD_COACHES[branchId][coachId];
        if (COACH_PRICES_MAP && COACH_PRICES_MAP[branchId] && COACH_PRICES_MAP[branchId][coachId]) return COACH_PRICES_MAP[branchId][coachId];
        return null;
    }

    function getBranchCoaches(branchId){
        const arr = BRANCH_COACHES_MAP?.[branchId] || BRANCH_COACHES_MAP?.[String(branchId)] || [];
        return Array.isArray(arr) ? arr : [];
    }

    function initBranchCoachState(branchId){
        if (!COACH_STATE[branchId]) COACH_STATE[branchId] = {};
        if (!COACH_UI[branchId]) COACH_UI[branchId] = { q: '', page: 1 };

        const coaches = getBranchCoaches(branchId);

        coaches.forEach(function(c){
            const coachId = c.id;
            if (COACH_STATE[branchId][coachId] !== undefined) return;

            const saved = getSavedCoach(branchId, coachId);
            const included = saved ? (parseInt(saved.is_included ?? saved.is_included) === 1) : true;
            const price = saved ? (saved.price ?? '') : '';

            COACH_STATE[branchId][coachId] = {
                is_included: included ? 1 : 0,
                price: price
            };
        });
    }

    function buildHiddenInputs(branchId){
        const wrap = document.getElementById(`coach_hidden_inputs_${branchId}`);
        if (!wrap) return;

        const coaches = getBranchCoaches(branchId);

        let html = '';
        coaches.forEach(function(c){
            const coachId = c.id;
            const st = COACH_STATE[branchId]?.[coachId] || {is_included: 1, price: ''};

            html += `
                <input type="hidden" name="coaches[${branchId}][${coachId}][is_included]" id="hid_inc_${branchId}_${coachId}" value="${st.is_included}">
                <input type="hidden" name="coaches[${branchId}][${coachId}][price]" id="hid_price_${branchId}_${coachId}" value="${String(st.price ?? '')}">
            `;
        });

        wrap.innerHTML = html;
    }

    function syncHiddenInput(branchId, coachId){
        const st = COACH_STATE[branchId]?.[coachId];
        if (!st) return;

        const inc = document.getElementById(`hid_inc_${branchId}_${coachId}`);
        const price = document.getElementById(`hid_price_${branchId}_${coachId}`);

        if (inc) inc.value = String(st.is_included ?? 0);
        if (price) price.value = String(st.price ?? '');
    }

    function setCoachInputsEnabled(branchId, enabled){
        const wrap = document.getElementById(`coach_hidden_inputs_${branchId}`);
        if (!wrap) return;

        wrap.querySelectorAll('input').forEach(function(inp){
            inp.disabled = !enabled;
        });
    }

    function renderCoachesTable(branchId){
        const tbody = document.getElementById(`coach_tbody_${branchId}`);
        const info = document.getElementById(`coach_page_info_${branchId}`);
        const prevBtn = document.getElementById(`coach_prev_${branchId}`);
        const nextBtn = document.getElementById(`coach_next_${branchId}`);
        const searchInp = document.getElementById(`coach_search_${branchId}`);

        if (!tbody) return;

        const ui = COACH_UI[branchId] || { q: '', page: 1 };
        const q = normalizeStr(ui.q);

        const coaches = getBranchCoaches(branchId);
        const filtered = coaches.filter(function(c){
            if (!q) return true;
            return normalizeStr(coachName(c)).includes(q);
        });

        const total = filtered.length;
        const pages = Math.max(1, Math.ceil(total / COACHES_PER_PAGE));
        const page = Math.min(Math.max(1, parseInt(ui.page || 1)), pages);
        COACH_UI[branchId].page = page;

        const start = (page - 1) * COACHES_PER_PAGE;
        const slice = filtered.slice(start, start + COACHES_PER_PAGE);

        let html = '';
        slice.forEach(function(c){
            const coachId = c.id;
            const st = COACH_STATE[branchId][coachId];
            const included = (parseInt(st.is_included) === 1);
            const price = (st.price ?? '');

            html += `
                <tr>
                    <td>${coachName(c)}</td>
                    <td>
                        <select class="form-select coach_inc" data-branch="${branchId}" data-coach="${coachId}">
                            <option value="1" ${included ? 'selected' : ''}>{{ trans('subscriptions.yes') }}</option>
                            <option value="0" ${!included ? 'selected' : ''}>{{ trans('subscriptions.no') }}</option>
                        </select>
                    </td>
                    <td>
                        <input type="number" step="0.01" class="form-control coach_price" data-branch="${branchId}" data-coach="${coachId}" value="${price}">
                    </td>
                </tr>
            `;
        });

        if (!html) {
            html = `
                <tr>
                    <td colspan="3" class="text-center text-muted">-</td>
                </tr>
            `;
        }

        tbody.innerHTML = html;

        if (info) info.textContent = `${page} / ${pages} ( ${total} )`;
        if (prevBtn) prevBtn.disabled = (page <= 1);
        if (nextBtn) nextBtn.disabled = (page >= pages);

        if (searchInp) {
            searchInp.value = ui.q || '';
        }

        tbody.querySelectorAll('.coach_inc').forEach(function(el){
            el.addEventListener('change', function(){
                const b = this.getAttribute('data-branch');
                const coach = this.getAttribute('data-coach');
                if (!COACH_STATE[b]) COACH_STATE[b] = {};
                if (!COACH_STATE[b][coach]) COACH_STATE[b][coach] = {is_included: 1, price: ''};

                COACH_STATE[b][coach].is_included = parseInt(this.value || 0);
                syncHiddenInput(b, coach);
            });
        });

        tbody.querySelectorAll('.coach_price').forEach(function(el){
            el.addEventListener('input', function(){
                const b = this.getAttribute('data-branch');
                const coach = this.getAttribute('data-coach');
                if (!COACH_STATE[b]) COACH_STATE[b] = {};
                if (!COACH_STATE[b][coach]) COACH_STATE[b][coach] = {is_included: 1, price: ''};

                COACH_STATE[b][coach].price = this.value;
                syncHiddenInput(b, coach);
            });
        });
    }

    function bindCoachControls(branchId){
        const prevBtn = document.getElementById(`coach_prev_${branchId}`);
        const nextBtn = document.getElementById(`coach_next_${branchId}`);
        const searchInp = document.getElementById(`coach_search_${branchId}`);

        if (prevBtn) {
            prevBtn.addEventListener('click', function(){
                COACH_UI[branchId].page = Math.max(1, (parseInt(COACH_UI[branchId].page || 1) - 1));
                renderCoachesTable(branchId);
            });
        }

        if (nextBtn) {
            nextBtn.addEventListener('click', function(){
                COACH_UI[branchId].page = (parseInt(COACH_UI[branchId].page || 1) + 1);
                renderCoachesTable(branchId);
            });
        }

        if (searchInp) {
            searchInp.addEventListener('input', function(){
                COACH_UI[branchId].q = this.value || '';
                COACH_UI[branchId].page = 1;
                renderCoachesTable(branchId);
            });
        }
    }

    function toggleMode(branchId){
        const mode = document.querySelector(`select.trainer_mode[data-branch="${branchId}"]`)?.value;
        document.querySelectorAll(`.uniform_wrap[data-branch="${branchId}"]`).forEach(el => el.style.display = (mode === 'uniform') ? 'block' : 'none');
        document.querySelectorAll(`.exceptions_wrap[data-branch="${branchId}"]`).forEach(el => el.style.display = (mode === 'exceptions') ? 'block' : 'none');
    }

    function togglePrivateCoach(branchId){
        const cb = document.getElementById(`is_private_coach_${branchId}`);
        const wrap = document.getElementById(`coach_section_${branchId}`);
        const enabled = cb ? cb.checked : false;

        if (wrap) wrap.style.display = enabled ? 'block' : 'none';

        // disable hidden inputs when section is hidden (so backend doesn't receive coaches)
        setCoachInputsEnabled(branchId, enabled);

        if (enabled) {
            // ensure mode fields are visible correctly
            toggleMode(branchId);
        }
    }

    function renderPricing(){
        const container = document.getElementById('pricing_container');
        const selected = $('#branches').val() || [];
        container.innerHTML = '';

        if (!selected.length) {
            container.innerHTML = `
                <div class="alert alert-info">
                    <i class="ri-information-line align-bottom me-1"></i>
                    {{ trans('subscriptions.pricing_select_branch_first') }}
                </div>
            `;
            return;
        }

        selected.forEach(function(branchId){
            const b = ALL_BRANCHES.find(x => String(x.id) === String(branchId));
            const priceRow = getSavedBranchPrice(branchId);

            const pwt = priceRow ? (priceRow.price_without_trainer ?? '') : '';
            const mode = priceRow ? (priceRow.trainer_pricing_mode ?? 'uniform') : 'uniform';
            const uni = priceRow ? (priceRow.trainer_uniform_price ?? '') : '';
            const def = priceRow ? (priceRow.trainer_default_price ?? '') : '';

            // default OFF: if editing and saved row has is_private_coach=1 or old input => ON
            const oldP = (OLD_PRICING && OLD_PRICING[branchId]) ? OLD_PRICING[branchId] : null;
            const isPrivateSaved = oldP ? (parseInt(oldP.is_private_coach || 0) === 1) : (priceRow ? (parseInt(priceRow.is_private_coach || 0) === 1) : false);

            let html = `
                <div class="card mb-3">
                    <div class="card-header d-flex align-items-center justify-content-between">
                        <h5 class="card-title mb-0">
                            <i class="ri-store-2-line align-bottom me-1"></i> ${getBranchName(b)}
                        </h5>
                        <span class="badge bg-soft-primary text-primary">{{ trans('subscriptions.pricing') }}</span>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">{{ trans('subscriptions.price_without_trainer') }}</label>
                                    <input type="number" step="0.01" class="form-control" name="pricing[${branchId}][price_without_trainer]" value="${pwt}">
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">{{ trans('subscriptions.private_coach') }}</label>
                                    <div class="form-check mt-1">
                                        <input class="form-check-input private_coach_cb" type="checkbox"
                                               id="is_private_coach_${branchId}"
                                               name="pricing[${branchId}][is_private_coach]"
                                               value="1" ${isPrivateSaved ? 'checked' : ''}>
                                        <label class="form-check-label" for="is_private_coach_${branchId}">
                                            {{ trans('subscriptions.private_coach_yes') }}
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-4 private_coach_deps" data-branch="${branchId}" style="display:none;">
                                <div class="mb-3">
                                    <label class="form-label">{{ trans('subscriptions.trainer_pricing_mode') }}</label>
                                    <select class="form-select trainer_mode" data-branch="${branchId}" name="pricing[${branchId}][trainer_pricing_mode]">
                                        <option value="uniform" ${mode==='uniform'?'selected':''}>{{ trans('subscriptions.trainer_pricing_uniform') }}</option>
                                        <option value="per_trainer" ${mode==='per_trainer'?'selected':''}>{{ trans('subscriptions.trainer_pricing_per_trainer') }}</option>
                                        <option value="exceptions" ${mode==='exceptions'?'selected':''}>{{ trans('subscriptions.trainer_pricing_exceptions') }}</option>
                                    </select>
                                </div>
                            </div>

                            <div class="col-md-4 uniform_wrap private_coach_deps" data-branch="${branchId}" style="display:none;">
                                <div class="mb-3">
                                    <label class="form-label">{{ trans('subscriptions.trainer_uniform_price') }}</label>
                                    <input type="number" step="0.01" class="form-control" name="pricing[${branchId}][trainer_uniform_price]" value="${uni}">
                                </div>
                            </div>

                            <div class="col-md-4 exceptions_wrap private_coach_deps" data-branch="${branchId}" style="display:none;">
                                <div class="mb-3">
                                    <label class="form-label">{{ trans('subscriptions.trainer_default_price') }}</label>
                                    <input type="number" step="0.01" class="form-control" name="pricing[${branchId}][trainer_default_price]" value="${def}">
                                </div>
                            </div>
                        </div>

                        <div id="coach_section_${branchId}" style="display:none;">
                            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-2">
                                <div class="d-flex align-items-center gap-2">
                                    <label class="form-label mb-0">{{ trans('subscriptions.coach') }}</label>
                                    <div class="search-box">
                                        <input type="text" class="form-control form-control-sm" id="coach_search_${branchId}" placeholder="{{ trans('subscriptions.search') }}">
                                    </div>
                                </div>

                                <div class="d-flex align-items-center gap-2">
                                    <button type="button" class="btn btn-soft-secondary btn-sm" id="coach_prev_${branchId}">
                                        <i class="ri-arrow-left-s-line align-bottom"></i>
                                    </button>
                                    <span class="text-muted small" id="coach_page_info_${branchId}">1 / 1</span>
                                    <button type="button" class="btn btn-soft-secondary btn-sm" id="coach_next_${branchId}">
                                        <i class="ri-arrow-right-s-line align-bottom"></i>
                                    </button>
                                    <span class="badge bg-soft-info text-info">{{ trans('subscriptions.per_page') }}: ${COACHES_PER_PAGE}</span>
                                </div>
                            </div>

                            <div id="coach_hidden_inputs_${branchId}" class="d-none"></div>

                            <div class="table-responsive">
                                <table class="table table-bordered align-middle">
                                    <thead class="table-light">
                                        <tr>
                                            <th>{{ trans('subscriptions.coach') }}</th>
                                            <th style="width:160px;">{{ trans('subscriptions.is_included') }}</th>
                                            <th style="width:220px;">{{ trans('subscriptions.coach_price') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody id="coach_tbody_${branchId}"></tbody>
                                </table>
                            </div>

                            <small class="text-muted">{{ trans('subscriptions.coach_pricing_note') }}</small>
                        </div>
                    </div>
                </div>
            `;

            container.insertAdjacentHTML('beforeend', html);

            // init coaches state only for branch coaches
            initBranchCoachState(branchId);
            buildHiddenInputs(branchId);
            bindCoachControls(branchId);
            renderCoachesTable(branchId);

            // Apply private coach toggle initial
            document.querySelectorAll(`.private_coach_deps[data-branch="${branchId}"]`).forEach(el => {
                el.style.display = isPrivateSaved ? 'block' : 'none';
            });
            togglePrivateCoach(branchId);

            // Apply mode visibility only when enabled
            if (isPrivateSaved) toggleMode(branchId);
        });

        $('.trainer_mode').off('change').on('change', function(){
            toggleMode($(this).data('branch'));
        });

        $('.private_coach_cb').off('change').on('change', function(){
            const branchId = this.id.replace('is_private_coach_', '');
            const enabled = this.checked;

            document.querySelectorAll(`.private_coach_deps[data-branch="${branchId}"]`).forEach(el => {
                el.style.display = enabled ? 'block' : 'none';
            });

            togglePrivateCoach(branchId);
        });
    }

    function togglePeriodOther(){
        const v = document.getElementById('sessions_period_type').value;
        document.getElementById('period_other_wrap').style.display = (v === 'other') ? 'block' : 'none';
    }

    function toggleGuest(){
        const checked = document.getElementById('allow_guest').checked;
        document.querySelectorAll('.guest_wrap').forEach(el => el.style.display = checked ? 'block' : 'none');
    }

    function toggleNotify(){
        const checked = document.getElementById('notify_before_end').checked;
        document.getElementById('notify_days_wrap').style.display = checked ? 'block' : 'none';
    }

    function toggleFreeze(){
        const checked = document.getElementById('allow_freeze').checked;
        document.getElementById('freeze_days_wrap').style.display = checked ? 'block' : 'none';

        if (!checked) {
            const inp = document.querySelector('input[name="max_freeze_days"]');
            if (inp) inp.value = '';
        }
    }

    document.addEventListener('DOMContentLoaded', function(){
        if (typeof $ !== 'undefined' && $.fn && $.fn.select2) {
            var isRtl = $('html').attr('dir') === 'rtl';

            $('.select2').select2({
                width: '100%',
                dir: isRtl ? 'rtl' : 'ltr'
            });

            $('#branches').select2({
                width: '100%',
                closeOnSelect: false,
                placeholder: '{{ trans('subscriptions.branches') }}',
                dir: isRtl ? 'rtl' : 'ltr'
            });
        }

        $('#branches').on('change', function(){
            renderPricing();
        });

        document.getElementById('sessions_period_type').addEventListener('change', togglePeriodOther);
        document.getElementById('allow_guest').addEventListener('change', toggleGuest);
        document.getElementById('notify_before_end').addEventListener('change', toggleNotify);
        document.getElementById('allow_freeze').addEventListener('change', toggleFreeze);

        togglePeriodOther();
        toggleGuest();
        toggleNotify();
        toggleFreeze();

        renderPricing();
    });
</script>
