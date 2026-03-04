@extends('layouts.master_table')

@section('title', trans('crm.seg_prospects'))

@section('content')
{{-- Page Title --}}
<div class="row">
    <div class="col-12">
        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
            <h4 class="mb-sm-0 font">
                <i class="fas fa-user-tag me-1"></i>
                {{ trans('crm.seg_prospects') }}
            </h4>
            <div class="page-title-right">
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item"><a href="{{ route('crm.dashboard') }}">{{ trans('crm.dashboard_title') }}</a></li>
                    <li class="breadcrumb-item active">{{ trans('crm.seg_prospects') }}</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<div class="container-fluid py-3" dir="rtl">

    {{-- ── Header Actions ──────────────────────────────────────── --}}
    <div class="d-flex align-items-center justify-content-between mb-3">
        <div>
            <h5 class="fw-bold mb-1">الأعضاء المحتملون</h5>
            <small class="text-muted">إجمالي: {{ number_format($prospects->total()) }} محتمل</small>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('crm.prospects.import') }}" class="btn btn-outline-success btn-sm">
                <i class="fas fa-file-excel me-1"></i>
                رفع Excel
            </a>
            <a href="{{ route('crm.prospects.create') }}" class="btn btn-primary btn-sm">
                <i class="fas fa-plus me-1"></i>
                عضو محتمل جديد
            </a>
        </div>
    </div>

    {{-- ── Filters ─────────────────────────────────────────────── --}}
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body">
            <form method="GET" action="{{ route('crm.prospects.index') }}" id="filterForm">
                <div class="row g-2 align-items-end">

                    {{-- بحث --}}
                    <div class="col-md-3">
                        <label class="form-label small fw-semibold">بحث</label>
                        <input type="text"
                               name="search"
                               value="{{ request('search') }}"
                               class="form-control form-control-sm"
                               placeholder="الاسم، الهاتف، البريد...">
                    </div>

                    {{-- الفرع --}}
                    <div class="col-md-2">
                        <label class="form-label small fw-semibold">الفرع</label>
                        <select name="branch_id" class="form-select form-select-sm">
                            <option value="">الكل</option>
                            @foreach($branches as $branch)
                                <option value="{{ $branch->id }}"
                                    {{ request('branch_id') == $branch->id ? 'selected' : '' }}>
                                    {{ $branch->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- الجنس --}}
                    <div class="col-md-2">
                        <label class="form-label small fw-semibold">الجنس</label>
                        <select name="gender" class="form-select form-select-sm">
                            <option value="">الكل</option>
                            <option value="male"   {{ request('gender') === 'male'   ? 'selected' : '' }}>ذكر</option>
                            <option value="female" {{ request('gender') === 'female' ? 'selected' : '' }}>أنثى</option>
                        </select>
                    </div>

                    {{-- ✅ تاريخ الإضافة (من) --}}
                    <div class="col-md-2">
                        <label class="form-label small fw-semibold">تاريخ الإضافة (من)</label>
                        <input type="date"
                               name="created_from"
                               value="{{ request('created_from') }}"
                               class="form-control form-control-sm">
                    </div>

                    {{-- ✅ تاريخ الإضافة (إلى) --}}
                    <div class="col-md-2">
                        <label class="form-label small fw-semibold">تاريخ الإضافة (إلى)</label>
                        <input type="date"
                               name="created_to"
                               value="{{ request('created_to') }}"
                               class="form-control form-control-sm">
                    </div>

                    {{-- حالة المتابعة --}}
                    <div class="col-md-2">
                        <label class="form-label small fw-semibold">حالة المتابعة</label>
                        <select name="followup_status" class="form-select form-select-sm">
                            <option value="">الكل</option>
                            <option value="no_followup" {{ request('followup_status') === 'no_followup' ? 'selected' : '' }}>بدون متابعة</option>
                            <option value="pending"     {{ request('followup_status') === 'pending'     ? 'selected' : '' }}>متابعة مفتوحة</option>
                            <option value="overdue"     {{ request('followup_status') === 'overdue'     ? 'selected' : '' }}>متأخرة</option>
                        </select>
                    </div>

                    {{-- أزرار --}}
                    <div class="col-md-3 d-flex gap-2">
                        <button type="submit" class="btn btn-primary btn-sm flex-fill">
                            <i class="fas fa-search"></i> بحث
                        </button>
                        <a href="{{ route('crm.prospects.index') }}" class="btn btn-outline-secondary btn-sm">
                            <i class="fas fa-redo"></i>
                        </a>
                    </div>

                </div>
            </form>
        </div>
    </div>

    {{-- ── Table ───────────────────────────────────────────────── --}}
    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            @if($prospects->isEmpty())
                <div class="text-center py-5 text-muted">
                    <i class="fas fa-inbox fa-3x mb-3"></i>
                    <p class="mb-0">لا يوجد أعضاء محتملون حالياً</p>
                    <a href="{{ route('crm.prospects.create') }}" class="btn btn-sm btn-primary mt-3">
                        <i class="fas fa-plus me-1"></i> إضافة أول عضو محتمل
                    </a>
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th style="width:5%">#</th>
                                <th>الاسم</th>
                                <th>الفرع</th>
                                <th>الهاتف</th>
                                <th>واتساب</th>
                                <th>المتابعات</th>
                                <th>آخر متابعة</th>
                                <th>تاريخ الإضافة</th>
                                <th style="width:12%">إجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($prospects as $prospect)
                            <tr>
                                <td class="text-muted small">{{ $loop->iteration + $prospects->firstItem() - 1 }}</td>
                                <td>
                                    <div class="fw-semibold">
                                        <a href="{{ route('crm.prospects.show', $prospect->id) }}"
                                           class="text-decoration-none text-dark">
                                            {{ $prospect->full_name }}
                                        </a>
                                    </div>
                                    @if($prospect->gender)
                                        <small class="text-muted">
                                            <i class="fas fa-{{ $prospect->gender === 'male' ? 'mars ' : 'venus text-danger' }}"></i>
                                            {{ $prospect->gender === 'male' ? 'ذكر' : 'أنثى' }}
                                        </small>
                                    @endif
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark">{{ $prospect->branch->name ?? '—' }}</span>
                                </td>
                                <td>
                                    <a href="tel:{{ $prospect->phone }}" class="text-decoration-none">
                                        <i class="fas fa-phone fa-xs  me-1"></i>
                                        {{ $prospect->phone }}
                                    </a>
                                </td>
                                <td>
                                    @if($prospect->whatsapp)
                                        <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $prospect->whatsapp) }}"
                                           target="_blank"
                                           class="btn btn-xs btn-success py-0 px-2"
                                           title="فتح واتساب">
                                            <i class="fab fa-whatsapp"></i>
                                        </a>
                                    @else
                                        <span class="text-muted small">—</span>
                                    @endif
                                </td>
                                <td>
                                    @if($prospect->followups_count > 0)
                                        <span class="badge bg-primary">{{ $prospect->followups_count }}</span>
                                    @else
                                        <span class="badge bg-light text-muted">0</span>
                                    @endif
                                </td>
                                <td>
                                    @php
                                        $lastFollowup = $prospect->followups->first();
                                    @endphp
                                    @if($lastFollowup)
                                        <div class="small">
                                            <span class="badge bg-{{ $lastFollowup->status_badge_class }}">
                                                {{ $lastFollowup->status_label }}
                                            </span>
                                            @if($lastFollowup->is_overdue)
                                                <span class="badge bg-danger ms-1">متأخر</span>
                                            @endif
                                        </div>
                                        <small class="text-muted">{{ $lastFollowup->next_action_at?->diffForHumans() }}</small>
                                    @else
                                        <span class="text-muted small">لا توجد</span>
                                    @endif
                                </td>
                                <td>
                                    <small class="text-muted">{{ $prospect->created_at->format('Y-m-d') }}</small>
                                </td>
                                <td>
                                    <div class="d-flex gap-1">
                                        <a href="{{ route('crm.prospects.show', $prospect->id) }}"
                                           class="btn btn-sm btn-outline-primary"
                                           title="عرض">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="{{ route('crm.prospects.edit', $prospect->id) }}"
                                           class="btn btn-sm btn-outline-warning"
                                           title="تعديل">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <form action="{{ route('crm.prospects.destroy', $prospect->id) }}"
                                              method="POST"
                                              class="d-inline"
                                              onsubmit="return confirm('هل أنت متأكد من حذف هذا العضو المحتمل؟')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                    class="btn btn-sm btn-outline-danger"
                                                    title="حذف">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if($prospects->hasPages())
                    <div class="seg-pager px-4 py-3 border-top d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <small class="text-muted">
                            عرض {{ $prospects->firstItem() }}–{{ $prospects->lastItem() }}
                            من {{ number_format($prospects->total()) }} عضو
                        </small>
                        <div id="seg-pagination">
                            {{ $prospects->onEachSide(1)->links('pagination::bootstrap-5') }}
                        </div>
                    </div>
                @endif
            @endif
        </div>
    </div>

</div>{{-- end container --}}
@endsection
