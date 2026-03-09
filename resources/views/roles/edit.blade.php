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

    @if (session('error'))
    <div class="alert alert-danger alert-dismissible fade show shadow-sm">
        <i class="mdi mdi-alert-circle-outline me-1"></i> {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif

    @if ($errors->any())
    <div class="alert alert-danger alert-dismissible fade show shadow-sm">
        <ul class="mb-0 small ps-4">
            @foreach ($errors->all() as $error)
            <li>- {{ $error }}</li>
            @endforeach
        </ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold text-info mb-1">
                <i class="mdi mdi-shield-account-outline me-2"></i>
                {{ trans('user_management_trans.role_edit') }}
            </h3>
            <p class="text-muted mb-0">{{ trans('user_management_trans.role_management_desc') }}</p>
        </div>
        <a href="{{ route('roles.index') }}" class="btn btn-light shadow-sm">
            <i class="mdi mdi-arrow-left"></i> {{ trans('main_trans.back') }}
        </a>
    </div>

    <div class="d-flex flex-wrap align-items-center justify-content-between mb-3 gap-2">
        <div class="input-group w-auto">
            <span class="input-group-text bg-light"><i class="mdi mdi-magnify text-muted"></i></span>
            <input type="text" id="permissionSearch" class="form-control" placeholder="{{ trans('user_management_trans.permission_search') }}">
        </div>
        <button type="button" id="toggleAll" class="btn btn-outline-primary btn-sm">
            <i class="mdi mdi-checkbox-multiple-marked-outline me-1"></i> {{ trans('user_management_trans.toggle_all') }}
        </button>
    </div>

    <form action="{{ route('roles.update', $role->id) }}" method="POST" class="bg-white rounded-4 shadow p-4">
        @csrf
        @method('PATCH')

        <div class="row mb-3">
            <div class="col-lg-6">
                <label class="form-label fw-bold">
                    <i class="mdi mdi-account-key-outline me-1 text-info"></i>
                    {{ trans('user_management_trans.role_name') }}
                </label>
                <input type="text" name="name" class="form-control form-control-lg shadow-sm"
                    value="{{ old('name', $role->name) }}" required>
            </div>
        </div>

        <div class="accordion accordion-flush" id="permissionAccordion">
            @foreach ($groupedpermission as $category => $permissions)
            @php $checkedCount = collect($permissions)->whereIn('id', $rolePermissions)->count(); @endphp
            <div class="accordion-item border mb-3 rounded shadow-sm">
                <h2 class="accordion-header" id="heading{{ $loop->index }}">
                    <button class="accordion-button collapsed fw-bold text-dark bg-light"
                        type="button" data-bs-toggle="collapse"
                        data-bs-target="#collapse{{ $loop->index }}" aria-expanded="false">
                        <span><i class="mdi mdi-folder-lock me-2 text-info"></i> {{ $category }}</span>
                        <span class="badge bg-primary rounded-pill count-badge ms-auto me-2"
                            data-total="{{ count($permissions) }}">
                            {{ $checkedCount }}/{{ count($permissions) }}
                        </span>
                    </button>
                </h2>
                <div id="collapse{{ $loop->index }}" class="accordion-collapse collapse"
                    data-bs-parent="#permissionAccordion">
                    <div class="accordion-body bg-white">
                        <div class="d-flex justify-content-end mb-2">
                            <button type="button" class="btn btn-sm btn-outline-info toggle-section-btn">
                                <i class="mdi mdi-checkbox-multiple-marked-outline me-1"></i>
                                {{ trans('user_management_trans.toggle_all') }} ({{ $category }})
                            </button>
                        </div>
                        <div class="row permission-section">
                            @foreach ($permissions as $perm)
                            <div class="col-md-4 col-lg-3 mb-2 permission-item">
                                <div class="form-check form-switch">
                                    <input class="form-check-input perm-checkbox" type="checkbox"
                                        name="permission[]"
                                        value="{{ $perm->name }}"
                                        {{ in_array($perm->id, $rolePermissions) ? 'checked' : '' }}>
                                    <label class="form-check-label small">
                                        {{ is_array($perm->title) ? ($perm->title['ar'] ?? $perm->title['en']) : $perm->title }}
                                    </label>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
            @endforeach
        </div>

        <div class="text-center mt-4">
            <button type="submit" class="btn btn-primary btn-lg shadow px-5">
                <i class="mdi mdi-content-save-outline me-1"></i> {{ trans('user_management_trans.confirm') }}
            </button>
        </div>
        <input type="hidden" name="permission[]" value="">
    </form>

</div>

<style>
    body {
        background-color: #f8faff;
    }

    .accordion-button:not(.collapsed) {
        background-color: #e8f1ff;
        color: #0d6efd;
        box-shadow: none;
    }

    .accordion-button:focus {
        box-shadow: none;
    }

    .form-check-input:checked {
        background-color: #0d6efd;
        border-color: #0d6efd;
    }

    .accordion-item {
        border-radius: 1rem !important;
        overflow: hidden;
    }

    .permission-item.hide {
        display: none;
    }

    .count-badge {
        font-size: 0.85rem;
        min-width: 60px;
        text-align: center;
    }
</style>

<script>
    window.addEventListener('load', function() {
        document.querySelectorAll('.toggle-section-btn').forEach(button => {
            button.addEventListener('click', () => {
                const section = button.closest('.accordion-body');
                const checkboxes = section.querySelectorAll('.perm-checkbox');
                const allChecked = Array.from(checkboxes).every(cb => cb.checked);
                checkboxes.forEach(cb => cb.checked = !allChecked);
                updateCounts();
            });
        });

        const globalToggle = document.getElementById('toggleAll');
        if (globalToggle) {
            globalToggle.addEventListener('click', () => {
                const checkboxes = document.querySelectorAll('.perm-checkbox');
                const allChecked = Array.from(checkboxes).every(cb => cb.checked);
                checkboxes.forEach(cb => cb.checked = !allChecked);
                updateCounts();
            });
        }

        const searchInput = document.getElementById('permissionSearch');
        if (searchInput) {
            searchInput.addEventListener('input', function() {
                const term = this.value.toLowerCase().trim();
                document.querySelectorAll('.permission-item').forEach(item => {
                    item.classList.toggle('hide', term && !item.textContent.toLowerCase().includes(term));
                });
            });
        }

        function updateCounts() {
            document.querySelectorAll('.accordion-item').forEach(item => {
                const checkboxes = item.querySelectorAll('.perm-checkbox');
                const checked = Array.from(checkboxes).filter(cb => cb.checked).length;
                const badge = item.querySelector('.count-badge');
                if (badge) badge.textContent = `${checked}/${badge.dataset.total}`;
            });
        }

        document.querySelectorAll('.perm-checkbox').forEach(cb => {
            cb.addEventListener('change', updateCounts);
        });

        updateCounts();
    });
</script>

@endsection