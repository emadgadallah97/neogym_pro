{{-- resources/views/crm/members/index.blade.php --}}
@extends('layouts.master_table')

@section('title', trans('crm.smart_segments_title') . ' — CRM')

@section('css')
<style>
    .seg-grid { width: 100%; border-collapse: collapse; }

    .seg-grid thead th {
        background: #f8f9fa;
        border-bottom: 2px solid #e9ecef;
        color: #495057;
        font-weight: 600;
        font-size: 0.85rem;
        padding: 10px 12px;
        white-space: nowrap;
    }

    .seg-grid tbody td {
        border-bottom: 1px solid #f0f0f0;
        padding: 10px 12px;
        vertical-align: middle;
        white-space: nowrap;
    }

    .seg-grid tbody tr:hover td { background-color: rgba(13,110,253,0.03); }

    .seg-btns { display: flex; gap: 6px; justify-content: center; align-items: center; }
    .seg-btns .btn {
        padding: 4px 10px !important;
        line-height: 1.2;
        font-size: 12px;
        white-space: nowrap;
        min-width: 72px;
    }

    .seg-pager .pagination { margin-bottom: 0; }
    .seg-pager .page-link  { min-width: 36px; text-align: center; }
    .nav-pills .nav-link   { font-size: 0.85rem; }

    /* Loading overlay */
    #seg-loading {
        display: none;
        position: absolute;
        inset: 0;
        background: rgba(255,255,255,0.7);
        z-index: 10;
        border-radius: inherit;
        align-items: center;
        justify-content: center;
        backdrop-filter: blur(2px);
    }
    #seg-main-region { position: relative; }
    #seg-main-region.seg-busy #seg-loading { display: flex; }
    #seg-main-region.seg-busy { pointer-events: none; }

    /* Toast */
    #seg-toast-wrap {
        position: fixed; bottom: 24px; left: 24px;
        z-index: 9999; display: flex;
        flex-direction: column; gap: 8px;
        pointer-events: none;
    }
    .seg-toast {
        background: #323232; color: #fff;
        padding: 10px 18px; border-radius: 8px;
        font-size: 0.87rem; opacity: 0;
        transform: translateY(10px);
        transition: all .25s ease;
        max-width: 320px;
        pointer-events: none;
    }
    .seg-toast.show { opacity: 1; transform: translateY(0); }
    .seg-toast.success { border-right: 4px solid #28a745; }
    .seg-toast.error   { border-right: 4px solid #dc3545; }
</style>
@endsection

@section('content')
<div class="container-fluid py-3" dir="rtl">

    {{-- Header --}}
    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1 small">
                    <li class="breadcrumb-item">
                        <a href="{{ route('crm.dashboard') }}" class="text-decoration-none">CRM</a>
                    </li>
                    <li class="breadcrumb-item active">{{ trans('crm.smart_segments_title') }}</li>
                </ol>
            </nav>
            <h4 class="fw-bold mb-0">{{ trans('crm.smart_segments_title') }}</h4>
        </div>
    </div>

    {{-- ══ كل ما يتغير بالـ AJAX ══ --}}
    <div id="seg-main-region">

        {{-- Loading Overlay --}}
        <div id="seg-loading">
            <div class="bg-white rounded shadow-sm px-4 py-3 d-flex align-items-center gap-2">
                <div class="spinner-border spinner-border-sm text-primary" role="status"></div>
                <span class="text-muted small fw-semibold">{{ trans('crm.loading_data') }}</span>
            </div>
        </div>

        {{-- Segment Tabs --}}
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-body py-3">
                <ul class="nav nav-pills flex-wrap gap-2 mb-0" id="seg-tabs">

                    {{-- تاب الكل --}}
                    @php
                        $isAllActive = $segment === 'all';
                    @endphp
                    <li class="nav-item">
                        <a href="{{ route('crm.members.index', array_merge(request()->only('search','branch_id'), ['segment' => 'all'])) }}"
                           class="seg-nav-link nav-link py-2 px-3 {{ $isAllActive ? 'active bg-dark' : 'text-muted' }}">
                            <i class="fas fa-users me-1"></i> {{ trans('crm.seg_all') }}
                            @if(($segmentCounts['all'] ?? 0) > 0)
                                <span class="badge ms-1 {{ $isAllActive ? 'bg-white text-dark' : 'bg-dark' }}">
                                    {{ number_format($segmentCounts['all']) }}
                                </span>
                            @endif
                        </a>
                    </li>

                    @foreach($segmentsMeta as $key => $meta)
                        @php
                            $isActive = $segment === $key;
                            $cnt      = $segmentCounts[$key] ?? 0;
                            $color    = $meta['color'];
                            $isDark   = in_array($color, ['warning', 'light']);
                        @endphp
                        <li class="nav-item">
                            <a href="{{ route('crm.members.index', array_merge(request()->only('search','branch_id'), ['segment' => $key])) }}"
                               class="seg-nav-link nav-link py-2 px-3 {{ $isActive ? 'active bg-'.$color.($isDark?' text-dark':'') : 'text-muted' }}">
                                {{ $meta['label'] }}
                                @if($cnt > 0)
                                    <span class="badge ms-1 {{ $isActive ? 'bg-white text-dark' : 'bg-'.$color.($isDark?' text-dark':'') }}">
                                        {{ number_format($cnt) }}
                                    </span>
                                @endif
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>

        {{-- Search & Filter --}}
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-body py-3">
                <form id="seg-search-form" method="GET" action="{{ route('crm.members.index') }}" class="row g-2 align-items-center">
                    <input type="hidden" name="segment" value="{{ $segment }}">

                    <div class="col-md-5">
                        <div class="input-group">
                            <span class="input-group-text bg-white border-end-0">{{ trans('crm.search') }}</span>
                            <input type="text"
                                   name="search"
                                   id="seg-search-input"
                                   class="form-control border-start-0"
                                   placeholder="{{ trans('crm.search_name_phone') }}"
                                   value="{{ $search }}">
                            @if($search)
                                <button type="button"
                                        class="btn btn-outline-secondary"
                                        onclick="this.previousElementSibling.value='';document.getElementById('seg-search-form').requestSubmit()">
                                    {{ trans('crm.clear') }}
                                </button>
                            @endif
                        </div>
                    </div>

                    <div class="col-md-3">
                        <select name="branch_id" class="form-select">
                            <option value="">{{ trans('crm.all_branches') }}</option>
                            @foreach($branches as $branch)
                                <option value="{{ $branch->id }}" {{ $branchId == $branch->id ? 'selected' : '' }}>
                                    {{ is_array($branch->name)
                                        ? ($branch->name[app()->getLocale()] ?? $branch->name['ar'] ?? $branch->name['en'] ?? '')
                                        : $branch->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary w-100">{{ trans('crm.apply') }}</button>
                    </div>

                    @if($search || $branchId)
                        <div class="col-md-2">
                            <a href="{{ route('crm.members.index', ['segment' => $segment]) }}"
                               class="seg-ajax-link btn btn-outline-secondary w-100">{{ trans('crm.reset') }}</a>
                        </div>
                    @endif
                </form>
            </div>
        </div>

        {{-- Table Card --}}
        <div class="card border-0 shadow-sm">

            <div class="card-header bg-white border-0 pt-3 pb-2 d-flex align-items-center justify-content-between">
                @php
                    $segColor = $segment === 'all' ? 'dark' : ($segmentsMeta[$segment]['color'] ?? 'secondary');
                    $segLabel = $segment === 'all' ? trans('crm.seg_all') : ($segmentsMeta[$segment]['label'] ?? '');
                @endphp
                <div class="d-flex align-items-center gap-2">
                    <span class="badge bg-{{ $segColor }} {{ $segColor==='warning'?'text-dark':'' }} rounded-pill px-3 py-2">
                        {{ $segLabel }}
                    </span>
                    <span class="text-muted small">{{ number_format($members->total()) }} {{ trans('crm.member') }}</span>
                </div>
                <small class="text-muted">{{ trans('crm.members_page_x_of_y', ['current' => $members->currentPage(), 'last' => $members->lastPage()]) }}</small>
            </div>

            <div class="card-body p-0">

                @if($members->isEmpty())
                    <div class="text-center py-5">
                        <h5 class="text-muted mb-1">{{ trans('crm.no_results_segment') }}</h5>
                        <p class="text-muted small mb-0">
                            {{ ($search || $branchId) ? trans('crm.try_search_criteria') : trans('crm.all_members_fine') }}
                        </p>
                    </div>
                @else

                    <div class="table-responsive">
                        <table class="seg-grid">
                            <thead>
                                <tr>
                                    <th class="ps-3" style="width:40px">#</th>
                                    <th style="min-width:190px">{{ trans('crm.th_member') }}</th>
                                    <th style="min-width:130px">{{ trans('crm.phone') }}</th>
                                    <th style="min-width:110px">{{ trans('crm.th_branch') }}</th>
                                    <th style="min-width:160px">
                                        @switch($segment)
                                            @case('expiring7')
                                            @case('expiring30') {{ trans('crm.th_subscription_expiry') }} @break
                                            @case('expired')    {{ trans('crm.th_last_sub') }}             @break
                                            @case('frozen')     {{ trans('crm.th_freeze_period') }}        @break
                                            @case('inactive')   {{ trans('crm.th_since_last_visit') }}     @break
                                            @case('new')        {{ trans('crm.th_join_date') }}             @break
                                            @case('debt')       {{ trans('crm.th_due_amount') }}            @break
                                            @default            {{ trans('crm.th_current_sub') }}
                                        @endswitch
                                    </th>
                                    <th style="min-width:110px">{{ trans('crm.th_last_visit') }}</th>
                                    <th class="text-center pe-3" style="min-width:240px">{{ trans('crm.th_actions') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($members as $member)
                                    @php
                                        $sub             = $latestSubs->get($member->id);
                                        $lastAtt         = $lastAttendances->get($member->id);
                                        $planNameDisplay = $sub->plan_name_display ?? '—';
                                        $waRaw           = $member->whatsapp ?: ($member->phone ?? '');
                                        $waNumber        = preg_replace('/[^0-9]/', '', $waRaw);
                                        $name = $member->first_name ?? '';
                                        $waText = match($segment) {
                                            'expiring7','expiring30' => trans('crm.wa_text_expiring', ['name' => $name]),
                                            'expired'               => trans('crm.wa_text_expired',  ['name' => $name]),
                                            'inactive'              => trans('crm.wa_text_inactive', ['name' => $name]),
                                            'debt'                  => trans('crm.wa_text_debt',     ['name' => $name]),
                                            'frozen'                => trans('crm.wa_text_frozen',   ['name' => $name]),
                                            default                 => trans('crm.wa_text_default',  ['name' => $name]),
                                        };
                                    @endphp

                                    <tr>
                                        <td class="ps-3 text-muted">
                                            {{ ($members->currentPage() - 1) * $members->perPage() + $loop->iteration }}
                                        </td>

                                        <td>
                                            <div class="d-flex align-items-center gap-2">
                                                @if($member->photo)
                                                    <img src="{{ url($member->photo) }}"
                                                         class="rounded-circle border"
                                                         width="36" height="36"
                                                         style="object-fit:cover;flex-shrink:0">
                                                @else
                                                    <div class="rounded-circle d-flex align-items-center justify-content-center fw-bold text-primary"
                                                         style="width:36px;height:36px;flex-shrink:0;background:rgba(13,110,253,0.1);border:1px solid rgba(13,110,253,0.2)">
                                                        {{ mb_strtoupper(mb_substr($member->first_name ?? '?', 0, 1)) }}
                                                    </div>
                                                @endif
                                                <div>
                                                    <div class="fw-semibold lh-sm">{{ $member->full_name }}</div>
                                                    <small class="text-muted">{{ $member->member_code }}</small>
                                                </div>
                                            </div>
                                        </td>

                                        <td>
                                            <div>{{ $member->phone ?? '—' }}</div>
                                            @if($member->whatsapp && $member->whatsapp !== $member->phone)
                                                <small class="text-success">{{ $member->whatsapp }}</small>
                                            @endif
                                        </td>

                                        <td>
                                            @if($member->branch)
                                                <span class="badge bg-light text-dark border">
                                                    {{ is_array($member->branch->name)
                                                        ? ($member->branch->name[app()->getLocale()] ?? $member->branch->name['ar'] ?? $member->branch->name['en'] ?? '—')
                                                        : $member->branch->name }}
                                                </span>
                                            @else
                                                <span class="text-muted">—</span>
                                            @endif
                                        </td>

                                        <td>
                                            @switch($segment)
                                                @case('expiring7')
                                                @case('expiring30')
                                                    @if($sub)
                                                        @php
                                                            $endDate  = \Carbon\Carbon::parse($sub->end_date);
                                                            $daysLeft = (int) now()->startOfDay()->diffInDays($endDate, false);
                                                        @endphp
                                                        <div class="mb-1 text-truncate" style="max-width:170px" title="{{ $planNameDisplay }}">
                                                            {{ $planNameDisplay }}
                                                        </div>
                                                        <span class="badge {{ $daysLeft <= 0 ? 'bg-danger' : ($daysLeft <= 7 ? 'bg-warning text-dark' : 'bg-info') }}">
                                                            {{ $daysLeft <= 0 ? trans('crm.ends_today') : trans('crm.after_x_days', ['count' => $daysLeft]) }}
                                                        </span>
                                                        <div class="text-muted mt-1" style="font-size:0.8rem">{{ $endDate->format('d/m/Y') }}</div>
                                                    @else
                                                        <span class="text-muted">—</span>
                                                    @endif
                                                    @break

                                                @case('expired')
                                                    @if($sub)
                                                        @php $endDate = \Carbon\Carbon::parse($sub->end_date); @endphp
                                                        <div class="mb-1 text-truncate" style="max-width:170px" title="{{ $planNameDisplay }}">{{ $planNameDisplay }}</div>
                                                        <span class="badge bg-danger">انتهى {{ $endDate->diffForHumans() }}</span>
                                                        <div class="text-muted mt-1" style="font-size:0.8rem">{{ $endDate->format('d/m/Y') }}</div>
                                                    @else
                                                        <span class="text-muted">—</span>
                                                    @endif
                                                    @break

                                                @case('frozen')
                                                    @if($member->freeze_from && $member->freeze_to)
                                                        <span class="badge bg-secondary">
                                                            {{ \Carbon\Carbon::parse($member->freeze_from)->format('d/m/Y') }} ← {{ \Carbon\Carbon::parse($member->freeze_to)->format('d/m/Y') }}
                                                        </span>
                                                    @else
                                                        <span class="badge bg-secondary">{{ trans('crm.frozen_badge') }}</span>
                                                    @endif
                                                    @break

                                                @case('inactive')
                                                    @if($lastAtt)
                                                        @php $daysSince = (int)\Carbon\Carbon::parse($lastAtt->attendance_date)->diffInDays(now()); @endphp
                                                        <span class="badge {{ $daysSince >= 30 ? 'bg-danger' : 'bg-warning text-dark' }}">
                                                            {{ trans('crm.since_x_days', ['count' => $daysSince]) }}
                                                        </span>
                                                        <div class="text-muted mt-1" style="font-size:0.8rem">
                                                            {{ \Carbon\Carbon::parse($lastAtt->attendance_date)->format('d/m/Y') }}
                                                        </div>
                                                    @else
                                                        <span class="badge bg-dark">{{ trans('crm.never_attended') }}</span>
                                                    @endif
                                                    @break

                                                @case('new')
                                                    <span class="badge bg-success">{{ $member->join_date?->format('d/m/Y') }}</span>
                                                    @break

                                                @case('debt')
                                                    @php $debtInfo = $unpaidAmounts->get($member->id); @endphp
                                                    @if($debtInfo)
                                                        <span class="fw-bold text-danger">{{ number_format($debtInfo->unpaid_total, 2) }}</span>
                                                        <div class="text-muted" style="font-size:0.8rem">{{ trans('crm.invoices_count', ['count' => $debtInfo->unpaid_count]) }}</div>
                                                    @else
                                                        <span class="text-muted">—</span>
                                                    @endif
                                                    @break

                                                @default
                                                    {{-- تاب الكل: عرض الاشتراك الحالي إن وجد --}}
                                                    @if($sub)
                                                        @php
                                                            $endDate  = \Carbon\Carbon::parse($sub->end_date);
                                                            $daysLeft = (int) now()->startOfDay()->diffInDays($endDate, false);
                                                        @endphp
                                                        <div class="mb-1 text-truncate" style="max-width:170px" title="{{ $planNameDisplay }}">
                                                            {{ $planNameDisplay }}
                                                        </div>
                                                        <span class="badge {{ $sub->status === 'active' ? ($daysLeft <= 7 ? 'bg-warning text-dark' : 'bg-success') : 'bg-secondary' }}">
                                                            {{ $sub->status === 'active' ? ($daysLeft <= 0 ? trans('crm.ends_today') : trans('crm.active_badge')) : trans('crm.expired_badge') }}
                                                        </span>
                                                    @else
                                                        <span class="text-muted">—</span>
                                                    @endif
                                                    @break
                                            @endswitch
                                        </td>

                                        <td>
                                            @if($lastAtt)
                                                <span class="text-muted" style="font-size:0.8rem">
                                                    {{ \Carbon\Carbon::parse($lastAtt->attendance_date)->diffForHumans() }}
                                                </span>
                                            @else
                                                <span class="text-muted">—</span>
                                            @endif
                                        </td>

                                        <td class="pe-3">
                                            <div class="seg-btns">
                                                @if($waNumber)
                                                    <a class="btn btn-success btn-sm"
                                                       target="_blank"
                                                       href="https://wa.me/{{ $waNumber }}?text={{ urlencode($waText) }}">
                                                        {{ trans('crm.whatsapp_btn') }}
                                                    </a>
                                                @else
                                                    <button class="btn btn-outline-secondary btn-sm" disabled>{{ trans('crm.whatsapp_btn') }}</button>
                                                @endif

                                                @can('crm_members_view')
                                                <a href="{{ route('crm.members.show', $member->id) }}"
                                                   class="btn btn-outline-primary btn-sm">
                                                    {{ trans('crm.view_btn') }}
                                                </a>
                                                @endcan

                                                @can('crm_followups_create')
                                                <button type="button"
                                                        class="btn btn-outline-warning btn-sm"
                                                        data-mid="{{ $member->id }}"
                                                        data-mname="{{ $member->full_name }}"
                                                        data-mbranch="{{ $member->branch_id }}"
                                                        data-mseg="{{ $segment }}"
                                                        onclick="segOpenFollowup(this)">
                                                    {{ trans('crm.followup_btn') }}
                                                </button>
                                                @endcan
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    @if($members->hasPages())
                        <div class="seg-pager px-4 py-3 border-top d-flex justify-content-between align-items-center flex-wrap gap-2">
                            <small class="text-muted">
                                {{ trans('crm.members_show_x_to_y', ['from' => $members->firstItem(), 'to' => $members->lastItem(), 'total' => number_format($members->total())]) }}
                            </small>
                            <div id="seg-pagination">
                                {{ $members->onEachSide(1)->links('pagination::bootstrap-5') }}
                            </div>
                        </div>
                    @endif

                @endif
            </div>
        </div>

    </div>{{-- #seg-main-region --}}

</div>

{{-- Toast --}}
<div id="seg-toast-wrap"></div>

{{-- Followup Modal (بدون <form> — كل شيء AJAX) --}}
<div class="modal fade" id="segFollowupDlg" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content" dir="rtl">
            <div class="modal-header border-0 pb-0">
                <h6 class="modal-title fw-bold">
                    <i class="fas fa-comments me-2 text-warning"></i>{{ trans('crm.add_followup_modal_title') }}
                </h6>
                <button type="button" class="btn-close ms-0 me-auto" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">
                {{-- معلومات العضو --}}
                <div class="alert alert-light border-start border-warning border-3 py-2 mb-3">
                    <i class="fas fa-user me-2 text-warning"></i>
                    <strong id="sfMemberName"></strong>
                </div>

                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label fw-semibold small">{{ trans('crm.followup_type') }}</label>
                        <select id="sfType" class="form-select form-select-sm" required>
                            <option value="renewal">{{ trans('crm.type_renewal') }}</option>
                            <option value="inactive">{{ trans('crm.type_inactive') }}</option>
                            <option value="freeze">{{ trans('crm.type_freeze') }}</option>
                            <option value="debt">{{ trans('crm.type_debt') }}</option>
                            <option value="general">{{ trans('crm.type_general') }}</option>
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-semibold small">{{ trans('crm.priority') }}</label>
                        <select id="sfPriority" class="form-select form-select-sm" required>
                            <option value="high">{{ trans('crm.priority_high') }}</option>
                            <option value="medium" selected>{{ trans('crm.priority_medium') }}</option>
                            <option value="low">{{ trans('crm.priority_low') }}</option>
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-semibold small">{{ trans('crm.type') }}</label>
                        <select id="sfStatus" class="form-select form-select-sm">
                            <option value="pending" selected>{{ trans('crm.status_pending') }}</option>
                            <option value="done">{{ trans('crm.status_done') }}</option>
                        </select>
                    </div>

                    <div class="col-12">
                        <label class="form-label fw-semibold small">{{ trans('crm.followup_date_label') }}</label>
                        <input type="datetime-local"
                               id="sfNextAction"
                               class="form-control form-control-sm">
                    </div>

                    <div class="col-12">
                        <label class="form-label fw-semibold small">{{ trans('crm.notes') }}</label>
                        <textarea id="sfNotes"
                                  class="form-control form-control-sm"
                                  rows="3"
                                  placeholder="{{ trans('crm.notes_ph') }}"></textarea>
                    </div>
                </div>
            </div>

            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">{{ trans('crm.cancel') }}</button>
                <button type="button" class="btn btn-warning btn-sm" id="sfSaveBtn" onclick="segSaveFollowup()">
                    <i class="fas fa-save me-1"></i> {{ trans('crm.save') }}
                </button>
            </div>
        </div>
    </div>
</div>

<script>
(function () {
    const CSRF      = document.querySelector('meta[name="csrf-token"]')?.content || '';
    const STORE_URL = '{{ route('crm.followups.store') }}';

    // ── AJAX Navigation ────────────────────────────────────────────
    var region = document.getElementById('seg-main-region');

    function segLoad(url) {
        region.classList.add('seg-busy');

        fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(r => r.text())
            .then(function (html) {
                var doc = new DOMParser().parseFromString(html, 'text/html');
                var newRegion = doc.getElementById('seg-main-region');
                if (newRegion) {
                    region.innerHTML = newRegion.innerHTML;
                    history.pushState(null, '', url);
                    bindEvents();
                }
            })
            .catch(() => { window.location.href = url; })
            .finally(() => { region.classList.remove('seg-busy'); });
    }

    function bindEvents() {
        // Tabs
        region.querySelectorAll('.seg-nav-link').forEach(function (link) {
            link.addEventListener('click', function (e) {
                e.preventDefault();
                segLoad(this.href);
            });
        });

        // Pagination
        var pager = region.querySelector('#seg-pagination');
        if (pager) {
            pager.querySelectorAll('a.page-link').forEach(function (link) {
                link.addEventListener('click', function (e) {
                    e.preventDefault();
                    if (this.href && this.href !== '#') segLoad(this.href);
                });
            });
        }

        // Search form
        var form = region.querySelector('#seg-search-form');
        if (form) {
            form.addEventListener('submit', function (e) {
                e.preventDefault();
                segLoad(this.action + '?' + new URLSearchParams(new FormData(this)).toString());
            });
        }

        // Reset link
        region.querySelectorAll('.seg-ajax-link').forEach(function (link) {
            link.addEventListener('click', function (e) {
                e.preventDefault();
                segLoad(this.href);
            });
        });
    }

    window.addEventListener('popstate', () => segLoad(window.location.href));
    document.addEventListener('DOMContentLoaded', bindEvents);

    // ── Toast ──────────────────────────────────────────────────────
    function toast(msg, type) {
        const wrap = document.getElementById('seg-toast-wrap');
        const el   = document.createElement('div');
        el.className = 'seg-toast ' + (type || 'success');
        el.textContent = msg;
        wrap.appendChild(el);
        requestAnimationFrame(() => el.classList.add('show'));
        setTimeout(() => {
            el.classList.remove('show');
            setTimeout(() => el.remove(), 300);
        }, 3000);
    }

    // ── Open Followup Modal ────────────────────────────────────────
    var segMap = {
        'expiring7':  { type: 'renewal',  priority: 'high'   },
        'expiring30': { type: 'renewal',  priority: 'medium' },
        'expired':    { type: 'renewal',  priority: 'high'   },
        'frozen':     { type: 'freeze',   priority: 'medium' },
        'inactive':   { type: 'inactive', priority: 'medium' },
        'debt':       { type: 'debt',     priority: 'high'   },
        'new':        { type: 'general',  priority: 'low'    },
        'all':        { type: 'general',  priority: 'medium' },
    };

    // نحتفظ بالعضو الحالي المفتوح في الموديل
    var _currentMemberId   = null;
    var _currentBranchId   = null;

    window.segOpenFollowup = function (btn) {
        var meta = segMap[btn.dataset.mseg] || { type: 'general', priority: 'medium' };

        const d   = new Date(); d.setDate(d.getDate() + 1);
        const pad = n => String(n).padStart(2, '0');
        const dt  = `${d.getFullYear()}-${pad(d.getMonth()+1)}-${pad(d.getDate())}T10:00`;

        _currentMemberId = btn.dataset.mid;
        _currentBranchId = btn.dataset.mbranch;

        document.getElementById('sfMemberName').textContent = btn.dataset.mname;
        document.getElementById('sfType').value             = meta.type;
        document.getElementById('sfPriority').value         = meta.priority;
        document.getElementById('sfStatus').value           = 'pending';
        document.getElementById('sfNextAction').value       = dt;
        document.getElementById('sfNotes').value            = '';

        const saveBtn = document.getElementById('sfSaveBtn');
        saveBtn.disabled = false;
        saveBtn.innerHTML = '<i class="fas fa-save me-1"></i> {{ trans('crm.save') }}';

        new bootstrap.Modal(document.getElementById('segFollowupDlg')).show();
    };

    // ── Save via AJAX ──────────────────────────────────────────────
    window.segSaveFollowup = function () {
        const btn = document.getElementById('sfSaveBtn');

        if (!_currentMemberId) {
            toast('{{ trans('crm.cannot_identify_member') }}', 'error');
            return;
        }

        const type     = document.getElementById('sfType').value;
        const priority = document.getElementById('sfPriority').value;
        const status   = document.getElementById('sfStatus').value;
        const nextAt   = document.getElementById('sfNextAction').value;
        const notes    = document.getElementById('sfNotes').value.trim();

        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-circle-notch fa-spin me-1"></i> {{ trans('crm.saving') }}';

        fetch(STORE_URL, {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': CSRF,
                'Content-Type': 'application/json',
                'Accept': 'application/json',
            },
            body: JSON.stringify({
                member_id:      _currentMemberId,
                branch_id:      _currentBranchId,
                type:           type,
                status:         status,
                priority:       priority,
                next_action_at: nextAt || null,
                notes:          notes  || null,
                result:         null,
            })
        })
        .then(r => r.json())
        .then(function (res) {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-save me-1"></i> {{ trans('crm.save') }}';

            if (res && res.success) {
                toast('{{ trans('crm.followup_saved') }}', 'success');
                bootstrap.Modal.getInstance(document.getElementById('segFollowupDlg'))?.hide();
            } else {
                let errMsg = '{{ trans('crm.save_failed_retry') }}';
                if (res && res.errors) {
                    errMsg = Object.values(res.errors).flat().join(' | ');
                } else if (res && res.message) {
                    errMsg = res.message;
                }
                toast(errMsg, 'error');
            }
        })
        .catch(function () {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-save me-1"></i> {{ trans('crm.save') }}';
            toast('Error connecting to server', 'error');
        });
    };

})();
</script>
@endsection
