@extends('layouts.master_table')

@section('title', trans('users.title_show') . ' - ' . $user->name)

<style>
    .user-avatar {
        width: 100px;
        height: 100px;
        border-radius: 50%;
        background-color: #f8f9fa;
        color: #0d6efd;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 2.5rem;
        font-weight: bold;
        border: 4px solid #e9ecef;
        margin-bottom: 1rem;
    }
    .info-list {
        list-style: none;
        padding: 0;
        margin: 0;
    }
    .info-list li {
        display: flex;
        justify-content: space-between;
        padding: 12px 0;
        border-bottom: 1px solid #f0f0f0;
    }
    .info-list li:last-child {
        border-bottom: none;
    }
    .info-key {
        color: #6c757d;
        font-weight: 500;
    }
    .info-val {
        font-weight: 600;
        color: #343a40;
    }
</style>

@section('content')
<div class="row">

    {{-- Breadcrumb & Actions --}}
    <div class="col-12 mb-3 d-flex justify-content-between align-items-center">
        <h4 class="mb-0 fw-bold">{{ trans('users.title_show') }}</h4>
        <div>
            @can('users_edit')
            <a href="{{ route('users.edit', $user->id) }}" class="btn btn-warning shadow-sm btn-sm">
                <i class="fas fa-edit me-1"></i> {{ trans('users.edit') }}
            </a>
            @endcan
            <a href="{{ route('users.index') }}" class="btn btn-secondary shadow-sm btn-sm ms-2">
                <i class="fas fa-arrow-right me-1"></i> {{ trans('users.back') }}
            </a>
        </div>
    </div>

    {{-- Avatar / Summary Card --}}
    <div class="col-md-4 mb-4">
        <div class="card shadow-sm border-0 h-100 text-center pt-4">
            <div class="card-body">

                <div class="d-flex justify-content-center">
                    @if($user->employee && $user->employee->photo)
                        <img src="{{ url('images/' . $user->employee->photo) }}"
                             class="user-avatar" alt="User Image" style="object-fit: cover;">
                    @else
                        <div class="user-avatar">
                            {{ mb_strtoupper(mb_substr($user->name, 0, 1)) }}
                        </div>
                    @endif
                </div>

                <h5 class="fw-bold mb-1">{{ $user->name }}</h5>
                <p class="text-muted mb-3">{{ $user->email }}</p>

                {{-- ✅ الحالة — مقارنة صحيحة بـ 'enabled' --}}
                @if($user->Status === 'enabled')
                    <span class="badge bg-success px-3 py-2 rounded-pill">
                        {{ trans('users.status_active') }}
                    </span>
                @else
                    <span class="badge bg-danger px-3 py-2 rounded-pill">
                        {{ trans('users.status_inactive') }}
                    </span>
                @endif

                <hr class="my-4">

                <div class="text-start">
                    <h6 class="fw-bold">
                        <i class="fas fa-shield-alt text-primary me-2"></i> {{ trans('users.roles') }}
                    </h6>
                    <div class="mt-2 text-center text-md-start">
                        @forelse($user->roles as $role)
                            <span class="badge bg-primary mb-1">{{ $role->name }}</span>
                        @empty
                            <span class="text-muted small">لا يوجد صلاحيات</span>
                        @endforelse
                    </div>
                </div>

            </div>
        </div>
    </div>

    {{-- Detailed Info Card --}}
    <div class="col-md-8 mb-4">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-header bg-white border-bottom py-3">
                <h5 class="mb-0 fw-bold">
                    <i class="fas fa-info-circle text-info me-2"></i> {{ trans('users.account_info') }}
                </h5>
            </div>

            <div class="card-body">
                <div class="row">

                    <div class="col-md-6 mb-4">
                        <ul class="info-list">
                            <li>
                                <span class="info-key">{{ trans('users.name') }}</span>
                                <span class="info-val">{{ $user->name }}</span>
                            </li>
                            <li>
                                <span class="info-key">{{ trans('users.email') }}</span>
                                <span class="info-val" style="word-break: break-all;">{{ $user->email }}</span>
                            </li>
                            {{-- ✅ الحالة — مقارنة صحيحة --}}
                            <li>
                                <span class="info-key">{{ trans('users.status') }}</span>
                                <span class="info-val">
                                    @if($user->Status === 'enabled')
                                        <span class="badge bg-success">{{ trans('users.status_active') }}</span>
                                    @else
                                        <span class="badge bg-danger">{{ trans('users.status_inactive') }}</span>
                                    @endif
                                </span>
                            </li>
                        </ul>
                    </div>

                    <div class="col-md-6 mb-4">
                        <ul class="info-list">

                            {{-- ✅ الفرع --}}
                            <li>
                                <span class="info-key">{{ trans('users.branch') }}</span>
                                <span class="info-val">
                                    @php
                                        $branchName = '—';
                                        if ($user->branch) {
                                            $n = $user->branch->name;
                                            $branchName = is_array($n)
                                                ? ($n[app()->getLocale()] ?? $n['ar'] ?? $n['en'] ?? '—')
                                                : ($n ?? '—');
                                        }
                                    @endphp
                                    {{ $branchName }}
                                </span>
                            </li>

                            <li>
                                <span class="info-key">{{ trans('users.employee') }}</span>
                                <span class="info-val">
                                    {{ $user->employee ? $user->employee->full_name : trans('users.no_employee') }}
                                </span>
                            </li>
                            <li>
                                <span class="info-key">تاريخ الإضافة</span>
                                <span class="info-val">
                                    {{ $user->created_at ? $user->created_at->format('Y-m-d') : '—' }}
                                </span>
                            </li>
                        </ul>
                    </div>

                </div>

                {{-- فروع الموظف --}}
                @if($user->employee)
                <div class="mt-2">
                    <h6 class="fw-bold border-bottom pb-2 mb-3">
                        <i class="fas fa-sitemap text-success me-2"></i> {{ trans('users.branches_info') }}
                    </h6>
                    <div class="d-flex flex-wrap gap-2">
                        @forelse($user->employee->branches as $empBranch)
                            @php
                                $eName = $empBranch->name;
                                $eNameStr = is_array($eName)
                                    ? ($eName[app()->getLocale()] ?? $eName['ar'] ?? $eName['en'] ?? '-')
                                    : ($eName ?? '-');
                            @endphp
                            <span class="badge bg-light text-dark border border-secondary">
                                <i class="fas fa-map-marker-alt text-danger me-1"></i>
                                {{ $eNameStr }}
                                @if($empBranch->pivot && $empBranch->pivot->is_primary)
                                    <small class="text-success ms-1">(أساسي للموظف)</small>
                                @endif
                            </span>
                        @empty
                            <span class="text-muted small">لا توجد فروع مرتبطة بالموظف</span>
                        @endforelse
                    </div>
                </div>
                @endif

            </div>
        </div>
    </div>

</div>
@endsection
