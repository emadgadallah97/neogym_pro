@extends('layouts.master_table')

@section('title', trans('crm.dashboard_title'))

@section('content')
{{-- Page Title --}}
<div class="row">
    <div class="col-12">
        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
            <h4 class="mb-sm-0 font">
                <i class="ri-hand-coin-line me-1"></i>
                {{ trans('crm.dashboard_title') }}
            </h4>
            <div class="page-title-right">
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item active">{{ trans('crm.dashboard_title') }}</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<div class="container-fluid py-3" dir="rtl">

{{-- ── Page Header ──────────────────────────────────────── --}}
<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h4 class="mb-0 fw-bold">
            <i class="fas fa-chart-line me-2"></i>
            {{ trans('crm.dashboard_title') }}
        </h4>
        <small class="text-muted">{{ now()->translatedFormat('l، d F Y') }}</small>
    </div>

    <div class="d-flex gap-2">
        <a href="{{ route('crm.prospects.index') }}" class="btn btn-sm btn-outline-primary">
            <i class="fas fa-users me-1"></i> {{ trans('crm.seg_prospects') }}
        </a>
        @can('crm_prospects_create')
        <a href="{{ route('crm.prospects.create') }}" class="btn btn-sm btn-outline-secondary">
            <i class="fas fa-user-tag me-1"></i> {{ trans('crm.new_prospect') }}
        </a>
        @endcan
        @can('crm_followups_create')
        <a href="{{ route('crm.followups.index') }}" class="btn btn-primary btn-sm">
            <i class="fas fa-plus me-1"></i> {{ trans('crm.new_followup') }}
        </a>
        @endcan
    </div>
</div>

{{-- ── Overdue Alert ──────────────────────────────────────── --}}
@if($overdueCount > 0)
<div class="alert alert-danger alert-dismissible fade show d-flex align-items-center mb-4" role="alert">
    <i class="fas fa-exclamation-triangle fa-lg me-3"></i>
    <div>
        <strong>{{ trans('crm.alert') }}</strong>
        {!! trans('crm.overdue_alert_msg', ['count' => $overdueCount]) !!}
        <a href="{{ route('crm.followups.index', ['filter' => 'overdue']) }}" class="alert-link me-2">
            {{ trans('crm.view_overdue') }}
        </a>
    </div>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

{{-- ── Stats Row 1 ────────────────────────────────────────── --}}
<div class="row g-3 mb-3">

    {{-- Total Members --}}
    <div class="col-xl-3 col-md-6">
        <a href="{{ route('crm.members.index', ['segment' => 'all']) }}" class="text-decoration-none">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="rounded-3 bg-primary bg-opacity-10 p-3">
                        <i class="fas fa-users fa-2x text-primary"></i>
                    </div>
                    <div>
                        <div class="fs-3 fw-bold">{{ number_format($totalMembers) }}</div>
                        <div class="text-muted small">{{ trans('crm.seg_all') }}</div>
                    </div>
                </div>
            </div>
        </a>
    </div>

    {{-- Active Members --}}
    <div class="col-xl-3 col-md-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="rounded-3 bg-success bg-opacity-10 p-3">
                    <i class="fas fa-user-check fa-2x text-success"></i>
                </div>
                <div>
                    <div class="fs-3 fw-bold text-success">{{ number_format($activeMembers) }}</div>
                    <div class="text-muted small">{{ trans('crm.active_members') }}</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Expiring in 7 days --}}
    <div class="col-xl-3 col-md-6">
        <a href="{{ route('crm.members.index', ['segment' => 'expiring7']) }}" class="text-decoration-none">
            <div class="card border-0 shadow-sm h-100 {{ $expiring7 > 0 ? 'border-start border-warning border-4' : '' }}">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="rounded-3 bg-warning bg-opacity-10 p-3">
                        <i class="fas fa-clock fa-2x text-warning"></i>
                    </div>
                    <div>
                        <div class="fs-3 fw-bold text-warning">{{ number_format($expiring7) }}</div>
                        <div class="text-muted small">{{ trans('crm.expiring_7_days') }}</div>
                    </div>
                </div>
            </div>
        </a>
    </div>

    {{-- Inactive --}}
    <div class="col-xl-3 col-md-6">
        <a href="{{ route('crm.members.index', ['segment' => 'inactive']) }}" class="text-decoration-none">
            <div class="card border-0 shadow-sm h-100 {{ $inactiveCount > 0 ? 'border-start border-dark border-4' : '' }}">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="rounded-3 bg-dark bg-opacity-10 p-3">
                        <i class="fas fa-user-slash fa-2x text-dark"></i>
                    </div>
                    <div>
                        <div class="fs-3 fw-bold text-dark">{{ number_format($inactiveCount) }}</div>
                        <div class="text-muted small">{{ trans('crm.inactive_14_days') }}</div>
                    </div>
                </div>
            </div>
        </a>
    </div>
</div>

{{-- ── Stats Row 2 ────────────────────────────────────────── --}}
<div class="row g-3 mb-4">

    {{-- Prospects --}}
    <div class="col-xl-3 col-md-6">
        <a href="{{ route('crm.prospects.index') }}" class="text-decoration-none">
            <div class="card border-0 shadow-sm h-100 {{ $prospectsCount > 0 ? 'border-start border-4' : '' }}"
                 style="{{ $prospectsCount > 0 ? 'border-color:#6f42c1 !important' : '' }}">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="rounded-3 p-3" style="background:rgba(111,66,193,0.1)">
                        <i class="fas fa-user-tag fa-2x" style="color:#6f42c1"></i>
                    </div>
                    <div>
                        <div class="fs-3 fw-bold" style="color:#6f42c1">{{ number_format($prospectsCount) }}</div>
                        <div class="text-muted small">{{ trans('crm.seg_prospects') }}</div>
                    </div>
                </div>
            </div>
        </a>
    </div>

    {{-- New Members --}}
    <div class="col-xl-3 col-md-6">
        <a href="{{ route('crm.members.index', ['segment' => 'new']) }}" class="text-decoration-none">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="rounded-3 bg-success bg-opacity-10 p-3">
                        <i class="fas fa-user-plus fa-2x text-success"></i>
                    </div>
                    <div>
                        <div class="fs-3 fw-bold text-success">{{ number_format($newLast30) }}</div>
                        <div class="text-muted small">أعضاء جدد (30 يوم)</div>
                        @if($newThisMonth !== $newLast30)
                            <div class="text-muted" style="font-size:0.75rem">
                                هذا الشهر: {{ number_format($newThisMonth) }}
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </a>
    </div>

    {{-- Today Follow-ups --}}
    <div class="col-xl-3 col-md-6">
        <a href="{{ route('crm.followups.index') }}" class="text-decoration-none">
            <div class="card border-0 shadow-sm h-100 {{ $todayCount > 0 ? 'border-start border-primary border-4' : '' }}">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="rounded-3 bg-primary bg-opacity-10 p-3">
                        <i class="fas fa-tasks fa-2x text-primary"></i>
                    </div>
                    <div>
                        <div class="fs-3 fw-bold">
                            {{ number_format($todayCount) }}
                            @if($overdueCount > 0)
                                <small class="badge bg-danger fs-6">
                                    {{ trans('crm.overdue_badge', ['count' => $overdueCount]) }}
                                </small>
                            @endif
                        </div>
                        <div class="text-muted small">{{ trans('crm.today_followups') }}</div>
                    </div>
                </div>
            </div>
        </a>
    </div>

    {{-- Unpaid Members --}}
    <div class="col-xl-3 col-md-6">
        <a href="{{ route('crm.members.index', ['segment' => 'debt']) }}" class="text-decoration-none">
            <div class="card border-0 shadow-sm h-100 {{ $unpaidMembersCount > 0 ? 'border-start border-danger border-4' : '' }}">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="rounded-3 bg-danger bg-opacity-10 p-3">
                        <i class="fas fa-file-invoice-dollar fa-2x text-danger"></i>
                    </div>
                    <div>
                        <div class="fs-3 fw-bold text-danger">{{ number_format($unpaidMembersCount) }}</div>
                        <div class="text-muted small">{{ trans('crm.unpaid_members') }}</div>
                        <div class="text-muted" style="font-size:0.75rem">
                            {{ number_format($unpaidInvoicesCount) }} فاتورة
                        </div>
                    </div>
                </div>
            </div>
        </a>
    </div>
</div>

{{-- ── Smart Segments ──────────────────────────────────────── --}}
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white border-0 pt-3 pb-0">
        <h6 class="fw-bold mb-0">
            <i class="fas fa-layer-group text-primary me-2"></i>
            {{ trans('crm.smart_segments') }}
            <small class="text-muted fw-normal">{{ trans('crm.segments_hint') }}</small>
        </h6>
    </div>
    <div class="card-body">
        <div class="row g-3">
            @foreach($segments as $seg)
            <div class="col-xl col-md-4 col-6">
                @php
                    $segRoute = ($seg['key'] === 'prospects')
                        ? route('crm.prospects.index')
                        : route('crm.members.index', ['segment' => $seg['key']]);
                    $isPurple = ($seg['color'] === 'purple');
                @endphp
                <a href="{{ $segRoute }}" class="text-decoration-none">
                    @if($isPurple)
                        <div class="card border-0 text-center p-3 h-100 segment-card"
                             style="background:rgba(111,66,193,0.1)">
                            <i class="fas {{ $seg['icon'] }} fa-2x mb-2" style="color:#6f42c1"></i>
                            <div class="fs-4 fw-bold" style="color:#6f42c1">{{ number_format($seg['count']) }}</div>
                            <div class="small text-muted mt-1">{{ $seg['label'] }}</div>
                        </div>
                    @else
                        <div class="card border-0 bg-{{ $seg['color'] }} bg-opacity-10 text-center p-3 h-100 segment-card">
                            <i class="fas {{ $seg['icon'] }} fa-2x text-{{ $seg['color'] }} mb-2"></i>
                            <div class="fs-4 fw-bold text-{{ $seg['color'] }}">{{ number_format($seg['count']) }}</div>
                            <div class="small text-muted mt-1">{{ $seg['label'] }}</div>
                        </div>
                    @endif
                </a>
            </div>
            @endforeach
        </div>
    </div>
</div>

{{-- ── Bottom Row ──────────────────────────────────────────── --}}
<div class="row g-3">

    {{-- Today's Follow-ups ─────────────────────────────────── --}}
    <div class="col-xl-7">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white border-0 pt-3 d-flex align-items-center justify-content-between">
                <h6 class="fw-bold mb-0">
                    <i class="fas fa-bell text-warning me-2"></i>
                    {{ trans('crm.today_and_overdue') }}
                    @if($todayFollowups->count() > 0)
                        <span class="badge bg-warning text-dark ms-1" id="todayCount">
                            {{ $todayFollowups->count() }}
                        </span>
                    @endif
                </h6>
                @can('crm_followups_view')
                <a href="{{ route('crm.followups.index') }}" class="btn btn-outline-primary btn-sm">
                    {{ trans('crm.view_all') }}
                </a>
                @endcan
            </div>
            <div class="card-body p-0">
                @if($todayFollowups->isEmpty())
                    <div class="text-center py-5 text-muted">
                        <i class="fas fa-check-circle fa-3x mb-2 text-success"></i>
                        <p class="mb-0">{{ trans('crm.no_followups_today') }}</p>
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0" id="todayFollowupsTable">
                            <thead class="table-light">
                                <tr>
                                    <th>{{ trans('crm.member') }}</th>
                                    <th>{{ trans('crm.type') }}</th>
                                    <th>{{ trans('crm.priority') }}</th>
                                    <th>{{ trans('crm.appointment') }}</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($todayFollowups as $followup)
                                <tr class="{{ $followup->is_overdue ? 'table-danger' : '' }}">
                                    <td>
                                        <div class="fw-semibold">
                                            {{ $followup->member->full_name ?? '—' }}
                                        </div>
                                        <small class="text-muted">
                                            <i class="fas fa-phone fa-xs me-1"></i>
                                            {{ $followup->member->phone ?? '—' }}
                                        </small>
                                    </td>
                                    <td>
                                        <span class="badge bg-{{ $followup->type_badge_class }}">
                                            {{ $followup->type_label }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge bg-{{ $followup->priority_badge_class }}">
                                            {{ $followup->priority_label }}
                                        </span>
                                    </td>
                                    <td>
                                        @if($followup->is_overdue)
                                            <span class="text-danger fw-bold small">
                                                <i class="fas fa-exclamation-circle me-1"></i>
                                                {{ trans('crm.overdue') }} ({{ $followup->next_action_at?->diffForHumans() }})
                                            </span>
                                        @else
                                            <span class="text-muted small">
                                                {{ $followup->next_action_at?->format('h:i A') }}
                                            </span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="d-flex gap-1">
                                            @if($followup->member?->whatsapp)
                                            <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $followup->member->whatsapp) }}"
                                               target="_blank"
                                               class="btn btn-sm btn-success"
                                               title="{{ trans('crm.send_whatsapp') }}">
                                                <i class="fab fa-whatsapp"></i>
                                            </a>
                                            @endif
                                            {{-- ✅ التوجه لصفحة المتابعات مع فلتر المتابعة المحددة --}}
                                            @can('crm_followups_view')
                                            <a href="{{ route('crm.followups.index', [
                                                    'quick'       => 'all',
                                                    'followup_id' => $followup->id,
                                                ]) }}"
                                               class="btn btn-sm btn-outline-primary"
                                               title="{{ trans('crm.view_details') }}">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            @endcan
                                        </div>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    {{-- ✅ Pagination controls للمتابعات --}}
                    <div class="d-flex align-items-center justify-content-between px-3 py-2 border-top bg-light">
                        <small class="text-muted" id="todayPagInfo"></small>
                        <nav>
                            <ul class="pagination pagination-sm mb-0" id="todayPagination"></ul>
                        </nav>
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Expiring in 7 Days ───────────────────────────── --}}
    <div class="col-xl-5">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white border-0 pt-3 d-flex align-items-center justify-content-between">
                <h6 class="fw-bold mb-0">
                    <i class="fas fa-hourglass-half text-warning me-2"></i>
                    {{ trans('crm.expiring_soon_7') }}
                    @if($expiringSoonList->count() > 0)
                        <span class="badge bg-warning text-dark ms-1">{{ $expiringSoonList->count() }}</span>
                    @endif
                </h6>
                <a href="{{ route('crm.members.index', ['segment' => 'expiring7']) }}"
                   class="btn btn-outline-warning btn-sm">
                    {{ trans('crm.view_all') }}
                </a>
            </div>
            <div class="card-body p-0">
                @if($expiringSoonList->isEmpty())
                    <div class="text-center py-5 text-muted">
                        <i class="fas fa-thumbs-up fa-3x mb-2 text-success"></i>
                        <p class="mb-0">{{ trans('crm.no_expiring_soon') }}</p>
                    </div>
                @else
                    <div id="expiringSoonContainer">
                        @foreach($expiringSoonList as $sub)
                        <div class="expiring-item list-group-item border-0 border-bottom py-3 px-3">
                            <div class="d-flex align-items-center justify-content-between">
                                <div class="flex-grow-1">
                                    <div class="fw-semibold">
                                        {{ $sub->member->full_name ?? '—' }}
                                    </div>
                                    <small class="text-muted">
                                        {{ is_array($sub->plan_name)
                                            ? ($sub->plan_name[app()->getLocale()] ?? $sub->plan_name['ar'] ?? '—')
                                            : ($sub->plan_name ?? '—') }}
                                    </small>
                                </div>
                                <div class="text-end">
                                    @php
                                        $daysLeft = (int) now()->startOfDay()->diffInDays(
                                            \Carbon\Carbon::parse($sub->end_date), false
                                        );
                                    @endphp
                                    <span class="badge {{ $daysLeft <= 2 ? 'bg-danger' : 'bg-warning text-dark' }}">
                                        {{ $daysLeft <= 0
                                            ? trans('crm.today')
                                            : trans('crm.days_left', ['count' => $daysLeft]) }}
                                    </span>
                                    <div class="mt-1">
                                        @if($sub->member?->whatsapp)
                                        <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $sub->member->whatsapp) }}?text={{ urlencode(trans('crm.whatsapp_renewal_msg', ['name' => $sub->member->first_name ?? ''])) }}"
                                           target="_blank"
                                           class="btn btn-xs btn-success py-0 px-2"
                                           style="font-size: 11px;">
                                            <i class="fab fa-whatsapp me-1"></i>{{ trans('crm.reminder') }}
                                        </a>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>

                    {{-- ✅ Pagination controls للاشتراكات --}}
                    <div class="d-flex align-items-center justify-content-between px-3 py-2 border-top bg-light">
                        <small class="text-muted" id="expiringPagInfo"></small>
                        <nav>
                            <ul class="pagination pagination-sm mb-0" id="expiringPagination"></ul>
                        </nav>
                    </div>
                @endif
            </div>
        </div>
    </div>

</div>{{-- end bottom row --}}

</div>{{-- end container --}}

<style>
    .segment-card {
        transition: transform 0.2s, box-shadow 0.2s;
        border-radius: 12px;
        cursor: pointer;
    }
    .segment-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 6px 20px rgba(0,0,0,0.1) !important;
    }
</style>

<script>
// ── Pagination Helper ──────────────────────────────────────────
function initTablePagination(tableId, paginationId, infoId, perPage) {
    const table    = document.getElementById(tableId);
    if (!table) return;

    const tbody    = table.querySelector('tbody');
    const rows     = Array.from(tbody.querySelectorAll('tr'));
    const totalRows = rows.length;
    let currentPage = 1;
    const totalPages = Math.ceil(totalRows / perPage);

    function render(page) {
        currentPage = page;
        const start = (page - 1) * perPage;
        const end   = start + perPage;

        rows.forEach((row, i) => {
            row.style.display = (i >= start && i < end) ? '' : 'none';
        });

        // Info text
        const infoEl = document.getElementById(infoId);
        if (infoEl) {
            const showing = Math.min(end, totalRows);
            infoEl.textContent = `عرض ${start + 1}–${showing} من ${totalRows}`;
        }

        // Pagination buttons
        const pagEl = document.getElementById(paginationId);
        if (!pagEl) return;
        pagEl.innerHTML = '';

        // زر السابق
        const prevLi = document.createElement('li');
        prevLi.className = `page-item ${page === 1 ? 'disabled' : ''}`;
        prevLi.innerHTML = `<a class="page-link" href="#">‹</a>`;
        prevLi.addEventListener('click', e => { e.preventDefault(); if (page > 1) render(page - 1); });
        pagEl.appendChild(prevLi);

        // أرقام الصفحات
        for (let p = 1; p <= totalPages; p++) {
            // إظهار الصفحات القريبة فقط
            if (totalPages > 5 && Math.abs(p - page) > 2 && p !== 1 && p !== totalPages) {
                if (p === 2 || p === totalPages - 1) {
                    const dots = document.createElement('li');
                    dots.className = 'page-item disabled';
                    dots.innerHTML = '<span class="page-link">…</span>';
                    pagEl.appendChild(dots);
                }
                continue;
            }

            const li = document.createElement('li');
            li.className = `page-item ${p === page ? 'active' : ''}`;
            li.innerHTML = `<a class="page-link" href="#">${p}</a>`;
            li.addEventListener('click', e => { e.preventDefault(); render(p); });
            pagEl.appendChild(li);
        }

        // زر التالي
        const nextLi = document.createElement('li');
        nextLi.className = `page-item ${page === totalPages ? 'disabled' : ''}`;
        nextLi.innerHTML = `<a class="page-link" href="#">›</a>`;
        nextLi.addEventListener('click', e => { e.preventDefault(); if (page < totalPages) render(page + 1); });
        pagEl.appendChild(nextLi);
    }

    if (totalPages > 1) render(1);
}

// ── List Pagination (expiring soon) ───────────────────────────
function initListPagination(containerId, paginationId, infoId, perPage) {
    const container = document.getElementById(containerId);
    if (!container) return;

    const items      = Array.from(container.querySelectorAll('.expiring-item'));
    const totalItems = items.length;
    let currentPage  = 1;
    const totalPages = Math.ceil(totalItems / perPage);

    function render(page) {
        currentPage = page;
        const start = (page - 1) * perPage;
        const end   = start + perPage;

        items.forEach((item, i) => {
            item.style.display = (i >= start && i < end) ? '' : 'none';
        });

        const infoEl = document.getElementById(infoId);
        if (infoEl) {
            const showing = Math.min(end, totalItems);
            infoEl.textContent = `عرض ${start + 1}–${showing} من ${totalItems}`;
        }

        const pagEl = document.getElementById(paginationId);
        if (!pagEl) return;
        pagEl.innerHTML = '';

        const prevLi = document.createElement('li');
        prevLi.className = `page-item ${page === 1 ? 'disabled' : ''}`;
        prevLi.innerHTML = `<a class="page-link" href="#">‹</a>`;
        prevLi.addEventListener('click', e => { e.preventDefault(); if (page > 1) render(page - 1); });
        pagEl.appendChild(prevLi);

        for (let p = 1; p <= totalPages; p++) {
            if (totalPages > 5 && Math.abs(p - page) > 2 && p !== 1 && p !== totalPages) {
                if (p === 2 || p === totalPages - 1) {
                    const dots = document.createElement('li');
                    dots.className = 'page-item disabled';
                    dots.innerHTML = '<span class="page-link">…</span>';
                    pagEl.appendChild(dots);
                }
                continue;
            }
            const li = document.createElement('li');
            li.className = `page-item ${p === page ? 'active' : ''}`;
            li.innerHTML = `<a class="page-link" href="#">${p}</a>`;
            li.addEventListener('click', e => { e.preventDefault(); render(p); });
            pagEl.appendChild(li);
        }

        const nextLi = document.createElement('li');
        nextLi.className = `page-item ${page === totalPages ? 'disabled' : ''}`;
        nextLi.innerHTML = `<a class="page-link" href="#">›</a>`;
        nextLi.addEventListener('click', e => { e.preventDefault(); if (page < totalPages) render(page + 1); });
        pagEl.appendChild(nextLi);
    }

    if (totalPages > 1) render(1);
}

// ── تشغيل Pagination ──────────────────────────────────────────
document.addEventListener('DOMContentLoaded', function () {
    initTablePagination('todayFollowupsTable', 'todayPagination', 'todayPagInfo', 10);
    initListPagination('expiringSoonContainer', 'expiringPagination', 'expiringPagInfo', 10);
});

// ── Auto Refresh ──────────────────────────────────────────────
setTimeout(() => window.location.reload(), 5 * 60 * 1000);
</script>
@endsection
