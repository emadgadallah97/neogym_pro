@extends('layouts.master_table')
@section('title')
{{ trans('coupons_offers.show') }}
@stop

@section('content')

@php
    $planNames = [];
    foreach($Offer->plans as $p) {
        $decoded = json_decode($p->name, true);
        if (is_array($decoded)) {
            $planNames[] = ($decoded[app()->getLocale()] ?? ($decoded['ar'] ?? ($decoded['en'] ?? '')));
        } else {
            $planNames[] = $p->name;
        }
    }

    $typeNames = [];
    foreach($Offer->types as $t) {
        $decoded = json_decode($t->name, true);
        if (is_array($decoded)) {
            $typeNames[] = ($decoded[app()->getLocale()] ?? ($decoded['ar'] ?? ($decoded['en'] ?? '')));
        } else {
            $typeNames[] = $t->name;
        }
    }
@endphp

<div class="row">
    <div class="col-12">
        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
            <h4 class="mb-sm-0">{{ trans('coupons_offers.offer') }} - {{ $Offer->getTranslation('name', app()->getLocale()) }}</h4>
            <div class="page-title-right">
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item"><a href="{{ route('offers.index') }}">{{ trans('coupons_offers.offers') }}</a></li>
                    <li class="breadcrumb-item active">{{ trans('coupons_offers.view') }}</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-12">
        <div class="card">

            <div class="card-header">
                <div class="d-flex align-items-center justify-content-between">
                    <h5 class="card-title mb-0">{{ trans('coupons_offers.view') }}</h5>

                    <div>
                        <a href="{{ route('offers.edit', $Offer->id) }}" class="btn btn-soft-secondary">
                            <i class="ri-pencil-fill align-bottom me-1"></i> {{ trans('coupons_offers.edit') }}
                        </a>
                        <a href="{{ route('offers.index') }}" class="btn btn-soft-secondary">
                            <i class="ri-arrow-go-back-line align-bottom me-1"></i> {{ trans('coupons_offers.back') }}
                        </a>
                    </div>
                </div>
            </div>

            <div class="card-body">
                <div class="row g-3">
                    <div class="col-lg-6">
                        <div class="border rounded p-3">
                            <div class="text-muted">{{ trans('coupons_offers.name_ar') }}</div>
                            <div class="fw-semibold">{{ $Offer->getTranslation('name','ar') }}</div>
                        </div>
                    </div>

                    <div class="col-lg-6">
                        <div class="border rounded p-3">
                            <div class="text-muted">{{ trans('coupons_offers.name_en') }}</div>
                            <div class="fw-semibold">{{ $Offer->getTranslation('name','en') }}</div>
                        </div>
                    </div>

                    <div class="col-lg-6">
                        <div class="border rounded p-3">
                            <div class="text-muted">{{ trans('coupons_offers.applies_to') }}</div>
                            <div>
                                <span class="badge bg-soft-info text-info">{{ trans('coupons_offers.' . $Offer->applies_to) }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-6">
                        <div class="border rounded p-3">
                            <div class="text-muted">{{ trans('coupons_offers.status') }}</div>
                            <div>
                                @if($Offer->is_expired)
                                    <span class="badge bg-warning">{{ trans('coupons_offers.expired') }}</span>
                                @else
                                    @if($Offer->status === 'active')
                                        <span class="badge bg-success">{{ trans('coupons_offers.active') }}</span>
                                    @else
                                        <span class="badge bg-danger">{{ trans('coupons_offers.disabled') }}</span>
                                    @endif
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-4">
                        <div class="border rounded p-3">
                            <div class="text-muted">{{ trans('coupons_offers.discount_type') }}</div>
                            <div class="fw-semibold">{{ trans('coupons_offers.' . $Offer->discount_type) }}</div>
                        </div>
                    </div>

                    <div class="col-lg-4">
                        <div class="border rounded p-3">
                            <div class="text-muted">{{ trans('coupons_offers.discount_value') }}</div>
                            <div class="fw-semibold">{{ $Offer->discount_value }}</div>
                        </div>
                    </div>

                    <div class="col-lg-4">
                        <div class="border rounded p-3">
                            <div class="text-muted">{{ trans('coupons_offers.priority') }}</div>
                            <div class="fw-semibold">{{ $Offer->priority }}</div>
                        </div>
                    </div>

                    <div class="col-lg-4">
                        <div class="border rounded p-3">
                            <div class="text-muted">{{ trans('coupons_offers.min_amount') }}</div>
                            <div class="fw-semibold">{{ $Offer->min_amount ?? '-' }}</div>
                        </div>
                    </div>

                    <div class="col-lg-4">
                        <div class="border rounded p-3">
                            <div class="text-muted">{{ trans('coupons_offers.max_discount') }}</div>
                            <div class="fw-semibold">{{ $Offer->max_discount ?? '-' }}</div>
                        </div>
                    </div>

                    <div class="col-lg-4">
                        <div class="border rounded p-3">
                            <div class="text-muted">{{ trans('coupons_offers.start_at') }} / {{ trans('coupons_offers.end_at') }}</div>
                            <div class="fw-semibold">
                                {{ $Offer->start_at ?? '-' }} / {{ $Offer->end_at ?? '-' }}
                            </div>
                        </div>
                    </div>

                    <div class="col-12">
                        <div class="border rounded p-3">
                            <div class="text-muted">{{ trans('coupons_offers.constraints') }}</div>

                            <div class="mt-2">
                                <div class="mb-2">
                                    <strong>{{ trans('coupons_offers.plans') }}:</strong>
                                    @if(count($planNames))
                                        @foreach($planNames as $pn)
                                            <span class="badge bg-soft-primary text-primary me-1 mb-1">{{ $pn }}</span>
                                        @endforeach
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </div>

                                <div class="mb-2">
                                    <strong>{{ trans('coupons_offers.types') }}:</strong>
                                    @if(count($typeNames))
                                        @foreach($typeNames as $tn)
                                            <span class="badge bg-soft-info text-info me-1 mb-1">{{ $tn }}</span>
                                        @endforeach
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </div>

                                <div>
                                    <strong>{{ trans('coupons_offers.durations') }}:</strong>
                                    @if($Offer->durations->count())
                                        @foreach($Offer->durations as $d)
                                            <span class="badge bg-soft-success text-success me-1 mb-1">
                                                {{ $d->duration_value }} {{ trans('coupons_offers.' . $d->duration_unit) }}
                                            </span>
                                        @endforeach
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-12">
                        <div class="border rounded p-3">
                            <div class="text-muted">{{ trans('coupons_offers.description_ar') }}</div>
                            <div class="mt-1">{{ $Offer->getTranslation('description','ar') ?: '-' }}</div>
                            <hr>
                            <div class="text-muted">{{ trans('coupons_offers.description_en') }}</div>
                            <div class="mt-1">{{ $Offer->getTranslation('description','en') ?: '-' }}</div>
                        </div>
                    </div>

                </div>
            </div>

        </div>
    </div>
</div>

@endsection
