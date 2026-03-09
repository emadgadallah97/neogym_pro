@extends('layouts.master')
@section('title')
{{ trans('user_management_trans.role_management') }}
@stop
@section('content')

<div class="container-fluid py-4">

    @if (session('success'))
    <div class="alert alert-success alert-dismissible fade show shadow-sm">
        <i class="mdi mdi-check-circle-outline me-1"></i> {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif

    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h3 class="fw-bold text-info mb-1">
                <i class="mdi mdi-account-shield-outline me-2"></i>
                {{ trans('user_management_trans.role_show') }}
            </h3>
            <p class="text-muted mb-0">{{ trans('user_management_trans.role_management_desc') }}</p>
        </div>
        <a href="{{ route('roles.index') }}" class="btn btn-outline-primary shadow-sm">
            <i class="mdi mdi-arrow-left"></i> {{ trans('main_trans.back') }}
        </a>
    </div>

    <div class="card border-0 bg-light shadow-sm rounded-4 mb-4">
        <div class="card-body d-flex align-items-center justify-content-between">
            <h5 class="text-muted mb-0">{{ trans('user_management_trans.permissions_total') }}</h5>
            <span class="badge bg-primary fs-5">{{ count($rolePermissions) }}</span>
        </div>
    </div>

    <div class="input-group w-50 mb-4">
        <span class="input-group-text bg-light border-0"><i class="mdi mdi-magnify text-muted"></i></span>
        <input type="text" id="permissionSearch" class="form-control border-0 shadow-sm"
            placeholder="{{ trans('user_management_trans.permission_search') }}">
    </div>

    <div class="row">
        @foreach ($groupedpermission as $category => $permissions)
        @php
        $filtered = $permissions->filter(fn($p) => in_array($p->name, $rolePermissions));
        @endphp

        @if ($filtered->count() > 0)
        <div class="col-md-6 col-lg-4 mb-4 module-block">
            <div class="card border-0 shadow rounded-4 module-card">
                <div class="card-header bg-primary text-white fw-bold d-flex justify-content-between align-items-center">
                    <span><i class="mdi mdi-folder-lock me-2"></i> {{ $category }}</span>
                    <span class="badge bg-light text-info">{{ $filtered->count() }}</span>
                </div>
                <div class="card-body bg-white">
                    @foreach ($filtered as $perm)
                    <span class="badge bg-light text-dark border border-1 border-primary mb-2 me-1 permission-item">
                        <i class="mdi mdi-lock-open-outline text-info me-1"></i>
                        {{ is_array($perm->title)
                                        ? ($perm->title[app()->getLocale()] ?? reset($perm->title))
                                        : $perm->title }}
                    </span>
                    @endforeach
                </div>
            </div>
        </div>
        @endif
        @endforeach
    </div>

    @if (count($rolePermissions) == 0)
    <div class="text-center py-4 text-muted">
        <i class="mdi mdi-information-outline fs-2 mb-2 d-block"></i>
        {{ trans('user_management_trans.no_permissions_assigned') }}
    </div>
    @endif

</div>

<style>
    body {
        background-color: #f8faff;
    }

    .module-card {
        transition: all 0.2s ease-in-out;
    }

    .module-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 8px 20px rgba(13, 110, 253, 0.1);
    }

    .permission-item {
        font-size: 0.85rem;
        border-radius: 10px;
        display: inline-block;
    }

    .permission-item:hover {
        background-color: #0d6efd;
        color: #fff;
    }

    .module-block.hide {
        display: none;
    }
</style>

<script>
    window.addEventListener('load', function() {
        const searchInput = document.getElementById('permissionSearch');
        const modules = document.querySelectorAll('.module-block');

        searchInput.addEventListener('input', function() {
            const term = this.value.toLowerCase().trim();
            modules.forEach(module => {
                const badges = module.querySelectorAll('.permission-item');
                let visibleCount = 0;
                badges.forEach(badge => {
                    const visible = term === '' || badge.textContent.toLowerCase().includes(term);
                    badge.style.display = visible ? 'inline-block' : 'none';
                    if (visible) visibleCount++;
                });
                module.style.display = visibleCount === 0 ? 'none' : '';
            });
        });
    });
</script>

@endsection