@extends('layouts.master')
@section('title')
{{ trans('main_trans.title') }}
@stop
@section('content')

@if (session('success'))
<div class="alert alert-success">
    {{ session('success') }}
</div>
@endif

<div class="row">
    <div class="col-12">
        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
            <h4 class="mb-sm-0">{{ trans('user_management_trans.role_management') }}</h4>
            <div class="page-title-right">
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item">
                        <a href="javascript:void(0);">{{ trans('user_management_trans.role') }}</a>
                    </li>
                    <li class="breadcrumb-item active">{{ trans('user_management_trans.role_management') }}</li>
                </ol>
            </div>
        </div>
    </div>
</div>

@if (session()->has('Add'))
<script>
    window.onload = function() {
        notif({
            msg: "{{ trans('user_management_trans.added_successfully') }}",
            type: "success"
        });
    }
</script>
@endif

@if (session()->has('edit'))
<script>
    window.onload = function() {
        notif({
            msg: "{{ trans('user_management_trans.updated_successfully') }}",
            type: "success"
        });
    }
</script>
@endif

@if (session()->has('delete'))
<script>
    window.onload = function() {
        notif({
            msg: "{{ trans('user_management_trans.deleted_successfully') }}",
            type: "error"
        });
    }
</script>
@endif

<div class="row row-sm">
    <div class="col-xl-12">
        <div class="card">
            <div class="card-header pb-0">
                <div class="d-flex justify-content-between">
                    <div class="col-lg-12 margin-tb">
                        <div class="pull-right">
                            <a style="margin: 10px;" class="btn btn-outline-success waves-effect waves-light shadow-none"
                                href="{{ route('roles.create') }}">
                                {{ trans('user_management_trans.role_create') }}
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card-body">
                <div class="table-responsive">
                    <table id="example"
                        class="table table-bordered dt-responsive nowrap table-striped align-middle"
                        style="width:100%">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>{{ trans('user_management_trans.name') }}</th>
                                <th>{{ trans('user_management_trans.processes') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php $i = 0; @endphp
                            @foreach ($roles as $key => $role)
                            <tr>
                                <td>{{ ++$i }}</td>
                                <td>{{ $role->name }}</td>
                                <td>
                                    <a class="btn btn-success btn-sm"
                                        href="{{ route('roles.show', $role->id) }}">{{ trans('user_management_trans.show') }}</a>

                                    <a class="btn btn-primary btn-sm"
                                        href="{{ route('roles.edit', $role->id) }}">{{ trans('user_management_trans.edit') }}</a>

                                    @if ($role->name !== 'owner')
                                    <form action="{{ route('roles.destroy', $role->id) }}"
                                        method="POST" style="display:inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-danger btn-sm"
                                            onclick="return confirm('{{ trans('user_management_trans.delete_confirm') }}')">
                                            {{ trans('user_management_trans.delete') }}
                                        </button>
                                    </form>
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('js')
<script src="{{ URL::asset('assets/plugins/notify/js/notifIt.js') }}"></script>
<script src="{{ URL::asset('assets/plugins/notify/js/notifit-custom.js') }}"></script>
@endsection