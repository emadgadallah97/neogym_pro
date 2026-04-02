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

{{-- زر الانتقال لصفحة الاشتراكات --}}
@can('sales_view_subscriptions')
<div class="row mb-3">
    <div class="col-12">
        <a href="{{ route('sales.subscriptions_list') }}" class="btn btn-soft-info">
            <i class="ri-list-check-2 me-1"></i>
            {{ trans('sales.view_subscriptions_list') ?? 'عرض الاشتراكات الحالية' }}
        </a>
    </div>
</div>
@endcan

<div class="row">
    <div class="col-lg-12">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-0">
                <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                    <div>
                        <h5 class="card-title mb-0">
                            <i class="ri-add-circle-line text-primary me-1"></i>
                            {{ trans('sales.new_subscription_sale') }}
                        </h5>
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
                    'Coaches' => $Coaches ?? [],
                    'Employees' => $Employees,
                    ])

                    <hr class="mt-4">
                    <div class="d-flex justify-content-end gap-2">
                        <button type="submit" class="btn btn-primary" name="action" value="save">
                            <i class="ri-save-3-line align-bottom me-1"></i> {{ trans('settings_trans.submit') }}
                        </button>
                        <button type="submit" class="btn btn-success" id="btnSaveAndPrint" name="action" value="save_and_print">
                            <i class="ri-printer-line align-bottom me-1"></i> {{ trans('sales.save_and_print') ?? 'حفظ وطباعة الفاتورة' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

{{-- Renewal Modal removed (moved to current_subscriptions) --}}

<script>
    document.addEventListener('DOMContentLoaded', function() {

        // ✅ Save & Print: open invoice in new blank tab after save
        @if(session('print_invoice_id'))
            (function() {
                var url = "{{ route('sales.invoice_print', session('print_invoice_id')) }}";
                window.open(url, '_blank');
            })();
        @endif

        // ✅ Save & Print PT addon
        @if(session('print_pt_invoice_id'))
            (function() {
                var url = "{{ route('sales.invoice_pt_print', session('print_pt_invoice_id')) }}";
                window.open(url, '_blank');
            })();
        @endif

        function debounce(fn, wait = 250) {
            let t = null;
            return function(...args) {
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
                return {
                    trainer_id: trainerId,
                    sessions_count: count
                };
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
            const selected = d.selected_offer || null;
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
                bestName.value = '';
            }
        }

        // AbortController لمنع تراكم preview
        let previewAbort = null;

        async function doSalesPreviewPricing() {
            const token = document.querySelector('input[name="_token"]')?.value || '';
            const payload = getPreviewPayload();
            if (!payload) return;

            try {
                if (previewAbort) previewAbort.abort();
                previewAbort = new AbortController();

                const res = await fetch("{{ route('sales.ajax.pricing_preview') }}", {
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

                setVal('total_amount_display', Number(d.amount_after_offer || 0).toFixed(2));

                setOfferNameFromPreview(d);

            } catch (e) {
                if (e && e.name === 'AbortError') return;
                console.error('[salesPreviewPricing]', e);
            }
        }

        window.salesPreviewPricing = debounce(doSalesPreviewPricing, 250);

        // Coordinator: preview + offers (no loop)
        let refreshing = false;
        let pending = false;

        window.salesRefreshAll = debounce(async function() {
            if (refreshing) {
                pending = true;
                return;
            }
            refreshing = true;
            try {
                await doSalesPreviewPricing();
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

        // Bind changes to trigger refresh
        ['branch_id', 'subscriptions_plan_id', 'start_date', 'subscriptions_type_id'].forEach(id => {
            const el = document.getElementById(id);
            if (el) el.addEventListener('change', window.salesRefreshAll);
        });

        // PT addons changes also trigger refresh
        document.addEventListener('change', function(e) {
            if (e.target && (e.target.classList.contains('pt-trainer') || e.target.classList.contains('pt-count'))) {
                window.salesRefreshAll();
            }
        });

        // Offer selection change triggers preview update
        const offerIdInput = document.getElementById('offer_id');
        if (offerIdInput) {
            const observer = new MutationObserver(function() {
                window.salesPreviewPricing();
            });
            observer.observe(offerIdInput, {
                attributes: true,
                attributeFilter: ['value']
            });
            offerIdInput.addEventListener('change', function() {
                window.salesPreviewPricing();
            });
        }

        // Offers dropdown change -> trigger preview
        const offersSelect = document.getElementById('offers_select');
        if (offersSelect) {
            offersSelect.addEventListener('change', function() {
                // Give the _form_discounts JS a tick to update offer_id hidden input
                setTimeout(function() {
                    window.salesPreviewPricing();
                }, 50);
            });
        }

        // First load
        window.salesRefreshAll();
    });
</script>

@endsection