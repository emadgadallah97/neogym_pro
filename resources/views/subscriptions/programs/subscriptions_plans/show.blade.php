@extends('layouts.master_table')
@section('title')
{{ trans('main_trans.title') }}
@stop





@section('content')





<div class="row">
    <div class="col-12">
        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
            <h4 class="mb-sm-0">{{ trans('subscriptions.plan_details') }}</h4>
            <div class="page-title-right">
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item"><a href="{{ route('subscriptions_plans.index') }}">{{ trans('subscriptions.subscriptions_plans') }}</a></li>
                    <li class="breadcrumb-item active">{{ trans('subscriptions.plan_details') }}</li>
                </ol>
            </div>
        </div>
    </div>
</div>





@php
    $locale = app()->getLocale();



    $branchDisplayName = function ($branch) use ($locale) {
        // $branch can be array with name as array
        if (is_array($branch) && isset($branch['name'])) {
            if (is_array($branch['name'])) {
                return $branch['name'][$locale] ?? ($branch['name']['ar'] ?? ($branch['name']['en'] ?? ''));
            }
            if (is_string($branch['name'])) {
                $decoded = json_decode($branch['name'], true);
                if (is_array($decoded)) {
                    return $decoded[$locale] ?? ($decoded['ar'] ?? ($decoded['en'] ?? ''));
                }
            }
        }
        return '';
    };



    $branchesCount = is_countable($Branches) ? count($Branches) : 0;
@endphp





<div class="row">
    <div class="col-lg-12">
        <div class="card">



            <div class="card-header">
                <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                    <div>
                        <h5 class="card-title mb-1">{{ trans('subscriptions.plan_details') }} - {{ $Plan->code }}</h5>
                        <div class="d-flex flex-wrap gap-2">
                            @if($Plan->status)
                                <span class="badge bg-success">{{ trans('subscriptions.active') }}</span>
                            @else
                                <span class="badge bg-danger">{{ trans('subscriptions.inactive') }}</span>
                            @endif



                            @if($Plan->type)
                                <span class="badge bg-soft-info text-info">{{ $Plan->type->getTranslation('name', $locale) }}</span>
                            @endif



                            @if($Plan->allow_freeze)
                                <span class="badge bg-soft-dark text-dark">{{ trans('subscriptions.allow_freeze') }}</span>
                            @endif



                            <span class="badge bg-soft-secondary text-secondary">{{ trans('subscriptions.branches') }}: {{ $branchesCount }}</span>
                        </div>
                    </div>



                    <div class="d-flex gap-2">
                        <a href="{{ route('subscriptions_plans.index') }}" class="btn btn-soft-secondary btn-sm">
                            <i class="ri-arrow-go-back-line align-bottom me-1"></i> {{ trans('subscriptions.back') }}
                        </a>
                        <a href="{{ route('subscriptions_plans.edit', $Plan->id) }}" class="btn btn-primary btn-sm">
                            <i class="ri-pencil-fill align-bottom me-1"></i> {{ trans('subscriptions.edit_plan') }}
                        </a>
                    </div>
                </div>
            </div>





            <div class="card-body">



                {{-- Basic information --}}
                <div class="card border mb-3">
                    <div class="card-header bg-soft-primary">
                        <h6 class="mb-0"><i class="ri-information-line align-bottom me-1"></i> {{ trans('subscriptions.section_basic_info') }}</h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">{{ trans('subscriptions.name_ar') }}</label>
                                    <input class="form-control" value="{{ $Plan->getTranslation('name','ar') }}" disabled>
                                </div>
                            </div>



                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">{{ trans('subscriptions.name_en') }}</label>
                                    <input class="form-control" value="{{ $Plan->getTranslation('name','en') }}" disabled>
                                </div>
                            </div>



                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">{{ trans('subscriptions.subscriptions_type') }}</label>
                                    <input class="form-control" value="{{ $Plan->type ? $Plan->type->getTranslation('name', $locale) : '' }}" disabled>
                                </div>
                            </div>



                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">{{ trans('subscriptions.description') }}</label>
                                    <textarea class="form-control" rows="2" disabled>{{ $Plan->description }}</textarea>
                                </div>
                            </div>



                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">{{ trans('subscriptions.notes') }}</label>
                                    <textarea class="form-control" rows="2" disabled>{{ $Plan->notes }}</textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>





                {{-- Sessions & duration --}}
                <div class="card border mb-3">
                    <div class="card-header bg-soft-info">
                        <h6 class="mb-0"><i class="ri-timer-line align-bottom me-1"></i> {{ trans('subscriptions.section_schedule') }}</h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-3">
                                <div class="mb-3">
                                    <label class="form-label">{{ trans('subscriptions.sessions_period_type') }}</label>
                                    <input class="form-control" value="{{ trans('subscriptions.period_' . $Plan->sessions_period_type) }}" disabled>
                                </div>
                            </div>



                            @if($Plan->sessions_period_type == 'other')
                                <div class="col-md-3">
                                    <div class="mb-3">
                                        <label class="form-label">{{ trans('subscriptions.sessions_period_other_label') }}</label>
                                        <input class="form-control" value="{{ $Plan->sessions_period_other_label }}" disabled>
                                    </div>
                                </div>
                            @endif



                            <div class="col-md-3">
                                <div class="mb-3">
                                    <label class="form-label">{{ trans('subscriptions.sessions_count') }}</label>
                                    <input class="form-control" value="{{ $Plan->sessions_count }}" disabled>
                                </div>
                            </div>



                            <div class="col-md-3">
                                <div class="mb-3">
                                    <label class="form-label">{{ trans('subscriptions.duration_days') }}</label>
                                    <input class="form-control" value="{{ $Plan->duration_days }}" disabled>
                                </div>
                            </div>



                            <div class="col-md-12">
                                <div class="mb-0">
                                    <label class="form-label">{{ trans('subscriptions.allowed_training_days') }}</label>
                                    <div class="d-flex flex-wrap gap-1">
                                        @foreach(($Plan->allowed_training_days ?? []) as $d)
                                            <span class="badge bg-soft-primary text-primary">{{ trans('subscriptions.day_' . $d) }}</span>
                                        @endforeach
                                        @if(empty($Plan->allowed_training_days) || count($Plan->allowed_training_days ?? []) == 0)
                                            <span class="text-muted">-</span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>





                {{-- Status & notifications --}}
                <div class="card border mb-3">
                    <div class="card-header bg-soft-success">
                        <h6 class="mb-0"><i class="ri-notification-3-line align-bottom me-1"></i> {{ trans('subscriptions.section_status_notifications') }}</h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-3">
                                <div class="mb-3">
                                    <label class="form-label">{{ trans('subscriptions.status') }}</label>
                                    <input class="form-control" value="{{ $Plan->status ? trans('subscriptions.active') : trans('subscriptions.inactive') }}" disabled>
                                </div>
                            </div>



                            <div class="col-md-3">
                                <div class="mb-3">
                                    <label class="form-label">{{ trans('subscriptions.notify_before_end') }}</label>
                                    <input class="form-control" value="{{ $Plan->notify_before_end ? trans('subscriptions.yes') : trans('subscriptions.no') }}" disabled>
                                </div>
                            </div>



                            @if($Plan->notify_before_end)
                                <div class="col-md-3">
                                    <div class="mb-3">
                                        <label class="form-label">{{ trans('subscriptions.notify_days_before_end') }}</label>
                                        <input class="form-control" value="{{ $Plan->notify_days_before_end }}" disabled>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>





                {{-- Freeze --}}
                <div class="card border mb-3">
                    <div class="card-header bg-soft-dark">
                        <h6 class="mb-0"><i class="ri-snowy-line align-bottom me-1"></i> {{ trans('subscriptions.allow_freeze') }}</h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-3">
                                <div class="mb-3">
                                    <label class="form-label">{{ trans('subscriptions.allow_freeze') }}</label>
                                    <input class="form-control" value="{{ $Plan->allow_freeze ? trans('subscriptions.yes') : trans('subscriptions.no') }}" disabled>
                                </div>
                            </div>



                            @if($Plan->allow_freeze)
                                <div class="col-md-3">
                                    <div class="mb-3">
                                        <label class="form-label">{{ trans('subscriptions.max_freeze_days') }}</label>
                                        <input class="form-control" value="{{ $Plan->max_freeze_days }}" disabled>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>





                {{-- Guests --}}
                <div class="card border mb-3">
                    <div class="card-header bg-soft-warning">
                        <h6 class="mb-0"><i class="ri-user-add-line align-bottom me-1"></i> {{ trans('subscriptions.section_guest') }}</h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-3">
                                <div class="mb-3">
                                    <label class="form-label">{{ trans('subscriptions.allow_guest') }}</label>
                                    <input class="form-control" value="{{ $Plan->allow_guest ? trans('subscriptions.yes') : trans('subscriptions.no') }}" disabled>
                                </div>
                            </div>



                            @if($Plan->allow_guest)
                                <div class="col-md-3">
                                    <div class="mb-3">
                                        <label class="form-label">{{ trans('subscriptions.guest_people_count') }}</label>
                                        <input class="form-control" value="{{ $Plan->guest_people_count }}" disabled>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="mb-3">
                                        <label class="form-label">{{ trans('subscriptions.guest_times_count') }}</label>
                                        <input class="form-control" value="{{ $Plan->guest_times_count }}" disabled>
                                    </div>
                                </div>



                                <div class="col-md-12">
                                    <div class="mb-0">
                                        <label class="form-label">{{ trans('subscriptions.guest_allowed_days') }}</label>
                                        <div class="d-flex flex-wrap gap-1">
                                            @foreach(($Plan->guest_allowed_days ?? []) as $d)
                                                <span class="badge bg-soft-warning text-warning">{{ trans('subscriptions.day_' . $d) }}</span>
                                            @endforeach
                                            @if(empty($Plan->guest_allowed_days) || count($Plan->guest_allowed_days ?? []) == 0)
                                                <span class="text-muted">-</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>





                {{-- Branches & pricing --}}
                <div class="card border mb-0">
                    <div class="card-header bg-soft-secondary">
                        <h6 class="mb-0"><i class="ri-store-2-line align-bottom me-1"></i> {{ trans('subscriptions.branches_pricing') }}</h6>
                    </div>
                    <div class="card-body">
                        @if(!$branchesCount)
                            <div class="alert alert-info mb-0">{{ trans('subscriptions.pricing_select_branch_first') }}</div>
                        @endif



                        @foreach($Branches as $Branch)
                            @php
                                $branchKey = $Branch['id'] ?? ($Branch['branch_id'] ?? $loop->index);
                            @endphp

                            <div class="card mb-3">
                                <div class="card-header d-flex align-items-center justify-content-between">
                                    <h6 class="mb-0">{{ $branchDisplayName($Branch) }}</h6>
                                    <span class="badge bg-soft-primary text-primary">{{ trans('subscriptions.pricing') }}</span>
                                </div>
                                <div class="card-body">



                                    <div class="row">
                                        <div class="col-md-4">
                                            <div class="mb-3">
                                                <label class="form-label">{{ trans('subscriptions.price_without_trainer') }}</label>
                                                <input class="form-control" value="{{ $Branch['price_without_trainer'] }}" disabled>
                                            </div>
                                        </div>



                                        <div class="col-md-4">
                                            <div class="mb-3">
                                                <label class="form-label">{{ trans('subscriptions.trainer_pricing_mode') }}</label>
                                                <input class="form-control" value="{{ trans('subscriptions.trainer_mode_' . $Branch['trainer_pricing_mode']) }}" disabled>
                                            </div>
                                        </div>



                                        @if($Branch['trainer_pricing_mode'] == 'uniform')
                                            <div class="col-md-4">
                                                <div class="mb-3">
                                                    <label class="form-label">{{ trans('subscriptions.trainer_uniform_price') }}</label>
                                                    <input class="form-control" value="{{ $Branch['trainer_uniform_price'] }}" disabled>
                                                </div>
                                            </div>
                                        @endif



                                        @if($Branch['trainer_pricing_mode'] == 'exceptions')
                                            <div class="col-md-4">
                                                <div class="mb-3">
                                                    <label class="form-label">{{ trans('subscriptions.trainer_default_price') }}</label>
                                                    <input class="form-control" value="{{ $Branch['trainer_default_price'] }}" disabled>
                                                </div>
                                            </div>
                                        @endif
                                    </div>



                                    {{-- Coaches table search + pagination (client side) --}}
                                    <div class="coach-table-wrapper" data-branch="{{ $branchKey }}" data-per-page="5">
                                        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-2">
                                            <div class="d-flex align-items-center gap-2">
                                                <label class="form-label mb-0">{{ trans('subscriptions.coach') }}</label>
                                                <div class="search-box">
                                                    <input type="text" class="form-control form-control-sm coach-search" placeholder="{{ trans('subscriptions.search') }}">
                                                </div>
                                            </div>



                                            <div class="d-flex align-items-center gap-2">
                                                <button type="button" class="btn btn-soft-secondary btn-sm coach-prev">
                                                    <i class="ri-arrow-left-s-line align-bottom"></i>
                                                </button>
                                                <span class="text-muted small coach-page-info">1 / 1</span>
                                                <button type="button" class="btn btn-soft-secondary btn-sm coach-next">
                                                    <i class="ri-arrow-right-s-line align-bottom"></i>
                                                </button>
                                                <span class="badge bg-soft-info text-info">{{ trans('subscriptions.per_page') }}: 5</span>
                                            </div>
                                        </div>



                                        <div class="table-responsive">
                                            <table class="table table-bordered align-middle coach-table" data-branch="{{ $branchKey }}">
                                                <thead class="table-light">
                                                    <tr>
                                                        <th>{{ trans('subscriptions.coach') }}</th>
                                                        <th style="width:150px;">{{ trans('subscriptions.is_included') }}</th>
                                                        <th style="width:200px;">{{ trans('subscriptions.final_coach_price') }}</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach($Branch['coaches'] as $c)
                                                        <tr class="coach-row" data-coach-name="{{ $c['name'] }}">
                                                            <td>{{ $c['name'] }}</td>
                                                            <td>
                                                                @if($c['is_included'])
                                                                    <span class="badge bg-success">{{ trans('subscriptions.yes') }}</span>
                                                                @else
                                                                    <span class="badge bg-danger">{{ trans('subscriptions.no') }}</span>
                                                                @endif
                                                            </td>
                                                            <td>
                                                                @if(!$c['is_included'])
                                                                    <span class="text-muted">-</span>
                                                                @else
                                                                    @php
                                                                        $final_price = null;
                                                                        if ($Branch['trainer_pricing_mode'] == 'uniform') {
                                                                            $final_price = $Branch['trainer_uniform_price'];
                                                                        } elseif ($Branch['trainer_pricing_mode'] == 'exceptions') {
                                                                            $final_price = $c['price'] !== null ? $c['price'] : $Branch['trainer_default_price'];
                                                                        } else {
                                                                            $final_price = $c['price'];
                                                                        }
                                                                    @endphp



                                                                    {{ $final_price }}
                                                                @endif
                                                            </td>
                                                        </tr>
                                                    @endforeach



                                                    <tr class="coach-no-results d-none">
                                                        <td colspan="3" class="text-center text-muted">{{ trans('subscriptions.no_results') }}</td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>



                                </div>
                            </div>
                        @endforeach



                    </div>
                </div>



            </div>





        </div>
    </div>
</div>





<script>
    (function(){
        function normalizeStr(s){
            return String(s ?? '').toLowerCase().trim();
        }


        function render(wrapper){
            const perPage = parseInt(wrapper.getAttribute('data-per-page') || '5');
            const q = normalizeStr(wrapper.__q || '');
            const page = Math.max(1, parseInt(wrapper.__page || 1));

            const rows = Array.from(wrapper.querySelectorAll('tbody .coach-row'));
            const noResults = wrapper.querySelector('tbody .coach-no-results');

            const filtered = rows.filter(function(tr){
                const name = normalizeStr(tr.getAttribute('data-coach-name') || tr.innerText);
                return !q || name.includes(q);
            });

            const total = filtered.length;
            const pages = Math.max(1, Math.ceil(total / perPage));
            const safePage = Math.min(page, pages);

            wrapper.__page = safePage;

            rows.forEach(tr => tr.classList.add('d-none'));

            if (total === 0) {
                if (noResults) noResults.classList.remove('d-none');
            } else {
                if (noResults) noResults.classList.add('d-none');

                const start = (safePage - 1) * perPage;
                const slice = filtered.slice(start, start + perPage);
                slice.forEach(tr => tr.classList.remove('d-none'));
            }

            const info = wrapper.querySelector('.coach-page-info');
            const prevBtn = wrapper.querySelector('.coach-prev');
            const nextBtn = wrapper.querySelector('.coach-next');

            if (info) info.textContent = `${safePage} / ${pages} ( ${total} )`;
            if (prevBtn) prevBtn.disabled = (safePage <= 1);
            if (nextBtn) nextBtn.disabled = (safePage >= pages);
        }


        function bind(wrapper){
            wrapper.__q = wrapper.__q || '';
            wrapper.__page = wrapper.__page || 1;

            const search = wrapper.querySelector('.coach-search');
            const prevBtn = wrapper.querySelector('.coach-prev');
            const nextBtn = wrapper.querySelector('.coach-next');

            if (search) {
                search.addEventListener('input', function(){
                    wrapper.__q = this.value || '';
                    wrapper.__page = 1;
                    render(wrapper);
                });
            }

            if (prevBtn) {
                prevBtn.addEventListener('click', function(){
                    wrapper.__page = Math.max(1, parseInt(wrapper.__page || 1) - 1);
                    render(wrapper);
                });
            }

            if (nextBtn) {
                nextBtn.addEventListener('click', function(){
                    wrapper.__page = parseInt(wrapper.__page || 1) + 1;
                    render(wrapper);
                });
            }

            render(wrapper);
        }


        document.addEventListener('DOMContentLoaded', function(){
            document.querySelectorAll('.coach-table-wrapper').forEach(function(wrapper){
                bind(wrapper);
            });
        });
    })();
</script>





@endsection
