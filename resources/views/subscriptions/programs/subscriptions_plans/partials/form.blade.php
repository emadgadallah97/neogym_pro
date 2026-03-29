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
                    <input type="text" class="form-control" id="name_ar" name="name_ar"
                        value="{{ $is_edit ? $Plan->getTranslation('name','ar') : old('name_ar') }}"
                        placeholder="{{ trans('subscriptions.name_ar') }}">
                    @error('name_ar')
                        <div class="text-danger small mt-1">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div class="col-md-4">
                <div class="mb-3">
                    <label for="name_en" class="form-label">{{ trans('subscriptions.name_en') }} <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="name_en" name="name_en"
                        value="{{ $is_edit ? $Plan->getTranslation('name','en') : old('name_en') }}"
                        placeholder="{{ trans('subscriptions.name_en') }}">
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
                            <option value="{{ $Type->id }}"
                                {{ ($is_edit ? $Plan->subscriptions_type_id : old('subscriptions_type_id')) == $Type->id ? 'selected' : '' }}>
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
                    <textarea class="form-control" name="description" rows="2"
                        placeholder="{{ trans('subscriptions.description') }}">{{ $is_edit ? $Plan->description : old('description') }}</textarea>
                    @error('description')
                        <div class="text-danger small mt-1">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div class="col-md-6">
                <div class="mb-3">
                    <label class="form-label">{{ trans('subscriptions.notes') }}</label>
                    <textarea class="form-control" name="notes" rows="2"
                        placeholder="{{ trans('subscriptions.notes') }}">{{ $is_edit ? $Plan->notes : old('notes') }}</textarea>
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
                            <option value="{{ $t }}"
                                {{ ($is_edit ? $Plan->sessions_period_type : old('sessions_period_type')) == $t ? 'selected' : '' }}>
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
                    <input type="text" class="form-control" name="sessions_period_other_label"
                        value="{{ $is_edit ? $Plan->sessions_period_other_label : old('sessions_period_other_label') }}">
                    @error('sessions_period_other_label')
                        <div class="text-danger small mt-1">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div class="col-md-2">
                <div class="mb-3">
                    <label class="form-label">{{ trans('subscriptions.sessions_count') }}</label>
                    <input type="number" class="form-control" name="sessions_count" min="1"
                        value="{{ $is_edit ? $Plan->sessions_count : old('sessions_count', 1) }}">
                    @error('sessions_count')
                        <div class="text-danger small mt-1">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div class="col-md-2">
                <div class="mb-3">
                    <label class="form-label">{{ trans('subscriptions.duration_days') }}</label>
                    <input type="number" class="form-control" name="duration_days" min="1"
                        value="{{ $is_edit ? $Plan->duration_days : old('duration_days', 30) }}">
                    @error('duration_days')
                        <div class="text-danger small mt-1">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div class="col-md-12 mb-3">
                <div class="form-check mt-2">
                    @php $cdo = $is_edit ? ($Plan->check_duration_only ?? 0) : old('check_duration_only', 0); @endphp
                    <input class="form-check-input" type="checkbox" name="check_duration_only" value="1"
                        id="check_duration_only" {{ $cdo ? 'checked' : '' }}>
                    <label class="form-check-label" for="check_duration_only">{{ trans('subscriptions.check_duration_only') }}</label>
                </div>
            </div>

            <div class="col-md-12">
                <div class="mb-0">
                    <label class="form-label">{{ trans('subscriptions.allowed_training_days') }}</label>
                    <select class="form-control select2" name="allowed_training_days[]" id="allowed_training_days" multiple>
                        @foreach($WeekDays as $d)
                            @php
                                $selected_days = $is_edit
                                    ? ($Plan->allowed_training_days ?? [])
                                    : (old('allowed_training_days', []) ?? []);
                            @endphp
                            <option value="{{ $d }}" {{ in_array($d, $selected_days) ? 'selected' : '' }}>
                                {{ trans('subscriptions.day_' . $d) }}
                            </option>
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
                    <label class="form-check-label" for="statuss">{{ trans('subscriptions.status_active') }}</label>
                </div>
            </div>

            <div class="col-md-4">
                <div class="form-check mt-1">
                    @php $nb = $is_edit ? $Plan->notify_before_end : old('notify_before_end', 0); @endphp
                    <input class="form-check-input" type="checkbox" name="notify_before_end" value="1"
                        id="notify_before_end" {{ $nb ? 'checked' : '' }}>
                    <label class="form-check-label" for="notify_before_end">{{ trans('subscriptions.notify_before_end') }}</label>
                </div>
            </div>

            <div class="col-md-4" id="notify_days_wrap" style="display:none;">
                <div class="mb-0">
                    <label class="form-label">{{ trans('subscriptions.notify_days_before_end') }}</label>
                    <input type="number" class="form-control" name="notify_days_before_end" min="1"
                        value="{{ $is_edit ? $Plan->notify_days_before_end : old('notify_days_before_end') }}">
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
                    <input class="form-check-input" type="checkbox" name="allow_freeze" value="1"
                        id="allow_freeze" {{ $af ? 'checked' : '' }}>
                    <label class="form-check-label" for="allow_freeze">{{ trans('subscriptions.allow_freeze') }}</label>
                </div>
            </div>

            <div class="col-md-4" id="freeze_days_wrap" style="display:none;">
                <div class="mb-0">
                    <label class="form-label">{{ trans('subscriptions.max_freeze_days') }} <span class="text-danger">*</span></label>
                    <input type="number" class="form-control" name="max_freeze_days" min="1"
                        value="{{ $is_edit ? $Plan->max_freeze_days : old('max_freeze_days') }}">
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
                    <input class="form-check-input" type="checkbox" name="allow_guest" value="1"
                        id="allow_guest" {{ $ag ? 'checked' : '' }}>
                    <label class="form-check-label" for="allow_guest">{{ trans('subscriptions.allow_guest') }}</label>
                </div>
            </div>

            <div class="col-md-3 guest_wrap" style="display:none;">
                <div class="mb-3 mt-2">
                    <label class="form-label">{{ trans('subscriptions.guest_people_count') }}</label>
                    <input type="number" class="form-control" name="guest_people_count" min="1"
                        value="{{ $is_edit ? $Plan->guest_people_count : old('guest_people_count') }}">
                    @error('guest_people_count')
                        <div class="text-danger small mt-1">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div class="col-md-3 guest_wrap" style="display:none;">
                <div class="mb-3 mt-2">
                    <label class="form-label">{{ trans('subscriptions.guest_times_count') }}</label>
                    <input type="number" class="form-control" name="guest_times_count" min="1"
                        value="{{ $is_edit ? $Plan->guest_times_count : old('guest_times_count') }}">
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
                                $selected_guest_days = $is_edit
                                    ? ($Plan->guest_allowed_days ?? [])
                                    : (old('guest_allowed_days', []) ?? []);
                            @endphp
                            <option value="{{ $d }}" {{ in_array($d, $selected_guest_days) ? 'selected' : '' }}>
                                {{ trans('subscriptions.day_' . $d) }}
                            </option>
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

{{-- ✅ حاوية التسعير (سعر أساسي فقط لكل فرع) --}}
<div id="pricing_container"></div>

<script>
    const ALL_BRANCHES  = @json($Branches);
    const BRANCH_PRICES = @json(isset($BranchPrices) ? $BranchPrices : []);
    const OLD_PRICING   = @json(old('pricing', []));

    // ─── Helpers ───────────────────────────────────────────────
    function parseJsonMaybe(v) {
        if (v === null || v === undefined) return null;
        if (typeof v === 'object') return v;
        if (typeof v === 'string') {
            try { return JSON.parse(v); } catch (e) { return null; }
        }
        return null;
    }

    function getBranchName(b) {
        if (!b) return '';
        const obj = parseJsonMaybe(b.name);
        if (obj) {
            return obj['{{ app()->getLocale() }}'] ?? obj.ar ?? obj.en ?? ('Branch #' + b.id);
        }
        return b.name ?? ('Branch #' + b.id);
    }

    function getSavedBasePrice(branchId) {
        // أولوية: old() ثم المحفوظ من قاعدة البيانات
        if (OLD_PRICING && OLD_PRICING[branchId] !== undefined) {
            return OLD_PRICING[branchId].base_price ?? OLD_PRICING[branchId].price_without_trainer ?? '';
        }
        if (BRANCH_PRICES && BRANCH_PRICES[branchId] !== undefined) {
            return BRANCH_PRICES[branchId].base_price ?? BRANCH_PRICES[branchId].price_without_trainer ?? '';
        }
        return '';
    }

    // ─── Render ─────────────────────────────────────────────────
    function renderPricing() {
        const container = document.getElementById('pricing_container');
        const selected  = $('#branches').val() || [];
        container.innerHTML = '';

        if (!selected.length) {
            container.innerHTML = `
                <div class="alert alert-info">
                    <i class="ri-information-line align-bottom me-1"></i>
                    {{ trans('subscriptions.pricing_select_branch_first') }}
                </div>`;
            return;
        }

        selected.forEach(function (branchId) {
            const b     = ALL_BRANCHES.find(x => String(x.id) === String(branchId));
            const price = getSavedBasePrice(branchId);

            const html = `
                <div class="card border mb-3">
                    <div class="card-header d-flex align-items-center justify-content-between">
                        <h5 class="card-title mb-0">
                            <i class="ri-store-2-line align-bottom me-1"></i> ${getBranchName(b)}
                        </h5>
                        <span class="badge bg-soft-primary text-primary">
                            {{ trans('subscriptions.pricing') }}
                        </span>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-0">
                                    <label class="form-label">
                                        {{ trans('subscriptions.base_price') }}
                                        <span class="text-danger">*</span>
                                    </label>
                                    <div class="input-group">
                                        <input
                                            type="number"
                                            step="0.01"
                                            min="0"
                                            class="form-control"
                                            name="pricing[${branchId}][base_price]"
                                            value="${price}"
                                            placeholder="0.00">
                                        <span class="input-group-text">
                                            <i class="ri-money-dollar-circle-line"></i>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>`;

            container.insertAdjacentHTML('beforeend', html);
        });
    }

    // ─── Toggles ─────────────────────────────────────────────────
    function togglePeriodOther() {
        const v = document.getElementById('sessions_period_type').value;
        document.getElementById('period_other_wrap').style.display = (v === 'other') ? 'block' : 'none';
    }

    function toggleGuest() {
        const checked = document.getElementById('allow_guest').checked;
        document.querySelectorAll('.guest_wrap').forEach(el => el.style.display = checked ? 'block' : 'none');
    }

    function toggleNotify() {
        const checked = document.getElementById('notify_before_end').checked;
        document.getElementById('notify_days_wrap').style.display = checked ? 'block' : 'none';
    }

    function toggleFreeze() {
        const checked = document.getElementById('allow_freeze').checked;
        document.getElementById('freeze_days_wrap').style.display = checked ? 'block' : 'none';
        if (!checked) {
            const inp = document.querySelector('input[name="max_freeze_days"]');
            if (inp) inp.value = '';
        }
    }

    // ─── Init ─────────────────────────────────────────────────────
    document.addEventListener('DOMContentLoaded', function () {
        if (typeof $ !== 'undefined' && $.fn && $.fn.select2) {
            const isRtl = $('html').attr('dir') === 'rtl';

            $('.select2').select2({ width: '100%', dir: isRtl ? 'rtl' : 'ltr' });

            $('#branches').select2({
                width: '100%',
                closeOnSelect: false,
                placeholder: '{{ trans('subscriptions.branches') }}',
                dir: isRtl ? 'rtl' : 'ltr'
            });
        }

        $('#branches').on('change', function () { renderPricing(); });

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
