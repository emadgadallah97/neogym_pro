@extends('layouts.master_table')
@section('title')
{{ trans('main_trans.title') }}
@stop







@section('content')






<div class="row">
    <div class="col-12">
        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
            <h4 class="mb-sm-0">{{ trans('subscriptions.subscriptions_plans') }}</h4>
            <div class="page-title-right">
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item"><a href="javascript: void(0);">{{ trans('subscriptions.subscriptions') }}</a></li>
                    <li class="breadcrumb-item active">{{ trans('subscriptions.subscriptions_plans') }}</li>
                </ol>
            </div>
        </div>
    </div>
</div>






@php
    // KPIs
    $totalPlans = $SubscriptionsPlans->count();
    $activePlans = $SubscriptionsPlans->where('status', 1)->count();
    $inactivePlans = $SubscriptionsPlans->where('status', 0)->count();
    $lastCreatedAt = $SubscriptionsPlans->max('created_at');

    // Types list (for filter)
    $Types = $SubscriptionsPlans->pluck('type')->filter()->unique('id')->sortBy('id');

    // Branches list (for filter + column)
    $BranchesList = DB::table('branches')
        ->select(['id','name'])
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
        <div class="card">
            <div class="card-body pb-0">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <h5 class="card-title mb-1">{{ trans('subscriptions.plans_kpis') }}</h5>
                        <p class="text-muted mb-0">{{ trans('subscriptions.quick_filters') }}</p>
                    </div>

                    <div class="d-flex gap-2">
                        <a href="{{ route('subscriptions_plans.create') }}" class="btn btn-primary">
                            <i class="ri-add-line align-bottom me-1"></i> {{ trans('subscriptions.add_new_plan') }}
                        </a>
                    </div>
                </div>

                <div class="row mt-3 g-3">
                    <div class="col-md-6 col-xl-4">
                        <div class="card mb-0 border">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="avatar-sm flex-shrink-0">
                                        <span class="avatar-title rounded bg-soft-primary text-primary">
                                            <i class="ri-coupon-3-line fs-20"></i>
                                        </span>
                                    </div>
                                    <div class="flex-grow-1 ms-3">
                                        <p class="text-muted mb-1">{{ trans('subscriptions.total_plans') }}</p>
                                        <h4 class="mb-0">{{ $totalPlans }}</h4>
                                        @if($lastCreatedAt)
                                            <small class="text-muted d-block">{{ trans('subscriptions.last_created') }}: {{ $lastCreatedAt }}</small>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6 col-xl-4">
                        <div class="card mb-0 border">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="avatar-sm flex-shrink-0">
                                        <span class="avatar-title rounded bg-soft-success text-success">
                                            <i class="ri-check-double-line fs-20"></i>
                                        </span>
                                    </div>
                                    <div class="flex-grow-1 ms-3">
                                        <p class="text-muted mb-1">{{ trans('subscriptions.active_plans') }}</p>
                                        <h4 class="mb-0">{{ $activePlans }}</h4>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6 col-xl-4">
                        <div class="card mb-0 border">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="avatar-sm flex-shrink-0">
                                        <span class="avatar-title rounded bg-soft-danger text-danger">
                                            <i class="ri-close-circle-line fs-20"></i>
                                        </span>
                                    </div>
                                    <div class="flex-grow-1 ms-3">
                                        <p class="text-muted mb-1">{{ trans('subscriptions.inactive_plans') }}</p>
                                        <h4 class="mb-0">{{ $inactivePlans }}</h4>
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
                        <label class="form-label mb-1">{{ trans('subscriptions.search') }}</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="ri-search-line"></i></span>
                            <input type="text" id="planSearch" class="form-control" placeholder="{{ trans('subscriptions.search_here') }}">
                        </div>
                    </div>

                    <div class="col-md-6 col-lg-2">
                        <label class="form-label mb-1">{{ trans('subscriptions.filter_status') }}</label>
                        <select id="filterStatus" class="form-select">
                            <option value="">{{ trans('subscriptions.all') }}</option>
                            <option value="1">{{ trans('subscriptions.active') }}</option>
                            <option value="0">{{ trans('subscriptions.inactive') }}</option>
                        </select>
                    </div>

                    <div class="col-md-6 col-lg-3">
                        <label class="form-label mb-1">{{ trans('subscriptions.filter_type') }}</label>
                        <select id="filterType" class="form-select">
                            <option value="">{{ trans('subscriptions.all') }}</option>
                            @foreach($Types as $Type)
                                <option value="{{ $Type->id }}">{{ $Type->getTranslation('name', app()->getLocale()) }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-6 col-lg-3">
                        <label class="form-label mb-1">{{ trans('subscriptions.filter_branches') }}</label>
                        <select id="filterBranches" class="form-select" multiple>
                            @foreach($BranchesList as $b)
                                <option value="{{ $b->id }}">{{ $branchName($b->name) }}</option>
                            @endforeach
                        </select>
                        <small class="text-muted">{{ trans('subscriptions.multi_select_hint') }}</small>
                    </div>

                    <div class="col-12 text-end">
                        <button type="button" id="clearFilters" class="btn btn-soft-secondary">
                            <i class="ri-refresh-line align-bottom me-1"></i> {{ trans('subscriptions.clear_filters') }}
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
                    <h5 class="card-title mb-0">{{ trans('subscriptions.subscriptions_plans') }}</h5>

                    {{-- Message --}}
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
                            <th data-ordering="false">{{ trans('subscriptions.sr_no') }}</th>
                            <th data-ordering="false">{{ trans('subscriptions.plan_code') }}</th>
                            <th data-ordering="false">{{ trans('subscriptions.name_ar') }}</th>
                            <th data-ordering="false">{{ trans('subscriptions.name_en') }}</th>
                            <th data-ordering="false">{{ trans('subscriptions.subscriptions_type') }}</th>
                            <th data-ordering="false">{{ trans('subscriptions.branches') }}</th>
                            <th data-ordering="false">{{ trans('subscriptions.status') }}</th>
                            <th>{{ trans('subscriptions.create_date') }}</th>
                            <th>{{ trans('subscriptions.action') }}</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php $i=0; ?>
                        @foreach($SubscriptionsPlans as $Plan)
                            <?php $i++; ?>
                            @php
                                $planBranchIds = DB::table('subscriptions_plan_branches')
                                    ->where('subscriptions_plan_id', $Plan->id)
                                    ->pluck('branch_id')
                                    ->toArray();

                                $planBranchesDb = DB::table('branches')
                                    ->whereIn('id', $planBranchIds)
                                    ->orderBy('id')
                                    ->get();

                                $planBranchNames = [];
                                foreach ($planBranchesDb as $pb) {
                                    $planBranchNames[] = $branchName($pb->name);
                                }
                            @endphp
                            <tr>
                                <td>{{$i}}</td>
                                <td>
                                    <span class="badge bg-soft-primary text-primary">{{$Plan->code}}</span>
                                </td>
                                <td class="text-truncate" style="max-width:220px;">
                                    {{$Plan->getTranslation('name','ar')}}
                                </td>
                                <td class="text-truncate" style="max-width:220px;">
                                    {{$Plan->getTranslation('name','en')}}
                                </td>
                                <td>
                                    {{-- Hidden token for filtering type --}}
                                    <span class="d-none">__TYPE__{{ (int)$Plan->subscriptions_type_id }}__</span>

                                    @if($Plan->type)
                                        {{$Plan->type->getTranslation('name', app()->getLocale())}}
                                    @endif
                                </td>
                                <td>
                                    {{-- Hidden tokens for filtering branches --}}
                                    <span class="d-none">
                                        @foreach($planBranchIds as $bid)
                                            __BRANCH__{{ (int)$bid }}__
                                        @endforeach
                                    </span>

                                    @if(count($planBranchNames))
                                        @foreach($planBranchNames as $bn)
                                            <span class="badge bg-soft-info text-info me-1 mb-1">{{ $bn }}</span>
                                        @endforeach
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td>
                                    {{-- Hidden token for filtering status --}}
                                    <span class="d-none">__STATUS__{{ $Plan->status ? 1 : 0 }}__</span>

                                    @if($Plan->status)
                                        <span class="badge bg-success">{{ trans('subscriptions.active') }}</span>
                                    @else
                                        <span class="badge bg-danger">{{ trans('subscriptions.inactive') }}</span>
                                    @endif
                                </td>
                                <td>{{$Plan->created_at}}</td>
                                <td>
                                    <div class="btn-group" role="group">
                                        <a href="{{ route('subscriptions_plans.show', $Plan->id) }}" class="btn btn-soft-primary btn-sm" title="{{ trans('subscriptions.view') }}">
                                            <i class="ri-eye-fill align-bottom"></i>
                                        </a>
                                        <a href="{{ route('subscriptions_plans.edit', $Plan->id) }}" class="btn btn-soft-secondary btn-sm" title="{{ trans('subscriptions.edit') }}">
                                            <i class="ri-pencil-fill align-bottom"></i>
                                        </a>
                                        <button type="button" data-bs-toggle="modal" data-bs-target="#deleteModal{{$Plan->id}}" class="btn btn-soft-danger btn-sm" title="{{ trans('subscriptions.delete') }}">
                                            <i class="ri-delete-bin-fill align-bottom"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>

                            <!-- delete -->
                            <div id="deleteModal{{$Plan->id}}" class="modal fade" tabindex="-1" aria-hidden="true" style="display: none;">
                                <div class="modal-dialog modal-dialog-centered">
                                    <div class="modal-content">
                                        <div class="modal-body text-center p-5">
                                            <lord-icon src="{{URL::asset('assets/images/icon/tdrtiskw.json')}}" trigger="loop"
                                                colors="primary:#f7b84b,secondary:#405189" style="width:130px;height:130px">
                                            </lord-icon>

                                            <div class="mt-4 pt-4">
                                                <h4>{{ trans('subscriptions.massagedelete_d') }}!</h4>
                                                <p class="text-muted">{{ trans('subscriptions.massagedelete_p') }} {{$Plan->getTranslation('name','ar')}}</p>

                                                <form action="{{ route('subscriptions_plans.destroy', $Plan->id) }}" method="post">
                                                    {{ method_field('delete') }}
                                                    {{ csrf_field() }}

                                                    <input class="form-control" id="id" name="id" value="{{$Plan->id}}" type="hidden">

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






{{-- Filters + Search integration with DataTable + Select2 for branches --}}
<script>
    document.addEventListener('DOMContentLoaded', function () {
        if (typeof $ === 'undefined') {
            return;
        }

        // Select2 for branches (multi)
        if ($.fn && $.fn.select2) {
            var isRtl = $('html').attr('dir') === 'rtl';

            $('#filterBranches').select2({
                width: '100%',
                placeholder: '{{ trans('subscriptions.filter_branches') }}',
                allowClear: true,
                closeOnSelect: false,
                dir: isRtl ? 'rtl' : 'ltr'
            });
        }

        if (!$.fn || !$.fn.DataTable) {
            return;
        }

        var table = $('#example').DataTable();

        // Column indices:
        // 0 sr, 1 code, 2 name_ar, 3 name_en, 4 type, 5 branches, 6 status, 7 created_at, 8 actions
        var statusColIndex = 6;
        var typeColIndex = 4;
        var branchesColIndex = 5;

        $('#planSearch').on('keyup change', function () {
            table.search(this.value).draw();
        });

        $('#filterStatus').on('change', function () {
            var v = $(this).val();
            if (v === '') {
                table.column(statusColIndex).search('').draw();
                return;
            }

            // search for hidden token inside the cell
            table.column(statusColIndex).search('__STATUS__' + v + '__', false, false).draw();
        });

        $('#filterType').on('change', function () {
            var v = $(this).val();
            if (v === '') {
                table.column(typeColIndex).search('').draw();
                return;
            }

            // search for hidden token inside the cell
            table.column(typeColIndex).search('__TYPE__' + v + '__', false, false).draw();
        });

        $('#filterBranches').on('change', function () {
            var selected = $(this).val() || [];

            if (selected.length === 0) {
                table.column(branchesColIndex).search('').draw();
                return;
            }

            // OR between tokens: __BRANCH__1__|__BRANCH__2__
            var tokens = selected.map(function(id){
                return '__BRANCH__' + id + '__';
            });

            var regex = '(?:' + tokens.join('|') + ')';
            table.column(branchesColIndex).search(regex, true, false).draw();
        });

        $('#clearFilters').on('click', function () {
            $('#planSearch').val('');
            $('#filterStatus').val('');
            $('#filterType').val('');

            // reset select2 (if exists) or normal select
            $('#filterBranches').val(null).trigger('change');

            table.search('');
            table.column(statusColIndex).search('');
            table.column(typeColIndex).search('');
            table.column(branchesColIndex).search('');
            table.draw();
        });
    });
</script>


@endsection
