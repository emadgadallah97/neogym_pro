@extends('layouts.master_table')

@section('title')
    {{ trans('sales.pt_addons_after_sale_title') ?? 'إضافة PT للاشتراك' }}
@stop

@section('content')
@php
    $s = $subscription;
@endphp

<div class="row">
    <div class="col-12">
        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
            <h4 class="mb-sm-0">
                {{ trans('sales.pt_addons_after_sale_title') ?? 'إضافة PT للاشتراك' }} #{{ $s->id }}
            </h4>
            <div class="page-title-right">
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item"><a href="{{ route('sales.index') }}">{{ trans('sales.sales') ?? 'Sales' }}</a></li>
                    <li class="breadcrumb-item active">{{ trans('sales.add_pt') ?? 'إضافة PT' }}</li>
                </ol>
            </div>
        </div>
    </div>
</div>

{{-- Messages --}}
@if (Session::has('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <strong>Success!</strong> {{ Session::get('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

@if (Session::has('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <strong>Error!</strong> {{ Session::get('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

@if ($errors->any())
    <div class="alert alert-danger">
        <ul class="mb-0">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="row">
    <div class="col-lg-5">
        <div class="card mb-3">
            <div class="card-header">
                <h5 class="card-title mb-0">{{ trans('sales.basic_info') ?? 'البيانات الأساسية' }}</h5>
            </div>
            <div class="card-body">
                <div class="mb-2">
                    <span class="text-muted">{{ trans('sales.member') ?? 'العضو' }}:</span>
                    <div class="fw-semibold">
                        {{ $s->member?->member_code ?? ($s->member_id ?? '-') }}
                        -
                        {{ $s->member?->full_name ?? trim(($s->member?->first_name ?? '').' '.($s->member?->last_name ?? '')) }}
                    </div>
                </div>

                <div class="mb-2">
                    <span class="text-muted">{{ trans('settings_trans.branch') ?? 'الفرع' }}:</span>
                    <div class="fw-semibold">{{ $s->branch?->getTranslation('name','ar') ?? '-' }}</div>
                </div>

                <div class="mb-2">
                    <span class="text-muted">{{ trans('sales.status') ?? 'الحالة' }}:</span>
                    <div class="fw-semibold">
                        <span class="badge bg-secondary">{{ trans('sales.status_'.$s->status) }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-7">
        <div class="card mb-3">
            <div class="card-header">
                <h5 class="card-title mb-0">{{ trans('sales.add_pt') ?? 'إضافة PT' }}</h5>
            </div>

            <div class="card-body">
                <form action="{{ route('sales.subscriptions.pt_addons.store', $s->id) }}" method="POST" id="ptAddonSaleForm">
                    @csrf

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">{{ trans('sales.trainer') ?? 'المدرب' }}</label>
                            <select name="trainer_id" id="trainer_id" class="form-select select2" required>
                                <option value="">{{ trans('settings_trans.choose') }}</option>
                                @foreach($Coaches as $c)
                                    <option value="{{ $c->id }}">
                                        {{ $c->full_name ?? trim(($c->first_name ?? '').' '.($c->last_name ?? '')) }}
                                    </option>
                                @endforeach
                            </select>
                            <small class="text-muted d-block mt-1">{{ trans('sales.trainer_filtered_by_branch') ?? 'المدربين حسب الفرع' }}</small>
                        </div>

                        <div class="col-md-3 mb-3">
                            <label class="form-label">{{ trans('sales.session_price') ?? 'سعر الحصة' }}</label>
                            <input type="text" id="session_price" class="form-control" value="0.00" readonly>
                        </div>

                        <div class="col-md-3 mb-3">
                            <label class="form-label">{{ trans('sales.sessions_count') ?? 'عدد الحصص' }}</label>
                            <input type="number" min="1" name="sessions_count" id="sessions_count" class="form-control" value="{{ old('sessions_count', 1) }}" required>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label">{{ trans('sales.payment_method') ?? 'طريقة الدفع' }}</label>
                            <select name="payment_method" class="form-select" required>
                                <option value="">{{ trans('settings_trans.choose') }}</option>
                                @foreach($paymentMethods as $pm)
                                    <option value="{{ $pm }}" @selected(old('payment_method')===$pm)>{{ $pm }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label">{{ trans('sales.paid_at') ?? 'تاريخ ووقت الدفع' }}</label>
                            <input
                                type="datetime-local"
                                name="paid_at"
                                class="form-control"
                                value="{{ old('paid_at', now()->format('Y-m-d\\TH:i')) }}"
                            >
                            <small class="text-muted d-block mt-1">{{ trans('sales.paid_at_hint') ?? 'يتم تعبئته تلقائياً ويمكن تعديله' }}</small>
                        </div>

                        {{-- تم حذف reference + notes من الشاشة (سيتم توليدهم تلقائياً في الكنترولر) --}}

                        <div class="col-md-12">
                            <div class="border rounded p-3 d-flex align-items-center justify-content-between flex-wrap gap-2">
                                <div class="fw-semibold">
                                    {{ trans('sales.total_amount') ?? 'الإجمالي' }}:
                                    <span id="total_amount">0.00</span>
                                </div>

                                <button type="submit" class="btn btn-primary">
                                    <i class="ri-save-3-line align-bottom me-1"></i>
                                    {{ trans('settings_trans.submit') ?? 'حفظ' }}
                                </button>
                            </div>
                        </div>
                    </div>

                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    if (window.jQuery && jQuery.fn && jQuery.fn.select2) {
        jQuery('.select2').select2({ width: '100%', dir: 'rtl', language: 'ar' });
    }

    const TOKEN = document.querySelector('input[name="_token"]')?.value || '';
    const priceInp = document.getElementById('session_price');
    const countInp = document.getElementById('sessions_count');
    const totalEl  = document.getElementById('total_amount');

    async function safeJson(res) {
        const ct = (res.headers.get('content-type') || '').toLowerCase();
        if (ct.includes('application/json')) return await res.json();
        const text = await res.text();
        throw new Error(text.substring(0, 300));
    }

    async function fetchTrainerPrice(trainerId) {
        const url = "{{ route('sales.ajax.trainersessionprice') }}";
        const res = await fetch(url, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': TOKEN
            },
            body: JSON.stringify({ trainerid: trainerId })
        });
        return await safeJson(res);
    }

    function recomputeTotal() {
        const price = parseFloat(priceInp.value || 0) || 0;
        const cnt   = parseInt(countInp.value || 0, 10) || 0;
        const total = Math.max(0, price) * Math.max(0, cnt);
        totalEl.textContent = total.toFixed(2);
    }

    async function onTrainerChanged(trainerIdRaw) {
        const trainerId = parseInt(trainerIdRaw || 0, 10);
        priceInp.value = '0.00';

        if (trainerId > 0) {
            try {
                const r = await fetchTrainerPrice(trainerId);
                if (r && r.ok) {
                    const p = parseFloat(r.data?.sessionprice || 0) || 0;
                    priceInp.value = p.toFixed(2);
                } else {
                    console.error('Trainer price response not ok', r);
                }
            } catch (e) {
                console.error('Trainer price fetch failed', e);
                priceInp.value = '0.00';
            }
        }

        recomputeTotal();
    }

    if (window.jQuery) {
        jQuery(document).on('change', '#trainer_id', function () {
            onTrainerChanged(this.value);
        });
        jQuery(document).on('select2:select', '#trainer_id', function () {
            jQuery(this).trigger('change');
        });
    } else {
        const trainerSel = document.getElementById('trainer_id');
        trainerSel?.addEventListener('change', function () {
            onTrainerChanged(this.value);
        });
    }

    countInp?.addEventListener('input', recomputeTotal);
    recomputeTotal();
});
</script>
@endsection
