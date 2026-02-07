@extends('layouts.master')

@section('title')
    {{ trans('settings_trans.commission_settings') }}
@endsection

@section('content')
    <div class="row">
        <div class="col-12">

            <div class="card">
                <div class="card-header">
                    <div class="d-flex align-items-center justify-content-between">
                        <h5 class="mb-0">{{ trans('settings_trans.commission_settings') }}</h5>

                        <a href="{{ url()->previous() }}" class="btn btn-light btn-sm">
                            <i class="mdi mdi-arrow-left"></i> {{ trans('settings_trans.back') ?? 'رجوع' }}
                        </a>
                    </div>
                </div>

                <div class="card-body">

                    @if(session('success'))
                        <div class="alert alert-success mb-3">{{ session('success') }}</div>
                    @endif

                    @if ($errors->any())
                        <div class="alert alert-danger mb-3">
                            <ul class="mb-0">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form method="post" action="{{ route('commission_settings.update', 1) }}">
                        @csrf
                        @method('patch')

                        @php
                            $current = old(
                                'calculate_commission_before_discounts',
                                isset($setting) ? (int)$setting->calculate_commission_before_discounts : 0
                            );
                        @endphp

                        <div class="mb-3">
                            <label class="form-label">{{ trans('settings_trans.commission_calc_timing') }}</label>

                            <div class="border rounded p-3 bg-light">
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="radio"
                                           name="calculate_commission_before_discounts"
                                           id="calc_before"
                                           value="1"
                                           {{ (string)$current === '1' ? 'checked' : '' }}>
                                    <label class="form-check-label" for="calc_before">
                                        {{ trans('settings_trans.commission_before_discounts') }}
                                    </label>
                                    <div class="text-muted small">{{ trans('settings_trans.commission_before_discounts_hint') }}</div>
                                </div>

                                <div class="form-check">
                                    <input class="form-check-input" type="radio"
                                           name="calculate_commission_before_discounts"
                                           id="calc_after"
                                           value="0"
                                           {{ (string)$current === '0' ? 'checked' : '' }}>
                                    <label class="form-check-label" for="calc_after">
                                        {{ trans('settings_trans.commission_after_discounts') }}
                                    </label>
                                    <div class="text-muted small">{{ trans('settings_trans.commission_after_discounts_hint') }}</div>
                                </div>
                            </div>
                        </div>

                        <div class="text-end">
                            <button type="submit" class="btn btn-primary">
                                {{ trans('settings_trans.submit') }}
                            </button>
                        </div>

                    </form>

                </div>
            </div>

        </div>
    </div>
@endsection
