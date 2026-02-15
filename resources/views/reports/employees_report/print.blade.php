<!doctype html>
@php
    use Carbon\Carbon;

    $rtl = app()->getLocale() === 'ar';

    $meta  = $meta ?? [];
    $chips = $chips ?? [];
    $kpis  = $kpis ?? [];
    $rows  = $rows ?? collect();

    $meta['title'] = $meta['title'] ?? (__('reports.employees_report_title') ?? 'تقرير بيانات الموظفين');

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

    $fallback = function ($key, $fb) {
        $t = __($key);
        return ($t === $key) ? $fb : $t;
    };

    $normalizeCompensationType = function($v){
        $s = strtolower(trim((string)$v));
        $allowed = ['salary_and_commission', 'salary_only', 'commission_only'];
        return in_array($s, $allowed, true) ? $s : null;
    };
    $translateCompensationType = function($v) use ($normalizeCompensationType, $fallback){
        $k = $normalizeCompensationType($v);
        $isAr = app()->getLocale() === 'ar';
        if ($k === 'salary_and_commission') return $fallback('reports.emp_comp_salary_and_commission', $isAr ? 'راتب + عمولة' : 'Salary & commission');
        if ($k === 'salary_only') return $fallback('reports.emp_comp_salary_only', $isAr ? 'راتب فقط' : 'Salary only');
        if ($k === 'commission_only') return $fallback('reports.emp_comp_commission_only', $isAr ? 'عمولة فقط' : 'Commission only');
        return $v ?: '-';
    };

    $normalizeCommissionValueType = function($v){
        $s = strtolower(trim((string)$v));
        return in_array($s, ['fixed','percent'], true) ? $s : null;
    };
    $translateCommissionValueType = function($v) use ($normalizeCommissionValueType, $fallback){
        $k = $normalizeCommissionValueType($v);
        $isAr = app()->getLocale() === 'ar';
        if ($k === 'fixed') return $fallback('reports.emp_comm_fixed', $isAr ? 'قيمة ثابتة' : 'Fixed');
        if ($k === 'percent') return $fallback('reports.emp_comm_percent', $isAr ? 'نسبة' : 'Percent');
        return $v ?: '-';
    };

    $normalizeSalaryTransferMethod = function($v){
        $s = strtolower(trim((string)$v));
        $allowed = ['ewallet','cash','bank_transfer','instapay','credit_card','cheque','other'];
        return in_array($s, $allowed, true) ? $s : null;
    };
    $translateSalaryTransferMethod = function($v) use ($normalizeSalaryTransferMethod, $fallback){
        $k = $normalizeSalaryTransferMethod($v);
        $isAr = app()->getLocale() === 'ar';
        if ($k === 'ewallet') return $fallback('reports.emp_transfer_ewallet', $isAr ? 'محفظة إلكترونية' : 'E-wallet');
        if ($k === 'cash') return $fallback('reports.emp_transfer_cash', $isAr ? 'نقدًا' : 'Cash');
        if ($k === 'bank_transfer') return $fallback('reports.emp_transfer_bank_transfer', $isAr ? 'تحويل بنكي' : 'Bank transfer');
        if ($k === 'instapay') return $fallback('reports.emp_transfer_instapay', $isAr ? 'InstaPay' : 'InstaPay');
        if ($k === 'credit_card') return $fallback('reports.emp_transfer_credit_card', $isAr ? 'بطاقة' : 'Credit card');
        if ($k === 'cheque') return $fallback('reports.emp_transfer_cheque', $isAr ? 'شيك' : 'Cheque');
        if ($k === 'other') return $fallback('reports.emp_transfer_other', $isAr ? 'أخرى' : 'Other');
        return $v ?: '-';
    };

    $genderText = function($g) use ($fallback){
        $v = strtolower(trim((string)$g));
        $isAr = app()->getLocale() === 'ar';
        if ($v === 'male' || $v === '1') return $fallback('reports.emp_gender_male', $isAr ? 'ذكر' : 'Male');
        if ($v === 'female' || $v === '2') return $fallback('reports.emp_gender_female', $isAr ? 'أنثى' : 'Female');
        return $g ?: '-';
    };

    $statusText = function($s) use ($fallback){
        $v = trim((string)$s);
        $isAr = app()->getLocale() === 'ar';
        if ($v === '1' || strtolower($v)==='active') return $fallback('reports.emp_status_active', $isAr ? 'نشط' : 'Active');
        if ($v === '0' || strtolower($v)==='inactive') return $fallback('reports.emp_status_inactive', $isAr ? 'غير نشط' : 'Inactive');
        return $s ?: '-';
    };

    $coachText = function($v) use ($fallback){
        $isAr = app()->getLocale() === 'ar';
        if ((string)$v === '1') return $fallback('reports.emp_yes', $isAr ? 'نعم' : 'Yes');
        if ((string)$v === '0') return $fallback('reports.emp_no', $isAr ? 'لا' : 'No');
        return '-';
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

        .table-wrap{
            width:100%;
            max-width:100%;
            margin:0 auto;
            overflow-x:auto;
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
            @page{ margin: 8mm 10mm; }
            .table-wrap{ overflow: hidden; }
            thead{display:table-header-group}
            tfoot{display:table-footer-group}
            table{page-break-inside:auto; break-inside:auto}
            tbody{display:table-row-group}
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
                {{ __('reports.emp_kpi_active') ?? 'نشط' }}:
                {{ (int)($kpis['active'] ?? 0) }}
            </div>
            <div>
                {{ __('reports.emp_kpi_coaches') ?? 'مدربين' }}:
                {{ (int)($kpis['coaches'] ?? 0) }}
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
            <div class="summary-label">{{ __('reports.emp_kpi_total') ?? 'إجمالي الموظفين' }}</div>
            <div class="summary-value">{{ (int)($kpis['total'] ?? 0) }}</div>
        </div>
        <div class="summary-card">
            <div class="summary-label">{{ __('reports.emp_kpi_active') ?? 'نشط' }}</div>
            <div class="summary-value">{{ (int)($kpis['active'] ?? 0) }}</div>
        </div>
        <div class="summary-card">
            <div class="summary-label">{{ __('reports.emp_kpi_inactive') ?? 'غير نشط' }}</div>
            <div class="summary-value">{{ (int)($kpis['inactive'] ?? 0) }}</div>
        </div>
        <div class="summary-card">
            <div class="summary-label">{{ __('reports.emp_kpi_avg_base_salary') ?? 'متوسط الراتب الأساسي' }}</div>
            <div class="summary-value">{{ (float)($kpis['avg_base_salary'] ?? 0) }}</div>
        </div>
    </div>

    <h3 class="section-title">{{ __('reports.emp_table_title') ?? 'تفاصيل الموظفين' }}</h3>
    <div class="section-subtitle">{{ __('reports.items_count') ?? 'عدد السجلات' }}: {{ $rows->count() }}</div>

    <div class="table-wrap">
        <table id="reportTable">
            <colgroup>
                <col style="width:3%">
                <col style="width:14%">
                <col style="width:9%">
                <col style="width:12%">
                <col style="width:5%">
                <col style="width:6%">
                <col style="width:5%">
                <col style="width:14%">
                <col style="width:10%">
                <col style="width:8%">
                <col style="width:8%">
                <col style="width:6%">
            </colgroup>

            <thead>
                <tr>
                    <th>#</th>
                    <th>{{ __('reports.emp_col_employee') ?? 'الموظف' }}</th>
                    <th>{{ __('reports.emp_col_job') ?? 'الوظيفة' }}</th>
                    <th>{{ __('reports.emp_col_branches') ?? 'الفروع' }}</th>
                    <th>{{ __('reports.emp_col_gender') ?? 'النوع' }}</th>
                    <th>{{ __('reports.emp_col_status') ?? 'الحالة' }}</th>
                    <th>{{ __('reports.emp_col_is_coach') ?? 'مدرب' }}</th>
                    <th>{{ __('reports.emp_col_compensation') ?? 'التعويض' }}</th>
                    <th>{{ __('reports.emp_col_transfer') ?? 'تحويل الراتب' }}</th>
                    <th>{{ __('reports.emp_col_experience') ?? 'الخبرة' }}</th>
                    <th>{{ __('reports.emp_col_contact') ?? 'التواصل' }}</th>
                    <th>{{ __('reports.emp_col_added_by') ?? 'مضاف بواسطة' }}</th>
                </tr>
            </thead>

            <tbody>
                @forelse($rows as $i => $r)
                    @php
                        $jobName = $nameJsonOrText($r->job_name ?? null) ?: '-';
                        $primaryBranchName = $nameJsonOrText($r->primary_branch_name ?? null) ?: '-';

                        $employeeName = trim(($r->first_name ?? '') . ' ' . ($r->last_name ?? '')) ?: '-';
                        $code = $r->code ?? '-';

                        $phone = $r->phone_1 ?: ($r->phone_2 ?: ($r->whatsapp ?: '-'));
                        $email = $r->email ?: '-';

                        $bio = $r->bio ?: '-';
                        $branchesCount = (int)($r->branches_count ?? 0);

                        $statusLbl = $statusText($r->status ?? null);
                        $statusBadge = ((string)($r->status ?? '') === '1') ? 'badge badge-success' : 'badge badge-danger';

                        $baseSalary = ($r->base_salary !== null && $r->base_salary !== '') ? $r->base_salary : '-';

                        $compTypeLbl = $translateCompensationType($r->compensation_type ?? null);
                        $commTypeLbl = $translateCommissionValueType($r->commission_value_type ?? null);
                        $transferLbl  = $translateSalaryTransferMethod($r->salary_transfer_method ?? null);

                        $spec = $r->specialization ?: '-';
                        $yrs  = ($r->years_experience !== null && $r->years_experience !== '') ? $r->years_experience : '-';
                    @endphp

                    <tr>
                        <td class="text-center">{{ $i + 1 }}</td>

                        <td class="wrap">
                            <span class="block"><span class="muted small">{{ __('reports.emp_col_code') ?? 'كود' }}:</span> {{ $code }}</span>
                            <span class="block"><span class="muted small">{{ __('reports.emp_col_name') ?? 'الاسم' }}:</span> {{ $employeeName }}</span>
                            <span class="block muted small">{{ __('reports.emp_col_bio') ?? 'نبذة' }}: {{ $bio }}</span>
                        </td>

                        <td class="wrap text-center">{{ $jobName }}</td>

                        <td class="wrap">
                            <span class="block"><span class="muted small">{{ __('reports.emp_col_primary_branch') ?? 'الفرع الأساسي' }}:</span> {{ $primaryBranchName }}</span>
                            <span class="block muted small">{{ __('reports.emp_branches_count') ?? 'عدد الفروع' }}: {{ $branchesCount }}</span>
                        </td>

                        <td class="text-center">{{ $genderText($r->gender ?? null) }}</td>

                        <td class="wrap text-center">
                            <span class="{{ $statusBadge }}">{{ $statusLbl }}</span>
                        </td>

                        <td class="text-center">{{ $coachText($r->is_coach ?? null) }}</td>

                        <td class="wrap">
                            <span class="block"><span class="muted small">{{ __('reports.emp_col_compensation_type') ?? 'النوع' }}:</span> {{ $compTypeLbl }}</span>
                            <span class="block muted small">{{ __('reports.emp_col_base_salary') ?? 'الأساسي' }}: {{ $baseSalary }}</span>
                            <span class="block muted small">{{ __('reports.emp_col_commission_value_type') ?? 'نوع العمولة' }}: {{ $commTypeLbl }}</span>
                            <span class="block muted small">{{ __('reports.emp_col_commission_percent') ?? 'نسبة' }}: {{ $r->commission_percent ?? '-' }} | {{ __('reports.emp_col_commission_fixed') ?? 'ثابت' }}: {{ $r->commission_fixed ?? '-' }}</span>
                        </td>

                        <td class="wrap">
                            <span class="block"><span class="muted small">{{ __('reports.emp_col_salary_transfer_method') ?? 'الطريقة' }}:</span> {{ $transferLbl }}</span>
                            <span class="block muted small">{{ $r->salary_transfer_details ?? '-' }}</span>
                        </td>

                        <td class="wrap text-center">
                            <span class="block">{{ $spec }}</span>
                            <span class="block muted small">{{ __('reports.emp_col_years_experience') ?? 'سنوات الخبرة' }}: {{ $yrs }}</span>
                        </td>

                        <td class="wrap">
                            <span class="block"><span class="muted small">{{ __('reports.emp_col_phone') ?? 'موبايل' }}:</span> {{ $phone }}</span>
                            <span class="block muted small">{{ __('reports.emp_col_email') ?? 'Email' }}: {{ $email }}</span>
                        </td>

                        <td class="wrap text-center">{{ $r->added_by_name ?? '-' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="12" class="text-center" style="padding:18px;color:#999">
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
