<!doctype html>
@php
    use Carbon\Carbon;

    $rtl = app()->getLocale() === 'ar';

    $meta  = $meta ?? [];
    $chips = $chips ?? [];
    $kpis  = $kpis ?? [];
    $rows  = $rows ?? collect();

    $meta['title'] = $meta['title'] ?? (__('reports.members_report_title') ?? 'تقرير الأعضاء');

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

    $fallback = function ($key, $fb) {
        $t = __($key);
        return ($t === $key) ? $fb : $t;
    };

    $translateStatus = function($v) use ($fallback){
        $k = strtolower(trim((string)$v));
        $isAr = app()->getLocale() === 'ar';
        if ($k === 'active') return $fallback('reports.mem_status_active', $isAr ? 'نشط' : 'Active');
        if ($k === 'inactive') return $fallback('reports.mem_status_inactive', $isAr ? 'غير نشط' : 'Inactive');
        if ($k === 'frozen') return $fallback('reports.mem_status_frozen', $isAr ? 'مجمد' : 'Frozen');
        return $v ?: '-';
    };

    $translateGender = function($v) use ($fallback){
        $k = strtolower(trim((string)$v));
        $isAr = app()->getLocale() === 'ar';
        if ($k === 'male') return $fallback('reports.mem_gender_male', $isAr ? 'ذكر' : 'Male');
        if ($k === 'female') return $fallback('reports.mem_gender_female', $isAr ? 'أنثى' : 'Female');
        return $v ?: '-';
    };

    $isFrozenNow = function($status, $from, $to){
        if (($status ?? '') !== 'frozen') return false;
        if (empty($from) || empty($to)) return false;
        $today = Carbon::today();
        return $today->between(Carbon::parse($from), Carbon::parse($to));
    };
@endphp
<html lang="{{ app()->getLocale() }}" dir="{{ $rtl ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <title>{{ $meta['title'] ?? '' }}</title>

    <style>
        *{margin:0;padding:0;box-sizing:border-box}
        body{
            font-family:'Segoe UI',DejaVu Sans,Arial,Tahoma;
            line-height:1.25;
            color:#333;
            background:#fff;
            direction:{{ $rtl ? 'rtl' : 'ltr' }};
        }
        .container{max-width:100%;margin:0 auto;padding:15px}

        .main-header{
            display:flex;
            justify-content:space-between;
            align-items:center;
            gap:14px;
            margin-bottom:14px;
            padding-bottom:14px;
            border-bottom:3px solid #1a5490;
        }
        .header-logo img{max-height:78px;max-width:110px;object-fit:contain}
        .header-center{flex:1;text-align:center}
        .org-name{font-size:22px;font-weight:700;color:#1a5490;margin-bottom:3px}
        .report-title{font-size:18px;font-weight:700;color:#1a5490;margin:8px 0 0}
        .header-right{
            flex:0 0 auto;
            text-align:{{ $rtl ? 'left' : 'right' }};
            font-size:10px;
            color:#666;
            line-height:1.55;
            min-width:210px;
            padding:9px 10px;
            background:#f8f9fa;
            border-radius:4px;
            border-{{ $rtl ? 'left' : 'right' }}:3px solid #1a5490;
        }

        .filters{
            background:#f0f4f8;
            border:1px solid #d0dce6;
            border-radius:6px;
            padding:10px 12px;
            margin-bottom:10px;
        }
        .filters-title{
            font-weight:700;
            color:#1a5490;
            font-size:11px;
            text-transform:uppercase;
            margin-bottom:8px;
        }
        .filter-row{display:flex;flex-wrap:wrap;gap:6px;line-height:1.45}
        .filter-item{font-size:10px}
        .filter-value{
            color:#333;background:#fff;padding:2px 7px;border-radius:3px;border:2px solid #1a5490;display:inline-block
        }

        .summary-cards{display:flex;flex-wrap:wrap;gap:8px;margin-bottom:10px}
        .summary-card{
            flex:1 1 180px;
            border-radius:8px;
            border:1px solid rgba(0,0,0,.06);
            padding:7px 9px;
            background:#fdfdff;
            font-size:10px;
        }
        .summary-label{color:#666;margin-bottom:3px}
        .summary-value{font-weight:700;font-size:13px;color:#1a5490}

        h3.section-title{font-size:14px;font-weight:700;color:#1a5490;margin-top:10px;margin-bottom:5px}
        .section-subtitle{font-size:10px;color:#777;margin-bottom:6px}

        .table-wrap{width:100%;max-width:100%;margin:0 auto;overflow-x:auto}

        table{
            width:100% !important;
            max-width:100% !important;
            margin:0 auto;
            border-collapse:collapse;
            font-size:10px;
            margin-top:6px;
            margin-bottom:10px;
            table-layout:fixed;
        }
        thead{
            background:linear-gradient(135deg,#1a5490 0%,#2d7ab8 100%);
            color:#fff;
        }
        th{
            border:1px solid #1a5490;
            padding:6px 5px;
            text-align:center;
            font-weight:700;
            font-size:10px;
            white-space:nowrap;
            overflow:hidden;
            text-overflow:ellipsis;
        }
        td{
            border:1px solid #ddd;
            padding:5px 5px;
            color:#555;
            font-size:10px;
            vertical-align:top;
            overflow:hidden;
            text-overflow:ellipsis;
            white-space:nowrap;
        }
        tbody tr:nth-child(even){background:#f9f9f9}

        .text-center{text-align:center}
        .wrap{
            white-space:normal !important;
            overflow:visible !important;
            text-overflow:clip !important;
            word-break:break-word;
            overflow-wrap:anywhere;
            line-height:1.2;
        }
        .muted{color:#777}
        .small{font-size:9px}
        .block{display:block}

        .badge{
            display:inline-block;
            padding:2px 7px;
            border-radius:999px;
            font-size:9px;
            font-weight:800;
            white-space:nowrap;
        }
        .badge-success{background:#e8f5e9;color:#166534;border:1px solid #b7e0c0}
        .badge-danger{background:#fee2e2;color:#991b1b;border:1px solid #fecaca}
        .badge-warning{background:#fff7ed;color:#9a3412;border:1px solid #fed7aa}

        @media print{
            .no-print{display:none!important}
            @page{ margin: 8mm 10mm; }
            .table-wrap{ overflow: hidden; }
            thead{display:table-header-group}
            table{page-break-inside:auto; break-inside:auto}
            tr{page-break-inside:auto !important; break-inside:auto !important}
            .main-header, .filters, .summary-cards{
                page-break-inside:avoid;
                break-inside:avoid-page;
            }
            .container{padding:0}
            thead th{-webkit-print-color-adjust:exact; print-color-adjust:exact}
            table{font-size:9.2px}
            th{font-size:9.2px;padding:4px 4px}
            td{font-size:9.2px;padding:4px 4px}
            .small{font-size:8.6px}
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
            <div>
                {{ __('reports.generated_at') ?? 'تاريخ الإنشاء' }}:
                {{ $meta['generated_at'] ?? now('Africa/Cairo')->format('Y-m-d H:i') }}
            </div>
            <div>
                {{ __('reports.items_count') ?? 'عدد السجلات' }}:
                {{ $meta['total_count'] ?? $rows->count() }}
            </div>
            <div>
                {{ __('reports.mem_kpi_active') ?? 'نشط' }}: {{ (int)($kpis['active'] ?? 0) }}
            </div>
            <div>
                {{ __('reports.mem_kpi_frozen_now') ?? 'مجمد الآن' }}: {{ (int)($kpis['frozen_now'] ?? 0) }}
            </div>
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
            <div class="summary-label">{{ __('reports.mem_kpi_total') ?? 'إجمالي الأعضاء' }}</div>
            <div class="summary-value">{{ (int)($kpis['total'] ?? 0) }}</div>
        </div>
        <div class="summary-card">
            <div class="summary-label">{{ __('reports.mem_kpi_active') ?? 'نشط' }}</div>
            <div class="summary-value">{{ (int)($kpis['active'] ?? 0) }}</div>
        </div>
        <div class="summary-card">
            <div class="summary-label">{{ __('reports.mem_kpi_inactive') ?? 'غير نشط' }}</div>
            <div class="summary-value">{{ (int)($kpis['inactive'] ?? 0) }}</div>
        </div>
        <div class="summary-card">
            <div class="summary-label">{{ __('reports.mem_kpi_frozen') ?? 'مجمد' }}</div>
            <div class="summary-value">{{ (int)($kpis['frozen'] ?? 0) }}</div>
        </div>
    </div>

    <h3 class="section-title">{{ __('reports.mem_table_title') ?? 'تفاصيل الأعضاء' }}</h3>
    <div class="section-subtitle">{{ __('reports.items_count') ?? 'عدد السجلات' }}: {{ $rows->count() }}</div>

    <div class="table-wrap">
        <table>
            <colgroup>
                <col style="width:3%">
                <col style="width:14%">
                <col style="width:9%">
                <col style="width:7%">
                <col style="width:6%">
                <col style="width:10%">
                <col style="width:16%">
                <col style="width:9%">
                <col style="width:16%">
                <col style="width:10%">
            </colgroup>
            <thead>
            <tr>
                <th>#</th>
                <th>{{ __('reports.mem_col_member') ?? 'العضو' }}</th>
                <th>{{ __('reports.mem_col_branch') ?? 'الفرع' }}</th>
                <th>{{ __('reports.mem_col_status') ?? 'الحالة' }}</th>
                <th>{{ __('reports.mem_col_gender') ?? 'النوع' }}</th>
                <th>{{ __('reports.mem_col_dates') ?? 'تواريخ' }}</th>
                <th>{{ __('reports.mem_col_location') ?? 'الموقع' }}</th>
                <th>{{ __('reports.mem_col_body') ?? 'قياسات' }}</th>
                <th>{{ __('reports.mem_col_medical') ?? 'طبي/ملاحظات' }}</th>
                <th>{{ __('reports.mem_col_added_by') ?? 'مضاف بواسطة' }}</th>
            </tr>
            </thead>
            <tbody>
            @forelse($rows as $i => $r)
                @php
                    $branchName = $nameJsonOrText($r->branch_name ?? null) ?: '-';
                    $govName = $nameJsonOrText($r->gov_name ?? null) ?: '-';
                    $cityName = $nameJsonOrText($r->city_name ?? null) ?: '-';
                    $areaName = $nameJsonOrText($r->area_name ?? null) ?: '-';

                    $name = trim(($r->first_name ?? '') . ' ' . ($r->last_name ?? '')) ?: '-';
                    $code = $r->member_code ?? '-';

                    $statusLbl = $translateStatus($r->status ?? null);
                    $genderLbl = $translateGender($r->gender ?? null);

                    $join = !empty($r->join_date) ? Carbon::parse($r->join_date)->format('Y-m-d') : '-';
                    $birth = !empty($r->birth_date) ? Carbon::parse($r->birth_date)->format('Y-m-d') : '-';

                    $freezeRange = '-';
                    $frozenNow = $isFrozenNow($r->status ?? null, $r->freeze_from ?? null, $r->freeze_to ?? null);
                    if (($r->status ?? '') === 'frozen') {
                        $from = !empty($r->freeze_from) ? Carbon::parse($r->freeze_from)->format('Y-m-d') : '---';
                        $to = !empty($r->freeze_to) ? Carbon::parse($r->freeze_to)->format('Y-m-d') : '---';
                        $freezeRange = $from . ' ⟶ ' . $to;
                    }

                    $statusBadge = 'badge badge-danger';
                    if (($r->status ?? '') === 'active') $statusBadge = 'badge badge-success';
                    if (($r->status ?? '') === 'frozen') $statusBadge = 'badge badge-warning';

                    $height = ($r->height !== null && $r->height !== '') ? $r->height : '-';
                    $weight = ($r->weight !== null && $r->weight !== '') ? $r->weight : '-';

                    $phone = $r->phone ?: '-';
                    $whatsapp = $r->whatsapp ?: '-';
                    $email = $r->email ?: '-';

                    $medical = $r->medical_conditions ?: '-';
                    $allergies = $r->allergies ?: '-';
                    $notes = $r->notes ?: '-';
                @endphp

                <tr>
                    <td class="text-center">{{ $i + 1 }}</td>

                    <td class="wrap">
                        <span class="block"><span class="muted small">{{ __('reports.mem_col_member_code') ?? 'كود' }}:</span> {{ $code }}</span>
                        <span class="block"><span class="muted small">{{ __('reports.mem_col_name') ?? 'الاسم' }}:</span> {{ $name }}</span>
                        <span class="block muted small">{{ __('reports.mem_col_phone') ?? 'موبايل' }}: {{ $phone }} | {{ __('reports.mem_col_whatsapp') ?? 'واتساب' }}: {{ $whatsapp }}</span>
                        <span class="block muted small">{{ __('reports.mem_col_email') ?? 'Email' }}: {{ $email }}</span>
                    </td>

                    <td class="wrap text-center">{{ $branchName }}</td>

                    <td class="wrap text-center">
                        <span class="{{ $statusBadge }}">{{ $statusLbl }}</span>
                        @if(($r->status ?? '') === 'frozen')
                            <div class="muted small">{{ __('reports.mem_col_freeze_range') ?? 'فترة التجميد' }}: {{ $freezeRange }}</div>
                            @if($frozenNow)
                                <div class="muted small">{{ __('reports.mem_frozen_now_badge') ?? 'مجمد الآن' }}</div>
                            @endif
                        @endif
                    </td>

                    <td class="text-center">{{ $genderLbl }}</td>

                    <td class="wrap text-center">
                        <span class="block">{{ __('reports.mem_col_join_date') ?? 'الاشتراك' }}: {{ $join }}</span>
                        <span class="block muted small">{{ __('reports.mem_col_birth_date') ?? 'الميلاد' }}: {{ $birth }}</span>
                    </td>

                    <td class="wrap">
                        <span class="block"><span class="muted small">{{ __('reports.mem_col_government') ?? 'محافظة' }}:</span> {{ $govName }}</span>
                        <span class="block muted small">{{ __('reports.mem_col_city') ?? 'مدينة' }}: {{ $cityName }}</span>
                        <span class="block muted small">{{ __('reports.mem_col_area') ?? 'منطقة' }}: {{ $areaName }}</span>
                    </td>

                    <td class="wrap text-center">
                        <span class="block">{{ __('reports.mem_col_height') ?? 'طول' }}: {{ $height }}</span>
                        <span class="block muted small">{{ __('reports.mem_col_weight') ?? 'وزن' }}: {{ $weight }}</span>
                    </td>

                    <td class="wrap">
                        <span class="block"><span class="muted small">{{ __('reports.mem_col_medical_conditions') ?? 'حالات' }}:</span> {{ $medical }}</span>
                        <span class="block muted small">{{ __('reports.mem_col_allergies') ?? 'حساسية' }}: {{ $allergies }}</span>
                        <span class="block muted small">{{ __('reports.mem_col_notes') ?? 'ملاحظات' }}: {{ $notes }}</span>
                    </td>

                    <td class="wrap text-center">{{ $r->added_by_name ?? '-' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="10" class="text-center" style="padding:18px;color:#999">
                        {{ __('reports.no_results') ?? 'لا توجد نتائج' }}
                    </td>
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
