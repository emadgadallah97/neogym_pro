@extends('layouts.master_table')

@section('title')
{{ trans('sales.sales') }}
@stop

@section('content')
<div class="row">
    <div class="col-12">
        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
            <h4 class="mb-sm-0">{{ trans('sales.sales') }}</h4>
            <div class="page-title-right">
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item">
                        <a href="javascript:void(0);">{{ trans('settings_trans.settings') }}</a>
                    </li>
                    <li class="breadcrumb-item active">{{ trans('sales.sales') }}</li>
                </ol>
            </div>
        </div>
    </div>
</div>

{{-- Messages --}}
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
    <div class="col-lg-12">
        <div class="card">
            <div class="card-header">
                <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                    <div>
                        <h5 class="card-title mb-0">{{ trans('sales.new_subscription_sale') }}</h5>
                        <small class="text-muted">
                            {{ trans('sales.form_hint') ?? '' }}
                        </small>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <form action="{{ route('sales.store') }}" method="post" id="saleForm">
                    @csrf

                    @include('sales._form', [
                        'Branches' => $Branches,
                        'Members' => $Members,
                        'Plans' => $Plans,
                        'Types' => $Types,

                        // ✅ Fix: لا تكسر الصفحة لو المتغير غير موجود
                        'Coaches' => $Coaches ?? [],

                        'Employees' => $Employees,
                    ])

                    <hr class="mt-4">
                    <div class="text-end">
                        <button type="submit" class="btn btn-primary">
                            <i class="ri-save-3-line align-bottom me-1"></i> {{ trans('settings_trans.submit') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@include('sales.partials.current_subscriptions', [
    'Branches' => $Branches,
    'Plans' => $Plans,
    'Types' => $Types,
    'Employees' => $Employees,
])



<script>
document.addEventListener('DOMContentLoaded', function () {

    // DataTable
    if (typeof $ !== 'undefined' && $.fn && $.fn.DataTable) {
        $('#salesTable').DataTable();
    }

    function debounce(fn, wait = 250) {
        let t = null;
        return function (...args) {
            clearTimeout(t);
            t = setTimeout(() => fn.apply(this, args), wait);
        };
    }

    async function safeJson(res) {
        const ct = (res.headers.get('content-type') || '').toLowerCase();
        if (ct.includes('application/json')) return await res.json();
        const text = await res.text();
        throw new Error(text.substring(0, 200));
    }

    function collectPtAddons() {
        const ptRows = Array.from(document.querySelectorAll('#ptAddonsTable tbody tr'));
        return ptRows.map(tr => {
            const trainerId = tr.querySelector('.pt-trainer')?.value || '';
            const count = tr.querySelector('.pt-count')?.value || '';
            return { trainer_id: trainerId, sessions_count: count };
        }).filter(x => x.trainer_id && x.sessions_count);
    }

    function getPreviewPayload() {
        const branchId = document.getElementById('branch_id')?.value || '';
        const planId = document.getElementById('subscriptions_plan_id')?.value || '';
        const typeId = document.getElementById('subscriptions_type_id')?.value || '';
        const startDate = document.getElementById('start_date')?.value || '';
        const offerId = document.getElementById('offer_id')?.value || '';

        if (!branchId || !planId) return null;

        return {
            branch_id: branchId,
            subscriptions_plan_id: planId,
            subscriptions_type_id: typeId || null,
            start_date: startDate || null,
            pt_addons: collectPtAddons(),
            offer_id: offerId || null
        };
    }

    function setVal(id, val) {
        const el = document.getElementById(id);
        if (el) el.value = (val ?? '0.00');
    }

    function setOfferNameFromPreview(d) {
        const selected = d.selected_offer || d.best_offer; // توافق للخلف
        const bestName = document.getElementById('best_offer_name');

        if (!bestName) return;

        if (selected && selected.offer && selected.offer.name) {
            const nameObj = selected.offer.name;
            let nameTxt = '-';
            if (typeof nameObj === 'object') {
                nameTxt = nameObj.ar || nameObj.en || Object.values(nameObj)[0] || '-';
            } else {
                nameTxt = nameObj;
            }
            bestName.value = nameTxt;
        } else {
            bestName.value = '{{ trans('sales.no_offers_available') ?? 'لا يوجد عروض متاحة' }}';
        }
    }

    // نستخدم AbortController لمنع تراكم طلبات preview
    let previewAbort = null;

    window.salesPreviewPricing = async function () {
        const token = document.querySelector('input[name="_token"]')?.value || '';
        const payload = getPreviewPayload();
        if (!payload) return;

        try {
            if (previewAbort) {
                previewAbort.abort();
            }
            previewAbort = new AbortController();

            const res = await fetch(`{{ route('sales.ajax.pricing_preview') }}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': token
                },
                body: JSON.stringify(payload),
                signal: previewAbort.signal
            });

            const json = await safeJson(res);
            if (!json || !json.ok) return;

            const d = json.data || {};

            // end_date
            if (d.end_date && document.getElementById('end_date')) {
                document.getElementById('end_date').value = d.end_date;
            }

            setVal('price_plan_display', Number(d.price_plan || 0).toFixed(2));
            setVal('price_pt_addons_display', Number(d.pt_total || 0).toFixed(2));
            setVal('gross_amount_display', Number(d.gross_amount || 0).toFixed(2));

            setVal('offer_discount_display', Number(d.offer_discount || 0).toFixed(2));
            setVal('amount_after_offer_display', Number(d.amount_after_offer || 0).toFixed(2));

            // إجمالي نهائي preview = بعد العرض فقط (الكوبون حسب زر التحقق أو عند الحفظ)
            setVal('total_amount_display', Number(d.amount_after_offer || 0).toFixed(2));

            setOfferNameFromPreview(d);

            // ✅ مهم جدًا: لا تستدعي salesLoadOffers من هنا (منع الـ loop)
        } catch (e) {
            // تجاهل AbortError + اطبع غيره
            if (e && (e.name === 'AbortError')) return;
            console.error(e);
        }
    };

    // debounce لطلبات preview
    window.salesPreviewPricing = debounce(window.salesPreviewPricing, 250);

    // Coordinator: يستدعي preview + offers من الخارج فقط وبحماية من التكرار
    let refreshing = false;
    let pending = false;

    window.salesRefreshAll = debounce(async function () {
        if (refreshing) {
            pending = true;
            return;
        }

        refreshing = true;

        try {
            await (window.salesPreviewPricing && window.salesPreviewPricing());
            // تحميل قائمة العروض (للاختيار اليدوي) - من الخارج فقط
            if (window.salesLoadOffers) {
                await window.salesLoadOffers();
            }
        } finally {
            refreshing = false;
            if (pending) {
                pending = false;
                window.salesRefreshAll();
            }
        }
    }, 300);

    // Bind changes
    ['branch_id','subscriptions_plan_id','start_date','subscriptions_type_id'].forEach(id => {
        const el = document.getElementById(id);
        if (el) el.addEventListener('change', window.salesRefreshAll);
    });

    // أول تحميل
    window.salesRefreshAll();
});
</script>

@endsection
