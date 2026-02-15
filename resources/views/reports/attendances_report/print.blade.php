<!doctype html>
@php
    use Carbon\Carbon;

    $rtl = app()->getLocale() === 'ar';

    $meta  = $meta ?? [];
    $chips = $chips ?? [];
    $kpis  = $kpis ?? [];
    $rows  = $rows ?? collect();

    $meta['title'] = $meta['title'] ?? (__('reports.attendances_report_title') ?? 'تقرير حضور الأعضاء');

    $locale = app()->getLocale();

    $nameJsonOrText = function ($nameJsonOrText) use ($locale) {
        if ($nameJsonOrText === null) return '';
        if (is_array($nameJsonOrText)) {
            return $nameJsonOrText[$locale] ?? ($nameJsonOrText['ar'] ?? ($nameJsonOrText['en'] ?? reset($nameJsonOrText)));
        }

        $v = (string)$nameJsonOrText;
        $decoded = json_decode($v, true);

        if (json_last_error() === JSON_ERROR_NONE) {
            if (is_array($decoded)) {
                return $decoded[$locale] ?? ($decoded['ar'] ?? ($decoded['en'] ?? reset($decoded)));
            }
            if (is_string($decoded)) {
                return $decoded;
            }
        }

        return $v;
    };

    $fmtDate = function ($v, $format) {
        if (empty($v)) return '-';
        try { return Carbon::parse($v)->format($format); } catch (\Throwable $e) { return '-'; }
    };

    // Normalize day key and translate (supports monday..sunday and mon..sun and Arabic labels if passed)
    $normalizeDayKey = function ($dayKey) {
        $v = trim((string)$dayKey);
        if ($v === '') return null;

        $k = strtolower($v);

        $shortToFull = [
            'mon' => 'monday',
            'tue' => 'tuesday',
            'wed' => 'wednesday',
            'thu' => 'thursday',
            'fri' => 'friday',
            'sat' => 'saturday',
            'sun' => 'sunday',
        ];
        if (isset($shortToFull[$k])) return $shortToFull[$k];

        if (in_array($k, ['monday','tuesday','wednesday','thursday','friday','saturday','sunday'], true)) {
            return $k;
        }

        $arToFull = [
            'الاثنين' => 'monday',
            'الثلاثاء' => 'tuesday',
            'الأربعاء' => 'wednesday',
            'الاربعاء' => 'wednesday',
            'الخميس' => 'thursday',
            'الجمعة' => 'friday',
            'السبت' => 'saturday',
            'الأحد' => 'sunday',
            'الاحد' => 'sunday',
        ];
        if (isset($arToFull[$v])) return $arToFull[$v];

        return $k;
    };

    $translateDayKey = function ($dayKey) use ($normalizeDayKey) {
        $k = $normalizeDayKey($dayKey);
        if (empty($k)) return '-';

        $isAr = app()->getLocale() === 'ar';

        $fallback = function ($key, $fb) {
            $t = __($key);
            return ($t === $key) ? $fb : $t;
        };

        $map = [
            'saturday'   => $fallback('reports.att_day_sat', $isAr ? 'السبت' : 'Saturday'),
            'sunday'     => $fallback('reports.att_day_sun', $isAr ? 'الأحد' : 'Sunday'),
            'monday'     => $fallback('reports.att_day_mon', $isAr ? 'الاثنين' : 'Monday'),
            'tuesday'    => $fallback('reports.att_day_tue', $isAr ? 'الثلاثاء' : 'Tuesday'),
            'wednesday'  => $fallback('reports.att_day_wed', $isAr ? 'الأربعاء' : 'Wednesday'),
            'thursday'   => $fallback('reports.att_day_thu', $isAr ? 'الخميس' : 'Thursday'),
            'friday'     => $fallback('reports.att_day_fri', $isAr ? 'الجمعة' : 'Friday'),
        ];

        return $map[$k] ?? ((string)$dayKey ?: '-');
    };

    // Method translation (manual / barcode) - supports "Barcode" too
    $methodText = function ($v) {
        $v = strtolower(trim((string)$v));
        if ($v === 'manual') return __('reports.att_method_manual') ?? 'Manual';
        if ($v === 'barcode') return __('reports.att_method_barcode') ?? 'Barcode';
        return $v ?: '-';
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

        /* wrapper: توسيط الجدول + منع خروجه */
        .table-wrap{
            width:100%;
            max-width:100%;
            margin:0 auto;     /* center */
            overflow-x:auto;   /* في الشاشة فقط: لو ضاقت النافذة */
        }

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
        .text-end{text-align:{{ $rtl ? 'left' : 'right' }}}

        /* Wrap فقط للأعمدة الكبيرة */
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

        @media print{
            .no-print{display:none!important}

            /* الاتجاه يتم التحكم به من style#print-orientation */
            @page{ margin: 8mm 10mm; }

            /* في الطباعة: لا سكرول وأي بروز يتم قصه داخل الصفحة */
            .table-wrap{ overflow: hidden; }

            /* تكرار header الجدول في كل صفحة */
            thead{display:table-header-group}
            tfoot{display:table-footer-group}

            /* اسمح بكسر الصفحات طبيعيًا بدون فراغات كبيرة */
            table{page-break-inside:auto; break-inside:auto}
            tbody{display:table-row-group}
            tr{page-break-inside:auto !important; break-inside:auto !important}

            /* تجنب كسر أجزاء الهيدر/الفلاتر/الـ KPIs */
            .main-header, .filters, .summary-cards{
                page-break-inside:avoid;
                break-inside:avoid-page;
            }

            .container{padding:0}

            thead th{-webkit-print-color-adjust:exact; print-color-adjust:exact}

            /* تكثيف للطباعة */
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

    {{-- Print controls --}}
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

        {{-- Header --}}
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
                    {{ __('reports.items_count') ?? 'عدد الحركات' }}:
                    {{ $meta['total_count'] ?? $rows->count() }}
                </div>
                <div>
                    {{ __('reports.att_kpi_unique_members') ?? 'أعضاء فريدين' }}:
                    {{ (int)($kpis['unique_members'] ?? 0) }}
                </div>
                <div>
                    {{ __('reports.att_kpi_cancelled') ?? 'ملغي' }}:
                    {{ (int)($kpis['cancelled'] ?? 0) }}
                </div>
            </div>
        </div>

        {{-- Chips --}}
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

        {{-- Summary --}}
        <div class="summary-cards">
            <div class="summary-card">
                <div class="summary-label">{{ __('reports.att_kpi_total') ?? 'إجمالي الحضور' }}</div>
                <div class="summary-value">{{ (int)($kpis['total'] ?? 0) }}</div>
            </div>
            <div class="summary-card">
                <div class="summary-label">{{ __('reports.att_kpi_unique_members') ?? 'أعضاء فريدين' }}</div>
                <div class="summary-value">{{ (int)($kpis['unique_members'] ?? 0) }}</div>
            </div>
            <div class="summary-card">
                <div class="summary-label">{{ __('reports.att_kpi_guests_total') ?? 'إجمالي الضيوف' }}</div>
                <div class="summary-value">{{ (int)($kpis['guests_total'] ?? 0) }}</div>
            </div>
            <div class="summary-card">
                <div class="summary-label">{{ __('reports.att_kpi_methods') ?? 'طرق الدخول' }}</div>
                <div class="summary-value">
                    {{ __('reports.att_method_manual') ?? 'Manual' }}: {{ (int)($kpis['manual'] ?? 0) }}
                    | {{ __('reports.att_method_barcode') ?? 'Barcode' }}: {{ (int)($kpis['barcode'] ?? 0) }}
                </div>
            </div>
        </div>

        {{-- Table --}}
        <h3 class="section-title">{{ __('reports.att_table_title') ?? 'تفاصيل الحضور' }}</h3>
        <div class="section-subtitle">
            {{ __('reports.items_count') ?? 'عدد الحركات' }}: {{ $rows->count() }}
        </div>

        <div class="table-wrap">
            <table id="reportTable">

                {{-- تثبيت عرض العمود الأخير + ضمان عدم خروج الجدول: نسب % مجموعها 100% --}}
                <colgroup>
                    <col style="width:3%">   {{-- # --}}
                    <col style="width:7%">   {{-- التاريخ --}}
                    <col style="width:5%">   {{-- الفرع --}}
                    <col style="width:14%">  {{-- العضو --}}
                    <col style="width:6%">   {{-- طريقة الدخول --}}
                    <col style="width:7%">   {{-- مسجل بواسطة --}}
                    <col style="width:10%">  {{-- ملغي --}}
                    <col style="width:11%">  {{-- Subscription --}}
                    <col style="width:8%">   {{-- PT --}}
                    <col style="width:8%">   {{-- Device/Gate --}}
                    <col style="width:4%">   {{-- اليوم --}}
                    <col style="width:3%">   {{-- ضيوف --}}
                    <col style="width:14%">  {{-- ملاحظات (ثابت) --}}
                </colgroup>

                <thead>
                    <tr>
                        <th>#</th>
                        <th>{{ __('reports.att_col_date') ?? 'التاريخ/الوقت' }}</th>
                        <th>{{ __('reports.att_col_branch') ?? 'الفرع' }}</th>
                        <th>{{ __('reports.att_col_member') ?? 'العضو' }}</th>
                        <th>{{ __('reports.att_col_method') ?? 'طريقة الدخول' }}</th>
                        <th>{{ __('reports.att_col_recorded_by') ?? 'مسجل بواسطة' }}</th>
                        <th>{{ __('reports.att_col_cancelled') ?? 'ملغي' }}</th>
                        <th>{{ __('reports.att_col_subscription_id') ?? 'Subscription' }}</th>
                        <th>{{ __('reports.att_col_pt_trainer') ?? 'PT' }}</th>
                        <th>{{ __('reports.att_col_device') ?? 'Device/Gate' }}</th>
                        <th>{{ __('reports.att_col_day_key') ?? 'اليوم' }}</th>
                        <th>{{ __('reports.att_col_guests') ?? 'ضيوف' }}</th>
                        <th>{{ __('reports.att_col_notes') ?? 'ملاحظات' }}</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse($rows as $i => $r)
                        @php
                            $branchName = $nameJsonOrText($r->branch_name ?? null) ?: '-';
                            $planName   = $nameJsonOrText($r->plan_name ?? null) ?: '-';

                            $memberName  = trim(($r->member_first_name ?? '') . ' ' . ($r->member_last_name ?? '')) ?: '-';
                            $memberCode  = $r->member_code ?? '-';
                            $memberPhone = $r->member_phone ?: ($r->member_phone2 ?: ($r->member_whatsapp ?: '-'));

                            $isCancelled = (int)($r->is_cancelled ?? 0) === 1;
                            $badgeClass  = $isCancelled ? 'badge badge-danger' : 'badge badge-success';
                            $badgeText   = $isCancelled ? (__('reports.att_cancelled') ?? 'ملغي') : (__('reports.att_not_cancelled') ?? 'غير ملغي');

                            $attDate = $fmtDate($r->attendance_date ?? null, 'Y-m-d');
                            $attTime = $r->attendance_time ?? '-';

                            $cancelAt = $fmtDate($r->cancelled_at ?? null, 'Y-m-d H:i');
                            $cancelBy = $r->cancelled_by_name ?? '-';

                            $subId    = $r->member_subscription_id ?? '-';
                            // FIX: match fields from buildQuery (sub_start_date / sub_end_date)
                            $subStart = $fmtDate($r->sub_start_date ?? null, 'Y-m-d');
                            $subEnd   = $fmtDate($r->sub_end_date ?? null, 'Y-m-d');

                            $ptAddonId = $r->pt_addon_id ?? '-';
                            $ptTrainer = $r->pt_trainer_name ?? '-';

                            $deviceId = $r->device_id ?? '-';
                            $gateId   = $r->gate_id ?? '-';

                            // FIX: translate day
                            $dayText = $translateDayKey($r->day_key ?? null);

                            $notes = $r->notes ?? '-';
                        @endphp

                        <tr>
                            <td class="text-center">{{ $i + 1 }}</td>

                            <td class="wrap text-center">
                                <span class="block">{{ $attDate }}</span>
                                <span class="block muted small">{{ $attTime }}</span>
                            </td>

                            <td class="wrap text-center">{{ $branchName }}</td>

                            <td class="wrap">
                                <span class="block"><span class="muted small">{{ __('reports.att_col_member_code') ?? 'كود' }}:</span> {{ $memberCode }}</span>
                                <span class="block"><span class="muted small">{{ __('reports.att_col_member') ?? 'العضو' }}:</span> {{ $memberName }}</span>
                                <span class="block muted small">{{ __('reports.att_col_phone') ?? 'الموبايل' }}: {{ $memberPhone }}</span>
                            </td>

                            <td class="text-center">{{ $methodText($r->checkin_method ?? '') }}</td>

                            <td class="wrap text-center">{{ $r->recorded_by_name ?? '-' }}</td>

                            <td class="wrap text-center">
                                <span class="{{ $badgeClass }}">{{ $badgeText }}</span>
                                @if($isCancelled)
                                    <span class="block muted small">{{ __('reports.att_col_cancelled_at') ?? 'تاريخ الإلغاء' }}: {{ $cancelAt }}</span>
                                    <span class="block muted small">{{ __('reports.att_col_cancelled_by') ?? 'أُلغي بواسطة' }}: {{ $cancelBy }}</span>
                                @endif
                            </td>

                            <td class="wrap">
                                <span class="block"><span class="muted small">ID:</span> {{ $subId }}</span>
                                <span class="block"><span class="muted small">{{ __('reports.att_col_plan_name') ?? 'الخطة' }}:</span> {{ $planName }}</span>
                                <span class="block muted small">{{ __('reports.att_col_date') ?? 'الفترة' }}: {{ $subStart }} - {{ $subEnd }}</span>
                            </td>

                            <td class="wrap">
                                <span class="block"><span class="muted small">Addon:</span> {{ $ptAddonId }}</span>
                                <span class="block muted small">{{ $ptTrainer }}</span>
                            </td>

                            <td class="wrap text-center">
                                <span class="block"><span class="muted small">D:</span> {{ $deviceId }}</span>
                                <span class="block"><span class="muted small">G:</span> {{ $gateId }}</span>
                            </td>

                            <td class="text-center">{{ $dayText }}</td>

                            <td class="text-center">{{ (int)($r->guests_count ?? 0) }}</td>

                            <td class="wrap">{{ $notes }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="13" class="text-center" style="padding:18px;color:#999">
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
