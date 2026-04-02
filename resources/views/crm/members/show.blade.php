{{-- resources/views/crm/members/show.blade.php --}}
@extends('layouts.master_table')

@section('title', trans('crm.member_profile_title') . ' — ' . $member->full_name)

@section('css')
<style>
    /* ── Avatar ── */
    .mp-avatar {
        width: 80px; height: 80px;
        border-radius: 50%;
        object-fit: cover;
        border: 3px solid #dee2e6;
        flex-shrink: 0;
    }
    .mp-avatar-placeholder {
        width: 80px; height: 80px;
        border-radius: 50%;
        background: rgba(13,110,253,0.1);
        border: 3px solid rgba(13,110,253,0.2);
        display: flex; align-items: center; justify-content: center;
        font-size: 2rem; font-weight: 700;
        color: #0d6efd; flex-shrink: 0;
    }

    /* ── Stat Cards ── */
    .mp-stat {
        border-radius: 12px;
        padding: 16px 20px;
        background: #f8f9fa;
        border: 1px solid #e9ecef;
    }
    .mp-stat .stat-value {
        font-size: 1.6rem;
        font-weight: 700;
        line-height: 1;
    }
    .mp-stat .stat-label {
        font-size: 0.78rem;
        color: #6c757d;
        margin-top: 4px;
    }

    /* ── Section Title ── */
    .mp-section-title {
        font-size: 0.9rem;
        font-weight: 700;
        color: #495057;
        border-bottom: 2px solid #e9ecef;
        padding-bottom: 8px;
        margin-bottom: 16px;
    }

    /* ── Timeline (followups) ── */
    .mp-timeline { position: relative; padding-right: 20px; }
    .mp-timeline::before {
        content: '';
        position: absolute;
        right: 6px; top: 0; bottom: 0;
        width: 2px;
        background: #e9ecef;
    }
    .mp-tl-item { position: relative; margin-bottom: 18px; }
    .mp-tl-dot {
        position: absolute;
        right: -20px; top: 4px;
        width: 12px; height: 12px;
        border-radius: 50%;
        border: 2px solid #fff;
        box-shadow: 0 0 0 2px #dee2e6;
    }

    /* ── Mini table ── */
    .mp-mini-table { width: 100%; border-collapse: collapse; font-size: 0.83rem; }
    .mp-mini-table thead th {
        background: #f8f9fa;
        border-bottom: 2px solid #e9ecef;
        padding: 7px 10px;
        font-weight: 600;
        white-space: nowrap;
    }
    .mp-mini-table tbody td {
        border-bottom: 1px solid #f0f0f0;
        padding: 7px 10px;
        vertical-align: middle;
        white-space: nowrap;
    }

    /* ── Info list ── */
    .mp-info-list { list-style: none; padding: 0; margin: 0; }
    .mp-info-list li {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        padding: 7px 0;
        border-bottom: 1px solid #f0f0f0;
        font-size: 0.85rem;
        gap: 12px;
    }
    .mp-info-list li:last-child { border-bottom: none; }
    .mp-info-list .info-key { color: #6c757d; flex-shrink: 0; }
    .mp-info-list .info-val { font-weight: 500; text-align: end; }

    /* ── Progress bar (sessions) ── */
    .sessions-bar { height: 8px; border-radius: 4px; }

    /* ── Toast ── */
    #mp-toast-wrap {
        position: fixed; bottom: 24px; left: 24px;
        z-index: 9999; display: flex;
        flex-direction: column; gap: 8px;
        pointer-events: none;
    }
    .mp-toast {
        background: #323232; color: #fff;
        padding: 10px 18px; border-radius: 8px;
        font-size: 0.87rem; opacity: 0;
        transform: translateY(10px);
        transition: all .25s ease;
        max-width: 320px;
    }
    .mp-toast.show { opacity: 1; transform: translateY(0); }
    .mp-toast.success { border-right: 4px solid #28a745; }
    .mp-toast.error   { border-right: 4px solid #dc3545; }

    /* ── Timeline new item animation ── */
    .mp-tl-new {
        animation: mpFadeIn .4s ease forwards;
    }
    @keyframes mpFadeIn {
        from { opacity:0; transform:translateY(-8px); }
        to   { opacity:1; transform:translateY(0); }
    }

    /* ── Empty state hidden ── */
    #mpEmptyState.d-none { display: none !important; }
</style>
@endsection

@section('content')
<div class="container-fluid py-3" dir="rtl">

    {{-- Breadcrumb --}}
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb small mb-0">
            <li class="breadcrumb-item">
                <a href="{{ route('crm.dashboard') }}" class="text-decoration-none">CRM</a>
            </li>
            <li class="breadcrumb-item">
                <a href="{{ route('crm.members.index') }}" class="text-decoration-none">{{ trans('crm.smart_segments_title') }}</a>
            </li>
            <li class="breadcrumb-item active">{{ $member->full_name }}</li>
        </ol>
    </nav>

    {{-- ══ ROW 1 — Header Card ══ --}}
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body">
            <div class="d-flex align-items-center gap-3 flex-wrap">

                {{-- Avatar --}}
                @if($member->photo)
                    <img src="{{ url($member->photo) }}" class="mp-avatar" alt="">
                @else
                    <div class="mp-avatar-placeholder">
                        {{ mb_strtoupper(mb_substr($member->first_name ?? '?', 0, 1)) }}
                    </div>
                @endif

                {{-- Basic Info --}}
                <div class="flex-grow-1">
                    <div class="d-flex align-items-center gap-2 flex-wrap mb-1">
                        <h5 class="fw-bold mb-0">{{ $member->full_name }}</h5>
                        @php
                            $statusMap = [
                                'active'   => ['success',   trans('crm.active_badge')],
                                'inactive' => ['secondary', trans('crm.status_inactive')],
                                'frozen'   => ['info',      trans('crm.frozen_badge')],
                                'expired'  => ['danger',    trans('crm.expired_badge')],
                            ];
                            [$sc, $sl] = $statusMap[$member->status] ?? ['secondary', $member->status];
                        @endphp
                        <span class="badge bg-{{ $sc }}">{{ $sl }}</span>
                    </div>

                    <div class="d-flex gap-3 flex-wrap text-muted small">
                        <span><i class="fas fa-hashtag me-1"></i>{{ $member->member_code }}</span>
                        @if($member->phone)
                            <span><i class="fas fa-phone me-1"></i>{{ $member->phone }}</span>
                        @endif
                        @if($member->branch)
                            <span>
                                <i class="fas fa-map-marker-alt me-1"></i>
                                {{ is_array($member->branch->name)
                                    ? ($member->branch->name[app()->getLocale()] ?? $member->branch->name['ar'] ?? '')
                                    : $member->branch->name }}
                            </span>
                        @endif
                        @if($member->join_date)
                            <span><i class="fas fa-calendar me-1"></i>{{ trans('crm.joined_date', ['date' => $member->join_date->format('d/m/Y')]) }}</span>
                        @endif
                    </div>
                </div>

                {{-- Actions --}}
                <div class="d-flex gap-2 flex-wrap">
                    @if($member->whatsapp || $member->phone)
                        @php
                            $waNum = preg_replace('/[^0-9]/', '', $member->whatsapp ?: $member->phone);
                            $waMsg = urlencode(trans('crm.wa_text_default', ['name' => $member->first_name ?? '']));
                        @endphp
                        <a href="https://wa.me/{{ $waNum }}?text={{ $waMsg }}"
                           target="_blank"
                           class="btn btn-success btn-sm">
                            <i class="fab fa-whatsapp me-1"></i> {{ trans('crm.whatsapp_btn') }}
                        </a>
                    @endif

                    @can('crm_followups_create')
                    <button type="button"
                            class="btn btn-warning btn-sm"
                            onclick="mpOpenFollowup()">
                        <i class="fas fa-plus me-1"></i> {{ trans('crm.add_followup_modal_title') }}
                    </button>
                    @endcan

                    <a href="{{ route('crm.members.index') }}" class="btn btn-outline-secondary btn-sm">
                        <i class="fas fa-arrow-right me-1"></i> {{ trans('crm.back_btn') }}
                    </a>
                </div>
            </div>
        </div>
    </div>

    {{-- ══ ROW 2 — Stats Bar ══ --}}
    <div class="row g-3 mb-3">
        <div class="col-6 col-md-3">
            <div class="mp-stat h-100">
                <div class="stat-value ">{{ $attendanceStats->total_days }}</div>
                <div class="stat-label">{{ trans('crm.total_attendance_days') }}</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="mp-stat h-100">
                <div class="stat-value text-success">{{ $attendanceStats->this_month }}</div>
                <div class="stat-label">{{ trans('crm.this_month_attendance') }}</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="mp-stat h-100">
                <div class="stat-value text-info">{{ $attendanceStats->avg_per_week }}</div>
                <div class="stat-label">{{ trans('crm.avg_visits_week') }}</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="mp-stat h-100">
                <div class="stat-value {{ $financialStats->unpaid_count > 0 ? 'text-danger' : 'text-secondary' }}">
                    {{ $financialStats->unpaid_count > 0 ? number_format($financialStats->unpaid_total, 0) : '0' }}
                </div>
                <div class="stat-label">
                    {{ $financialStats->unpaid_count > 0
                        ? trans('crm.pending_amount_x', ['count' => $financialStats->unpaid_count])
                        : trans('crm.no_pending_invoices') }}
                </div>
            </div>
        </div>
    </div>

    {{-- ══ ROW 3 — Subscription + Personal Info ══ --}}
    <div class="row g-3 mb-3">

        {{-- الاشتراك الحالي --}}
        <div class="col-md-8">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="mp-section-title">
                        <i class="fas fa-id-card me-2 text-primary"></i>{{ trans('crm.current_subscription') }}
                    </div>

                    @if($activeSub)
                        @php
                            $endDate  = \Carbon\Carbon::parse($activeSub->end_date);
                            $daysLeft = (int) now()->startOfDay()->diffInDays($endDate, false);
                            $sessUsed = ($activeSub->sessions_count ?? 0) - ($activeSub->sessions_remaining ?? 0);
                            $sessPct  = $activeSub->sessions_count > 0
                                ? round(($activeSub->sessions_remaining / $activeSub->sessions_count) * 100)
                                : 0;
                        @endphp
                        <div class="row g-3">
                            <div class="col-sm-6">
                                <ul class="mp-info-list">
                                    <li>
                                        <span class="info-key">الباقة</span>
                                        <span class="info-val">{{ $activeSub->plan_name_display }}</span>
                                    </li>
                                    <li>
                                        <span class="info-key">تاريخ البداية</span>
                                        <span class="info-val">{{ \Carbon\Carbon::parse($activeSub->start_date)->format('d/m/Y') }}</span>
                                    </li>
                                    <li>
                                        <span class="info-key">تاريخ الانتهاء</span>
                                        <span class="info-val">
                                            {{ $endDate->format('d/m/Y') }}
                                            <span class="badge ms-1 {{ $daysLeft <= 0 ? 'bg-danger' : ($daysLeft <= 7 ? 'bg-warning text-dark' : 'bg-success') }}">
                                                {{ $daysLeft <= 0 ? trans('crm.ended') : trans('crm.after_x_days', ['count' => $daysLeft]) }}
                                            </span>
                                        </span>
                                    </li>
                                    <li>
                                        <span class="info-key">سعر الباقة</span>
                                        <span class="info-val">{{ number_format($activeSub->price_plan ?? 0, 2) }}</span>
                                    </li>
                                    @if(($activeSub->total_discount ?? 0) > 0)
                                        <li>
                                            <span class="info-key">الخصم</span>
                                            <span class="info-val text-success">- {{ number_format($activeSub->total_discount, 2) }}</span>
                                        </li>
                                    @endif
                                    <li>
                                        <span class="info-key">الإجمالي</span>
                                        <span class="info-val fw-bold text-primary">{{ number_format($activeSub->total_amount ?? 0, 2) }}</span>
                                    </li>
                                </ul>
                            </div>
                            <div class="col-sm-6">
                                @if($activeSub->sessions_count > 0)
                                    <div class="mb-3">
                                        <div class="d-flex justify-content-between small mb-1">
                                            <span class="text-muted">{{ trans('crm.remaining_sessions') }}</span>
                                            <span class="fw-semibold">
                                                {{ $activeSub->sessions_remaining ?? 0 }} / {{ $activeSub->sessions_count }}
                                            </span>
                                        </div>
                                        <div class="progress sessions-bar">
                                            <div class="progress-bar {{ $sessPct < 25 ? 'bg-danger' : ($sessPct < 50 ? 'bg-warning' : 'bg-success') }}"
                                                 style="width: {{ $sessPct }}%"></div>
                                        </div>
                                        <div class="text-muted small mt-1">{{ trans('crm.sessions_used', ['count' => $sessUsed]) }}</div>
                                    </div>
                                @endif

                                @if($ptAddon)
                                    <div class="alert alert-light border-start border-warning border-3 py-2 px-3 mb-0">
                                        <div class="fw-semibold small mb-1">
                                            <i class="fas fa-dumbbell me-1 text-warning"></i>{{ trans('crm.personal_trainer') }}
                                        </div>
                                        <ul class="mp-info-list" style="font-size:0.8rem">
                                            <li>
                                                <span class="info-key">المدرب</span>
                                                <span class="info-val">{{ $ptAddon->trainer_name ?? '—' }}</span>
                                            </li>
                                            <li>
                                                <span class="info-key">الجلسات المتبقية</span>
                                                <span class="info-val">{{ $ptAddon->sessions_remaining ?? 0 }} / {{ $ptAddon->sessions_count ?? 0 }}</span>
                                            </li>
                                            <li>
                                                <span class="info-key">{{ trans('crm.session_price') }}</span>
                                                <span class="info-val">{{ number_format($ptAddon->session_price ?? 0, 2) }}</span>
                                            </li>
                                        </ul>
                                    </div>
                                @endif
                            </div>
                        </div>
                    @else
                        <div class="text-center py-4">
                            <i class="fas fa-times-circle fa-2x text-danger mb-2"></i>
                            <p class="text-muted mb-0">{{ trans('crm.no_active_subscription') }}</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- البيانات الشخصية --}}
        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="mp-section-title">
                        <i class="fas fa-user me-2 text-secondary"></i>{{ trans('crm.personal_info') }}
                    </div>
                    <ul class="mp-info-list">
                        @if($member->gender)
                            <li>
                                <span class="info-key">{{ trans('crm.gender') }}</span>
                                <span class="info-val">{{ $member->gender === 'male' ? trans('crm.gender_male') : trans('crm.gender_female') }}</span>
                            </li>
                        @endif
                        @if($member->birth_date)
                            <li>
                                <span class="info-key">{{ trans('crm.birth_date') }}</span>
                                <span class="info-val">
                                    {{ $member->birth_date->format('d/m/Y') }}
                                    <small class="text-muted">({{ trans('crm.age_years', ['age' => $member->birth_date->age]) }})</small>
                                </span>
                            </li>
                        @endif
                        @if($member->phone2)
                            <li>
                                <span class="info-key">{{ trans('crm.phone_extra') }}</span>
                                <span class="info-val">{{ $member->phone2 }}</span>
                            </li>
                        @endif
                        @if($member->email)
                            <li>
                                <span class="info-key">{{ trans('crm.email') }}</span>
                                <span class="info-val" style="word-break:break-all">{{ $member->email }}</span>
                            </li>
                        @endif
                        @if($member->height)
                            <li>
                                <span class="info-key">{{ trans('crm.height_weight') }}</span>
                                <span class="info-val">{{ trans('crm.height_weight_val', ['h' => $member->height, 'w' => $member->weight]) }}</span>
                            </li>
                        @endif
                        @if($member->medical_conditions)
                            <li>
                                <span class="info-key">{{ trans('crm.medical_conditions') }}</span>
                                <span class="info-val text-danger">{{ $member->medical_conditions }}</span>
                            </li>
                        @endif
                            <li>
                                <span class="info-key">{{ trans('crm.total_paid') }}</span>
                            <span class="info-val text-success fw-bold">
                                {{ number_format($financialStats->total_paid, 2) }}
                            </span>
                        </li>
                        @if($attendanceStats->last_visit)
                            <li>
                                <span class="info-key">{{ trans('crm.th_last_visit') }}</span>
                                <span class="info-val">
                                    {{ \Carbon\Carbon::parse($attendanceStats->last_visit)->format('d/m/Y') }}
                                    <small class="text-muted d-block">{{ \Carbon\Carbon::parse($attendanceStats->last_visit)->diffForHumans() }}</small>
                                </span>
                            </li>
                        @endif
                    </ul>
                </div>
            </div>
        </div>

    </div>

    {{-- ══ ROW 4 — Subscription History + Attendance ══ --}}
    <div class="row g-3 mb-3">

        {{-- تاريخ الاشتراكات --}}
        <div class="col-md-7">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="mp-section-title">
                        <i class="fas fa-history me-2 text-info"></i>{{ trans('crm.subscription_history') }}
                        <span class="badge bg-light text-dark fw-normal ms-1">{{ $allSubscriptions->count() }}</span>
                    </div>

                    @if($allSubscriptions->isEmpty())
                        <p class="text-muted text-center py-3 mb-0">{{ trans('crm.no_subscriptions') }}</p>
                    @else
                        <div class="table-responsive">
                            <table class="mp-mini-table">
                                <thead>
                                    <tr>
                                    <th>{{ trans('crm.plan') }}</th>
                                        <th>{{ trans('crm.start_date') }}</th>
                                        <th>{{ trans('crm.end_date') }}</th>
                                        <th>{{ trans('crm.sessions') }}</th>
                                        <th>{{ trans('crm.total') }}</th>
                                        <th>{{ trans('crm.type') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($allSubscriptions as $sub)
                                        @php
                                        $subStatusMap = [
                                                'active'    => ['success',   trans('crm.active_badge')],
                                                'expired'   => ['danger',    trans('crm.expired_badge')],
                                                'cancelled' => ['secondary', trans('crm.cancelled_badge')],
                                                'frozen'    => ['info',      trans('crm.frozen_badge')],
                                            ];
                                            [$ssc, $ssl] = $subStatusMap[$sub->status] ?? ['secondary', $sub->status];
                                        @endphp
                                        <tr>
                                            <td>
                                                <div style="max-width:140px" class="text-truncate" title="{{ $sub->plan_name_display }}">
                                                    {{ $sub->plan_name_display }}
                                                </div>
                                            </td>
                                            <td>{{ \Carbon\Carbon::parse($sub->start_date)->format('d/m/Y') }}</td>
                                            <td>{{ \Carbon\Carbon::parse($sub->end_date)->format('d/m/Y') }}</td>
                                            <td>
                                                @if($sub->sessions_count > 0)
                                                    {{ $sub->sessions_remaining }}/{{ $sub->sessions_count }}
                                                @else
                                                    <span class="text-muted">—</span>
                                                @endif
                                            </td>
                                            <td>{{ number_format($sub->total_amount ?? 0, 0) }}</td>
                                            <td><span class="badge bg-{{ $ssc }}">{{ $ssl }}</span></td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif

                    @if($unpaidInvoices->isNotEmpty())
                        <div class="mt-3">
                            <div class="mp-section-title text-danger">
                                <i class="fas fa-exclamation-triangle me-2"></i>{{ trans('crm.pending_invoices_title') }}
                            </div>
                            <div class="table-responsive">
                                <table class="mp-mini-table">
                                    <thead>
                                        <tr>
                                            <th>{{ trans('crm.invoice_no') }}</th>
                                            <th>{{ trans('crm.amount') }}</th>
                                            <th>{{ trans('crm.date') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($unpaidInvoices as $inv)
                                            <tr>
                                                <td>{{ $inv->id }}</td>
                                                <td class="fw-bold text-danger">{{ number_format($inv->total, 2) }}</td>
                                                <td>{{ \Carbon\Carbon::parse($inv->created_at)->format('d/m/Y') }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- آخر 30 حضور --}}
        <div class="col-md-5">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="mp-section-title">
                        <i class="fas fa-calendar-check me-2 text-success"></i>{{ trans('crm.recent_visits') }}
                        <span class="badge bg-light text-dark fw-normal ms-1">{{ $recentAttendances->count() }}</span>
                    </div>

                    @if($recentAttendances->isEmpty())
                        <p class="text-muted text-center py-3 mb-0">{{ trans('crm.no_visits_recorded') }}</p>
                    @else
                        <div style="max-height: 340px; overflow-y: auto;">
                            <table class="mp-mini-table">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>{{ trans('crm.th_date') }}</th>
                                        <th>{{ trans('crm.th_since') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($recentAttendances as $i => $att)
                                        <tr>
                                            <td class="text-muted">{{ $i + 1 }}</td>
                                            <td>{{ \Carbon\Carbon::parse($att->attendance_date)->format('d/m/Y') }}</td>
                                            <td class="text-muted">{{ \Carbon\Carbon::parse($att->attendance_date)->diffForHumans() }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>

    </div>

    {{-- ══ ROW 5 — CRM Followups Timeline ══ --}}
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body">
            <div class="d-flex align-items-center justify-content-between mb-3">
                <div class="mp-section-title mb-0">
                    <i class="fas fa-comments me-2 text-warning"></i>{{ trans('crm.crm_followup_log') }}
                    <span class="badge bg-light text-dark fw-normal ms-1" id="mpFollowupCount">{{ $followups->count() }}</span>
                </div>
                @can('crm_followups_create')
                <button type="button" class="btn btn-warning btn-sm" onclick="mpOpenFollowup()">
                    <i class="fas fa-plus me-1"></i> {{ trans('crm.add_followup_modal_title') }}
                </button>
                @endcan
            </div>

            {{-- Empty state --}}
            <div id="mpEmptyState" class="{{ $followups->count() > 0 ? 'd-none' : '' }} text-center py-4">
                <i class="fas fa-clipboard-list fa-2x text-muted mb-2"></i>
                <p class="text-muted mb-0">{{ trans('crm.no_followups_for_member') }}</p>
            </div>

            {{-- Timeline --}}
            <div class="mp-timeline" id="mpTimeline">
                @foreach($followups as $fu)
                    @php
                        $dotColor = match($fu->priority) {
                            'high'   => '#dc3545',
                            'medium' => '#ffc107',
                            default  => '#6c757d',
                        };
                        $isOverdue = $fu->status === 'pending'
                            && $fu->next_action_at
                            && $fu->next_action_at->lt(now());
                        $statusBadge = match($fu->status) {
                            'done'      => 'success',
                            'cancelled' => 'secondary',
                            default     => $isOverdue ? 'danger' : 'primary',
                        };
                        $statusLabel = ($fu->status === 'pending' && $isOverdue) ? trans('crm.overdue') : $fu->status_label;
                    @endphp
                    <div class="mp-tl-item">
                        <div class="mp-tl-dot" style="background: {{ $dotColor }}"></div>
                        <div class="bg-light rounded p-3" style="border-right: 3px solid {{ $dotColor }}">
                            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-2">
                                <div class="d-flex gap-2 flex-wrap">
                                    <span class="badge bg-{{ $fu->type_badge_class }}">{{ $fu->type_label }}</span>
                                    <span class="badge bg-{{ $fu->priority_badge_class }}">{{ $fu->priority_label }}</span>
                                    <span class="badge bg-{{ $statusBadge }}">{{ $statusLabel }}</span>
                                </div>
                                <small class="text-muted">{{ $fu->created_at->format('d/m/Y H:i') }}</small>
                            </div>

                            @if($fu->notes)
                                <p class="mb-1 small">{{ $fu->notes }}</p>
                            @endif

                            <div class="d-flex gap-3 flex-wrap" style="font-size:0.8rem; color:#6c757d">
                                @if($fu->next_action_at)
                                    <span>
                                        <i class="fas fa-clock me-1"></i>
                                        {{ trans('crm.followup_date_label') }}: {{ $fu->next_action_at->format('d/m/Y H:i') }}
                                    </span>
                                @endif
                                @if($fu->result)
                                    <span>
                                        <i class="fas fa-check me-1 text-success"></i>
                                        {{ $fu->result }}
                                    </span>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

        </div>
    </div>

</div>

{{-- Toast --}}
<div id="mp-toast-wrap"></div>

{{-- ══ Followup Modal ══ --}}
<div class="modal fade" id="mpFollowupDlg" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content" dir="rtl">
            <div class="modal-header border-0 pb-0">
                <h6 class="modal-title fw-bold">
                    <i class="fas fa-comments me-2 text-warning"></i>
                    {{ trans('crm.add_followup_modal_title') }} — {{ $member->full_name }}
                </h6>
                <button type="button" class="btn-close ms-0 me-auto" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">
                {{-- ملاحظة: لا form هنا — كل شيء عبر AJAX --}}
                <div class="row g-3">

                    <div class="col-md-6">
                        <label class="form-label fw-semibold small">{{ trans('crm.followup_type') }}</label>
                        <select id="mp_type" class="form-select form-select-sm">
                            <option value="renewal">{{ trans('crm.type_renewal') }}</option>
                            <option value="inactive">{{ trans('crm.type_inactive') }}</option>
                            <option value="freeze">{{ trans('crm.type_freeze') }}</option>
                            <option value="debt">{{ trans('crm.type_debt') }}</option>
                            <option value="general" selected>{{ trans('crm.type_general') }}</option>
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label fw-semibold small">{{ trans('crm.priority') }}</label>
                        <select id="mp_priority" class="form-select form-select-sm">
                            <option value="high">{{ trans('crm.priority_high') }}</option>
                            <option value="medium" selected>{{ trans('crm.priority_medium') }}</option>
                            <option value="low">{{ trans('crm.priority_low') }}</option>
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label fw-semibold small">{{ trans('crm.type') }}</label>
                        <select id="mp_status" class="form-select form-select-sm">
                            <option value="pending" selected>{{ trans('crm.status_pending') }}</option>
                            <option value="done">{{ trans('crm.status_done') }}</option>
                        </select>
                    </div>

                    <div class="col-12">
                        <label class="form-label fw-semibold small">{{ trans('crm.followup_date_label') }}</label>
                        <input type="datetime-local"
                               id="mp_next_action_at"
                               class="form-control form-control-sm">
                    </div>

                    <div class="col-12">
                        <label class="form-label fw-semibold small">{{ trans('crm.notes') }}</label>
                        <textarea id="mp_notes"
                                  class="form-control form-control-sm"
                                  rows="3"
                                  placeholder="{{ trans('crm.notes_ph') }}"></textarea>
                    </div>

                </div>
            </div>

            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">{{ trans('crm.cancel') }}</button>
                <button type="button" class="btn btn-warning btn-sm" id="mpSaveBtn" onclick="mpSaveFollowup()">
                    <i class="fas fa-save me-1"></i> {{ trans('crm.save') }}
                </button>
            </div>
        </div>
    </div>
</div>

<script>
(function () {
    const CSRF        = document.querySelector('meta[name="csrf-token"]')?.content || '';
    const STORE_URL   = '{{ route('crm.followups.store') }}';
    const MEMBER_ID   = {{ $member->id }};
    const BRANCH_ID   = {{ $member->branch_id ?? 'null' }};
    const MEMBER_NAME = '{{ addslashes($member->full_name) }}';

    // ── Toast ───────────────────────────────────────────
    function toast(msg, type) {
        const wrap = document.getElementById('mp-toast-wrap');
        const el   = document.createElement('div');
        el.className = 'mp-toast ' + (type || 'success');
        el.textContent = msg;
        wrap.appendChild(el);
        requestAnimationFrame(() => el.classList.add('show'));
        setTimeout(() => {
            el.classList.remove('show');
            setTimeout(() => el.remove(), 300);
        }, 3000);
    }

    // ── Open Modal ───────────────────────────────────────
    window.mpOpenFollowup = function () {
        const d   = new Date();
        d.setDate(d.getDate() + 1);
        const pad = n => String(n).padStart(2, '0');
        document.getElementById('mp_next_action_at').value =
            `${d.getFullYear()}-${pad(d.getMonth()+1)}-${pad(d.getDate())}T10:00`;

        document.getElementById('mp_type').value     = 'prospect'; // ✅ افتراضي للمحتملين
        document.getElementById('mp_priority').value = 'medium';
        document.getElementById('mp_status').value   = 'pending';
        document.getElementById('mp_notes').value    = '';

        const btn = document.getElementById('mpSaveBtn');
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-save me-1"></i> حفظ';

        new bootstrap.Modal(document.getElementById('mpFollowupDlg')).show();
    };

    // ── Save via AJAX ────────────────────────────────────
    window.mpSaveFollowup = function () {
        const btn      = document.getElementById('mpSaveBtn');
        const type     = document.getElementById('mp_type').value;
        const priority = document.getElementById('mp_priority').value;
        const status   = document.getElementById('mp_status').value;
        const nextAt   = document.getElementById('mp_next_action_at').value;
        const notes    = document.getElementById('mp_notes').value.trim();

        if (!type || !priority) {
            toast('يرجى تعبئة الحقول المطلوبة', 'error');
            return;
        }

        // ✅ إصلاح branch_id: قراءة من JS constant مباشرة وليس FormData
        // لأنه قد يكون disabled فلا يُرسل مع الفورم
        const branchId = BRANCH_ID;

        if (!branchId) {
            toast('تعذّر تحديد الفرع، يرجى تحديث الصفحة', 'error');
            return;
        }

        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-circle-notch fa-spin me-1"></i> جاري الحفظ...';

        fetch(STORE_URL, {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': CSRF,
                'Content-Type': 'application/json',
                'Accept': 'application/json',
            },
            body: JSON.stringify({
                member_id:      MEMBER_ID,
                branch_id:      branchId,   // ✅ مضمون دائماً
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
            btn.innerHTML = '<i class="fas fa-save me-1"></i> حفظ';

            if (res && res.success) {
                toast('تم حفظ المتابعة بنجاح ✓', 'success');

                bootstrap.Modal.getInstance(document.getElementById('mpFollowupDlg'))?.hide();

                mpPrependTimeline(res.followup || {
                    type,
                    priority,
                    status,
                    next_action_at: nextAt,
                    notes,
                    created_at_human: 'الآن'
                });

            } else {
                let errMsg = 'تعذّر الحفظ، حاول مجدداً';
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
            btn.innerHTML = '<i class="fas fa-save me-1"></i> حفظ';
            toast('تعذّر الاتصال بالخادم', 'error');
        });
    };

    // ── Prepend new item to timeline ─────────────────────
    window.mpPrependTimeline = function (fu) {
        const typeMap = {
            renewal:  ['primary',   'تجديد اشتراك'],
            freeze:   ['info',      'إلغاء تجميد'],
            inactive: ['warning',   'عضو غير نشط'],
            debt:     ['danger',    'تحصيل مديونية'],
            general:  ['secondary', 'متابعة عامة'],
            prospect: ['success',   'عميل محتمل'],  // ✅ جديد
        };
        const prioMap = {
            high:   ['danger',    'عالية',   '#dc3545'],
            medium: ['warning',   'متوسطة',  '#ffc107'],
            low:    ['secondary', 'منخفضة',  '#6c757d'],
        };
        const stMap = {
            pending:   ['primary',   'قيد المتابعة'],
            done:      ['success',   'منتهية'],
            cancelled: ['secondary', 'ملغاة'],
        };

        const [typeCls, typeLabel]                 = typeMap[fu.type]     || ['secondary', fu.type];
        const [prioCls, prioLabel, dotColor]       = prioMap[fu.priority] || ['secondary', fu.priority, '#6c757d'];
        const [stCls, stLabel]                     = stMap[fu.status]     || ['primary', fu.status];

        const nextAtFmt = fu.next_action_at
            ? (() => {
                const d = new Date(fu.next_action_at.replace('T', ' '));
                return !isNaN(d)
                    ? d.toLocaleDateString('ar-EG') + ' ' +
                      d.toLocaleTimeString('ar-EG', {hour:'2-digit', minute:'2-digit'})
                    : '';
              })()
            : '';

        const now = new Date().toLocaleDateString('ar-EG') + ' ' +
                    new Date().toLocaleTimeString('ar-EG', {hour:'2-digit', minute:'2-digit'});

        const html = `
            <div class="mp-tl-item mp-tl-new">
                <div class="mp-tl-dot" style="background:${dotColor}"></div>
                <div class="bg-light rounded p-3" style="border-right:3px solid ${dotColor}">
                    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-2">
                        <div class="d-flex gap-2 flex-wrap">
                            <span class="badge bg-${typeCls}">${typeLabel}</span>
                            <span class="badge bg-${prioCls}">${prioLabel}</span>
                            <span class="badge bg-${stCls}">${stLabel}</span>
                        </div>
                        <small class="text-muted">${now}</small>
                    </div>
                    ${fu.notes ? `<p class="mb-1 small">${fu.notes}</p>` : ''}
                    ${nextAtFmt ? `
                        <div class="d-flex gap-3 flex-wrap" style="font-size:0.8rem;color:#6c757d">
                            <span><i class="fas fa-clock me-1"></i>موعد المتابعة: ${nextAtFmt}</span>
                        </div>` : ''}
                </div>
            </div>`;

        const timeline   = document.getElementById('mpTimeline');
        const emptyState = document.getElementById('mpEmptyState');

        if (emptyState) emptyState.classList.add('d-none');

        timeline.insertAdjacentHTML('afterbegin', html);

        const badge = document.getElementById('mpFollowupCount');
        if (badge) badge.textContent = parseInt(badge.textContent || '0') + 1;
    };
})();
</script>

@endsection
