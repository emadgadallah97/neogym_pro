@extends('layouts.master_table')
@section('title')
{{ trans('coupons_offers.show') }}
@stop

@section('content')

@php
    $planNames = [];
    foreach($Coupon->plans as $p) {
        $decoded = json_decode($p->name, true);
        if (is_array($decoded)) {
            $planNames[] = ($decoded[app()->getLocale()] ?? ($decoded['ar'] ?? ($decoded['en'] ?? '')));
        } else {
            $planNames[] = $p->name;
        }
    }

    $typeNames = [];
    foreach($Coupon->types as $t) {
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
            <h4 class="mb-sm-0">{{ trans('coupons_offers.coupon') }} - {{ $Coupon->code }}</h4>
            <div class="page-title-right">
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item"><a href="{{ route('coupons.index') }}">{{ trans('coupons_offers.coupons') }}</a></li>
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
                        <a href="{{ route('coupons.edit', $Coupon->id) }}" class="btn btn-soft-secondary">
                            <i class="ri-pencil-fill align-bottom me-1"></i> {{ trans('coupons_offers.edit') }}
                        </a>
                        <a href="{{ route('coupons.index') }}" class="btn btn-soft-secondary">
                            <i class="ri-arrow-go-back-line align-bottom me-1"></i> {{ trans('coupons_offers.back') }}
                        </a>
                    </div>
                </div>
            </div>

            <div class="card-body">
                <div class="row g-3">

                    <div class="col-lg-4">
                        <div class="border rounded p-3">
                            <div class="text-muted">{{ trans('coupons_offers.code') }}</div>
                            <div class="fw-semibold">{{ $Coupon->code }}</div>
                        </div>
                    </div>

                    <div class="col-lg-4">
                        <div class="border rounded p-3">
                            <div class="text-muted">{{ trans('coupons_offers.applies_to') }}</div>
                            <div><span class="badge bg-soft-info text-info">{{ trans('coupons_offers.' . $Coupon->applies_to) }}</span></div>
                        </div>
                    </div>

                    <div class="col-lg-4">
                        <div class="border rounded p-3">
                            <div class="text-muted">{{ trans('coupons_offers.status') }}</div>
                            <div>
                                @if($Coupon->is_expired)
                                    <span class="badge bg-warning">{{ trans('coupons_offers.expired') }}</span>
                                @else
                                    @if($Coupon->status === 'active')
                                        <span class="badge bg-success">{{ trans('coupons_offers.active') }}</span>
                                    @else
                                        <span class="badge bg-danger">{{ trans('coupons_offers.disabled') }}</span>
                                    @endif
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-6">
                        <div class="border rounded p-3">
                            <div class="text-muted">{{ trans('coupons_offers.name_ar') }} / {{ trans('coupons_offers.name_en') }}</div>
                            <div class="fw-semibold">{{ $Coupon->getTranslation('name','ar') }} / {{ $Coupon->getTranslation('name','en') }}</div>
                        </div>
                    </div>

                    <div class="col-lg-6">
                        <div class="border rounded p-3">
                            <div class="text-muted">{{ trans('coupons_offers.discount_type') }} / {{ trans('coupons_offers.discount_value') }}</div>
                            <div class="fw-semibold">{{ trans('coupons_offers.' . $Coupon->discount_type) }} / {{ $Coupon->discount_value }}</div>
                        </div>
                    </div>

                    <div class="col-lg-4">
                        <div class="border rounded p-3">
                            <div class="text-muted">{{ trans('coupons_offers.min_amount') }}</div>
                            <div class="fw-semibold">{{ $Coupon->min_amount ?? '-' }}</div>
                        </div>
                    </div>

                    <div class="col-lg-4">
                        <div class="border rounded p-3">
                            <div class="text-muted">{{ trans('coupons_offers.max_discount') }}</div>
                            <div class="fw-semibold">{{ $Coupon->max_discount ?? '-' }}</div>
                        </div>
                    </div>

                    <div class="col-lg-4">
                        <div class="border rounded p-3">
                            <div class="text-muted">{{ trans('coupons_offers.max_uses_total') }} / {{ trans('coupons_offers.max_uses_per_member') }}</div>
                            <div class="fw-semibold">{{ $Coupon->max_uses_total ?? '-' }} / {{ $Coupon->max_uses_per_member ?? '-' }}</div>
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
                                    @if($Coupon->durations->count())
                                        @foreach($Coupon->durations as $d)
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
                            <div class="mt-1">{{ $Coupon->getTranslation('description','ar') ?: '-' }}</div>
                            <hr>
                            <div class="text-muted">{{ trans('coupons_offers.description_en') }}</div>
                            <div class="mt-1">{{ $Coupon->getTranslation('description','en') ?: '-' }}</div>
                        </div>
                    </div>

                    <div class="col-12">
                        <div class="border rounded p-3">
                            <div class="d-flex align-items-center justify-content-between">
                                <div>
                                    <div class="text-muted">Usages</div>
                                    <div class="fw-semibold">{{ $Coupon->usages->count() }}</div>
                                </div>
                            </div>

                            @if($Coupon->usages->count())
                                <div class="table-responsive mt-3">
                                    <table class="table table-bordered table-striped align-middle mb-0">
                                        <thead>
                                            <tr>
                                                <th>#</th>
                                                <th>Member</th>
                                                <th>Applied To</th>
                                                <th>Before</th>
                                                <th>Discount</th>
                                                <th>After</th>
                                                <th>Used At</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php $i=0; ?>
                                            @foreach($Coupon->usages as $u)
                                                <?php $i++; ?>
                                                <tr>
                                                    <td>{{ $i }}</td>
                                                    <td>{{ $u->member_id ?? '-' }}</td>
                                                    <td>{{ $u->applied_to_type ? ($u->applied_to_type . ' #' . $u->applied_to_id) : '-' }}</td>
                                                    <td>{{ $u->amount_before ?? '-' }}</td>
                                                    <td>{{ $u->discount_amount ?? '-' }}</td>
                                                    <td>{{ $u->amount_after ?? '-' }}</td>
                                                    <td>{{ $u->used_at ?? '-' }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @else
                                <div class="text-muted mt-2">-</div>
                            @endif
                        </div>
                    </div>

                </div>
            </div>

        </div>
    </div>
</div>

@endsection
