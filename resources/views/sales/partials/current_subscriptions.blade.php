@php
    $Branches = $Branches ?? collect();
    $Plans = $Plans ?? collect();
    $Types = $Types ?? collect();
    $Employees = $Employees ?? collect();
    $req = request();

    $statusOptions = ['active','expired','frozen','cancelled','pendingpayment'];
@endphp

<div class="row mt-4">
    <div class="col-lg-12">
        <div class="card">
            <div class="card-header">
                <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                    <h5 class="card-title mb-0">
                        {{ trans('sales.current_subscriptions') ?? 'الاشتراكات الحالية' }}
                    </h5>
                </div>
            </div>

            <div class="card-body">
                {{-- Filters (لن تعمل submit للصفحة؛ هتعمل reload للداتاتيبل فقط) --}}
                <form id="currentSubsFilters" class="row g-2 align-items-end mb-3">
                    <div class="col-lg-3 col-md-6">
                        <label class="form-label mb-1">{{ trans('sales.search') ?? 'بحث' }}</label>
                        <input type="text" name="q" class="form-control form-control-sm"
                               value="{{ $req->get('q') }}"
                               placeholder="{{ trans('sales.search_member_placeholder') ?? 'ابحث بكود العضو أو الاسم' }}">
                    </div>

                    <div class="col-lg-2 col-md-6">
                        <label class="form-label mb-1">{{ trans('settings_trans.branch') ?? 'الفرع' }}</label>
                        <select name="branch_id" class="form-select form-select-sm">
                            <option value="">{{ trans('settings_trans.choose') }}</option>
                            @foreach($Branches as $b)
                                <option value="{{ $b->id }}">{{ $b->getTranslation('name','ar') }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-lg-2 col-md-6">
                        <label class="form-label mb-1">{{ trans('sales.status') ?? 'الحالة' }}</label>
                        <select name="status" class="form-select form-select-sm">
                            <option value="">{{ trans('sales.all') ?? 'الكل' }}</option>
                            @foreach($statusOptions as $st)
                                <option value="{{ $st }}">{{ trans('sales.status_'.$st) }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-lg-2 col-md-6">
                        <label class="form-label mb-1">{{ trans('subscriptions.subscriptions_plans') ?? 'الخطة' }}</label>
                        <select name="subscriptions_plan_id" class="form-select form-select-sm">
                            <option value="">{{ trans('sales.all') ?? 'الكل' }}</option>
                            @foreach($Plans as $p)
                                <option value="{{ $p->id }}">
                                    {{ ($p->code ? '['.$p->code.'] ' : '') . $p->getTranslation('name','ar') }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-lg-2 col-md-6">
                        <label class="form-label mb-1">{{ trans('subscriptions.subscriptions_types') ?? 'النوع' }}</label>
                        <select name="subscriptions_type_id" class="form-select form-select-sm">
                            <option value="">{{ trans('sales.all') ?? 'الكل' }}</option>
                            @foreach($Types as $t)
                                <option value="{{ $t->id }}">{{ $t->getTranslation('name','ar') }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-lg-2 col-md-6">
                        <label class="form-label mb-1">{{ trans('sales.source') ?? 'المصدر' }}</label>
                        <select name="source" class="form-select form-select-sm">
                            <option value="">{{ trans('sales.all') ?? 'الكل' }}</option>
                            <option value="reception">{{ trans('sales.source_reception') }}</option>
                            <option value="website">{{ trans('sales.source_website') }}</option>
                            <option value="mobile">{{ trans('sales.source_mobile') }}</option>
                            <option value="callcenter">{{ trans('sales.source_callcenter') }}</option>
                            <option value="partner">{{ trans('sales.source_partner') }}</option>
                            <option value="other">{{ trans('sales.source_other') }}</option>
                        </select>
                    </div>

                    <div class="col-lg-2 col-md-6">
                        <label class="form-label mb-1">{{ trans('sales.sales_employee') ?? 'موظف المبيعات' }}</label>
                        <select name="sales_employee_id" class="form-select form-select-sm">
                            <option value="">{{ trans('sales.all') ?? 'الكل' }}</option>
                            @foreach($Employees as $e)
                                <option value="{{ $e->id }}">
                                    {{ $e->full_name ?? trim(($e->first_name ?? '').' '.($e->last_name ?? '')) }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-lg-2 col-md-6">
                        <label class="form-label mb-1">{{ trans('sales.has_pt_addons') ?? 'حصص المدرب (PT)' }}</label>
                        <select name="has_pt_addons" class="form-select form-select-sm">
                            <option value="">{{ trans('sales.all') ?? 'الكل' }}</option>
                            <option value="1">{{ trans('sales.with_pt_addons') ?? 'يوجد' }}</option>
                            <option value="0">{{ trans('sales.without_pt_addons') ?? 'لا يوجد' }}</option>
                        </select>
                    </div>

                    <div class="col-lg-2 col-md-6">
                        <label class="form-label mb-1">{{ trans('sales.date_from') ?? 'من' }}</label>
                        <input type="date" name="date_from" class="form-control form-control-sm">
                    </div>

                    <div class="col-lg-2 col-md-6">
                        <label class="form-label mb-1">{{ trans('sales.date_to') ?? 'إلى' }}</label>
                        <input type="date" name="date_to" class="form-control form-control-sm">
                    </div>

                    <div class="col-lg-2 col-md-6 d-flex gap-2">
                        <button class="btn btn-primary btn-sm w-100" type="submit">
                            {{ trans('sales.apply_filters') ?? 'تطبيق' }}
                        </button>
                        <button class="btn btn-light btn-sm w-100" type="button" id="btnClearCurrentSubsFilters">
                            {{ trans('sales.clear_filters') ?? 'مسح' }}
                        </button>
                    </div>
                </form>

                {{-- DataTable --}}
                <div class="table-responsive">
                    <table id="currentSubscriptionsTable" class="table table-bordered dt-responsive nowrap table-striped align-middle mb-0 w-100">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>{{ trans('members.member_code') ?? 'Member' }}</th>
                                <th>{{ trans('subscriptions.subscriptions_plans') ?? 'Plan' }}</th>
                                <th>{{ trans('settings_trans.branch') ?? 'Branch' }}</th>
                                <th>{{ trans('sales.sessions') ?? 'Sessions' }}</th>
                                <th>{{ trans('sales.pt_addons_short') ?? 'PT' }}</th>
                                <th>{{ trans('sales.source') ?? 'Source' }}</th>
                                <th>{{ trans('sales.total_amount') ?? 'Total' }}</th>
                                <th>{{ trans('sales.status') ?? 'Status' }}</th>
                                <th>{{ trans('settings_trans.create_date') ?? 'Created at' }}</th>
                                <th>{{ trans('sales.actions') ?? 'Actions' }}</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Modal --}}
<div class="modal fade" id="subscriptionShowModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">{{ trans('sales.subscription_details') ?? 'تفاصيل الاشتراك' }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ trans('sales.close') ?? 'إغلاق' }}"></button>
            </div>
            <div class="modal-body" id="subscriptionShowModalBody">
                <div class="text-muted">{{ trans('sales.loading') ?? 'جاري التحميل...' }}</div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    // ===== Modal (عرض تفاصيل الاشتراك) =====
    const modalEl = document.getElementById('subscriptionShowModal');
    const modalBody = document.getElementById('subscriptionShowModalBody');

    function openModal() {
        if (window.bootstrap && bootstrap.Modal) {
            bootstrap.Modal.getOrCreateInstance(modalEl).show();
        } else if (window.jQuery && jQuery.fn && jQuery.fn.modal) {
            window.jQuery(modalEl).modal('show');
        } else {
            modalEl.classList.add('show');
            modalEl.style.display = 'block';
        }
    }

    async function loadSubscription(id) {
        modalBody.innerHTML = '<div class="text-muted">{{ trans('sales.loading') ?? 'جاري التحميل...' }}</div>';
        openModal();

        const url = "{{ route('sales.ajax.subscriptions.modal', ['id' => '___ID___']) }}".replace('___ID___', id);

        try {
            const res = await fetch(url, { headers: { 'Accept': 'application/json' } });
            const json = await res.json();
            if (!json || !json.ok) throw new Error(json?.message || 'Load failed');
            modalBody.innerHTML = json.html || '';
        } catch (e) {
            modalBody.innerHTML = '<div class="alert alert-danger mb-0">{{ trans('sales.ajax_error_try_again') ?? 'حدث خطأ، حاول مرة أخرى' }}</div>';
        }
    }

    // Event delegation (لأن الصفوف بتتولد بالـ AJAX)
    if (window.jQuery) {
        jQuery(document).on('click', '.js-subscription-show', function () {
            const id = jQuery(this).data('id');
            if (id) loadSubscription(id);
        });
    } else {
        document.addEventListener('click', function(e){
            const btn = e.target.closest('.js-subscription-show');
            if (!btn) return;
            const id = btn.getAttribute('data-id');
            if (id) loadSubscription(id);
        });
    }

    // ===== DataTable server-side =====
    if (!(window.jQuery && jQuery.fn && jQuery.fn.DataTable)) return;

    const $form = jQuery('#currentSubsFilters');

    const table = jQuery('#currentSubscriptionsTable').DataTable({
        processing: true,
        serverSide: true,
        pageLength: 10,
        lengthMenu: [10, 25, 50, 100],
        responsive: true,
        searching: false, // هنستخدم input q بتاعنا بدل search الافتراضي
        ajax: {
            url: "{{ route('sales.ajax.current_subscriptions.table') }}",
            type: "GET",
            data: function (d) {
                const arr = $form.serializeArray();
                arr.forEach(x => d[x.name] = x.value);
            }
        },
        columns: [
            { data: 'rownum', orderable: false, searchable: false },
            { data: 'member', orderable: false, searchable: false },
            { data: 'plan', orderable: false, searchable: false },
            { data: 'branch', orderable: false, searchable: false },
            { data: 'base_sessions', orderable: false, searchable: false },
            { data: 'pt', orderable: false, searchable: false },
            { data: 'source', orderable: false, searchable: false },
            { data: 'total', name: 'total_amount' },
            { data: 'status', name: 'status' },
            { data: 'created_at', name: 'created_at' },
            { data: 'actions', orderable: false, searchable: false },
        ],
        order: [[9, 'desc']],
        drawCallback: function() {
            // Fix responsive collapse alignment لو احتجت
        }
    });

    // زر تطبيق الفلاتر: يمنع reload للصفحة ويعمل reload للجدول فقط
    $form.on('submit', function(e){
        e.preventDefault();
        table.ajax.reload(null, true);
    });

    // مسح الفلاتر
    jQuery('#btnClearCurrentSubsFilters').on('click', function(){
        $form[0].reset();
        table.ajax.reload(null, true);
    });

    // (اختياري) لو عايز reload تلقائي مع تغيير أي فلتر بدون ضغط تطبيق:
    // $form.on('change', 'select,input[type=date]', function(){ table.ajax.reload(null, true); });

    // ✅ Save & Print: open invoice in new blank tab after save
    @if(session('print_invoice_id'))
        (function() {
            var url = "{{ route('sales.invoice_print', session('print_invoice_id')) }}";
            window.open(url, '_blank');
        })();
    @endif

    let currentRenewData = null;
    let renewCouponDiscount = 0;

    // Handle Renew Button Click
    $(document).on('click', '.js-subscription-renew', async function() {
        const subId = $(this).data('id');
        const urlDetails = "{{ route('sales.subscriptions.renew_details', ':id') }}".replace(':id', subId);
        const urlSubmit = "{{ route('sales.subscriptions.renew', ':id') }}".replace(':id', subId);
        
        try {
            const res = await fetch(urlDetails);
            const json = await res.json();
            
            if (json.ok) {
                const data = json.data;
                currentRenewData = data;
                renewCouponDiscount = 0;
                
                $('#renew_member_name').text(data.member);
                $('#renew_plan_name').text(data.plan_name);
                $('#renew_old_end_date').text(data.old_end_date || '-');
                $('#renew_base_price').text(Number(data.base_price).toFixed(2));
                
                let offerSelect = $('#renew_offer_id');
                offerSelect.empty().append('<option value="">---</option>');
                if (data.offers && data.offers.length > 0) {
                    data.offers.forEach(off => {
                        let name = off.name && off.name.ar ? off.name.ar : (off.name || 'Offer');
                        offerSelect.append(`<option value="${off.id}" data-discount="${off.discount_amount}">${name} (-${off.discount_amount})</option>`);
                    });
                }
                
                calculateRenewTotal();
                
                $('#renewalForm').attr('action', urlSubmit);
                
                // Use bootstrap 5 or 4 modal depending on the template, trying bs5 first
                if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
                    const renewalModal = new bootstrap.Modal(document.getElementById('renewalModal'));
                    renewalModal.show();
                } else {
                    $('#renewalModal').modal('show');
                }
            } else {
                alert(json.message || 'Error occurred');
            }
        } catch (e) {
            console.error(e);
            alert('Connection error');
        }
    });

    function calculateRenewTotal() {
        let basePrice = parseFloat(currentRenewData.base_price || 0);
        let selectedOffer = $('#renew_offer_id').find(':selected');
        let offerDiscount = parseFloat(selectedOffer.data('discount') || 0);
        let net = Math.max(0, basePrice - offerDiscount - renewCouponDiscount);
        
        $('#calc_base_price').text(basePrice.toFixed(2));
        $('#calc_offer_discount').text(offerDiscount.toFixed(2));
        $('#calc_coupon_discount').text(renewCouponDiscount.toFixed(2));
        $('#calc_net_total').text(net.toFixed(2));
    }

    $('#renew_offer_id').on('change', function() {
        // Reset coupon when offer changes to avoid invalid combinations
        $('#renew_coupon_code').val('');
        $('#coupon_message').text('');
        renewCouponDiscount = 0;
        calculateRenewTotal();
    });

    $('#btn_renew_apply_coupon').on('click', async function() {
        let code = $('#renew_coupon_code').val();
        let msgEl = $('#coupon_message');
        msgEl.text('').removeClass('text-success text-danger');

        if (!code) { 
            renewCouponDiscount = 0; 
            calculateRenewTotal(); 
            return; 
        }
        
        let payload = {
            branch_id: currentRenewData.branch_id,
            subscriptions_plan_id: currentRenewData.subscriptions_plan_id,
            subscriptions_type_id: currentRenewData.subscriptions_type_id,
            offer_id: $('#renew_offer_id').val() || null,
            coupon_code: code,
            pt_addons: []
        };

        try {
            const res = await fetch("{{ route('sales.ajax.validate_coupon') }}", {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                body: JSON.stringify(payload)
            });
            const d = await res.json();
            if (d.ok) {
                renewCouponDiscount = parseFloat(d.data.coupon_discount || 0);
                msgEl.text(d.message || 'Coupon applied').addClass('text-success');
                calculateRenewTotal();
            } else {
                renewCouponDiscount = 0;
                msgEl.text(d.message || 'Invalid coupon').addClass('text-danger');
                calculateRenewTotal();
            }
        } catch(e) {
            console.error(e);
            msgEl.text('Error validating coupon').addClass('text-danger');
        }
    });

});
</script>

{{-- Renewal Modal --}}
<div class="modal fade" id="renewalModal" tabindex="-1" role="dialog" aria-labelledby="renewalModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <form id="renewalForm" method="POST" action="">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="renewalModalLabel">{{ trans('sales.renew_subscription') ?? 'تجديد الاشتراك' }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <!-- Read-only info -->
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <strong>{{ trans('sales.member') }}:</strong> <span id="renew_member_name"></span>
                        </div>
                        <div class="col-md-6">
                            <strong>{{ trans('sales.plan_name') }}:</strong> <span id="renew_plan_name"></span>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <strong>{{ trans('sales.old_end_date') ?? 'تاريخ الانتهاء القديم' }}:</strong> <span id="renew_old_end_date"></span>
                        </div>
                        <div class="col-md-6">
                            <strong>{{ trans('sales.base_price') ?? 'السعر الأساسي' }}:</strong> <span id="renew_base_price"></span>
                        </div>
                    </div>
                    
                    <!-- Form Fields -->
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">{{ trans('sales.start_date') }} <span class="text-danger">*</span></label>
                            <input type="date" name="start_date" id="renew_start_date" class="form-control" value="{{ date('Y-m-d') }}" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">{{ trans('sales.payment_method') }} <span class="text-danger">*</span></label>
                            <select name="payment_method" id="renew_payment_method" class="form-select" required>
                                <option value="cash">{{ trans('sales.cash') ?? 'نقدي' }}</option>
                                <option value="card">{{ trans('sales.card') ?? 'بطاقة' }}</option>
                                <option value="transfer">{{ trans('sales.transfer') ?? 'تحويل' }}</option>
                                <option value="instapay">{{ trans('sales.instapay') ?? 'InstaPay' }}</option>
                                <option value="ewallet">{{ trans('sales.ewallet') ?? 'محفظة' }}</option>
                                <option value="cheque">{{ trans('sales.cheque') ?? 'شيك' }}</option>
                                <option value="other">{{ trans('sales.payment_other') ?? 'أخرى' }}</option>
                            </select>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">{{ trans('sales.sales_employee') }}</label>
                            <select name="sales_employee_id" id="renew_sales_employee_id" class="form-select select2">
                                <option value="">---</option>
                                @foreach($Employees as $emp)
                                    <option value="{{ $emp->id }}">{{ $emp->full_name ?? trim(($emp->first_name ?? '') . ' ' . ($emp->last_name ?? '')) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">{{ trans('sales.offer') ?? 'العرض' }}</label>
                            <select name="offer_id" id="renew_offer_id" class="form-select">
                                <option value="">---</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <label class="form-label">{{ trans('sales.coupon_code') }}</label>
                            <div class="input-group">
                                <input type="text" name="coupon_code" id="renew_coupon_code" class="form-control">
                                <button class="btn btn-outline-secondary" type="button" id="btn_renew_apply_coupon">{{ trans('sales.apply') ?? 'تطبيق' }}</button>
                            </div>
                            <small id="coupon_message" class="d-block mt-1"></small>
                        </div>
                    </div>

                    {{-- Calculator UI --}}
                    <div class="card shadow-sm mb-3 border-primary border-opacity-25">
                        <div class="card-body p-0">
                            <table class="table table-sm table-borderless align-middle mb-0">
                                <tbody>
                                    <tr>
                                        <td class="text-muted ps-3">{{ trans('sales.base_price') ?? 'السعر الأساسي' }}</td>
                                        <td class="text-end pe-3 fw-semibold"><span id="calc_base_price">0.00</span></td>
                                    </tr>
                                    <tr>
                                        <td class="text-muted ps-3 text-danger">{{ trans('sales.discount_offer') ?? 'خصم العرض' }}</td>
                                        <td class="text-end pe-3 text-danger">-<span id="calc_offer_discount">0.00</span></td>
                                    </tr>
                                    <tr class="border-bottom">
                                        <td class="text-muted ps-3 text-danger">{{ trans('sales.discount_coupon') ?? 'خصم الكوبون' }}</td>
                                        <td class="text-end pe-3 text-danger">-<span id="calc_coupon_discount">0.00</span></td>
                                    </tr>
                                    <tr class="bg-primary bg-opacity-10">
                                        <td class="ps-3 fw-bold">{{ trans('sales.net_total') ?? 'الإجمالي المستحق' }}</td>
                                        <td class="text-end pe-3 fw-bold fs-5"><span id="calc_net_total">0.00</span></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <label class="form-label">{{ trans('sales.notes') }}</label>
                            <textarea name="notes" class="form-control" rows="2"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ trans('settings_trans.close') ?? 'إغلاق' }}</button>
                    <button type="submit" class="btn btn-primary">{{ trans('sales.confirm_renewal') ?? 'تأكيد التجديد' }}</button>
                </div>
            </div>
        </form>
    </div>
</div>
