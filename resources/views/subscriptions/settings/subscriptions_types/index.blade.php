@extends('layouts.master_table')
@section('title')
{{ trans('main_trans.title') }}
@stop


@section('content')


<div class="row">
    <div class="col-12">
        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
            <h4 class="mb-sm-0">{{ trans('subscriptions.settings') }}</h4>
            <div class="page-title-right">
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item"><a href="javascript: void(0);">{{ trans('subscriptions.settings') }}</a></li>
                    <li class="breadcrumb-item active">{{ trans('subscriptions.subscriptions_types_settings') }}</li>
                </ol>
            </div>
        </div>
    </div>
</div>


<div class="row">
    <div class="col-lg-12">
        <div class="card">


            <div class="card-header">
                <h5 class="card-title mb-0">{{ trans('subscriptions.subscriptions_types') }}</h5>


                {{-- Message --}}
                @if (Session::has('success'))
                    <div class="alert alert-success alert-dismissible" role="alert">
                        <button type="button" class="close" data-dismiss="alert">
                            <i class="fa fa-times"></i>
                        </button>
                        <strong>Success !</strong> {{ session('success') }}
                    </div>
                @endif


                @if (Session::has('error'))
                    <div class="alert alert-danger alert-dismissible" role="alert">
                        <button type="button" class="close" data-dismiss="alert">
                            <i class="fa fa-times"></i>
                        </button>
                        <strong>Error !</strong> {{ session('error') }}
                    </div>
                @endif


            </div>


            <div class="d-grid gap-2">
                <button data-bs-toggle="modal" data-bs-target="#signupModals" class="btn btn-primary waves-effect waves-light" type="button">
                    {{ trans('subscriptions.add_new_subscriptions_type') }}
                </button>
            </div>


            <div class="card-body">
                <table id="example" class="table table-bordered dt-responsive nowrap table-striped align-middle" style="width:100%">
                    <thead>
                        <tr>
                            <th data-ordering="false">{{ trans('subscriptions.sr_no') }}</th>
                            <th data-ordering="false">{{ trans('subscriptions.id') }}</th>
                            <th data-ordering="false">{{ trans('subscriptions.name_ar') }}</th>
                            <th data-ordering="false">{{ trans('subscriptions.name_en') }}</th>
                            <th data-ordering="false">{{ trans('subscriptions.description') }}</th>
                            <th data-ordering="false">{{ trans('subscriptions.status') }}</th>
                            <th>{{ trans('subscriptions.create_date') }}</th>
                            <th>{{ trans('subscriptions.action') }}</th>
                        </tr>
                    </thead>


                    <tbody>
                        <?php $i=0; ?>
                        @foreach($SubscriptionsTypes as $SubscriptionsType)
                            <tr>
                                <?php $i++; ?>
                                <td>{{$i}}</td>
                                <td>{{$SubscriptionsType->id}}</td>
                                <td>{{$SubscriptionsType->getTranslation('name','ar')}}</td>
                                <td>{{$SubscriptionsType->getTranslation('name','en')}}</td>
                                <td>{{$SubscriptionsType->description}}</td>
                                <td>
                                    @if($SubscriptionsType->status)
                                        <span class="badge bg-success">{{ trans('subscriptions.active') }}</span>
                                    @else
                                        <span class="badge bg-danger">{{ trans('subscriptions.inactive') }}</span>
                                    @endif
                                </td>
                                <td>{{$SubscriptionsType->created_at}}</td>
                                <td>
                                    <div class="dropdown d-inline-block">
                                        <button class="btn btn-soft-secondary btn-sm dropdown" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                            <i class="ri-more-fill align-middle"></i>
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end">
                                            <li>
                                                <button data-bs-toggle="modal" data-bs-target="#signupModals{{$SubscriptionsType->id}}" class="dropdown-item edit-item-btn">
                                                    <i class="ri-pencil-fill align-bottom me-2 text-muted"></i> Edit
                                                </button>
                                            </li>
                                            <li>
                                                <button data-bs-toggle="modal" data-bs-target="#signupModal{{$SubscriptionsType->id}}" class="dropdown-item edit-item-btn">
                                                    <i class="ri-delete-bin-fill align-bottom me-2 text-muted"></i> Delete
                                                </button>
                                            </li>
                                        </ul>
                                    </div>
                                </td>
                            </tr>


                            <!-- edit -->
                            <div id="signupModals{{$SubscriptionsType->id}}" class="modal fade" tabindex="-1" aria-hidden="true" style="display: none;">
                                <div class="modal-dialog modal-dialog-centered">
                                    <div class="modal-content border-0 overflow-hidden">
                                        <div class="modal-header p-3">
                                            <h4 class="card-title mb-0">{{ trans('subscriptions.update_subscriptions_type') }}</h4>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>


                                        <div class="modal-body">
                                            <form action="{{ route('subscriptions_types.update','test') }}" method="post">
                                                {{ method_field('patch') }}
                                                @csrf


                                                <input class="form-control" id="id" name="id" value="{{$SubscriptionsType->id}}" type="hidden">


                                                <div class="form-group mb-3">
                                                    <label for="name_ar" class="form-label">{{ trans('subscriptions.name_ar') }}</label>
                                                    <input type="text" class="form-control" id="name_ar" name="name_ar" value="{{$SubscriptionsType->getTranslation('name','ar')}}">
                                                </div>


                                                <div class="form-group mb-3">
                                                    <label for="name_en" class="form-label">{{ trans('subscriptions.name_en') }}</label>
                                                    <input type="text" class="form-control" id="name_en" name="name_en" value="{{$SubscriptionsType->getTranslation('name','en')}}">
                                                </div>


                                                <div class="form-group mb-3">
                                                    <label class="form-label">{{ trans('subscriptions.description') }}</label>
                                                    <textarea class="form-control" name="description" rows="2">{{$SubscriptionsType->description}}</textarea>
                                                </div>


                                                <div class="form-check mb-3">
                                                    <input class="form-check-input" type="checkbox" name="status" value="1" id="status{{$SubscriptionsType->id}}" {{ $SubscriptionsType->status ? 'checked' : '' }}>
                                                    <label class="form-check-label" for="status{{$SubscriptionsType->id}}">
                                                        {{ trans('subscriptions.status_active') }}
                                                    </label>
                                                </div>


                                                <div class="modal-footer px-0 pb-0">
                                                    <button type="submit" class="btn btn-primary">{{ trans('subscriptions.submit') }}</button>
                                                </div>


                                            </form>
                                        </div>


                                    </div>
                                </div>
                            </div>


                            <!-- delete -->
                            <div id="signupModal{{$SubscriptionsType->id}}" class="modal fade" tabindex="-1" aria-hidden="true" style="display: none;">
                                <div class="modal-dialog modal-dialog-centered">
                                    <div class="modal-content">
                                        <div class="modal-body text-center p-5">
                                            <lord-icon src="{{URL::asset('assets/images/icon/tdrtiskw.json')}}" trigger="loop"
                                                colors="primary:#f7b84b,secondary:#405189" style="width:130px;height:130px">
                                            </lord-icon>


                                            <div class="mt-4 pt-4">
                                                <h4>{{ trans('subscriptions.massagedelete_d') }}!</h4>
                                                <p class="text-muted">{{ trans('subscriptions.massagedelete_p') }} {{$SubscriptionsType->getTranslation('name','ar')}}</p>


                                                <form action="{{ route('subscriptions_types.destroy','test') }}" method="post">
                                                    {{ method_field('delete') }}
                                                    {{ csrf_field() }}


                                                    <input class="form-control" id="id" name="id" value="{{$SubscriptionsType->id}}" type="hidden">


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


<!-- add new -->
<div id="signupModals" class="modal fade" tabindex="-1" aria-hidden="true" style="display: none;">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 overflow-hidden">
            <div class="modal-header p-3">
                <h4 class="card-title mb-0">{{ trans('subscriptions.add_new_subscriptions_type') }}</h4>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>


            <div class="modal-body">
                <form action="{{route('subscriptions_types.store')}}" method="post">
                    {{ csrf_field() }}


                    <div class="mb-3">
                        <label for="name_ar" class="form-label">{{ trans('subscriptions.name_ar') }}</label>
                        <input type="text" class="form-control" id="name_ar" name="name_ar" placeholder="{{ trans('subscriptions.name_ar_enter') }}">
                    </div>


                    <div class="mb-3">
                        <label for="name_en" class="form-label">{{ trans('subscriptions.name_en') }}</label>
                        <input type="text" class="form-control" id="name_en" name="name_en" placeholder="{{ trans('subscriptions.name_en_enter') }}">
                    </div>


                    <div class="mb-3">
                        <label class="form-label">{{ trans('subscriptions.description') }}</label>
                        <textarea class="form-control" name="description" rows="2" placeholder="{{ trans('subscriptions.description_enter') }}"></textarea>
                    </div>


                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" name="status" value="1" id="status_new" checked>
                        <label class="form-check-label" for="status_new">
                            {{ trans('subscriptions.status_active') }}
                        </label>
                    </div>


                    <div class="text-end">
                        <button type="submit" class="btn btn-primary">{{ trans('subscriptions.submit') }}</button>
                    </div>


                </form>
            </div>


        </div>
    </div>
</div>


@endsection
