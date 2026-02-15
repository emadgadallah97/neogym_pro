<!doctype html>
@php
    $rtl = app()->getLocale() === 'ar';

    $meta  = $meta ?? [];
    $chips = $chips ?? [];
    $kpis  = $kpis ?? [];
    $rows  = $rows ?? collect();

    $meta['title'] = $meta['title'] ?? (__('reports.subscriptions_report_title') ?? 'تقرير خطط الاشتراكات');
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

    $trYesNo = function($v) use ($fallback){
        $isAr = app()->getLocale()==='ar';
        if ((string)$v === '1' || $v === true) return $fallback('reports.sub_yes', $isAr?'نعم':'Yes');
        if ((string)$v === '0' || $v === false) return $fallback('reports.sub_no', $isAr?'لا':'No');
        return '-';
    };

    $trStatus = function($v) use ($fallback){
        $isAr = app()->getLocale()==='ar';
        if ((string)$v === '1' || strtolower((string)$v)==='active') return $fallback('reports.sub_status_active', $isAr?'نشط':'Active');
        if ((string)$v === '0' || strtolower((string)$v)==='inactive') return $fallback('reports.sub_status_inactive', $isAr?'غير نشط':'Inactive');
        return ((string)$v !== '') ? (string)$v : '-';
    };

    $trPeriod = function($type, $otherLabel=null) use ($fallback){
        $t = strtolower(trim((string)$type));
        $isAr = app()->getLocale()==='ar';
        if ($t==='') return '-';

        $map = [
            'daily' => $fallback('reports.sub_period_daily', $isAr?'يومي':'Daily'),
            'weekly' => $fallback('reports.sub_period_weekly', $isAr?'أسبوعي':'Weekly'),
            'monthly' => $fallback('reports.sub_period_monthly', $isAr?'شهري':'Monthly'),
            'quarterly' => $fallback('reports.sub_period_quarterly', $isAr?'ربع سنوي':'Quarterly'),
            'semi_yearly' => $fallback('reports.sub_period_semi_yearly', $isAr?'نصف سنوي':'Semi-yearly'),
            'yearly' => $fallback('reports.sub_period_yearly', $isAr?'سنوي':'Yearly'),
            'other' => $fallback('reports.sub_period_other', $isAr?'أخرى':'Other'),
        ];

        $label = $map[$t] ?? $type;

        if ($t==='other' && !empty($otherLabel)) return $label.' - '.$otherLabel;
        return (string)$label;
    };

    // ✅ FIX: Accept sat/sun and full day names (saturday, sunday, ...)
    $normalizeDayKey = function($k){
        $k = strtolower(trim((string)$k));

        $aliases = [
            'saturday' => 'sat', 'sat' => 'sat', 'sa' => 'sat',
            'sunday' => 'sun', 'sun' => 'sun', 'su' => 'sun',
            'monday' => 'mon', 'mon' => 'mon', 'mo' => 'mon',
            'tuesday' => 'tue', 'tue' => 'tue', 'tu' => 'tue',
            'wednesday' => 'wed', 'wed' => 'wed', 'we' => 'wed',
            'thursday' => 'thu', 'thu' => 'thu', 'th' => 'thu',
            'friday' => 'fri', 'fri' => 'fri', 'fr' => 'fri',
        ];

        return $aliases[$k] ?? $k;
    };

    $trDay = function($d) use ($fallback, $normalizeDayKey){
        $k = $normalizeDayKey($d);
        $isAr = app()->getLocale()==='ar';
        $map = [
            'sat' => $fallback('reports.sub_day_sat', $isAr?'السبت':'Sat'),
            'sun' => $fallback('reports.sub_day_sun', $isAr?'الأحد':'Sun'),
            'mon' => $fallback('reports.sub_day_mon', $isAr?'الإثنين':'Mon'),
            'tue' => $fallback('reports.sub_day_tue', $isAr?'الثلاثاء':'Tue'),
            'wed' => $fallback('reports.sub_day_wed', $isAr?'الأربعاء':'Wed'),
            'thu' => $fallback('reports.sub_day_thu', $isAr?'الخميس':'Thu'),
            'fri' => $fallback('reports.sub_day_fri', $isAr?'الجمعة':'Fri'),
        ];
        return $map[$k] ?? (string)$d;
    };

    // ✅ FIX: Support JSON array OR comma-separated string: "saturday, sunday, monday"
    $fmtDays = function($jsonOrArray) use ($trDay){
        if ($jsonOrArray === null || $jsonOrArray === '') return '-';
        $arr = $jsonOrArray;

        if (is_string($arr)) {
            $decoded = json_decode($arr, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $arr = $decoded;
            } else {
                if (strpos($arr, ',') !== false) {
                    $arr = array_map('trim', explode(',', $arr));
                }
            }
        }

        if (!is_array($arr)) {
            $single = trim((string)$arr);
            return $single !== '' ? $trDay($single) : '-';
        }

        $out=[];
        foreach($arr as $d){
            if ($d===null || $d==='') continue;
            $out[] = $trDay($d);
        }
        return !empty($out) ? implode('، ',$out) : '-';
    };

    $parseBranchConcat = function($concat) use ($nameJsonOrText){
        $concat = trim((string)$concat);
        if ($concat === '') return [];

        $items = array_values(array_filter(array_map('trim', explode('||', $concat))));
        $out = [];

        foreach ($items as $it) {
            $parts = explode('::', $it);

            $branchRaw = $parts[0] ?? '';
            $priceRaw = $parts[1] ?? '';
            $activeRaw = $parts[2] ?? '0';

            $bn = $nameJsonOrText($branchRaw) ?: '-';
            $price = trim((string)$priceRaw);
            $price = ($price === '' ? '-' : $price);
            $active = is_numeric($activeRaw) ? (int)$activeRaw : 0;

            $out[] = ['branch' => $bn, 'price' => $price, 'active' => $active];
        }

        return $out;
    };

    $fmt = function($v){
        if ($v===null || $v==='') return '-';
        return (string)$v;
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
        .header-right{flex:0 0 auto;text-align:{{ $rtl ? 'left' : 'right' }};font-size:10px;color:#666;line-height:1.55;min-width:210px;padding:9px 10px;background:#f8f9fa;border-radius:4px;border-{{ $rtl ? 'left' : 'right' }}:3px solid #1a5490;}
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
        .badge{display:inline-block;padding:2px 7px;border-radius:999px;font-size:9px;font-weight:800;white-space:nowrap;}
        .badge-success{background:#e8f5e9;color:#166534;border:1px solid #b7e0c0}
        .badge-danger{background:#fee2e2;color:#991b1b;border:1px solid #fecaca}
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
                {{ __('reports.sub_kpi_active') ?? 'نشط' }}:
                {{ (int)($kpis['active'] ?? 0) }}
            </div>
            <div>
                {{ __('reports.sub_kpi_avg_price') ?? 'متوسط السعر الأساسي' }}:
                {{ (float)($kpis['avg_price'] ?? 0) }}
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
            <div class="summary-label">{{ __('reports.sub_kpi_total') ?? 'إجمالي الخطط' }}</div>
            <div class="summary-value">{{ (int)($kpis['total'] ?? 0) }}</div>
        </div>
        <div class="summary-card">
            <div class="summary-label">{{ __('reports.sub_kpi_active') ?? 'نشط' }}</div>
            <div class="summary-value">{{ (int)($kpis['active'] ?? 0) }}</div>
        </div>
        <div class="summary-card">
            <div class="summary-label">{{ __('reports.sub_kpi_inactive') ?? 'غير نشط' }}</div>
            <div class="summary-value">{{ (int)($kpis['inactive'] ?? 0) }}</div>
        </div>
        <div class="summary-card">
            <div class="summary-label">{{ __('reports.sub_kpi_branches_used') ?? 'فروع مستخدمة' }}</div>
            <div class="summary-value">{{ (int)($kpis['branches_used'] ?? 0) }}</div>
        </div>
    </div>

    <h3 class="section-title">{{ __('reports.sub_table_title') ?? 'تفاصيل الخطط' }}</h3>
    <div class="section-subtitle">{{ __('reports.items_count') ?? 'عدد السجلات' }}: {{ $rows->count() }}</div>

    <div class="table-wrap">
        <table>
            <colgroup>
                <col style="width:3%">
                <col style="width:16%">
                <col style="width:9%">
                <col style="width:12%">
                <col style="width:12%">
                <col style="width:13%">
                <col style="width:10%">
                <col style="width:16%">
                <col style="width:9%">
            </colgroup>
            <thead>
            <tr>
                <th>#</th>
                <th>{{ __('reports.sub_col_plan') ?? 'الخطة' }}</th>
                <th>{{ __('reports.sub_col_status') ?? 'الحالة' }}</th>
                <th>{{ __('reports.sub_col_period') ?? 'الفترة/الأيام' }}</th>
                <th>{{ __('reports.sub_col_limits') ?? 'الحدود' }}</th>
                <th>{{ __('reports.sub_col_guest') ?? 'الضيف' }}</th>
                <th>{{ __('reports.sub_col_freeze') ?? 'التجميد' }}</th>
                <th>{{ __('reports.sub_col_branches_price') ?? 'الفروع/السعر/الاشتراكات' }}</th>
                <th>{{ __('reports.sub_col_created_by') ?? 'مضاف بواسطة' }}</th>
            </tr>
            </thead>
            <tbody>
            @forelse($rows as $i => $r)
                @php
                    $planName = $nameJsonOrText($r->plan_name ?? null) ?: '-';
                    $typeName = $nameJsonOrText($r->type_name ?? null) ?: '-';

                    $statusBadge = ((string)($r->status ?? '') === '1') ? 'badge badge-success' : 'badge badge-danger';
                    $period = $trPeriod($r->sessions_period_type ?? null, $r->sessions_period_other_label ?? null);
                    $days = $fmtDays($r->allowed_training_days ?? null);

                    $guestDays = $fmtDays($r->guest_allowed_days ?? null);

                    $branchItems = $parseBranchConcat($r->branches_price_active_concat ?? '');
                @endphp
                <tr>
                    <td class="text-center">{{ $i + 1 }}</td>

                    <td class="wrap">
                        <span class="block"><span class="muted small">{{ __('reports.sub_col_code') ?? 'كود' }}:</span> {{ $r->code ?? '-' }}</span>
                        <span class="block"><span class="muted small">{{ __('reports.sub_col_name') ?? 'الاسم' }}:</span> {{ $planName }}</span>
                        <span class="block muted small">{{ __('reports.sub_col_type') ?? 'النوع' }}: {{ $typeName }}</span>
                    </td>

                    <td class="text-center">
                        <span class="{{ $statusBadge }}">{{ $trStatus($r->status ?? null) }}</span>
                    </td>

                    <td class="wrap text-center">
                        <span class="block">{{ $period }}</span>
                        <span class="block muted small">{{ __('reports.sub_col_allowed_training_days') ?? 'أيام التدريب' }}: {{ $days }}</span>
                    </td>

                    <td class="wrap text-center">
                        <span class="block">{{ __('reports.sub_col_sessions_count') ?? 'الجلسات' }}: {{ $fmt($r->sessions_count ?? null) }}</span>
                        <span class="block muted small">{{ __('reports.sub_col_duration_days') ?? 'المدة' }}: {{ $fmt($r->duration_days ?? null) }}</span>
                    </td>

                    <td class="wrap">
                        <span class="block">{{ __('reports.sub_col_allow_guest') ?? 'السماح' }}: {{ $trYesNo($r->allow_guest ?? null) }}</span>
                        <span class="block muted small">{{ __('reports.sub_col_guest_people_count') ?? 'عدد الأشخاص' }}: {{ $fmt($r->guest_people_count ?? null) }}</span>
                        <span class="block muted small">{{ __('reports.sub_col_guest_times_count') ?? 'عدد المرات' }}: {{ $fmt($r->guest_times_count ?? null) }}</span>
                        <span class="block muted small">{{ __('reports.sub_col_guest_allowed_days') ?? 'أيام الضيف' }}: {{ $guestDays }}</span>
                    </td>

                    <td class="wrap text-center">
                        <span class="block">{{ __('reports.sub_col_allow_freeze') ?? 'السماح' }}: {{ $trYesNo($r->allow_freeze ?? null) }}</span>
                        <span class="block muted small">{{ __('reports.sub_col_max_freeze_days') ?? 'أقصى أيام' }}: {{ $fmt($r->max_freeze_days ?? null) }}</span>
                    </td>

                    <td class="wrap">
                        <span class="block muted small">{{ __('reports.sub_col_branches_count') ?? 'عدد الفروع' }}: {{ (int)($r->branches_count ?? 0) }}</span>
                        @if(!empty($branchItems))
                            @foreach($branchItems as $bi)
                                <span class="block">
                                    {{ $bi['branch'] ?? '-' }}
                                    — {{ __('reports.sub_branch_price') ?? 'السعر' }}: {{ $bi['price'] ?? '-' }}
                                    — {{ __('reports.sub_active_subs') ?? 'اشتراكات فعالة' }}: {{ (int)($bi['active'] ?? 0) }}
                                </span>
                            @endforeach
                        @else
                            <span class="block">-</span>
                        @endif
                    </td>

                    <td class="wrap text-center">{{ $r->created_by_name ?? '-' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="9" class="text-center" style="padding:18px;color:#999">
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
