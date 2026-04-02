@extends('layouts.master_table')
@section('title')
{{ trans('settings_trans.referral_sources_settings') }}
@stop

@section('content')

<div class="row">
    <div class="col-12">
        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
            <h4 class="mb-sm-0 font">
                <i class="ri-compass-discover-line me-1"></i>
                {{ trans('settings_trans.referral_sources_settings') }}
            </h4>
            <div class="page-title-right">
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item"><a href="{{ route('settings.index') }}">{{ trans('settings_trans.settings') }}</a></li>
                    <li class="breadcrumb-item active">{{ trans('settings_trans.referral_sources_settings') }}</li>
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
                    <h5 class="card-title mb-0">{{ trans('settings_trans.referral_sources_list') }}</h5>

                    <div class="d-flex gap-2">
                        <button data-bs-toggle="modal" data-bs-target="#addReferralSourceModal" class="btn btn-primary btn-sm">
                            <i class="ri-add-line align-bottom me-1"></i> {{ trans('settings_trans.add_new_referral_source') }}
                        </button>
                    </div>
                </div>
            </div>

            <div class="card-body">

                @if (Session::has('success'))
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        {{ session('success') }}
                    </div>
                @endif

                @if (Session::has('error'))
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        {{ session('error') }}
                    </div>
                @endif

                @if ($errors->any())
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        <ul class="mb-0 ps-3">
                            @foreach ($errors->all() as $err)
                                <li>{{ $err }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <table id="example" class="table table-bordered dt-responsive nowrap table-striped align-middle" style="width:100%">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>{{ trans('settings_trans.id') }}</th>
                            <th>{{ trans('settings_trans.name_ar') }}</th>
                            <th>{{ trans('settings_trans.name_en') }}</th>
                            <th>{{ trans('settings_trans.sort_order') }}</th>
                            <th>{{ trans('settings_trans.status') }}</th>
                            <th>{{ trans('settings_trans.notes') }}</th>
                            <th>{{ trans('settings_trans.create_date') }}</th>
                            <th>{{ trans('settings_trans.action') }}</th>
                        </tr>
                    </thead>

                    <tbody>
                        @php $i = 0; @endphp
                        @foreach($ReferralSources as $t)
                            @php
                                $i++;
                                $nameAr = $t->getTranslation('name', 'ar');
                                $nameEn = $t->getTranslation('name', 'en');
                            @endphp
                            <tr>
                                <td>{{ $i }}</td>
                                <td>{{ $t->id }}</td>
                                <td>{{ $nameAr }}</td>
                                <td>{{ $nameEn }}</td>
                                <td>{{ $t->sort_order }}</td>
                                <td>
                                    @if($t->status)
                                        <span class="badge bg-success">{{ trans('settings_trans.active') }}</span>
                                    @else
                                        <span class="badge bg-danger">{{ trans('settings_trans.inactive') }}</span>
                                    @endif
                                </td>
                                <td>{{ $t->notes ?? '—' }}</td>
                                <td>{{ $t->created_at }}</td>
                                <td>
                                    <div class="dropdown d-inline-block">
                                        <button class="btn btn-soft-secondary btn-sm dropdown" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                            <i class="ri-more-fill align-middle"></i>
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end">
                                            <li>
                                                <button type="button" data-bs-toggle="modal" data-bs-target="#editReferralSourceModal{{ $t->id }}" class="dropdown-item">
                                                    <i class="ri-pencil-fill align-bottom me-2 text-muted"></i> {{ trans('settings_trans.update') }}
                                                </button>
                                            </li>
                                            <li>
                                                <button type="button" data-bs-toggle="modal" data-bs-target="#deleteReferralSourceModal{{ $t->id }}" class="dropdown-item">
                                                    <i class="ri-delete-bin-fill align-bottom me-2 text-muted"></i> {{ trans('settings_trans.delete') }}
                                                </button>
                                            </li>
                                        </ul>
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

<!-- Add Modal -->
<div id="addReferralSourceModal" class="modal fade" tabindex="-1" aria-hidden="true" style="display: none;">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 overflow-hidden">
            <div class="modal-header p-3">
                <h4 class="card-title mb-0">{{ trans('settings_trans.add_new_referral_source') }}</h4>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body">
                <form action="{{ route('referral_sources.store') }}" method="post">
                    @csrf

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">{{ trans('settings_trans.name_ar') }}</label>
                            <input type="text" name="name_ar" class="form-control" value="{{ old('name_ar') }}" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">{{ trans('settings_trans.name_en') }}</label>
                            <input type="text" name="name_en" class="form-control" value="{{ old('name_en') }}" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">{{ trans('settings_trans.status') }}</label>
                            <select name="status" class="form-select" required>
                                <option value="1" {{ old('status', '1') == '1' ? 'selected' : '' }}>{{ trans('settings_trans.active') }}</option>
                                <option value="0" {{ old('status') === '0' ? 'selected' : '' }}>{{ trans('settings_trans.inactive') }}</option>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">{{ trans('settings_trans.sort_order') }}</label>
                            <input type="number" name="sort_order" class="form-control" min="0" value="{{ old('sort_order', 0) }}">
                        </div>

                        <div class="col-12">
                            <label class="form-label">{{ trans('settings_trans.notes') }}</label>
                            <input type="text" name="notes" class="form-control" value="{{ old('notes') }}">
                        </div>
                    </div>

                    <div class="text-end mt-3">
                        <button type="submit" class="btn btn-primary">{{ trans('settings_trans.submit') }}</button>
                    </div>

                </form>
            </div>

        </div>
    </div>
</div>

@foreach($ReferralSources as $t)
    @php
        $nameAr = $t->getTranslation('name', 'ar');
        $nameEn = $t->getTranslation('name', 'en');
    @endphp

    <!-- Edit Modal -->
    <div id="editReferralSourceModal{{ $t->id }}" class="modal fade" tabindex="-1" aria-hidden="true" style="display: none;">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content border-0 overflow-hidden">
                <div class="modal-header p-3">
                    <h4 class="card-title mb-0">{{ trans('settings_trans.update_referral_source') }}</h4>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    <form action="{{ route('referral_sources.update', $t->id) }}" method="post">
                        @csrf
                        {{ method_field('patch') }}

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">{{ trans('settings_trans.name_ar') }}</label>
                                <input type="text" name="name_ar" class="form-control" value="{{ $nameAr }}" required>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">{{ trans('settings_trans.name_en') }}</label>
                                <input type="text" name="name_en" class="form-control" value="{{ $nameEn }}" required>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">{{ trans('settings_trans.status') }}</label>
                                <select name="status" class="form-select" required>
                                    <option value="1" {{ $t->status ? 'selected' : '' }}>{{ trans('settings_trans.active') }}</option>
                                    <option value="0" {{ !$t->status ? 'selected' : '' }}>{{ trans('settings_trans.inactive') }}</option>
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">{{ trans('settings_trans.sort_order') }}</label>
                                <input type="number" name="sort_order" class="form-control" min="0" value="{{ $t->sort_order }}">
                            </div>

                            <div class="col-12">
                                <label class="form-label">{{ trans('settings_trans.notes') }}</label>
                                <input type="text" name="notes" class="form-control" value="{{ $t->notes }}">
                            </div>
                        </div>

                        <div class="text-end mt-3">
                            <button type="submit" class="btn btn-primary">{{ trans('settings_trans.submit') }}</button>
                        </div>

                    </form>
                </div>

            </div>
        </div>
    </div>

    <!-- Delete Modal -->
    <div id="deleteReferralSourceModal{{ $t->id }}" class="modal fade" tabindex="-1" aria-hidden="true" style="display: none;">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-body text-center p-5">
                    <div class="mt-2">
                        <h4>{{ trans('settings_trans.massagedelete_d') }}</h4>
                        <p class="text-muted">{{ trans('settings_trans.massagedelete_p') }} {{ $nameAr }}</p>

                        <form action="{{ route('referral_sources.destroy', $t->id) }}" method="post">
                            @csrf
                            {{ method_field('delete') }}

                            <button type="submit" class="btn btn-warning">{{ trans('settings_trans.massagedelete') }}</button>
                        </form>
                    </div>

                </div>
            </div>
        </div>
    </div>
@endforeach

@endsection
