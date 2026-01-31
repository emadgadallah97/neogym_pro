@extends('layouts.master_table')

@section('title')
    {{ trans('main_trans.title') }}
@stop

@section('content')

@php
    $CITIES_JS = $Cities->map(function ($c) {
        return [
            'id' => $c->id,
            'name' => $c->getTranslation('name', 'ar'),
            'gov' => $c->id_government,
        ];
    })->values();

    $AREAS_JS = $Areas->map(function ($a) {
        return [
            'id' => $a->id,
            'name' => $a->getTranslation('name', 'ar'),
            'city' => $a->id_city,
        ];
    })->values();

    // Route templates (مع الـ locale)
    $MEMBERS_ROUTES = [
        'show'    => route('members.show', '__ID__'),
        'update'  => route('members.update', '__ID__'),
        'destroy' => route('members.destroy', '__ID__'),
        'card'    => route('members.card', '__ID__'),
        'qr_png'  => route('members.qr_png', '__ID__'),
    ];

    // KPIs
    $totalMembers  = $Members->count();
    $countActive   = $Members->where('status', 'active')->count();
    $countInactive = $Members->where('status', 'inactive')->count();
    $countFrozen   = $Members->where('status', 'frozen')->count();

    $branchCounts = $Members->whereNotNull('branch_id')
        ->groupBy('branch_id')
        ->map(fn($items) => $items->count());
@endphp

<div class="row">
    <div class="col-12">
        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
            <h4 class="mb-sm-0">{{ trans('members.members') }}</h4>
            <div class="page-title-right">
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item"><a href="javascript: void(0);">{{ trans('settings_trans.settings') }}</a></li>
                    <li class="breadcrumb-item active">{{ trans('members.members') }}</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-12">
        <div class="card">

            <div class="card-header">
                <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                    <div>
                        <h5 class="card-title mb-0">{{ trans('members.members_list') }}</h5>
                        <small class="text-muted">{{ trans('members.members_hint') }}</small>
                    </div>

                    <div class="d-flex gap-2">
                        <button data-bs-toggle="modal" data-bs-target="#addMemberModal"
                                class="btn btn-primary waves-effect waves-light" type="button">
                            <i class="mdi mdi-account-plus-outline"></i>
                            {{ trans('members.add_new_member') }}
                        </button>
                    </div>
                </div>

                {{-- Session Messages --}}
                @if (Session::has('success'))
                    <div class="alert alert-success alert-dismissible mt-3" role="alert">
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        <strong>Success!</strong> {{ session('success') }}
                    </div>
                @endif

                @if (Session::has('error'))
                    <div class="alert alert-danger alert-dismissible mt-3" role="alert">
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        <strong>Error!</strong> {{ session('error') }}
                    </div>
                @endif

                @if ($errors->any())
                    <div class="alert alert-danger mt-3">
                        <ul class="mb-0">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                {{-- AJAX Messages --}}
                <div id="membersAjaxMsg" class="mt-3"></div>
            </div>

            <div class="card-body">

                {{-- KPIs --}}
                <div class="row g-3 mb-3">

                    <div class="col-md-6 col-xl-3">
                        <div class="card border border-primary bg-soft-primary mb-0">
                            <div class="card-body">
                                <div class="d-flex align-items-center justify-content-between">
                                    <div>
                                        <small class="text-muted">{{ trans('members.members') }}</small>
                                        <h4 class="mb-0">{{ $totalMembers }}</h4>
                                    </div>
                                    <div class="avatar-sm">
                                        <span class="avatar-title rounded bg-primary">
                                            <i class="mdi mdi-account-group-outline"></i>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6 col-xl-3">
                        <div class="card border border-success bg-soft-success mb-0">
                            <div class="card-body">
                                <div class="d-flex align-items-center justify-content-between">
                                    <div>
                                        <small class="text-muted">{{ trans('members.active') }}</small>
                                        <h4 class="mb-0">{{ $countActive }}</h4>
                                    </div>
                                    <div class="avatar-sm">
                                        <span class="avatar-title rounded bg-success">
                                            <i class="mdi mdi-check-circle-outline"></i>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6 col-xl-3">
                        <div class="card border border-danger bg-soft-danger mb-0">
                            <div class="card-body">
                                <div class="d-flex align-items-center justify-content-between">
                                    <div>
                                        <small class="text-muted">{{ trans('members.inactive') }}</small>
                                        <h4 class="mb-0">{{ $countInactive }}</h4>
                                    </div>
                                    <div class="avatar-sm">
                                        <span class="avatar-title rounded bg-danger">
                                            <i class="mdi mdi-close-circle-outline"></i>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6 col-xl-3">
                        <div class="card border border-warning bg-soft-warning mb-0">
                            <div class="card-body">
                                <div class="d-flex align-items-center justify-content-between">
                                    <div>
                                        <small class="text-muted">{{ trans('members.frozen') }}</small>
                                        <h4 class="mb-0">{{ $countFrozen }}</h4>
                                    </div>
                                    <div class="avatar-sm">
                                        <span class="avatar-title rounded bg-warning">
                                            <i class="mdi mdi-snowflake"></i>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>

                {{-- Branch KPIs --}}
                <div class="card border shadow-none mb-3">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <div class="fw-semibold">{{ trans('members.branch') }}</div>
                            <small class="text-muted">{{ trans('members.members') }}</small>
                        </div>

                        <div class="d-flex flex-wrap gap-2">
                            @foreach($Branches as $Branch)
                                @php
                                    $bName = $Branch->getTranslation('name','ar');
                                    $bCount = $branchCounts[$Branch->id] ?? 0;
                                @endphp

                                <span class="badge bg-soft-info text-info p-2">
                                    {{ $bName }}: <span class="fw-semibold text-dark">{{ $bCount }}</span>
                                </span>
                            @endforeach
                        </div>
                    </div>
                </div>

                {{-- Search Filters Bar --}}
                <div class="card border shadow-none mb-3">
                    <div class="card-body">
                        <div class="row g-2 align-items-end">
                            <div class="col-md-5">
                                <label class="form-label mb-1">{{ trans('members.search') }}</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light"><i class="mdi mdi-magnify"></i></span>
                                    <input type="text" class="form-control" id="memberGlobalSearch"
                                           placeholder="{{ trans('members.search_hint') }}">
                                </div>
                                <small class="text-muted">{{ trans('members.search_note') }}</small>
                            </div>

                            <div class="col-md-3">
                                <label class="form-label mb-1">{{ trans('members.branch') }}</label>
                                <select class="form-select" id="filterBranch">
                                    <option value="">{{ trans('settings_trans.choose') }}</option>
                                    @foreach($Branches as $Branch)
                                        <option value="{{ $Branch->getTranslation('name','ar') }}">
                                            {{ $Branch->getTranslation('name','ar') }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-2">
                                <label class="form-label mb-1">{{ trans('settings_trans.status') }}</label>
                                <select class="form-select" id="filterStatus">
                                    <option value="">{{ trans('settings_trans.choose') }}</option>
                                    <option value="{{ trans('members.active') }}">{{ trans('members.active') }}</option>
                                    <option value="{{ trans('members.inactive') }}">{{ trans('members.inactive') }}</option>
                                    <option value="{{ trans('members.frozen') }}">{{ trans('members.frozen') }}</option>
                                </select>
                            </div>

                            <div class="col-md-2">
                                <button type="button" class="btn btn-soft-secondary w-100" id="btnResetFilters">
                                    <i class="mdi mdi-filter-remove-outline"></i>
                                    {{ trans('members.reset_filters') }}
                                </button>
                            </div>

                            <div class="col-12 mt-2">
                                <div class="alert alert-info mb-0 py-2 px-3">
                                    <i class="mdi mdi-information-outline"></i>
                                    <strong>{{ trans('members.required_fields_note') }}</strong>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Table --}}
                <div class="table-responsive">
                    <table id="membersTable" class="table table-bordered dt-responsive nowrap table-striped align-middle" style="width:100%">
                        <thead class="table-light">
                        <tr>
                            <th data-ordering="false">{{ trans('settings_trans.srno') }}</th>
                            <th>{{ trans('members.member_code') }}</th>
                            <th>{{ trans('members.full_name') }}</th>
                            <th>{{ trans('members.branch') }}</th>
                            <th>{{ trans('members.phone') }}</th>
                            <th>{{ trans('settings_trans.status') }}</th>
                            <th>{{ trans('members.join_date') }}</th>
                            <th data-ordering="false">{{ trans('settings_trans.action') }}</th>
                        </tr>
                        </thead>

                        <tbody>
                        @php $i = 0; @endphp
                        @foreach($Members as $Member)
                            @php
                                $i++;
                                $branchName = $Member->branch ? $Member->branch->getTranslation('name','ar') : '-';
                                $photoUrl = !empty($Member->photo) ? url($Member->photo) : '';
                            @endphp

                            <tr data-row-id="{{ $Member->id }}">
                                <td>{{ $i }}</td>

                                <td>
                                    <span class="badge bg-dark-subtle text-dark fs-6 px-2 py-1">{{ $Member->member_code }}</span>
                                </td>

                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="avatar-xs">
                                            @if(!empty($Member->photo))
                                                <img src="{{ $photoUrl }}" class="rounded-circle" style="width:34px;height:34px;object-fit:cover">
                                            @else
                                                <span class="avatar-title rounded-circle bg-soft-primary text-primary">
                                                    <i class="mdi mdi-account-outline"></i>
                                                </span>
                                            @endif
                                        </div>
                                        <div class="lh-1">
                                            <div class="fw-semibold">{{ $Member->first_name }} {{ $Member->last_name }}</div>
                                            <small class="text-muted">{{ $Member->email ?? '-' }}</small>
                                        </div>
                                    </div>
                                </td>

                                <td>
                                    <span class="badge bg-soft-info text-info">{{ $branchName }}</span>
                                </td>

                                <td>{{ $Member->phone ?? '-' }}</td>

                                <td>
                                    @if($Member->status == 'active')
                                        <span class="badge bg-success">{{ trans('members.active') }}</span>
                                    @elseif($Member->status == 'inactive')
                                        <span class="badge bg-danger">{{ trans('members.inactive') }}</span>
                                    @else
                                        <span class="badge bg-warning text-dark">{{ trans('members.frozen') }}</span>
                                    @endif
                                </td>

                                <td>{{ optional($Member->join_date)->format('Y-m-d') }}</td>

                                <td>
                                    <div class="d-flex align-items-center gap-1 justify-content-end flex-wrap">

                                        {{-- View (AJAX show) --}}
                                        <button type="button"
                                                class="btn btn-sm btn-soft-info btn-icon"
                                                title="{{ trans('members.view') }}"
                                                data-tooltip="1"
                                                data-bs-toggle="modal"
                                                data-bs-target="#viewMemberModal"
                                                data-id="{{ $Member->id }}"
                                                data-show-url="{{ route('members.show', $Member->id) }}">
                                            <i class="mdi mdi-eye-outline"></i>
                                        </button>

                                        {{-- Edit (AJAX show + set update action) --}}
                                        <button type="button"
                                                class="btn btn-sm btn-soft-warning btn-icon btn-edit-member"
                                                title="{{ trans('members.edit') }}"
                                                data-tooltip="1"
                                                data-bs-toggle="modal"
                                                data-bs-target="#editMemberModal"
                                                data-id="{{ $Member->id }}"
                                                data-show-url="{{ route('members.show', $Member->id) }}">
                                            <i class="mdi mdi-pencil-outline"></i>
                                        </button>

                                        {{-- Card --}}
                                        <a class="btn btn-sm btn-soft-primary btn-icon"
                                           title="{{ trans('members.print_card') }}"
                                           data-tooltip="1"
                                           target="_blank"
                                           href="{{ route('members.card', $Member->id) }}">
                                            <i class="mdi mdi-card-account-details-outline"></i>
                                        </a>

                                        {{-- QR --}}
                                        <a class="btn btn-sm btn-soft-secondary btn-icon"
                                           title="{{ trans('members.download_qr_png') }}"
                                           data-tooltip="1"
                                           href="{{ route('members.qr_png', $Member->id) }}">
                                            <i class="mdi mdi-qrcode"></i>
                                        </a>

                                        {{-- Delete --}}
                                        <button type="button"
                                                class="btn btn-sm btn-soft-danger btn-icon btn-delete-member"
                                                title="{{ trans('members.delete') }}"
                                                data-tooltip="1"
                                                data-bs-toggle="modal"
                                                data-bs-target="#deleteMemberModal"
                                                data-id="{{ $Member->id }}"
                                                data-name="{{ $Member->first_name }} {{ $Member->last_name }}">
                                            <i class="ri-delete-bin-6-line"></i>
                                        </button>

                                    </div>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>

                    </table>
                </div>

            </div>
        </div>
    </div>
</div>

{{-- View Modal --}}
@include('members.show')

{{-- Delete Modal --}}
<div id="deleteMemberModal" class="modal fade" tabindex="-1" aria-hidden="true" style="display: none">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-body text-center p-5">
                <div class="mt-4 pt-2">
                    <h4>{{ trans('members.delete_confirm_title') }}</h4>
                    <p class="text-muted">
                        {{ trans('members.delete_confirm_text') }}
                        <span id="deleteMemberName"></span>
                    </p>

                    {{-- action سيتم ضبطه بالـ JS --}}
                    <form action="{{ route('members.destroy','__ID__') }}" method="post" id="deleteMemberForm">
                        @csrf
                        @method('delete')
                        <input class="form-control" name="id" id="deleteMemberId" type="hidden">
                        <button class="btn btn-warning" type="submit">{{ trans('members.confirm_delete') }}</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Add Modal --}}
@include('members.form', [
    'mode' => 'add',
    'modalId' => 'addMemberModal',
    'headerClass' => 'bg-soft-success',
    'icon' => 'mdi-account-plus-outline',
    'title' => trans('members.add_new_member'),
    'formId' => 'addMemberForm',
    'action' => route('members.store'),
    'httpMethod' => 'post',
    'member' => null,
    'Branches' => $Branches,
    'Governments' => $Governments,
    'Cities' => $Cities,
    'Areas' => $Areas,
])

{{-- Edit Modal --}}
@include('members.form', [
    'mode' => 'edit',
    'modalId' => 'editMemberModal',
    'headerClass' => 'bg-soft-warning',
    'icon' => 'mdi-account-edit-outline',
    'title' => trans('members.update_member'),
    'formId' => 'editMemberForm',
    // action سيتم ضبطه بالـ JS
    'action' => route('members.update','__ID__'),
    'httpMethod' => 'patch',
    'member' => null,
    'Branches' => $Branches,
    'Governments' => $Governments,
    'Cities' => $Cities,
    'Areas' => $Areas,
])

<script>
    const MEMBERS_TR = {
        active: @json(trans('members.active')),
        inactive: @json(trans('members.inactive')),
        frozen: @json(trans('members.frozen')),
        choose: @json(trans('settings_trans.choose')),
        view: @json(trans('members.view')),
        edit: @json(trans('members.edit')),
        delete: @json(trans('members.delete')),
        print_card: @json(trans('members.print_card')),
        download_qr_png: @json(trans('members.download_qr_png')),
        submit: @json(trans('settings_trans.submit')),
        saved: @json(trans('members.saved')),
        male: @json(trans('members.male')),
        female: @json(trans('members.female')),
    };

    const MEMBERS_ROUTES = @json($MEMBERS_ROUTES);
    const CITIES = @json($CITIES_JS);
    const AREAS  = @json($AREAS_JS);

    function routeWithId(tpl, id){
        return String(tpl).replace('__ID__', String(id));
    }

    function alertBox(type, message, errors = null) {
        let html = `<div class="alert alert-${type} alert-dismissible" role="alert">
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            <div>${message}</div>`;
        if (errors) {
            html += `<ul class="mb-0 mt-2">`;
            Object.keys(errors).forEach(k => {
                (errors[k] || []).forEach(e => html += `<li>${e}</li>`);
            });
            html += `</ul>`;
        }
        html += `</div>`;
        return html;
    }

    function statusBadge(status) {
        if (status === 'active') return `<span class="badge bg-success">${MEMBERS_TR.active}</span>`;
        if (status === 'inactive') return `<span class="badge bg-danger">${MEMBERS_TR.inactive}</span>`;
        return `<span class="badge bg-warning text-dark">${MEMBERS_TR.frozen}</span>`;
    }

    function statusText(status) {
        if (status === 'active') return MEMBERS_TR.active;
        if (status === 'inactive') return MEMBERS_TR.inactive;
        return MEMBERS_TR.frozen;
    }

    function initFreezeToggle(formEl) {
        const statusSelect = formEl.querySelector('.member-status');
        const freezeBox = formEl.querySelector('.freeze-box');
        if (!statusSelect || !freezeBox) return;

        function toggle() {
            const v = statusSelect.value;
            freezeBox.style.display = (v === 'frozen') ? 'block' : 'none';
        }

        statusSelect.addEventListener('change', toggle);
        toggle();
    }

    function fillSelect(selectEl, items, selectedId) {
        if (!selectEl) return;

        const current = selectEl.value;
        selectEl.innerHTML = `<option value="">${MEMBERS_TR.choose}</option>`;
        items.forEach(it => {
            const opt = document.createElement('option');
            opt.value = it.id;
            opt.textContent = it.name;
            selectEl.appendChild(opt);
        });

        if (selectedId !== undefined && selectedId !== null && selectedId !== '') {
            selectEl.value = String(selectedId);
        } else if (current) {
            selectEl.value = current;
        }
    }

    function initGeo(formEl) {
        const gov = formEl.querySelector('.gov-select');
        const city = formEl.querySelector('.city-select');
        const area = formEl.querySelector('.area-select');

        if (!gov || !city || !area) return;

        function refreshCities(selectedCityId = null) {
            const govId = gov.value;
            const filtered = govId ? CITIES.filter(x => String(x.gov) === String(govId)) : [];
            fillSelect(city, filtered, selectedCityId);
            fillSelect(area, [], null);
        }

        function refreshAreas(selectedAreaId = null) {
            const cityId = city.value;
            const filtered = cityId ? AREAS.filter(x => String(x.city) === String(cityId)) : [];
            fillSelect(area, filtered, selectedAreaId);
        }

        gov.addEventListener('change', function(){ refreshCities(null); });
        city.addEventListener('change', function(){ refreshAreas(null); });

        const initGov = gov.value;
        const initCity = city.value;
        const initArea = area.value;

        if (initGov) {
            refreshCities(initCity);
            if (initCity) refreshAreas(initArea);
        }
    }

    function bindAjaxForm(formId, onSuccess) {
        const form = document.getElementById(formId);
        if (!form) return;

        form.addEventListener('submit', function(e){
            e.preventDefault();

            const url = form.getAttribute('action');
            const method = (form.getAttribute('data-method') || form.getAttribute('method') || 'POST').toUpperCase();
            const fd = new FormData(form);

            $.ajax({
                url: url,
                method: method === 'GET' ? 'GET' : 'POST',
                data: fd,
                processData: false,
                contentType: false,
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                success: function(resp){
                    form.querySelectorAll('input[type="file"]').forEach(inp => inp.value = '');
                    $('#membersAjaxMsg').html(alertBox('success', resp.message || MEMBERS_TR.saved));
                    if (typeof onSuccess === 'function') onSuccess(resp);

                    const modalEl = form.closest('.modal');
                    if (modalEl) {
                        const m = bootstrap.Modal.getInstance(modalEl);
                        if (m) m.hide();
                    }

                    if (formId === 'addMemberForm') {
                        form.reset();
                        initFreezeToggle(form);
                        initGeo(form);
                    }
                },
                error: function(xhr){
                    if (xhr.status === 422) {
                        const r = xhr.responseJSON || {};
                        $('#membersAjaxMsg').html(alertBox('danger', r.message || 'Validation Error', r.errors || null));
                        // القيم تظل كما هي لأننا لم نعمل reset
                    } else {
                        $('#membersAjaxMsg').html(alertBox('danger', 'Server Error'));
                    }
                }
            });
        });
    }

    function initTooltips(scope = document) {
        if (!window.bootstrap || !bootstrap.Tooltip) return;

        scope.querySelectorAll('[data-tooltip="1"]').forEach(el => {
            if (el.getAttribute('data-bs-tooltip-inited') === '1') return;
            new bootstrap.Tooltip(el);
            el.setAttribute('data-bs-tooltip-inited', '1');
        });
    }

    function renderRow(member) {
        // member هنا من الكنترولر: snake_case
        const photo = member.photo_url;
        const avatar = photo
            ? `<img src="${photo}" class="rounded-circle" style="width:34px;height:34px;object-fit:cover">`
            : `<span class="avatar-title rounded-circle bg-soft-primary text-primary"><i class="mdi mdi-account-outline"></i></span>`;

        const email = member.email ? member.email : '-';
        const showUrl = routeWithId(MEMBERS_ROUTES.show, member.id);

        return `
<tr data-row-id="${member.id}">
    <td></td>
    <td><span class="badge bg-dark-subtle text-dark fs-6 px-2 py-1">${member.member_code || ''}</span></td>
    <td>
        <div class="d-flex align-items-center gap-2">
            <div class="avatar-xs">${avatar}</div>
            <div class="lh-1">
                <div class="fw-semibold">${member.full_name || ''}</div>
                <small class="text-muted">${email}</small>
            </div>
        </div>
    </td>
    <td><span class="badge bg-soft-info text-info">${member.branch_name || '-'}</span></td>
    <td>${member.phone || '-'}</td>
    <td>${statusBadge(member.status)}</td>
    <td>${member.join_date || ''}</td>
    <td>
        <div class="d-flex align-items-center gap-1 justify-content-end flex-wrap">

            <button type="button"
                class="btn btn-sm btn-soft-info btn-icon"
                title="${MEMBERS_TR.view}"
                data-tooltip="1"
                data-bs-toggle="modal"
                data-bs-target="#viewMemberModal"
                data-id="${member.id}"
                data-show-url="${showUrl}"
            ><i class="mdi mdi-eye-outline"></i></button>

            <button type="button"
                class="btn btn-sm btn-soft-warning btn-icon btn-edit-member"
                title="${MEMBERS_TR.edit}"
                data-tooltip="1"
                data-bs-toggle="modal"
                data-bs-target="#editMemberModal"
                data-id="${member.id}"
                data-show-url="${showUrl}"
            ><i class="mdi mdi-pencil-outline"></i></button>

            <a class="btn btn-sm btn-soft-primary btn-icon"
               title="${MEMBERS_TR.print_card}"
               data-tooltip="1"
               target="_blank"
               href="${member.card_url || routeWithId(MEMBERS_ROUTES.card, member.id)}"
            ><i class="mdi mdi-card-account-details-outline"></i></a>

            <a class="btn btn-sm btn-soft-secondary btn-icon"
               title="${MEMBERS_TR.download_qr_png}"
               data-tooltip="1"
               href="${member.qr_png_url || routeWithId(MEMBERS_ROUTES.qr_png, member.id)}"
            ><i class="mdi mdi-qrcode"></i></a>

            <button type="button"
                class="btn btn-sm btn-soft-danger btn-icon btn-delete-member"
                title="${MEMBERS_TR.delete}"
                data-tooltip="1"
                data-bs-toggle="modal"
                data-bs-target="#deleteMemberModal"
                data-id="${member.id}"
                data-name="${member.full_name || ''}"
            ><i class="ri-delete-bin-6-line"></i></button>

        </div>
    </td>
</tr>`;
    }

    function fetchMember(url, onSuccess) {
        if (!url) return;

        $.ajax({
            url: url,
            method: 'GET',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            success: function(resp){
                if (!resp || !resp.member) return;
                if (typeof onSuccess === 'function') onSuccess(resp.member);
            },
            error: function(){
                $('#membersAjaxMsg').html(alertBox('danger', 'Failed to load member'));
            }
        });
    }

    document.addEventListener('DOMContentLoaded', function () {

        document.querySelectorAll('.member-form').forEach(f => {
            initFreezeToggle(f);
            initGeo(f);
        });

        // DataTable
        let dt = null;
        if (window.jQuery && $.fn.DataTable) {
            dt = $('#membersTable').DataTable({
                responsive: true,
                pageLength: 10,
                order: [[6, 'desc']],
                dom: "<'row align-items-center'<'col-md-6'l><'col-md-6 text-end'>>" +
                     "<'row'<'col-12'tr>>" +
                     "<'row align-items-center'<'col-md-5'i><'col-md-7'p>>",
            });

            dt.on('order.dt search.dt draw.dt', function () {
                let i = 1;
                dt.column(0, { search: 'applied', order: 'applied' }).nodes().each(function (cell) {
                    cell.innerHTML = i++;
                });
            }).draw();

            dt.on('draw.dt', function () {
                initTooltips(document.getElementById('membersTable'));
            });
        }

        initTooltips(document);

        $('#memberGlobalSearch').on('keyup change', function () {
            if (!dt) return;
            dt.search(this.value).draw();
        });

        $('#filterBranch').on('change', function () {
            if (!dt) return;
            dt.column(3).search(this.value).draw();
        });

        $('#filterStatus').on('change', function () {
            if (!dt) return;
            dt.column(5).search(this.value).draw();
        });

        $('#btnResetFilters').on('click', function () {
            $('#memberGlobalSearch').val('');
            $('#filterBranch').val('');
            $('#filterStatus').val('');
            if (!dt) return;
            dt.search('');
            dt.columns().search('');
            dt.draw();
        });

        // Add / Edit forms AJAX
        bindAjaxForm('addMemberForm', function(resp){
            if (!dt || !resp || !resp.member) return;
            const rowHtml = renderRow(resp.member);
            const $row = $(rowHtml);
            dt.row.add($row).draw(false);
        });

        bindAjaxForm('editMemberForm', function(resp){
            if (!dt || !resp || !resp.member) return;

            const rowHtml = renderRow(resp.member);
            const $row = $(rowHtml);

            const oldRow = document.querySelector(`tr[data-row-id="${resp.member.id}"]`);
            if (oldRow) dt.row(oldRow).remove();

            dt.row.add($row).draw(false);
        });

        // Delete modal fill + set correct action URL
        $(document).on('click', '.btn-delete-member', function(){
            const id = $(this).data('id');
            $('#deleteMemberId').val(id);
            $('#deleteMemberName').text($(this).data('name'));

            const form = document.getElementById('deleteMemberForm');
            if (form) {
                form.action = routeWithId(MEMBERS_ROUTES.destroy, id);
            }
        });

        // Delete submit
        $('#deleteMemberForm').on('submit', function(e){
            e.preventDefault();

            const fd = new FormData(this);

            $.ajax({
                url: $(this).attr('action'),
                method: 'POST',
                data: fd,
                processData: false,
                contentType: false,
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                success: function(resp){
                    $('#membersAjaxMsg').html(alertBox('success', resp.message || MEMBERS_TR.saved));
                    const oldRow = document.querySelector(`tr[data-row-id="${resp.id}"]`);
                    if (dt && oldRow) dt.row(oldRow).remove().draw(false);

                    const modalEl = document.getElementById('deleteMemberModal');
                    const m = bootstrap.Modal.getInstance(modalEl);
                    if (m) m.hide();
                },
                error: function(){
                    $('#membersAjaxMsg').html(alertBox('danger', 'Server Error'));
                }
            });
        });

        // VIEW modal (AJAX show)
        const viewModal = document.getElementById('viewMemberModal');
        if (viewModal) {
            viewModal.addEventListener('show.bs.modal', function (event) {
                const btn = event.relatedTarget;
                if (!btn) return;

                const url = btn.getAttribute('data-show-url');
                fetchMember(url, function(m){

                    // snake_case keys
                    document.getElementById('viewMemberTitle').textContent = m.full_name || '-';
                    document.getElementById('viewMemberSubtitle').textContent = m.member_code || '-';

                    document.getElementById('viewMemberName').textContent = m.full_name || '-';
                    document.getElementById('viewMemberCode').textContent = m.member_code || '-';
                    document.getElementById('viewMemberBranch').textContent = m.branch_name || '-';

                    document.getElementById('viewMemberPhone').textContent = m.phone || '-';
                    document.getElementById('viewMemberPhone2').textContent = m.phone2 || '-';
                    document.getElementById('viewMemberWhatsapp').textContent = m.whatsapp || '-';
                    document.getElementById('viewMemberEmail').textContent = m.email || '-';

                    document.getElementById('viewMemberGender').textContent =
                        m.gender === 'male' ? MEMBERS_TR.male : (m.gender === 'female' ? MEMBERS_TR.female : '-');

                    document.getElementById('viewMemberBirth').textContent = m.birth_date || '-';

                    document.getElementById('viewMemberAddress').textContent = m.address || '-';
                    document.getElementById('viewMemberGovernment').textContent = m.government_name || '-';
                    document.getElementById('viewMemberCity').textContent = m.city_name || '-';
                    document.getElementById('viewMemberArea').textContent = m.area_name || '-';

                    document.getElementById('viewMemberJoin').textContent = m.join_date || '-';

                    document.getElementById('viewMemberStatusBadge').textContent = statusText(m.status);
                    document.getElementById('viewMemberStatusText').textContent  = statusText(m.status);

                    document.getElementById('viewMemberFreezeFrom').textContent = m.freeze_from || '-';
                    document.getElementById('viewMemberFreezeTo').textContent = m.freeze_to || '-';

                    document.getElementById('viewMemberHeight').textContent = (m.height ?? '-') ;
                    document.getElementById('viewMemberWeight').textContent = (m.weight ?? '-') ;

                    document.getElementById('viewMemberMedical').textContent = m.medical_conditions || '-';
                    document.getElementById('viewMemberAllergies').textContent = m.allergies || '-';
                    document.getElementById('viewMemberNotes').textContent = m.notes || '-';

                    // links
                    document.getElementById('viewMemberCardLink').href = m.card_url || routeWithId(MEMBERS_ROUTES.card, m.id);
                    document.getElementById('viewMemberQrLink').href = m.qr_png_url || routeWithId(MEMBERS_ROUTES.qr_png, m.id);

                    // photo
                    const img = document.getElementById('viewMemberPhoto');
                    const ph = document.getElementById('viewMemberPhotoPlaceholder');
                    if (m.photo_url) {
                        img.src = m.photo_url;
                        img.style.display = 'inline-block';
                        ph.style.display = 'none';
                    } else {
                        img.src = '';
                        img.style.display = 'none';
                        ph.style.display = 'inline-flex';
                    }
                });
            });
        }

        // EDIT modal (AJAX show + set correct update URL)
        const editModal = document.getElementById('editMemberModal');
        if (editModal) {
            editModal.addEventListener('show.bs.modal', function (event) {
                const btn = event.relatedTarget;
                if (!btn) return;

                const url = btn.getAttribute('data-show-url');
                fetchMember(url, function(m){

                    // set correct form action (بدلاً من test)
                    const editForm = document.getElementById('editMemberForm');
                    if (editForm) {
                        editForm.action = routeWithId(MEMBERS_ROUTES.update, m.id);
                    }

                    $('#editMemberId').val(m.id);

                    $('#editFirstName').val(m.first_name || '');
                    $('#editLastName').val(m.last_name || '');
                    $('#editBranchId').val(m.branch_id || '');

                    $('#editGender').val(m.gender || '');
                    $('#editBirthDate').val(m.birth_date || '');

                    $('#editPhone').val(m.phone || '');
                    $('#editPhone2').val(m.phone2 || '');
                    $('#editWhatsapp').val(m.whatsapp || '');
                    $('#editEmail').val(m.email || '');

                    $('#editAddress').val(m.address || '');

                    $('#editJoinDate').val(m.join_date || '');

                    $('#editStatus').val(m.status || 'active');
                    $('#editFreezeFrom').val(m.freeze_from || '');
                    $('#editFreezeTo').val(m.freeze_to || '');

                    $('#editHeight').val(m.height || '');
                    $('#editWeight').val(m.weight || '');

                    $('#editMedical').val(m.medical_conditions || '');
                    $('#editAllergies').val(m.allergies || '');
                    $('#editNotes').val(m.notes || '');

                    // Geo selects
                    $('#editGov').val(m.id_government || '');

                    if (editForm) {
                        initFreezeToggle(editForm);
                        initGeo(editForm);

                        const govEl  = editForm.querySelector('.gov-select');
                        const cityEl = editForm.querySelector('.city-select');
                        const areaEl = editForm.querySelector('.area-select');

                        if (govEl) govEl.value = (m.id_government || '');

                        if (cityEl) {
                            const filteredCities = (m.id_government) ? CITIES.filter(x => String(x.gov) === String(m.id_government)) : [];
                            fillSelect(cityEl, filteredCities, m.id_city || '');
                        }

                        if (areaEl) {
                            const filteredAreas = (m.id_city) ? AREAS.filter(x => String(x.city) === String(m.id_city)) : [];
                            fillSelect(areaEl, filteredAreas, m.id_area || '');
                        }
                    }

                    // Photo link
                    const wrap = document.getElementById('editPhotoLinkWrap');
                    const link = document.getElementById('editPhotoLink');
                    if (wrap && link) {
                        if (m.photo_url) {
                            link.href = m.photo_url;
                            wrap.style.display = 'block';
                        } else {
                            link.href = '#';
                            wrap.style.display = 'none';
                        }
                    }
                });
            });
        }

    });
</script>

@endsection
