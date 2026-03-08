@extends('layouts.master_table')

@section('title', trans('users.title'))

@section('content')
<div class="row">
    <div class="col-12">
        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
            <h4 class="mb-sm-0">{{ trans('users.users_list') }}</h4>
            <div class="page-title-right">
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item"><a href="javascript: void(0);">{{ trans('users.title') }}</a></li>
                    <li class="breadcrumb-item active">{{ trans('users.users_list') }}</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-12">
        <div class="card shadow-sm border-0">
            <div class="card-header border-bottom py-3 d-flex justify-content-between align-items-center">
                <h5 class="mb-0 fw-bold">{{ trans('users.users_list') }}</h5>
                <a href="{{ route('users.create') }}" class="btn btn-primary btn-label waves-effect waves-light btn-sm">
                    <i class="ri-add-line label-icon align-middle fs-16 me-2"></i> {{ trans('users.title_create') }}
                </a>
            </div>

            <div class="card-body">

                {{-- Alert Messages --}}
                @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <strong>{{ trans('main_trans.success') }}!</strong> {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                @endif

                @if(session('error'))
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <strong>{{ trans('main_trans.error') }}!</strong> {{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                @endif

                {{-- Search Bar --}}
                <div class="card border shadow-none mb-3">
                    <div class="card-body p-3">
                        <div class="row g-2 align-items-center">
                            <div class="col-md-4">
                                <label class="form-label mb-1">{{ trans('employees.search') ?? 'بحث' }}</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-end-0">
                                        <i class="ri-search-line"></i>
                                    </span>
                                    <input type="text" class="form-control border-start-0" id="users_global_search"
                                        placeholder="{{ trans('users.search_hint') ?? 'ابحث بالاسم / البريد' }}">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="table-responsive">
                    <table id="users-datatable"
                        class="table table-bordered dt-responsive nowrap table-striped align-middle"
                        style="width:100%">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>{{ trans('users.name') }}</th>
                                <th>{{ trans('users.email') }}</th>
                                <th>{{ trans('users.status') }}</th>
                                <th>{{ trans('users.roles') }}</th>
                                <th>{{ trans('users.branch') }}</th>
                                <th>{{ trans('users.employee') }}</th>
                                <th style="width: 15%">{{ trans('users.actions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($users as $index => $user)
                            <tr>
                                <td>{{ $index + 1 }}</td>

                                <td class="fw-medium">{{ $user->name }}</td>

                                <td>{{ $user->email }}</td>

                                {{-- Status --}}
                                <td>
                                    @if($user->Status == 'enabled')
                                        <span class="badge bg-success">{{ trans('users.status_active') }}</span>
                                    @else
                                        <span class="badge bg-danger">{{ trans('users.status_inactive') }}</span>
                                    @endif
                                </td>

                                {{-- ✅ Roles --}}
<td>
    @forelse($user->roles as $role)
        <span class="badge bg-primary me-1">{{ $role->name }}</span>
    @empty
        <span class="text-muted">-</span>
    @endforelse
</td>


                                {{-- Branch --}}
                                <td>
                                    @php
                                        $branchName = '-';
                                        if ($user->branch) {
                                            $n = $user->branch->name;
                                            $branchName = is_array($n)
                                                ? ($n[app()->getLocale()] ?? $n['ar'] ?? $n['en'] ?? '-')
                                                : ($n ?? '-');
                                        }
                                    @endphp
                                    {{ $branchName }}
                                </td>

                                {{-- Employee --}}
                                <td>{{ $user->employee ? $user->employee->full_name : '-' }}</td>

                                {{-- ✅ Actions: show + edit + delete --}}
                                <td>
                                    <div class="d-flex gap-2 flex-wrap">

                                        {{-- Show --}}
                                        <a href="{{ route('users.show', $user->id) }}"
                                            class="btn btn-sm btn-soft-success btn-icon"
                                            title="{{ trans('users.show') ?? 'عرض' }}">
                                            <i class="ri-eye-line"></i>
                                        </a>

                                        {{-- Edit --}}
                                        <a href="{{ route('users.edit', $user->id) }}"
                                            class="btn btn-sm btn-soft-info btn-icon"
                                            title="{{ trans('users.edit') }}">
                                            <i class="ri-pencil-line"></i>
                                        </a>

                                        {{-- Delete --}}
                                        <form action="{{ route('users.destroy', $user->id) }}" method="POST"
                                            onsubmit="return confirm('{{ trans('users.delete_confirm') }}')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                class="btn btn-sm btn-soft-danger btn-icon"
                                                title="{{ trans('users.delete') }}">
                                                <i class="ri-delete-bin-line"></i>
                                            </button>
                                        </form>

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
</div>

<script>
    $(document).ready(function () {
        if ($.fn.DataTable) {
            var table = $('#users-datatable').DataTable({
                responsive: true,
                dom: 'lrtip',
                language: {
                    url: "{{ app()->getLocale() == 'ar' ? '//cdn.datatables.net/plug-ins/1.13.6/i18n/ar.json' : '' }}"
                }
            });

            $('#users_global_search').on('keyup', function () {
                table.search(this.value).draw();
            });
        }
    });
</script>
@endsection
