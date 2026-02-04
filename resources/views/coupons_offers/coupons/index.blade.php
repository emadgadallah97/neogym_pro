@extends('layouts.master_table')
@section('title')
{{ trans('coupons_offers.coupons') }}
@stop

@section('content')

@php
    $totalCoupons = $Coupons->count();
    $activeCoupons = $Coupons->where('status', 'active')->count();
    $disabledCoupons = $Coupons->where('status', 'disabled')->count();
    $expiredCoupons = $Coupons->filter(function($c){ return isset($c->is_expired) && $c->is_expired; })->count();
    $lastCreatedAt = $Coupons->max('created_at');

    // Branches list (for filter + column)
    $BranchesList = DB::table('branches')
        ->select(['id','name'])
        ->whereNull('deleted_at')
        ->where('status', 1)
        ->orderByDesc('id')
        ->get();

    $branchName = function ($nameJsonOrText) {
        $decoded = json_decode($nameJsonOrText, true);
        if (is_array($decoded)) {
            return $decoded[app()->getLocale()] ?? ($decoded['ar'] ?? ($decoded['en'] ?? ''));
        }
        return $nameJsonOrText;
    };
@endphp

<div class="row">
    <div class="col-12">
        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
            <h4 class="mb-sm-0">{{ trans('coupons_offers.coupons') }}</h4>
            <div class="page-title-right">
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item"><a href="javascript: void(0);">{{ trans('coupons_offers.coupons') }}</a></li>
                    <li class="breadcrumb-item active">{{ trans('coupons_offers.coupons') }}</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body pb-0">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <h5 class="card-title mb-1">{{ trans('subscriptions.plans_kpis') }}</h5>
                        <p class="text-muted mb-0">{{ trans('subscriptions.quick_filters') }}</p>
                    </div>

                    <div class="d-flex gap-2">
                        <a href="{{ route('coupons.create') }}" class="btn btn-primary">
                            <i class="ri-add-line align-bottom me-1"></i> {{ trans('coupons_offers.add_new_coupon') }}
                        </a>
                    </div>
                </div>

                <div class="row mt-3 g-3">
                    <div class="col-md-6 col-xl-3">
                        <div class="card mb-0 border">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="avatar-sm flex-shrink-0">
                                        <span class="avatar-title rounded bg-soft-primary text-primary">
                                            <i class="ri-coupon-3-line fs-20"></i>
                                        </span>
                                    </div>
                                    <div class="flex-grow-1 ms-3">
                                        <p class="text-muted mb-1">{{ trans('coupons_offers.coupons') }}</p>
                                        <h4 class="mb-0">{{ $totalCoupons }}</h4>
                                        @if($lastCreatedAt)
                                            <small class="text-muted d-block">{{ trans('subscriptions.last_created') }}: {{ $lastCreatedAt }}</small>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6 col-xl-3">
                        <div class="card mb-0 border">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="avatar-sm flex-shrink-0">
                                        <span class="avatar-title rounded bg-soft-success text-success">
                                            <i class="ri-check-double-line fs-20"></i>
                                        </span>
                                    </div>
                                    <div class="flex-grow-1 ms-3">
                                        <p class="text-muted mb-1">{{ trans('coupons_offers.active') }}</p>
                                        <h4 class="mb-0">{{ $activeCoupons }}</h4>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6 col-xl-3">
                        <div class="card mb-0 border">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="avatar-sm flex-shrink-0">
                                        <span class="avatar-title rounded bg-soft-danger text-danger">
                                            <i class="ri-close-circle-line fs-20"></i>
                                        </span>
                                    </div>
                                    <div class="flex-grow-1 ms-3">
                                        <p class="text-muted mb-1">{{ trans('coupons_offers.disabled') }}</p>
                                        <h4 class="mb-0">{{ $disabledCoupons }}</h4>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6 col-xl-3">
                        <div class="card mb-0 border">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="avatar-sm flex-shrink-0">
                                        <span class="avatar-title rounded bg-soft-warning text-warning">
                                            <i class="ri-timer-line fs-20"></i>
                                        </span>
                                    </div>
                                    <div class="flex-grow-1 ms-3">
                                        <p class="text-muted mb-1">{{ trans('coupons_offers.expired') }}</p>
                                        <h4 class="mb-0">{{ $expiredCoupons }}</h4>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <hr class="mt-4 mb-0">
            </div>

            <div class="card-body pt-3">
                <div class="row g-3 align-items-end">
                    <div class="col-md-6 col-lg-4">
                        <label class="form-label mb-1">{{ trans('coupons_offers.search') }}</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="ri-search-line"></i></span>
                            <input type="text" id="couponSearch" class="form-control" placeholder="{{ trans('coupons_offers.search_here') }}">
                        </div>
                    </div>

                    <div class="col-md-6 col-lg-2">
                        <label class="form-label mb-1">{{ trans('coupons_offers.status') }}</label>
                        <select id="filterStatus" class="form-select">
                            <option value="">{{ trans('coupons_offers.all') }}</option>
                            <option value="active">{{ trans('coupons_offers.active') }}</option>
                            <option value="disabled">{{ trans('coupons_offers.disabled') }}</option>
                        </select>
                    </div>

                    <div class="col-md-6 col-lg-3">
                        <label class="form-label mb-1">{{ trans('coupons_offers.applies_to') }}</label>
                        <select id="filterAppliesTo" class="form-select">
                            <option value="">{{ trans('coupons_offers.all') }}</option>
                            <option value="any">{{ trans('coupons_offers.any') }}</option>
                            <option value="subscription">{{ trans('coupons_offers.subscription') }}</option>
                            <option value="sale">{{ trans('coupons_offers.sale') }}</option>
                            <option value="service">{{ trans('coupons_offers.service') }}</option>
                        </select>
                    </div>

                    <div class="col-md-6 col-lg-3">
                        <label class="form-label mb-1">{{ trans('coupons_offers.branches') }}</label>
                        <select id="filterBranches" class="form-select" multiple>
                            @foreach($BranchesList as $b)
                                <option value="{{ $b->id }}">{{ $branchName($b->name) }}</option>
                            @endforeach
                        </select>
                        <small class="text-muted">{{ trans('subscriptions.multi_select_hint') }}</small>
                    </div>

                    <div class="col-12 text-end">
                        <button type="button" id="clearFilters" class="btn btn-soft-secondary">
                            <i class="ri-refresh-line align-bottom me-1"></i> {{ trans('coupons_offers.clear_filters') }}
                        </button>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-12">
        <div class="card">

            <div class="card-header">
                <div class="d-flex align-items-center justify-content-between">
                    <h5 class="card-title mb-0">{{ trans('coupons_offers.coupons') }}</h5>

                    <div class="ms-3">
                        @if (Session::has('success'))
                            <div class="alert alert-success alert-dismissible mb-0 py-2 px-3" role="alert">
                                <button type="button" class="close" data-dismiss="alert">
                                    <i class="fa fa-times"></i>
                                </button>
                                <strong>Success !</strong> {{ session('success') }}
                            </div>
                        @endif

                        @if (Session::has('error'))
                            <div class="alert alert-danger alert-dismissible mb-0 py-2 px-3" role="alert">
                                <button type="button" class="close" data-dismiss="alert">
                                    <i class="fa fa-times"></i>
                                </button>
                                <strong>Error !</strong> {{ session('error') }}
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <div class="card-body">
                <table id="example" class="table table-bordered dt-responsive nowrap table-striped align-middle" style="width:100%">
                    <thead>
                        <tr>
                            <th data-ordering="false">#</th>
                            <th data-ordering="false">{{ trans('coupons_offers.code') }}</th>
                            <th data-ordering="false">{{ trans('coupons_offers.name_ar') }}</th>
                            <th data-ordering="false">{{ trans('coupons_offers.name_en') }}</th>
                            <th data-ordering="false">{{ trans('coupons_offers.applies_to') }}</th>
                            <th data-ordering="false">{{ trans('coupons_offers.branches') }}</th>
                            <th data-ordering="false">{{ trans('coupons_offers.discount_type') }}</th>
                            <th data-ordering="false">{{ trans('coupons_offers.discount_value') }}</th>
                            <th data-ordering="false">{{ trans('coupons_offers.status') }}</th>
                            <th data-ordering="false">Used</th>
                            <th>{{ trans('subscriptions.create_date') }}</th>
                            <th>{{ trans('coupons_offers.actions') }}</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php $i=0; ?>
                        @foreach($Coupons as $Coupon)
                            <?php $i++; ?>

                            @php
                                // Branches for this coupon
                                $couponBranchIds = DB::table('coupon_branches')
                                    ->where('coupon_id', $Coupon->id)
                                    ->pluck('branch_id')
                                    ->toArray();

                                $couponBranchesDb = [];
                                $couponBranchNames = [];

                                if (count($couponBranchIds)) {
                                    $couponBranchesDb = DB::table('branches')
                                        ->whereIn('id', $couponBranchIds)
                                        ->orderBy('id')
                                        ->get();

                                    foreach ($couponBranchesDb as $cb) {
                                        $couponBranchNames[] = $branchName($cb->name);
                                    }
                                }
                            @endphp

                            <tr>
                                <td>{{ $i }}</td>

                                <td>
                                    <span class="badge bg-soft-primary text-primary">{{ $Coupon->code }}</span>
                                </td>

                                <td class="text-truncate" style="max-width:220px;">
                                    {{ $Coupon->getTranslation('name','ar') }}
                                </td>

                                <td class="text-truncate" style="max-width:220px;">
                                    {{ $Coupon->getTranslation('name','en') }}
                                </td>

                                <td>
                                    <span class="d-none">__APPLIES__{{ $Coupon->applies_to }}__</span>
                                    <span class="badge bg-soft-info text-info">{{ trans('coupons_offers.' . $Coupon->applies_to) }}</span>
                                </td>

                                <td>
                                    {{-- Hidden tokens for filtering branches --}}
                                    <span class="d-none">
                                        @foreach($couponBranchIds as $bid)
                                            __BRANCH__{{ (int)$bid }}__
                                        @endforeach
                                    </span>

                                    @if(count($couponBranchNames))
                                        @foreach($couponBranchNames as $bn)
                                            <span class="badge bg-soft-info text-info me-1 mb-1">{{ $bn }}</span>
                                        @endforeach
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>

                                <td>
                                    <span class="badge bg-soft-secondary text-secondary">{{ trans('coupons_offers.' . $Coupon->discount_type) }}</span>
                                </td>

                                <td>
                                    <span class="badge bg-soft-success text-success">{{ $Coupon->discount_value }}</span>
                                </td>

                                <td>
                                    <span class="d-none">__STATUS__{{ $Coupon->status }}__</span>

                                    @if($Coupon->is_expired)
                                        <span class="badge bg-warning">{{ trans('coupons_offers.expired') }}</span>
                                    @else
                                        @if($Coupon->status === 'active')
                                            <span class="badge bg-success">{{ trans('coupons_offers.active') }}</span>
                                        @else
                                            <span class="badge bg-danger">{{ trans('coupons_offers.disabled') }}</span>
                                        @endif
                                    @endif
                                </td>

                                <td>
                                    <span class="badge bg-soft-dark text-dark">{{ $Coupon->usages_count }}</span>
                                </td>

                                <td>{{ $Coupon->created_at }}</td>

                                <td>
                                    <div class="btn-group" role="group">
                                        <a href="{{ route('coupons.show', $Coupon->id) }}" class="btn btn-soft-primary btn-sm" title="{{ trans('coupons_offers.view') }}">
                                            <i class="ri-eye-fill align-bottom"></i>
                                        </a>
                                        <a href="{{ route('coupons.edit', $Coupon->id) }}" class="btn btn-soft-secondary btn-sm" title="{{ trans('coupons_offers.edit') }}">
                                            <i class="ri-pencil-fill align-bottom"></i>
                                        </a>
                                        <button type="button" data-bs-toggle="modal" data-bs-target="#deleteModal{{$Coupon->id}}" class="btn btn-soft-danger btn-sm" title="{{ trans('coupons_offers.delete') }}">
                                            <i class="ri-delete-bin-fill align-bottom"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>

                            <!-- delete -->
                            <div id="deleteModal{{$Coupon->id}}" class="modal fade" tabindex="-1" aria-hidden="true" style="display: none;">
                                <div class="modal-dialog modal-dialog-centered">
                                    <div class="modal-content">
                                        <div class="modal-body text-center p-5">
                                            <lord-icon src="{{URL::asset('assets/images/icon/tdrtiskw.json')}}" trigger="loop"
                                                colors="primary:#f7b84b,secondary:#405189" style="width:130px;height:130px">
                                            </lord-icon>

                                            <div class="mt-4 pt-4">
                                                <h4>{{ trans('subscriptions.massagedelete_d') }}!</h4>
                                                <p class="text-muted">{{ trans('subscriptions.massagedelete_p') }} {{ $Coupon->code }}</p>

                                                <form action="{{ route('coupons.destroy', $Coupon->id) }}" method="post">
                                                    {{ method_field('delete') }}
                                                    {{ csrf_field() }}

                                                    <input class="form-control" id="id" name="id" value="{{ $Coupon->id }}" type="hidden">

                                                    <button class="btn btn-warning" data-bs-target="#secondmodal" data-bs-toggle="modal" data-bs-dismiss="modal">
                                                        {{ trans('subscriptions.massagedelete') }}
                                                    </button>
                                                </form>
                                            </div>

                                        </div>
                                    </div>
                                </div>
                            </div>

                        @endforeach
                    </tbody>
                </table>
            </div>

        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        if (typeof $ === 'undefined') return;

        // Select2 for branches (multi)
        if ($.fn && $.fn.select2) {
            var isRtl = $('html').attr('dir') === 'rtl';
            $('#filterBranches').select2({
                width: '100%',
                placeholder: '{{ trans('coupons_offers.branches') }}',
                allowClear: true,
                closeOnSelect: false,
                dir: isRtl ? 'rtl' : 'ltr'
            });
        }

        if (!$.fn || !$.fn.DataTable) return;

        var table = $('#example').DataTable();

        // columns:
        // 0 #, 1 code, 2 ar, 3 en, 4 applies_to, 5 branches, 6 discount_type, 7 discount_value, 8 status, 9 used, 10 created, 11 actions
        var appliesColIndex = 4;
        var branchesColIndex = 5;
        var statusColIndex = 8;

        $('#couponSearch').on('keyup change', function () {
            table.search(this.value).draw();
        });

        $('#filterStatus').on('change', function () {
            var v = $(this).val();
            if (v === '') {
                table.column(statusColIndex).search('').draw();
                return;
            }
            table.column(statusColIndex).search('__STATUS__' + v + '__', false, false).draw();
        });

        $('#filterAppliesTo').on('change', function () {
            var v = $(this).val();
            if (v === '') {
                table.column(appliesColIndex).search('').draw();
                return;
            }
            table.column(appliesColIndex).search('__APPLIES__' + v + '__', false, false).draw();
        });

        $('#filterBranches').on('change', function () {
            var selected = $(this).val() || [];

            if (selected.length === 0) {
                table.column(branchesColIndex).search('').draw();
                return;
            }

            var tokens = selected.map(function(id){
                return '__BRANCH__' + id + '__';
            });

            var regex = '(?:' + tokens.join('|') + ')';
            table.column(branchesColIndex).search(regex, true, false).draw();
        });

        $('#clearFilters').on('click', function () {
            $('#couponSearch').val('');
            $('#filterStatus').val('');
            $('#filterAppliesTo').val('');

            $('#filterBranches').val(null).trigger('change');

            table.search('');
            table.column(appliesColIndex).search('');
            table.column(branchesColIndex).search('');
            table.column(statusColIndex).search('');
            table.draw();
        });
    });
</script>

@endsection
