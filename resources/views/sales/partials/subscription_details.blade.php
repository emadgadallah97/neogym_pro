@php
    $s = $subscription;
    $inModal = $inModal ?? false;

    $baseIncluded = (int)($s->sessions_included ?? $s->sessions_count ?? 0);
    $baseRemaining = (int)($s->sessions_remaining ?? 0);

    $ptSessionsCount = (int)($s->ptAddons?->sum('sessions_count') ?? 0);
    $ptSessionsRemaining = (int)($s->ptAddons?->sum('sessions_remaining') ?? 0);
@endphp

<div class="row">
    <div class="col-lg-6">
        <div class="card @if($inModal) mb-3 @endif">
            <div class="card-header">
                <h5 class="card-title mb-0">{{ trans('sales.basic_info') ?? 'البيانات الأساسية' }}</h5>
            </div>
            <div class="card-body">
                <div class="mb-2">
                    <span class="text-muted">{{ trans('sales.member') ?? 'العضو' }}:</span>
                    <div class="fw-semibold">
                        {{ $s->member?->member_code ?? $s->member_id }} - {{ $s->member?->full_name ?? '' }}
                    </div>
                </div>

                <div class="mb-2">
                    <span class="text-muted">{{ trans('settings_trans.branch') ?? 'الفرع' }}:</span>
                    <div class="fw-semibold">{{ $s->branch?->getTranslation('name','ar') ?? '-' }}</div>
                </div>

                <div class="mb-2">
                    <span class="text-muted">{{ trans('subscriptions.subscriptions_plans') ?? 'الخطة' }}:</span>
                    <div class="fw-semibold">{{ $s->plan?->getTranslation('name','ar') ?? '-' }}</div>
                </div>

                <div class="mb-2">
                    <span class="text-muted">{{ trans('subscriptions.subscriptions_types') ?? 'النوع' }}:</span>
                    <div class="fw-semibold">{{ $s->type?->getTranslation('name','ar') ?? '-' }}</div>
                </div>

                <div class="mb-2">
                    <span class="text-muted">{{ trans('sales.status') ?? 'الحالة' }}:</span>
                    <div class="fw-semibold">
                        <span class="badge bg-secondary">{{ $s->status }}</span>
                    </div>
                </div>

                <div class="mb-2">
                    <span class="text-muted">{{ trans('sales.source') ?? 'قناة الاشتراك' }}:</span>
                    <div class="fw-semibold">{{ $s->source ?? '-' }}</div>
                </div>

                <div class="mb-2">
                    <span class="text-muted">{{ trans('sales.start_date') ?? 'تاريخ البداية' }}:</span>
                    <div class="fw-semibold">{{ $s->start_date }}</div>
                </div>

                <div class="mb-2">
                    <span class="text-muted">{{ trans('sales.end_date') ?? 'تاريخ النهاية' }}:</span>
                    <div class="fw-semibold">{{ $s->end_date ?? '-' }}</div>
                </div>

                <div class="mb-2">
                    <span class="text-muted">{{ trans('sales.allow_all_branches') ?? 'الحضور من أي فرع' }}:</span>
                    <div class="fw-semibold">
                        {{ $s->allow_all_branches ? (trans('sales.yes') ?? 'نعم') : (trans('sales.no') ?? 'لا') }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="card @if($inModal) mb-3 @endif">
            <div class="card-header">
                <h5 class="card-title mb-0">{{ trans('sales.pricing') ?? 'التسعير والعمولة' }}</h5>
            </div>
            <div class="card-body">
                <div class="mb-2">
                    <span class="text-muted">{{ trans('sales.price_plan') ?? 'سعر الخطة' }}:</span>
                    <div class="fw-semibold">{{ number_format((float)$s->price_plan, 2) }}</div>
                </div>
                <div class="mb-2">
                    <span class="text-muted">{{ trans('sales.price_pt_addons') ?? 'سعر جلسات المدرب' }}:</span>
                    <div class="fw-semibold">{{ number_format((float)$s->price_pt_addons, 2) }}</div>
                </div>
                <div class="mb-2">
                    <span class="text-muted">{{ trans('sales.total_discount') ?? 'إجمالي الخصم' }}:</span>
                    <div class="fw-semibold">{{ number_format((float)$s->total_discount, 2) }}</div>
                </div>
                <div class="mb-2">
                    <span class="text-muted">{{ trans('sales.total_amount') ?? 'الإجمالي النهائي' }}:</span>
                    <div class="fw-semibold">{{ number_format((float)$s->total_amount, 2) }}</div>
                </div>

                <hr>

                <div class="mb-2">
                    <span class="text-muted">{{ trans('sales.sales_employee') ?? 'موظف المبيعات' }}:</span>
                    <div class="fw-semibold">{{ $s->salesEmployee?->full_name ?? '-' }}</div>
                </div>

                <div class="mb-2">
                    <span class="text-muted">{{ trans('sales.commission_base_amount') ?? 'أساس العمولة' }}:</span>
                    <div class="fw-semibold">{{ $s->commission_base_amount ?? '-' }}</div>
                </div>

                <div class="mb-2">
                    <span class="text-muted">{{ trans('sales.commission_amount') ?? 'قيمة العمولة' }}:</span>
                    <div class="fw-semibold">{{ $s->commission_amount ?? '-' }}</div>
                </div>

                <hr>

                <div class="mb-2">
                    <span class="text-muted">{{ trans('sales.subscription_sessions') ?? 'حصص الاشتراك' }}:</span>
                    <div class="fw-semibold">
                        {{ trans('sales.remaining') ?? 'متبقي' }}: {{ $baseRemaining }} / {{ $baseIncluded }}
                    </div>
                </div>

                <div class="mb-2">
                    <span class="text-muted">{{ trans('sales.pt_sessions') ?? 'حصص المدرب (PT)' }}:</span>
                    <div class="fw-semibold">
                        {{ trans('sales.remaining') ?? 'متبقي' }}: {{ $ptSessionsRemaining }} / {{ $ptSessionsCount }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- PT Addons --}}
<div class="card @if($inModal) mb-3 @endif">
    <div class="card-header">
        <h5 class="card-title mb-0">{{ trans('sales.pt_addons_title') ?? 'جلسات المدرب الإضافية' }}</h5>
    </div>
    <div class="card-body">
        @if($s->ptAddons && $s->ptAddons->count())
            <div class="table-responsive">
                <table class="table table-bordered table-striped align-middle mb-0">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>{{ trans('sales.trainer') ?? 'المدرب' }}</th>
                            <th>{{ trans('sales.sessions_count') ?? 'الجلسات' }}</th>
                            <th>{{ trans('sales.sessions_remaining') ?? 'المتبقي' }}</th>
                            <th>{{ trans('sales.pt_total') ?? 'الإجمالي' }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php $i=0; @endphp
                        @foreach($s->ptAddons as $pt)
                            <tr>
                                <td>{{ ++$i }}</td>
                                <td>{{ $pt->trainer?->full_name ?? '-' }}</td>
                                <td>{{ (int)$pt->sessions_count }}</td>
                                <td>{{ (int)$pt->sessions_remaining }}</td>
                                <td>{{ number_format((float)$pt->total_amount, 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="text-muted">-</div>
        @endif
    </div>
</div>
