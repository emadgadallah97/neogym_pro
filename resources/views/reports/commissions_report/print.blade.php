<!doctype html>
@php
    $rtl = app()->getLocale() === 'ar';

    $meta  = $meta ?? [];
    $chips = $chips ?? [];
    $kpis  = $kpis ?? [];
    $group = $group ?? ['rows'=>[],'group_by'=>'sales_employee'];
    $rows  = $rows ?? collect();

    $meta['title'] = $meta['title'] ?? (__('reports.commissions_report_title') ?? 'تقرير العمولات');
    $locale = app()->getLocale();

    $nameJsonOrText = function ($nameJsonOrText) use ($locale) {
        if ($nameJsonOrText === null) return '';
        if (is_array($nameJsonOrText)) {
            return $nameJsonOrText[$locale] ?? ($nameJsonOrText['ar'] ?? ($nameJsonOrText['en'] ?? reset($nameJsonOrText)));
        }
        $v = (string)$nameJsonOrText;
        $decoded = json_decode($v, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            if (is_array($decoded)) return $decoded[$locale] ?? ($decoded['ar'] ?? ($decoded['en'] ?? reset($decoded)));
            if (is_string($decoded)) return $decoded;
        }
        return $v;
    };

    $fmt = function($v){
        return number_format((float)$v, 2, '.', '');
    };
@endphp
<html lang="{{ app()->getLocale() }}" dir="{{ $rtl ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <title>{{ $meta['title'] ?? '' }}</title>

    <style>
        *{margin:0;padding:0;box-sizing:border-box}
        body{font-family:'Segoe UI',DejaVu Sans,Arial,Tahoma;line-height:1.25;color:#333;background:#fff;direction:{{ $rtl ? 'rtl' : 'ltr' }};}
        .container{max-width:100%;margin:0 auto;padding:15px}
        .main-header{display:flex;justify-content:space-between;align-items:center;gap:14px;margin-bottom:14px;padding-bottom:14px;border-bottom:3px solid #1a5490;}
        .header-logo img{max-height:78px;max-width:110px;object-fit:contain}
        .header-center{flex:1;text-align:center}
        .org-name{font-size:22px;font-weight:700;color:#1a5490;margin-bottom:3px}
        .report-title{font-size:18px;font-weight:700;color:#1a5490;margin:8px 0 0}
        .header-right{flex:0 0 auto;text-align:{{ $rtl ? 'left' : 'right' }};font-size:10px;color:#666;line-height:1.55;min-width:240px;padding:9px 10px;background:#f8f9fa;border-radius:4px;border-{{ $rtl ? 'left' : 'right' }}:3px solid #1a5490;}
        .filters{background:#f0f4f8;border:1px solid #d0dce6;border-radius:6px;padding:10px 12px;margin-bottom:10px;}
        .filters-title{font-weight:700;color:#1a5490;font-size:11px;text-transform:uppercase;margin-bottom:8px;}
        .filter-row{display:flex;flex-wrap:wrap;gap:6px;line-height:1.45}
        .filter-item{font-size:10px}
        .filter-value{color:#333;background:#fff;padding:2px 7px;border-radius:3px;border:2px solid #1a5490;display:inline-block}

        .summary-cards{display:flex;flex-wrap:wrap;gap:8px;margin-bottom:10px}
        .summary-card{flex:1 1 180px;border-radius:8px;border:1px solid rgba(0,0,0,.06);padding:7px 9px;background:#fdfdff;font-size:10px;}
        .summary-label{color:#666;margin-bottom:3px}
        .summary-value{font-weight:700;font-size:13px;color:#1a5490}

        h3.section-title{font-size:14px;font-weight:700;color:#1a5490;margin-top:10px;margin-bottom:5px}
        .section-subtitle{font-size:10px;color:#777;margin-bottom:6px}

        .table-wrap{width:100%;max-width:100%;margin:0 auto;overflow-x:auto}
        table{width:100%!important;max-width:100%!important;margin:0 auto;border-collapse:collapse;font-size:10px;margin-top:6px;margin-bottom:10px;table-layout:fixed;}
        thead{background:linear-gradient(135deg,#1a5490 0%,#2d7ab8 100%);color:#fff;}
        th{border:1px solid #1a5490;padding:6px 5px;text-align:center;font-weight:700;font-size:10px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;}
        td{border:1px solid #ddd;padding:5px 5px;color:#555;font-size:10px;vertical-align:top;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;}
        tbody tr:nth-child(even){background:#f9f9f9}

        .text-center{text-align:center}
        .wrap{white-space:normal!important;overflow:visible!important;text-overflow:clip!important;word-break:break-word;overflow-wrap:anywhere;line-height:1.2;}
        .muted{color:#777}
        .small{font-size:9px}
        .block{display:block}
        .fw-semibold{font-weight:600}

        @media print{
            .no-print{display:none!important}
            @page{ margin: 8mm 10mm; }
            .table-wrap{ overflow: hidden; }
            thead{display:table-header-group}
            table{page-break-inside:auto; break-inside:auto}
            tr{page-break-inside:auto !important; break-inside:auto !important}
            .main-header, .filters, .summary-cards{page-break-inside:avoid;break-inside:avoid-page;}
            .container{padding:0}
            thead th{-webkit-print-color-adjust:exact; print-color-adjust:exact}
        }
    </style>

    <style id="print-orientation">
        @page{ size: A4 landscape; margin: 8mm 10mm; }
    </style>
</head>
<body>

<div class="no-print" style="text-align: {{ $rtl ? 'left' : 'right' }}; margin:10px 15px;">
    <label style="font-size:12px;margin-{{ $rtl ? 'left' : 'right' }}:10px">
        <input type="checkbox" id="landscapeToggle" checked>
        {{ __('reports.landscape_mode') ?? 'وضع أفقي' }}
    </label>

    <button onclick="window.print()"
            style="background:#1a5490;color:#fff;border:none;padding:8px 16px;border-radius:4px;cursor:pointer;font-size:14px;font-weight:600">
        {{ __('reports.print') ?? 'طباعة' }}
    </button>
</div>

<div class="container">

    <div class="main-header">
        <div class="header-logo">
            <img src="{{ asset('assets/images/logo.png') }}" alt="logo">
        </div>

        <div class="header-center">
            <div class="org-name">{{ $meta['org_name'] ?? '-' }}</div>
            <div class="report-title">{{ $meta['title'] ?? '' }}</div>
        </div>

        <div class="header-right">
            <div>{{ __('reports.generated_at') ?? 'تاريخ الإنشاء' }}: {{ $meta['generated_at'] ?? now('Africa/Cairo')->format('Y-m-d H:i') }}</div>
            <div>{{ __('reports.items_count') ?? 'عدد السجلات' }}: {{ $meta['total_count'] ?? $rows->count() }}</div>
            <div>{{ __('reports.com_kpi_total_all') ?? 'إجمالي العمولات' }}: {{ (float)($kpis['total_commission_all'] ?? 0) }}</div>
            <div>{{ __('reports.com_kpi_paid') ?? 'مدفوعة' }}: {{ (float)($kpis['paid_commission'] ?? 0) }}</div>
            <div>{{ __('reports.com_kpi_unpaid') ?? 'غير مدفوعة' }}: {{ (float)($kpis['unpaid_commission'] ?? 0) }}</div>
        </div>
    </div>

    @if(!empty($chips))
        <div class="filters">
            <div class="filters-title">{{ __('reports.filters_title') ?? 'عوامل التصفية' }}</div>
            <div class="filter-row">
                @foreach($chips as $c)
                    <div class="filter-item"><span class="filter-value">{{ $c }}</span></div>
                @endforeach
            </div>
        </div>
    @endif

    <div class="summary-cards">
        <div class="summary-card">
            <div class="summary-label">{{ __('reports.com_kpi_total_all') ?? 'إجمالي العمولات (الكل)' }}</div>
            <div class="summary-value">{{ (float)($kpis['total_commission_all'] ?? 0) }}</div>
        </div>
        <div class="summary-card">
            <div class="summary-label">{{ __('reports.com_kpi_included') ?? 'غير مستبعدة' }}</div>
            <div class="summary-value">{{ (float)($kpis['total_commission_included'] ?? 0) }}</div>
        </div>
        <div class="summary-card">
            <div class="summary-label">{{ __('reports.com_kpi_excluded') ?? 'مستبعدة' }}</div>
            <div class="summary-value">{{ (float)($kpis['total_commission_excluded'] ?? 0) }}</div>
        </div>
        <div class="summary-card">
            <div class="summary-label">{{ __('reports.com_kpi_settled') ?? 'ضمن تسوية / بدون تسوية' }}</div>
            <div class="summary-value">{{ (int)($kpis['settled_items_count'] ?? 0) }} / {{ (int)($kpis['unsettled_items_count'] ?? 0) }}</div>
        </div>
    </div>

    <h3 class="section-title">{{ __('reports.com_group_title') ?? 'ملخص التجميع' }}</h3>
    <div class="section-subtitle">{{ __('reports.com_group_hint') ?? 'حسب خيار التجميع' }}</div>

    <div class="table-wrap">
        <table>
            <colgroup>
                <col style="width:40%">
                <col style="width:15%">
                <col style="width:15%">
                <col style="width:15%">
                <col style="width:15%">
            </colgroup>
            <thead>
            <tr>
                <th>{{ __('reports.com_group_col_name') ?? 'البند' }}</th>
                <th>{{ __('reports.com_group_col_count') ?? 'عدد البنود' }}</th>
                <th>{{ __('reports.com_group_col_total') ?? 'إجمالي العمولة' }}</th>
                <th>{{ __('reports.com_group_col_excluded') ?? 'مستبعدة' }}</th>
                <th>{{ __('reports.com_group_col_paid') ?? 'مدفوعة' }}</th>
            </tr>
            </thead>
            <tbody>
            @forelse(($group['rows'] ?? []) as $g)
                <tr>
                    <td class="wrap">{{ $g['group_name'] ?? '-' }}</td>
                    <td class="text-center">{{ (int)($g['items_count'] ?? 0) }}</td>
                    <td class="text-center">{{ (float)($g['total_commission'] ?? 0) }}</td>
                    <td class="text-center">{{ (float)($g['excluded_commission'] ?? 0) }}</td>
                    <td class="text-center">{{ (float)($g['paid_commission'] ?? 0) }}</td>
                </tr>
            @empty
                <tr><td colspan="5" class="text-center" style="padding:18px;color:#999">{{ __('reports.no_results') ?? 'لا توجد نتائج' }}</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>

    <h3 class="section-title">{{ __('reports.com_table_title') ?? 'تفاصيل العمولات' }}</h3>
    <div class="section-subtitle">{{ __('reports.items_count') ?? 'عدد السجلات' }}: {{ $rows->count() }}</div>

    <div class="table-wrap">
        <table>
            <colgroup>
                <col style="width:4%">
                <col style="width:10%">
                <col style="width:10%">
                <col style="width:10%">
                <col style="width:16%">
                <col style="width:8%">
                <col style="width:8%">
                <col style="width:9%">
                <col style="width:10%">
                <col style="width:15%">
            </colgroup>
            <thead>
            <tr>
                <th>#</th>
                <th>{{ __('reports.com_col_sale_date') ?? 'تاريخ البيع' }}</th>
                <th>{{ __('reports.com_col_branch') ?? 'الفرع' }}</th>
                <th>{{ __('reports.com_col_member') ?? 'العضو' }}</th>
                <th>{{ __('reports.com_col_subscription') ?? 'الاشتراك' }}</th>
                <th>{{ __('reports.com_col_sale_total') ?? 'قيمة البيع' }}</th>
                <th>{{ __('reports.com_col_commission_base') ?? 'أساس' }}</th>
                <th>{{ __('reports.com_col_commission_amount') ?? 'العمولة' }}</th>
                <th>{{ __('reports.com_col_excluded') ?? 'مستبعد' }}</th>
                <th>{{ __('reports.com_col_settlement') ?? 'التسوية/الدفع' }}</th>
            </tr>
            </thead>
            <tbody>
            @forelse($rows as $i => $r)
                @php
                    $branchName = $nameJsonOrText($r->branch_name ?? null) ?: '-';
                    $planName = $nameJsonOrText($r->plan_name ?? null) ?: '-';
                    $typeName = $nameJsonOrText($r->type_name ?? null) ?: '-';
                    $saleDate = $r->sale_date ? \Carbon\Carbon::parse($r->sale_date)->format('Y-m-d H:i') : '-';
                    $excluded = ((int)($r->is_excluded ?? 0) === 1);
                    $paid = ((int)($r->commission_is_paid ?? 0) === 1);

                    // UPDATED: member name & code
                    $memberName = trim((string)($r->member_name ?? ''));
                    $memberCode = trim((string)($r->member_code ?? ''));
                @endphp
                <tr>
                    <td class="text-center">{{ $i + 1 }}</td>
                    <td class="text-center">{{ $saleDate }}</td>
                    <td class="wrap">{{ $branchName }}</td>

                    {{-- UPDATED: Member column with name & code --}}
                    <td class="wrap">
                        <span class="block fw-semibold">{{ $memberName ?: ('#' . ($r->member_id ?? '-')) }}</span>
                        <span class="block muted small">{{ __('reports.member_code') ?? 'كود العضو' }}: {{ $memberCode ?: '-' }}</span>
                    </td>

                    <td class="wrap">
                        <span class="block"><span class="muted small">{{ __('reports.com_col_subscription') ?? 'الاشتراك' }}:</span> #{{ $r->subscription_id ?? '-' }}</span>
                        <span class="block muted small">{{ __('reports.com_col_plan') ?? 'الخطة' }}: {{ $planName }}</span>
                        <span class="block muted small">{{ __('reports.com_col_type') ?? 'النوع' }}: {{ $typeName }}</span>
                        <span class="block muted small">{{ __('reports.com_col_source') ?? 'المصدر' }}: {{ $r->source ?? '-' }}</span>
                        <span class="block muted small">{{ __('reports.com_col_sales_employee') ?? 'موظف' }}: {{ $r->sales_employee_name ?? '-' }}</span>
                    </td>
                    <td class="text-center">{{ $fmt($r->sale_total ?? 0) }}</td>
                    <td class="text-center">{{ $fmt($r->commission_base_amount ?? 0) }}</td>
                    <td class="text-center">{{ $fmt($r->commission_amount ?? 0) }}</td>
                    <td class="wrap">
                        @if($excluded)
                            <span class="block">{{ __('reports.sub_yes') ?? 'نعم' }}</span>
                            <span class="block muted small">{{ $r->exclude_reason ?? '-' }}</span>
                        @else
                            {{ __('reports.sub_no') ?? 'لا' }}
                        @endif
                    </td>
                    <td class="wrap">
                        <span class="block"><span class="muted small">{{ __('reports.com_col_paid') ?? 'الدفع' }}:</span> {{ $paid ? __('reports.sub_yes') : __('reports.sub_no') }}</span>
                        <span class="block muted small">{{ __('reports.com_col_settlement') ?? 'التسوية' }}: {{ $r->commission_settlement_id ? '#'.$r->commission_settlement_id : '-' }}</span>
                        <span class="block muted small">{{ __('reports.com_settlement_status') ?? 'حالة التسوية' }}: {{ $r->settlement_status ?? '-' }}</span>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="10" class="text-center" style="padding:18px;color:#999">{{ __('reports.no_results') ?? 'لا توجد نتائج' }}</td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>

</div>

<script>
(function(){
    var cb = document.getElementById('landscapeToggle');
    var styleBlock = document.getElementById('print-orientation');
    if (cb && styleBlock) {
        cb.addEventListener('change', function(){
            styleBlock.textContent = this.checked
                ? '@page{ size: A4 landscape; margin: 8mm 10mm; }'
                : '@page{ size: A4 portrait;  margin: 8mm 10mm; }';
        });
    }
})();
</script>
</body>
</html>
