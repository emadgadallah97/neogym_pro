@extends('layouts.master_table')

@section('title', trans('users.title_edit'))

@section('content')
<div class="row">
    <div class="col-12">
        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
            <h4 class="mb-sm-0">{{ trans('users.title_edit') }}</h4>
            <div class="page-title-right">
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item"><a href="{{ route('users.index') }}">{{ trans('users.title') }}</a></li>
                    <li class="breadcrumb-item active">{{ trans('users.title_edit') }}</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<div class="row">
    {{-- Alerts --}}
    @if(session('success'))
    <div class="col-12 mb-3">
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <strong>{{ trans('main_trans.success') }}!</strong> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    </div>
    @endif

    @if($errors->any())
    <div class="col-12 mb-3">
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    </div>
    @endif

    {{-- Edit User Details Card --}}
    <div class="col-md-8 mb-4">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
                <h5 class="mb-0 fw-bold">
                    <i class="ri-user-settings-line me-2 text-primary fs-20 align-middle"></i>
                    {{ trans('users.title_edit') }}: {{ $user->name }}
                </h5>
                <a href="{{ route('users.index') }}" class="btn btn-soft-secondary btn-sm">
                    <i class="ri-arrow-right-line me-1"></i> {{ trans('users.back') }}
                </a>
            </div>

            <div class="card-body">
                <form action="{{ route('users.update', $user->id) }}" method="POST">
                    @csrf
                    @method('PATCH')

                    <div class="row g-4">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">{{ trans('users.name') }} <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control" value="{{ old('name', $user->name) }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">{{ trans('users.email') }} <span class="text-danger">*</span></label>
                            <input type="email" name="email" class="form-control" value="{{ old('email', $user->email) }}" required>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-semibold">{{ trans('users.status') }} <span class="text-danger">*</span></label>
                            <select name="Status" class="form-select select2" required>
                                <option value="enabled" {{ old('Status', $user->Status) == 'enabled' ? 'selected' : '' }}>{{ trans('users.status_active') }}</option>
                                <option value="disabled" {{ old('Status', $user->Status) == 'disabled' ? 'selected' : '' }}>{{ trans('users.status_inactive') }}</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">{{ trans('users.branch') }} <span class="text-danger">*</span></label>
                            <select name="branch_id" class="form-select select2" required>
                                @foreach($branches as $branch)
                                <option value="{{ $branch->id }}" {{ old('branch_id', $user->branch_id) == $branch->id ? 'selected' : '' }}>
                                    {{ is_array($branch->name) ? ($branch->name[app()->getLocale()] ?? $branch->name['ar']) : $branch->name }}
                                </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">{{ trans('users.employee') }} <small class="text-muted">({{ trans('users.branches_info') }})</small></label>
                            <select name="employee_id" class="form-select select2">
                                <option value="">{{ trans('users.no_employee') }}</option>
                                @foreach($employees as $emp)
                                <option value="{{ $emp->id }}" {{ old('employee_id', $user->employee_id) == $emp->id ? 'selected' : '' }}>
                                    {{ $emp->full_name }}
                                </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-12">
                            <label class="form-label fw-semibold mb-3">{{ trans('users.roles') }} <span class="text-danger">*</span></label>
                            <div class="row g-3">
                                @foreach($roles as $role)
                                <div class="col-md-4">
                                    <div class="form-check card-radio p-0">
                                        <input class="form-check-input" type="checkbox" name="roles_name[]" value="{{ $role }}" id="role_{{ $role }}" {{ in_array($role, $userRole) ? 'checked' : '' }}>
                                        <label class="form-check-label p-3 h-100" for="role_{{ $role }}">
                                            <span class="fs-14 fw-semibold text-wrap">{{ $role }}</span>
                                        </label>
                                    </div>
                                </div>
                                @endforeach
                            </div>
                        </div>

                        <div class="col-md-12 text-end mt-5 border-top pt-3">
                            <button type="submit" class="btn btn-warning btn-label waves-effect waves-light">
                                <i class="ri-save-line label-icon align-middle fs-16 me-2"></i> {{ trans('users.save') }}
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Change Password Card --}}
    <div class="col-md-4 mb-4">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-header bg-white border-bottom py-3">
                <h5 class="mb-0 fw-bold text-danger">
                    <i class="ri-lock-password-line me-2 align-middle"></i>
                    {{ trans('users.change_password') }}
                </h5>
            </div>

            <div class="card-body">
                <form action="{{ route('users.updatePassword', $user->id) }}" method="POST">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label fw-semibold">{{ trans('users.new_password') }} <span class="text-danger">*</span></label>
                        <input type="password" name="password" class="form-control" placeholder="••••••••" required minlength="6">
                    </div>
                    <div class="mb-4">
                        <label class="form-label fw-semibold">{{ trans('users.confirm_new_password') }} <span class="text-danger">*</span></label>
                        <input type="password" name="confirm-password" class="form-control" placeholder="••••••••" required minlength="6">
                    </div>

                    <div class="text-end">
                        <button type="submit" class="btn btn-danger btn-label waves-effect waves-light w-100">
                            <i class="ri-shield-keyhole-line label-icon align-middle fs-16 me-2"></i> {{ trans('users.change_password') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    $(document).ready(function() {
        if ($('.select2').length) {
            $('.select2').each(function() {
                $(this).select2({
                    dropdownParent: $(this).parent(),
                    placeholder: "{{ trans('users.choose') }}",
                    allowClear: true
                });
            });
        }
    });
</script>
@endsection
