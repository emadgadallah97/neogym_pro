@extends('layouts.master')
@section('title')
{{ trans('settings_trans.system_settings') }}
@stop

@section('content')

    <!-- start page title -->
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                <h4 class="mb-sm-0 font">{{ trans('settings_trans.settings') }}</h4>

                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item">
                            <a href="javascript: void(0);">{{ trans('settings_trans.settings') }}</a>
                        </li>
                        <li class="breadcrumb-item active">{{ trans('settings_trans.system_settings') }}</li>
                    </ol>
                </div>

            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-body">

                    @php
                        $isEdit = !empty($setting);
                    @endphp

                    <form method="post"
                          action="{{ $isEdit ? route('general_settings.update', $setting->id) : route('general_settings.store') }}"
                          enctype="multipart/form-data">
                        @csrf
                        @if($isEdit)
                            @method('PUT')
                        @endif

                        <div class="row">

                            <div class="col-md-6 mb-3">
                                <label class="form-label">{{ trans('settings_trans.organization_name_ar') }}</label>
                                <input type="text" class="form-control" name="name_ar"
                                       value="{{ old('name_ar', $setting ? ($setting->getTranslation('name','ar') ?? '') : '') }}">
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">{{ trans('settings_trans.organization_name_en') }}</label>
                                <input type="text" class="form-control" name="name_en"
                                       value="{{ old('name_en', $setting ? ($setting->getTranslation('name','en') ?? '') : '') }}">
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">{{ trans('settings_trans.country') }}</label>
                                <select class="form-select" name="country_id">
                                    <option value="">{{ trans('settings_trans.choose') }}</option>
                                    @foreach($countries as $row)
                                        <option value="{{ $row->id }}"
                                            {{ (string)old('country_id', $setting->country_id ?? '') === (string)$row->id ? 'selected' : '' }}>
                                            {{ is_array($row->name) ? ($row->name['ar'] ?? '') : $row->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">{{ trans('settings_trans.currency') }}</label>
                                <select class="form-select" name="currency_id">
                                    <option value="">{{ trans('settings_trans.choose') }}</option>
                                    @foreach($currencies as $row)
                                        <option value="{{ $row->id }}"
                                            {{ (string)old('currency_id', $setting->currency_id ?? '') === (string)$row->id ? 'selected' : '' }}>
                                            {{ is_array($row->name) ? ($row->name['ar'] ?? '') : $row->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">{{ trans('settings_trans.commercial_register') }}</label>
                                <input type="text" class="form-control" name="commercial_register"
                                       value="{{ old('commercial_register', $setting->commercial_register ?? '') }}">
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">{{ trans('settings_trans.tax_register') }}</label>
                                <input type="text" class="form-control" name="tax_register"
                                       value="{{ old('tax_register', $setting->tax_register ?? '') }}">
                            </div>

                            <div class="col-md-4 mb-3">
                                <label class="form-label">{{ trans('settings_trans.phone') }}</label>
                                <input type="text" class="form-control" name="phone"
                                       value="{{ old('phone', $setting->phone ?? '') }}">
                            </div>

                            <div class="col-md-4 mb-3">
                                <label class="form-label">{{ trans('settings_trans.email') }}</label>
                                <input type="text" class="form-control" name="email"
                                       value="{{ old('email', $setting->email ?? '') }}">
                            </div>

                            <div class="col-md-4 mb-3">
                                <label class="form-label">{{ trans('settings_trans.website') }}</label>
                                <input type="text" class="form-control" name="website"
                                       value="{{ old('website', $setting->website ?? '') }}">
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">{{ trans('settings_trans.logo') }}</label>
                                <input type="file" class="form-control" name="logo" accept="image/*">
                                @if(!empty($setting) && !empty($setting->logo))
                                    <div class="mt-2">
                                        <a href="{{ asset('/'.$setting->logo) }}" target="_blank">
                                            {{ trans('settings_trans.view_logo') }}
                                        </a>
                                    </div>
                                @endif
                            </div>

                            <div class="col-md-6 mb-3 d-flex align-items-end">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="status" id="statuss"
                                           {{ old('status', $setting->status ?? 1) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="statuss">
                                        {{ trans('settings_trans.status_active') }}
                                    </label>
                                </div>
                            </div>

                            <div class="col-md-12 mb-3">
                                <label class="form-label">{{ trans('settings_trans.notes') }}</label>
                                <textarea class="form-control" name="notes" rows="3">{{ old('notes', $setting->notes ?? '') }}</textarea>
                            </div>

                            <div class="col-md-12">
                                <button type="submit" class="btn btn-primary">
                                    {{ $isEdit ? trans('settings_trans.update') : trans('settings_trans.save') }}
                                </button>
                            </div>

                        </div>

                    </form>

                </div>
            </div>
        </div>
    </div>

@endsection
