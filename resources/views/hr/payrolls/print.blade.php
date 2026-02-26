<!doctype html>
@php
    use Carbon\Carbon;

    $rtl = app()->getLocale() === 'ar';

    $meta  = $meta  ?? [];
    $chips = $chips ?? [];
    $kpis  = $kpis  ?? [];
    $rows  = $rows  ?? collect();

    // robust translation fallback (avoid showing keys like hr.xxx / reports.xxx)
    $t = function(string $key, string $fallback = '') {
        $val = trans($key);
        if ($val === $key || $val === '' || $val === null) return $fallback !== '' ? $fallback : $key;
        return $val;
    };
    $tt = function(string $key, string $fallback = '') {
        $val = __($key);
        if ($val === $key || $val === '' || $val === null) return $fallback !== '' ? $fallback : $key;
        return $val;
    };

    $meta['title'] = $meta['title'] ?? $t('hr.payrolls', 'الرواتب');
    $meta['org_name'] = $meta['org_name'] ?? (config('app.name') ?? '-');
    $meta['generated_at'] = $meta['generated_at'] ?? now('Africa/Cairo')->format('Y-m-d H:i');
    $meta['total_count'] = $meta['total_count'] ?? $rows->count();

    $money = function($v){
        return number_format((float)($v ?? 0), 2);
    };

    $fmtDate = function ($v, $format) {
        if (empty($v)) return '-';
        try { return Carbon::parse($v)->format($format); } catch (\Throwable $e) { return '-'; }
    };

    $statusText = function($s) use ($t) {
        $s = strtolower(trim((string)$s));
        if ($s === 'draft') return $t('hr.draft', 'مسودة');
        if ($s === 'approved') return $t('hr.approved', 'معتمد');
        if ($s === 'paid') return $t('hr.paid', 'مدفوع');
        return $s !== '' ? $s : '-';
    };

    // KPIs defaults
    $kpis = array_merge([
        'total_rows'     => $rows->count(),
        'draft'          => 0,
        'approved'       => 0,
        'paid'           => 0,
        'sum_base'       => 0,
        'sum_overtime'   => 0,
        'sum_allowances' => 0,
        'sum_advances'   => 0,
        'sum_deductions' => 0,
        'sum_gross'      => 0,
        'sum_net'        => 0,
    ], $kpis);
@endphp

<html lang="{{ app()->getLocale() }}" dir="{{ $rtl ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <title>{{ $meta['title'] ?? '' }}</title>

    <style>
        *{margin:0;padding:0;box-sizing:border-box}
        body{
            font-family:'Segoe UI',DejaVu Sans,Arial,Tahoma;
            line-height:1.25;color:#333;background:#fff;
            direction:{{ $rtl ? 'rtl' : 'ltr' }};
            padding-bottom: 22px; /* space for print footer */
        }
        .container{max-width:100%;margin:0 auto;padding:15px}

        .main-header{
            display:flex;justify-content:space-between;align-items:center;gap:14px;
            margin-bottom:14px;padding-bottom:14px;border-bottom:3px solid #1a5490;
        }
        .header-logo img{max-height:78px;max-width:110px;object-fit:contain}
        .header-center{flex:1;text-align:center}
        .org-name{font-size:22px;font-weight:700;color:#1a5490;margin-bottom:3px}
        .report-title{font-size:18px;font-weight:700;color:#1a5490;margin:8px 0 0}
        .header-right{
            flex:0 0 auto;
            text-align:{{ $rtl ? 'left' : 'right' }};
            font-size:10px;color:#666;line-height:1.55;min-width:210px;
            padding:9px 10px;background:#f8f9fa;border-radius:4px;
            border-{{ $rtl ? 'left' : 'right' }}:3px solid #1a5490;
        }

        .filters{
            background:#f0f4f8;border:1px solid #d0dce6;border-radius:6px;
            padding:10px 12px;margin-bottom:10px;
        }
        .filters-title{font-weight:700;color:#1a5490;font-size:11px;text-transform:uppercase;margin-bottom:8px}
        .filter-row{display:flex;flex-wrap:wrap;gap:6px;line-height:1.45}
        .filter-item{font-size:10px}
        .filter-value{color:#333;background:#fff;padding:2px 7px;border-radius:3px;border:2px solid #1a5490;display:inline-block}

        .summary-cards{display:flex;flex-wrap:wrap;gap:8px;margin-bottom:10px}
        .summary-card{
            flex:1 1 180px;border-radius:8px;border:1px solid rgba(0,0,0,.06);
            padding:7px 9px;background:#fdfdff;font-size:10px;
        }
        .summary-label{color:#666;margin-bottom:3px}
        .summary-value{font-weight:700;font-size:13px;color:#1a5490}

        h3.section-title{font-size:14px;font-weight:700;color:#1a5490;margin-top:10px;margin-bottom:5px}
        .section-subtitle{font-size:10px;color:#777;margin-bottom:6px}

        .table-wrap{width:100%;max-width:100%;margin:0 auto;overflow-x:auto}
        table{
            width:100% !important;max-width:100% !important;margin:0 auto;border-collapse:collapse;
            font-size:10px;margin-top:6px;margin-bottom:10px;table-layout:fixed;
        }
        thead{background:linear-gradient(135deg,#1a5490 0%,#2d7ab8 100%);color:#fff}
        th{
            border:1px solid #1a5490;padding:6px 5px;text-align:center;font-weight:700;font-size:10px;
            white-space:nowrap;overflow:hidden;text-overflow:ellipsis;
        }
        td{
            border:1px solid #ddd;padding:5px 5px;color:#555;font-size:10px;vertical-align:top;
            overflow:hidden;text-overflow:ellipsis;white-space:nowrap;
        }
        tfoot td{
            background:#f4f7fb;
            font-weight:700;
            color:#1a5490;
        }
        tbody tr:nth-child(even){background:#f9f9f9}
        .text-center{text-align:center}
        .text-end{text-align:right}
        .wrap{
            white-space:normal !important;overflow:visible !important;text-overflow:clip !important;
            word-break:break-word;overflow-wrap:anywhere;line-height:1.2;
        }
        .muted{color:#777}
        .small{font-size:9px}
        .block{display:block}

        .badge{
            display:inline-block;
            padding:2px 7px;
            border-radius:999px;
            font-size:9px;
            border:1px solid rgba(0,0,0,.08);
            background:#f8f9fa;
            color:#333;
            white-space:nowrap;
        }
        .badge.draft{background:#f2f2f2;color:#555}
        .badge.approved{background:#e7f1ff;color:#1a5490;border-color:#cfe2ff}
        .badge.paid{background:#eaf7ef;color:#0f5132;border-color:#cfe9d8}

        .print-footer{
            display:none;
        }

        @media print{
            .no-print{display:none!important}
            @page{ margin: 8mm 10mm; }

            .table-wrap{ overflow: hidden; }
            thead{display:table-header-group}
            tfoot{display:table-footer-group}
            table{page-break-inside:auto; break-inside:auto}
            tbody{display:table-row-group}
            tr{page-break-inside:auto !important; break-inside:auto !important}
            .main-header, .filters, .summary-cards{page-break-inside:avoid;break-inside:avoid-page}
            .container{padding:0}

            thead th{-webkit-print-color-adjust:exact; print-color-adjust:exact}
            tfoot td{-webkit-print-color-adjust:exact; print-color-adjust:exact}

            table{font-size:9.2px}
            th{font-size:9.2px;padding:4px 4px}
            td{font-size:9.2px;padding:4px 4px}
            .small{font-size:8.6px}

            .print-footer{
                display:flex;
                position:fixed;
                bottom:0;
                left:0;
                right:0;
                padding:4px 10mm;
                border-top:1px solid #ddd;
                background:#fff;
                font-size:9px;
                color:#666;
                justify-content:space-between;
                align-items:center;
            }
            .page-counter::after{
                content: " {{ $rtl ? 'صفحة' : 'Page' }} " counter(page) " / " counter(pages);
            }
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
        {{ $tt('reports.landscape_mode', 'وضع أفقي') }}
    </label>

    <button onclick="window.print()"
            style="background:#1a5490;color:#fff;border:none;padding:8px 16px;border-radius:4px;cursor:pointer;font-size:14px;font-weight:600">
        {{ $tt('reports.print', 'طباعة') }}
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
            <div>
                {{ $tt('reports.generated_at', 'تاريخ الإنشاء') }}:
                {{ $meta['generated_at'] ?? now('Africa/Cairo')->format('Y-m-d H:i') }}
            </div>
            <div>
                {{ $tt('reports.items_count', 'عدد السجلات') }}:
                {{ $meta['total_count'] ?? $rows->count() }}
            </div>
            <div>
                {{ $t('hr.total_net', 'إجمالي الصافي') }}:
                {{ $money($kpis['sum_net'] ?? 0) }}
            </div>
        </div>
    </div>

    @if(!empty($chips))
        <div class="filters">
            <div class="filters-title">{{ $tt('reports.filters_title', 'عوامل التصفية') }}</div>
            <div class="filter-row">
                @foreach($chips as $c)
                    <div class="filter-item"><span class="filter-value">{{ $c }}</span></div>
                @endforeach
            </div>
        </div>
    @endif

    <div class="summary-cards">
        <div class="summary-card">
            <div class="summary-label">{{ $t('hr.total_rows', 'إجمالي السجلات') }}</div>
            <div class="summary-value">{{ (int)($kpis['total_rows'] ?? $rows->count()) }}</div>
            <div class="muted small" style="margin-top:4px;line-height:1.4">
                {{ $t('hr.draft', 'مسودة') }}: {{ (int)($kpis['draft'] ?? 0) }} |
                {{ $t('hr.approved', 'معتمد') }}: {{ (int)($kpis['approved'] ?? 0) }} |
                {{ $t('hr.paid', 'مدفوع') }}: {{ (int)($kpis['paid'] ?? 0) }}
            </div>
        </div>

        <div class="summary-card">
            <div class="summary-label">{{ $t('hr.total_salary_base', 'إجمالي الأساسي') }}</div>
            <div class="summary-value">{{ $money($kpis['sum_base'] ?? 0) }}</div>
            <div class="muted small" style="margin-top:4px">
                {{ $t('hr.total_gross', 'إجمالي الإجمالي') }}: {{ $money($kpis['sum_gross'] ?? 0) }}
            </div>
        </div>

        <div class="summary-card">
            <div class="summary-label">{{ $t('hr.total_overtime', 'إجمالي الإضافي') }}</div>
            <div class="summary-value">{{ $money($kpis['sum_overtime'] ?? 0) }}</div>
            <div class="muted small" style="margin-top:4px">
                {{ $t('hr.total_allowances', 'إجمالي البدلات') }}: {{ $money($kpis['sum_allowances'] ?? 0) }}
            </div>
        </div>

        <div class="summary-card">
            <div class="summary-label">{{ $t('hr.total_deductions', 'إجمالي الخصومات') }}</div>
            <div class="summary-value">{{ $money($kpis['sum_deductions'] ?? 0) }}</div>
            <div class="muted small" style="margin-top:4px">
                {{ $t('hr.total_advances', 'إجمالي السلف') }}: {{ $money($kpis['sum_advances'] ?? 0) }}
            </div>
        </div>

        <div class="summary-card">
            <div class="summary-label">{{ $t('hr.total_net', 'إجمالي الصافي') }}</div>
            <div class="summary-value">{{ $money($kpis['sum_net'] ?? 0) }}</div>
        </div>
    </div>

    <h3 class="section-title">{{ $t('hr.payrolls_list', 'قائمة الرواتب') }}</h3>
    <div class="section-subtitle">{{ $tt('reports.items_count', 'عدد السجلات') }}: {{ $rows->count() }}</div>

    <div class="table-wrap">
        <table id="reportTable">
            <colgroup>
                <col style="width:3%">
                <col style="width:16%">
                <col style="width:6%">
                <col style="width:7%">
                <col style="width:7%">
                <col style="width:7%">
                <col style="width:7%">
                <col style="width:7%">
                <col style="width:7%">
                <col style="width:8%">
                <col style="width:12%">
                <col style="width:5%">
                <col style="width:8%">
            </colgroup>

            <thead>
                <tr>
                    <th>#</th>
                    <th>{{ $t('hr.employee', 'الموظف') }}</th>
                    <th>{{ $t('hr.month', 'الشهر') }}</th>
                    <th>{{ $t('hr.base_salary', 'الأساسي') }}</th>
                    <th>{{ $t('hr.overtime', 'إضافي') }}</th>
                    <th>{{ $t('hr.allowances', 'بدلات') }}</th>
                    <th>{{ $t('hr.advances', 'سلف') }}</th>
                    <th>{{ $t('hr.deductions', 'خصومات') }}</th>
                    <th>{{ $t('hr.net_salary', 'الصافي') }}</th>
                    <th>{{ $t('hr.payment_method', 'طريقة الصرف') }}</th>
                    <th>{{ $t('hr.payment_details', 'بيان الصرف') }}</th>
                    <th>{{ $t('hr.status', 'الحالة') }}</th>
                    <th>{{ $t('hr.payment_date', 'تاريخ الصرف') }}</th>
                </tr>
            </thead>

            <tbody>
                @forelse($rows as $i => $r)
                    @php
                        $month   = $r->month ? Carbon::parse($r->month)->format('Y-m') : '-';

                        $empName = $r->employee?->full_name
                            ?? $r->employee?->fullname
                            ?? $r->employee?->name
                            ?? '-';

                        $empCode = $r->employee?->code ?? '';

                        $st = strtolower(trim((string)($r->status ?? '')));
                        $badgeClass = ($st === 'draft') ? 'draft' : (($st === 'approved') ? 'approved' : (($st === 'paid') ? 'paid' : ''));
                    @endphp
                    <tr>
                        <td class="text-center">{{ $i + 1 }}</td>

                        <td class="wrap">
                            <span class="block">{{ $empName }}</span>
                            <span class="block muted small">{{ $empCode ? '(' . $empCode . ')' : '' }}</span>
                        </td>

                        <td class="text-center"><code>{{ $month }}</code></td>

                        <td class="text-end">{{ $money($r->base_salary ?? 0) }}</td>
                        <td class="text-end">{{ $money($r->overtime_amount ?? 0) }}</td>
                        <td class="text-end">{{ $money($r->allowances_amount ?? 0) }}</td>
                        <td class="text-end">{{ $money($r->advances_deduction ?? 0) }}</td>
                        <td class="text-end">{{ $money($r->deductions_amount ?? 0) }}</td>
                        <td class="text-end">{{ $money($r->net_salary ?? 0) }}</td>

                        <td class="text-center">{{ $r->payment_method ?: '-' }}</td>
                        <td class="wrap">{{ $r->salary_transfer_details ?: '-' }}</td>

                        <td class="text-center">
                            <span class="badge {{ $badgeClass }}">{{ $statusText($r->status ?? '') }}</span>
                        </td>

                        <td class="text-center">
                            {{ $r->payment_date ? Carbon::parse($r->payment_date)->toDateString() : '-' }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="13" class="text-center" style="padding:18px;color:#999">
                            {{ $tt('reports.no_results', 'لا توجد نتائج') }}
                        </td>
                    </tr>
                @endforelse
            </tbody>

            @if($rows->count())
                <tfoot>
                    <tr>
                        <td colspan="3" class="text-center">{{ $t('hr.total', 'الإجمالي') }}</td>
                        <td class="text-end">{{ $money($kpis['sum_base'] ?? 0) }}</td>
                        <td class="text-end">{{ $money($kpis['sum_overtime'] ?? 0) }}</td>
                        <td class="text-end">{{ $money($kpis['sum_allowances'] ?? 0) }}</td>
                        <td class="text-end">{{ $money($kpis['sum_advances'] ?? 0) }}</td>
                        <td class="text-end">{{ $money($kpis['sum_deductions'] ?? 0) }}</td>
                        <td class="text-end">{{ $money($kpis['sum_net'] ?? 0) }}</td>
                        <td colspan="4"></td>
                    </tr>
                </tfoot>
            @endif
        </table>
    </div>

</div>

<div class="print-footer">
    <div>
        {{ $tt('reports.generated_at', 'تاريخ الإنشاء') }}:
        {{ $meta['generated_at'] ?? now('Africa/Cairo')->format('Y-m-d H:i') }}
    </div>
    <div class="page-counter"></div>
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
