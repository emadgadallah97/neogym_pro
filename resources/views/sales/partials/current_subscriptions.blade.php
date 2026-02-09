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
});
</script>
