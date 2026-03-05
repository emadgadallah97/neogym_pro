<!doctype html>
@php
    $rtl = app()->getLocale() === 'ar';
    $locale = app()->getLocale();

    $emp = $settlement->salesEmployee
        ? ($settlement->salesEmployee->fullname ?? trim(($settlement->salesEmployee->first_name ?? '') . ' ' . ($settlement->salesEmployee->last_name ?? '')))
        : null;

    $branchName = null;
    if ($settlement->branch) {
        $branchName = method_exists($settlement->branch, 'getTranslation')
            ? $settlement->branch->getTranslation('name', $locale)
            : ($settlement->branch->name ?? null);
    }

    $status = (string) ($settlement->status ?? '');

    $nameJsonOrText = function ($nameJsonOrText) use ($locale) {
        if ($nameJsonOrText === null)
            return '';
        if (is_array($nameJsonOrText)) {
            return $nameJsonOrText[$locale] ?? ($nameJsonOrText['ar'] ?? ($nameJsonOrText['en'] ?? reset($nameJsonOrText)));
        }
        $v = (string) $nameJsonOrText;
        $decoded = json_decode($v, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            if (is_array($decoded))
                return $decoded[$locale] ?? ($decoded['ar'] ?? ($decoded['en'] ?? reset($decoded)));
            if (is_string($decoded))
                return $decoded;
        }
        return $v;
    };

    $fmt = function ($v) {
        return number_format((float) $v, 2, '.', '');
    };
@endphp
<html lang="{{ app()->getLocale() }}" dir="{{ $rtl ? 'rtl' : 'ltr' }}">

<head>
    <meta charset="utf-8">
    <title>{{ trans('accounting.commissions_print_title') }} #{{ $settlement->id }}</title>

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box
        }

        body {
            font-family: 'Segoe UI', DejaVu Sans, Arial, Tahoma;
            line-height: 1.25;
            color: #333;
            background: #fff;

            direction: {
                    {
                    $rtl ? 'rtl': 'ltr'
                }
            }

            ;
        }

        .container {
            max-width: 100%;
            margin: 0 auto;
            padding: 15px
        }

        .main-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 14px;
            margin-bottom: 14px;
            padding-bottom: 14px;
            border-bottom: 3px solid #1a5490;
        }

        .header-logo img {
            max-height: 78px;
            max-width: 110px;
            object-fit: contain
        }

        .header-center {
            flex: 1;
            text-align: center
        }

        .org-name {
            font-size: 22px;
            font-weight: 700;
            color: #1a5490;
            margin-bottom: 3px
        }

        .report-title {
            font-size: 18px;
            font-weight: 700;
            color: #1a5490;
            margin: 8px 0 0
        }

        .header-right {
            flex: 0 0 auto;

            text-align: {
                    {
                    $rtl ? 'left': 'right'
                }
            }

            ;
            font-size:10px;
            color:#666;
            line-height:1.55;
            min-width:240px;
            padding:9px 10px;
            background:#f8f9fa;
            border-radius:4px;

            border- {
                    {
                    $rtl ? 'left': 'right'
                }
            }

            :3px solid #1a5490;
        }

        .filters {
            background: #f0f4f8;
            border: 1px solid #d0dce6;
            border-radius: 6px;
            padding: 10px 12px;
            margin-bottom: 10px;
        }

        .filters-title {
            font-weight: 700;
            color: #1a5490;
            font-size: 11px;
            text-transform: uppercase;
            margin-bottom: 8px;
        }

        .filter-row {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
            line-height: 1.45
        }

        .filter-item {
            font-size: 10px
        }

        .filter-value {
            color: #333;
            background: #fff;
            padding: 2px 7px;
            border-radius: 3px;
            border: 2px solid #1a5490;
            display: inline-block
        }

        .summary-cards {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-bottom: 10px
        }

        .summary-card {
            flex: 1 1 180px;
            border-radius: 8px;
            border: 1px solid rgba(0, 0, 0, .06);
            padding: 7px 9px;
            background: #fdfdff;
            font-size: 10px;
        }

        .summary-label {
            color: #666;
            margin-bottom: 3px
        }

        .summary-value {
            font-weight: 700;
            font-size: 13px;
            color: #1a5490
        }

        h3.section-title {
            font-size: 14px;
            font-weight: 700;
            color: #1a5490;
            margin-top: 10px;
            margin-bottom: 5px
        }

        .section-subtitle {
            font-size: 10px;
            color: #777;
            margin-bottom: 6px
        }

        .table-wrap {
            width: 100%;
            max-width: 100%;
            margin: 0 auto;
            overflow-x: auto
        }

        table {
            width: 100% !important;
            max-width: 100% !important;
            margin: 0 auto;
            border-collapse: collapse;
            font-size: 10px;
            margin-top: 6px;
            margin-bottom: 10px;
            table-layout: fixed;
        }

        thead {
            background: linear-gradient(135deg, #1a5490 0%, #2d7ab8 100%);
            color: #fff;
        }

        th {
            border: 1px solid #1a5490;
            padding: 6px 5px;
            text-align: center;
            font-weight: 700;
            font-size: 10px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        td {
            border: 1px solid #ddd;
            padding: 5px 5px;
            color: #555;
            font-size: 10px;
            vertical-align: top;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        tbody tr:nth-child(even) {
            background: #f9f9f9
        }

        .text-center {
            text-align: center
        }

        .wrap {
            white-space: normal !important;
            overflow: visible !important;
            text-overflow: clip !important;
            word-break: break-word;
            overflow-wrap: anywhere;
            line-height: 1.2;
        }

        .muted {
            color: #777
        }

        .small {
            font-size: 9px
        }

        .block {
            display: block
        }

        .fw-semibold {
            font-weight: 600
        }

        @media print {
            .no-print {
                display: none !important
            }

            @page {
                margin: 8mm 10mm;
            }

            .table-wrap {
                overflow: hidden;
            }

            thead {
                display: table-header-group
            }

            table {
                page-break-inside: auto;
                break-inside: auto
            }

            tr {
                page-break-inside: auto !important;
                break-inside: auto !important
            }

            .main-header,
            .filters,
            .summary-cards {
                page-break-inside: avoid;
                break-inside: avoid-page;
            }

            .container {
                padding: 0
            }

            thead th {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact
            }
        }
    </style>

    <style id="print-orientation">
        @page {
            size: A4 landscape;
            margin: 8mm 10mm;
        }
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
            {{ __('reports.print') ?? trans('accounting.print') }}
        </button>
    </div>

    <div class="container">

        {{-- Header --}}
        <div class="main-header">
            <div class="header-logo">
                <img src="{{ asset('assets/images/logo.png') }}" alt="logo">
            </div>

            <div class="header-center">
                <div class="org-name">{{ $orgName ?? '-' }}</div>
                <div class="report-title">{{ trans('accounting.commissions_print_title') }}</div>
            </div>

            <div class="header-right">
                <div>{{ trans('accounting.commissions_generated_at') }}: {{ now('Africa/Cairo')->format('Y-m-d H:i') }}
                </div>
                <div>{{ trans('accounting.commissions_settlement_id') }}: #{{ $settlement->id }}</div>
                <div>{{ trans('accounting.status') }}:
                    @if($status === 'paid')
                        {{ trans('accounting.paid') }}
                    @elseif($status === 'draft')
                        {{ trans('accounting.draft') }}
                    @else
                        {{ trans('accounting.cancelled') }}
                    @endif
                </div>
                <div>{{ trans('accounting.items_count') }}: {{ (int) $settlement->all_items_count }}</div>
                <div>{{ trans('accounting.total_amount') }}: {{ $fmt($settlement->total_commission_amount) }}</div>
            </div>
        </div>

        {{-- Filters / Meta --}}
        <div class="filters">
            <div class="filters-title">{{ trans('accounting.commissions_settlement_details') }}</div>
            <div class="filter-row">
                <div class="filter-item">
                    {{ trans('accounting.date_from') }}: <span
                        class="filter-value">{{ optional($settlement->date_from)->format('Y-m-d') }}</span>
                </div>
                <div class="filter-item">
                    {{ trans('accounting.date_to') }}: <span
                        class="filter-value">{{ optional($settlement->date_to)->format('Y-m-d') }}</span>
                </div>
                <div class="filter-item">
                    {{ trans('accounting.sales_employee') }}: <span
                        class="filter-value">{{ $emp ?: trans('accounting.all_employees') }}</span>
                </div>
                @if($branchName)
                    <div class="filter-item">
                        {{ trans('accounting.branch') }}: <span class="filter-value">{{ $branchName }}</span>
                    </div>
                @endif
                @if($status === 'paid')
                    <div class="filter-item">
                        {{ trans('accounting.paid_at') }}: <span
                            class="filter-value">{{ $settlement->paid_at ? \Carbon\Carbon::parse($settlement->paid_at)->format('Y-m-d H:i') : '-' }}</span>
                    </div>
                    <div class="filter-item">
                        {{ trans('accounting.paid_by') }}: <span
                            class="filter-value">{{ $settlement->paidByUser->name ?? '-' }}</span>
                    </div>
                @endif
            </div>
        </div>

        {{-- Summary Cards --}}
        <div class="summary-cards">
            <div class="summary-card">
                <div class="summary-label">{{ trans('accounting.total_amount') }} ({{ trans('accounting.included') }})
                </div>
                <div class="summary-value">{{ $fmt($settlement->total_commission_amount) }}</div>
            </div>
            <div class="summary-card">
                <div class="summary-label">{{ trans('accounting.excluded_amount') }}</div>
                <div class="summary-value">{{ $fmt($settlement->total_excluded_commission_amount) }}</div>
            </div>
            <div class="summary-card">
                <div class="summary-label">{{ trans('accounting.total_amount_all') }}</div>
                <div class="summary-value">{{ $fmt($settlement->total_all_commission_amount) }}</div>
            </div>
            <div class="summary-card">
                <div class="summary-label">{{ trans('accounting.items_count') }} ({{ trans('accounting.included') }} /
                    {{ trans('accounting.excluded') }})</div>
                <div class="summary-value">{{ (int) $settlement->items_count }} /
                    {{ (int) $settlement->excluded_items_count }}</div>
            </div>
        </div>

        @if(!empty($settlement->notes))
            <div class="filters" style="background:#fff8e1;border-color:#ffe082;">
                <div class="filters-title">{{ trans('accounting.notes') }}</div>
                <div style="font-size:11px;">{{ $settlement->notes }}</div>
            </div>
        @endif

        {{-- Items Table --}}
        <h3 class="section-title">{{ trans('accounting.commissions_items') }}</h3>
        <div class="section-subtitle">{{ trans('accounting.items_count') }}: {{ $settlement->items->count() }}</div>

        <div class="table-wrap">
            <table>
                <colgroup>
                    <col style="width:4%">
                    <col style="width:10%">
                    <col style="width:18%">
                    <col style="width:16%">
                    <col style="width:14%">
                    <col style="width:12%">
                    <col style="width:10%">
                    <col style="width:16%">
                </colgroup>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>{{ trans('accounting.subscription_id') }}</th>
                        <th>{{ trans('accounting.sales_employee') }}</th>
                        <th>{{ trans('accounting.branch') }}</th>
                        <th>{{ trans('accounting.create_date') }}</th>
                        <th>{{ trans('accounting.commission_amount') }}</th>
                        <th>{{ trans('accounting.status') }}</th>
                        <th>{{ trans('accounting.exclude_reason') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($settlement->items as $i => $it)
                        @php
                            $itemEmpName = '-';
                            if ($it->salesEmployee) {
                                $itemEmpName = $it->salesEmployee->fullname
                                    ?? trim(($it->salesEmployee->first_name ?? '') . ' ' . ($it->salesEmployee->last_name ?? ''));
                                $itemEmpName = $itemEmpName ?: ('#' . $it->sales_employee_id);
                            }

                            $itemBranchName = '-';
                            if ($it->branch) {
                                $itemBranchName = method_exists($it->branch, 'getTranslation')
                                    ? $it->branch->getTranslation('name', $locale)
                                    : ($nameJsonOrText($it->branch->name ?? null) ?: '-');
                            }
                        @endphp
                        <tr>
                            <td class="text-center">{{ $i + 1 }}</td>
                            <td class="text-center">{{ $it->member_subscription_id }}</td>
                            <td class="wrap">{{ $itemEmpName }}</td>
                            <td class="wrap">{{ $itemBranchName }}</td>
                            <td class="text-center">
                                {{ $it->subscription_created_at ? \Carbon\Carbon::parse($it->subscription_created_at)->format('Y-m-d H:i') : '-' }}
                            </td>
                            <td class="text-center">{{ $fmt($it->commission_amount) }}</td>
                            <td class="text-center">
                                @if($it->is_excluded)
                                    {{ trans('accounting.excluded') }}
                                @else
                                    {{ trans('accounting.included') }}
                                @endif
                            </td>
                            <td class="wrap">{{ $it->exclude_reason ?? '-' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center" style="padding:18px;color:#999">
                                {{ __('reports.no_results') ?? 'لا توجد نتائج' }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

    </div>

    <script>
        (function () {
            var cb = document.getElementById('landscapeToggle');
            var styleBlock = document.getElementById('print-orientation');
            if (cb && styleBlock) {
                cb.addEventListener('change', function () {
                    styleBlock.textContent = this.checked ?
                        '@page{ size: A4 landscape; margin: 8mm 10mm; }' :
                        '@page{ size: A4 portrait;  margin: 8mm 10mm; }';
                });
            }
        })();
    </script>
</body>

</html>
