<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ trans('sales.invoice') ?? 'فاتورة' }} #{{ $subscription->invoice->invoice_number ?? $subscription->id }}</title>

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Arial, sans-serif;
            font-size: 13px;
            color: #333;
            background: #f3f3f9;
            padding: 20px;
            direction: {{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }};
        }

        .invoice-container {
            max-width: 1000px;
            margin: 0 auto;
            background: #fff;
            padding: 24px 28px;
            border-radius: 8px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.06);
        }

        /* ─── Header ─── */
        .invoice-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 2px solid #405189;
            padding-bottom: 12px;
            margin-bottom: 16px;
        }

        .brand-section {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .brand-logo {
            max-height: 55px;
            width: auto;
            border-radius: 4px;
        }

        .brand-info h1 {
            font-size: 22px;
            color: #293450;
            font-weight: 700;
            margin-bottom: 2px;
        }

        .brand-info .company-name {
            font-size: 13px;
            color: #405189;
            font-weight: 600;
        }

        .invoice-meta {
            text-align: {{ app()->getLocale() === 'ar' ? 'left' : 'right' }};
            background: #f8f9fa;
            padding: 10px 14px;
            border-radius: 6px;
            border: 1px solid #eef0f3;
        }

        .invoice-meta p {
            margin: 3px 0;
            font-size: 12px;
            color: #555;
        }

        .invoice-meta strong {
            color: #333;
            display: inline-block;
            min-width: 90px;
        }

        /* ─── Info Section (2 columns) ─── */
        .info-section {
            display: flex;
            justify-content: space-between;
            gap: 16px;
            margin-bottom: 16px;
        }

        .info-box {
            flex: 1;
            background: #fafbfc;
            border: 1px solid #eef0f3;
            border-radius: 6px;
            padding: 10px 14px;
        }

        .info-box h4 {
            font-size: 13px;
            color: #405189;
            margin-bottom: 8px;
            border-bottom: 1px dashed #dde0ea;
            padding-bottom: 5px;
            font-weight: 700;
        }

        .info-box p {
            margin: 4px 0;
            font-size: 12px;
            color: #444;
        }

        .info-box .label {
            color: #777;
            display: inline-block;
            min-width: 90px;
            font-size: 12px;
        }

        /* ─── Items Table ─── */
        table.items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 16px;
            border: 1px solid #dde0ea;
            border-radius: 6px;
            overflow: hidden;
            font-size: 12px;
        }

        table.items-table th {
            background: #405189;
            color: #fff;
            padding: 9px 12px;
            font-size: 12px;
            font-weight: 600;
            text-align: {{ app()->getLocale() === 'ar' ? 'right' : 'left' }};
        }

        table.items-table td {
            padding: 8px 12px;
            border-bottom: 1px solid #eef0f3;
            color: #444;
        }

        table.items-table tr:last-child td {
            border-bottom: none;
        }

        table.items-table tr:nth-child(even) td {
            background: #fafafc;
        }

        /* ─── Totals ─── */
        .bottom-section {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 16px;
            margin-bottom: 16px;
        }

        .bottom-notes {
            flex: 1;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .totals-wrap {
            width: 300px;
            flex-shrink: 0;
        }

        .totals-table {
            width: 100%;
            border-collapse: collapse;
            border: 1px solid #dde0ea;
            border-radius: 6px;
            overflow: hidden;
            font-size: 12px;
        }

        .totals-table tr td {
            padding: 7px 12px;
            border-bottom: 1px solid #eef0f3;
            color: #444;
        }

        .totals-table tr td:last-child {
            text-align: {{ app()->getLocale() === 'ar' ? 'left' : 'right' }};
            font-weight: 700;
            color: #222;
        }

        .totals-table tr:last-child td {
            border-bottom: none;
        }

        .totals-table .total-row {
            background: #405189;
        }

        .totals-table .total-row td {
            font-size: 13px;
            color: #fff !important;
            font-weight: 700;
            border-bottom: none;
        }

        .totals-table .discount-row td {
            color: #e85d75 !important;
        }

        /* ─── Footer ─── */
        .footer-note {
            text-align: center;
            font-size: 11px;
            color: #aaa;
            border-top: 1px dashed #dde0ea;
            padding-top: 10px;
            margin-top: 6px;
        }

        /* ─── Buttons ─── */
        .no-print {
            text-align: center;
            margin-bottom: 16px;
        }

        .no-print button {
            padding: 8px 22px;
            background: #405189;
            color: #fff;
            border: none;
            border-radius: 5px;
            font-size: 13px;
            font-weight: 600;
            font-family: inherit;
            cursor: pointer;
            margin: 0 4px;
            transition: background 0.2s;
        }

        .no-print button:hover { background: #33406e; }

        .no-print .btn-close-print {
            background: #f3f6f9;
            color: #333;
            border: 1px solid #ddd;
        }

        .no-print .btn-close-print:hover { background: #e2e5e8; }

        /* ─── PRINT ─── */
        @media print {
            @page {
                size: A4 landscape;
                margin: 10mm 12mm;
            }

            .no-print { display: none !important; }

            body {
                padding: 0;
                background: #fff;
                font-size: 11px;
            }

            .invoice-container {
                box-shadow: none;
                padding: 0;
                margin: 0;
                width: 100%;
                max-width: 100%;
                border-radius: 0;
            }

            .invoice-header {
                padding-bottom: 8px;
                margin-bottom: 10px;
            }

            .brand-info h1  { font-size: 18px; }
            .brand-logo     { max-height: 45px; }

            .info-section,
            .bottom-section { margin-bottom: 10px; gap: 10px; }

            .info-box       { padding: 7px 10px; }
            .info-box h4    { font-size: 11px; margin-bottom: 5px; }
            .info-box p,
            .info-box .label { font-size: 11px; }

            table.items-table th,
            table.items-table td { padding: 6px 10px; font-size: 11px; }

            .totals-table tr td  { padding: 5px 10px; font-size: 11px; }
            .totals-table .total-row td { font-size: 12px; }

            .invoice-meta { padding: 7px 10px; }
            .invoice-meta p { font-size: 11px; }

            .footer-note { font-size: 10px; padding-top: 7px; }

            .totals-wrap { width: 260px; }
        }
    </style>
</head>

<body>

    @php
        $s       = $subscription;
        $inv     = $s->invoice;
        $setting = \App\Models\general\GeneralSetting::first();
    @endphp

    <div class="invoice-container">

        {{-- Buttons --}}
        <div class="no-print">
            <button onclick="window.print()">
                {{ trans('sales.print') ?? 'طباعة' }}
            </button>
            <button class="btn-close-print" onclick="window.close()">
                {{ trans('sales.close') ?? 'إغلاق' }}
            </button>
        </div>

        {{-- Header --}}
        <div class="invoice-header">
            <div class="brand-section">
                @if($setting && $setting->logo)
                    <img src="{{ asset($setting->logo) }}" alt="Logo" class="brand-logo">
                @endif
                <div class="brand-info">
                    <h1>{{ trans('sales.invoice') ?? 'فاتورة' }}</h1>
                    <p class="company-name">{{ $setting->name ?? config('app.name', 'NeoGym Pro') }}</p>
                </div>
            </div>

            <div class="invoice-meta">
                <p>
                    <strong>{{ trans('sales.invoice_number') ?? 'رقم الفاتورة' }}:</strong>
                    {{ $inv->invoice_number ?? '-' }}
                </p>
                <p>
                    <strong>{{ trans('sales.invoice_date') ?? 'تاريخ الفاتورة' }}:</strong>
                    {{ $inv->issued_at ?? $s->created_at->format('Y-m-d H:i') }}
                </p>
                <p>
                    <strong>{{ trans('sales.status') ?? 'الحالة' }}:</strong>
                    {{ $inv->status ?? $s->status }}
                </p>
            </div>
        </div>

        {{-- Member + Subscription Info --}}
        <div class="info-section">
            <div class="info-box">
                <h4>{{ trans('sales.member_info') ?? 'بيانات العضو' }}</h4>
                <p>
                    <span class="label">{{ trans('members.member_code') ?? 'كود العضو' }}:</span>
                    {{ $s->member?->member_code ?? $s->member_id }}
                </p>
                <p>
                    <span class="label">{{ trans('sales.member') ?? 'الاسم' }}:</span>
                    {{ $s->member?->full_name ?? '-' }}
                </p>
                @if($s->member?->phone)
                <p>
                    <span class="label">{{ trans('members.phone') ?? 'الجوال' }}:</span>
                    {{ $s->member->phone }}
                </p>
                @endif
            </div>

            <div class="info-box">
                <h4>{{ trans('sales.subscription_info') ?? 'بيانات الاشتراك' }}</h4>
                <p>
                    <span class="label">{{ trans('settings_trans.branch') ?? 'الفرع' }}:</span>
                    {{ $s->branch?->getTranslation('name', app()->getLocale()) ?? '-' }}
                </p>
                <p>
                    <span class="label">{{ trans('subscriptions.subscriptions_plans') ?? 'الخطة' }}:</span>
                    {{ $s->plan?->getTranslation('name', app()->getLocale()) ?? '-' }}
                </p>
                <p>
                    <span class="label">{{ trans('sales.start_date') ?? 'تاريخ البداية' }}:</span>
                    {{ $s->start_date }}
                </p>
                <p>
                    <span class="label">{{ trans('sales.end_date') ?? 'تاريخ النهاية' }}:</span>
                    {{ $s->end_date ?? '-' }}
                </p>
            </div>

            {{-- Sales Employee --}}
            @if($s->salesEmployee)
            <div class="info-box">
                <h4>{{ trans('sales.sales_employee') ?? 'موظف المبيعات' }}</h4>
                <p>{{ $s->salesEmployee->full_name ?? '-' }}</p>
            </div>
            @endif
        </div>

        {{-- Items Table --}}
        <table class="items-table">
            <thead>
                <tr>
                    <th style="width:40px">#</th>
                    <th>{{ trans('sales.item') ?? 'البند' }}</th>
                    <th>{{ trans('sales.details') ?? 'التفاصيل' }}</th>
                    <th style="width:120px">{{ trans('sales.amount') ?? 'المبلغ' }}</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>1</td>
                    <td>{{ trans('sales.item_plan') ?? 'الخطة' }}</td>
                    <td>
                        {{ $s->plan?->getTranslation('name', app()->getLocale()) ?? '-' }}
                        ({{ (int)$s->sessions_count }} {{ trans('sales.sessions') ?? 'حصة' }})
                    </td>
                    <td>{{ number_format((float)$s->price_plan, 2) }}</td>
                </tr>
                @if($s->ptAddons && $s->ptAddons->count())
                    @foreach($s->ptAddons as $idx => $pt)
                    <tr>
                        <td>{{ $idx + 2 }}</td>
                        <td>{{ trans('sales.pt_sessions') ?? 'جلسات مدرب (PT)' }}</td>
                        <td>
                            {{ $pt->trainer?->full_name ?? '-' }}
                            ({{ (int)$pt->sessions_count }} {{ trans('sales.sessions') ?? 'جلسة' }})
                        </td>
                        <td>{{ number_format((float)$pt->total_amount, 2) }}</td>
                    </tr>
                    @endforeach
                @endif
            </tbody>
        </table>

        {{-- Bottom: Notes + Totals --}}
        <div class="bottom-section">

            {{-- Notes (only if exists) --}}
            <div class="bottom-notes">
                @if($s->notes)
                <div class="info-box">
                    <h4>{{ trans('settings_trans.notes') ?? 'ملاحظات' }}</h4>
                    <p>{{ $s->notes }}</p>
                </div>
                @endif
            </div>

            {{-- Totals --}}
            <div class="totals-wrap">
                <table class="totals-table">
                    <tr>
                        <td>{{ trans('sales.subtotal_gross') ?? 'الإجمالي قبل الخصم' }}</td>
                        <td>{{ number_format((float)$s->price_plan + (float)$s->price_pt_addons, 2) }}</td>
                    </tr>
                    @if((float)$s->discount_offer_amount > 0)
                    <tr class="discount-row">
                        <td>{{ trans('sales.discount_offer') ?? 'خصم العرض' }}</td>
                        <td>-{{ number_format((float)$s->discount_offer_amount, 2) }}</td>
                    </tr>
                    @endif
                    @if((float)$s->discount_coupon_amount > 0)
                    <tr class="discount-row">
                        <td>{{ trans('sales.discount_coupon') ?? 'خصم الكوبون' }}</td>
                        <td>-{{ number_format((float)$s->discount_coupon_amount, 2) }}</td>
                    </tr>
                    @endif
                    @if((float)$s->total_discount > 0)
                    <tr class="discount-row">
                        <td>{{ trans('sales.total_discount') ?? 'إجمالي الخصومات' }}</td>
                        <td>-{{ number_format((float)$s->total_discount, 2) }}</td>
                    </tr>
                    @endif
                    <tr class="total-row">
                        <td>{{ trans('sales.net_total') ?? 'الإجمالي المستحق' }}</td>
                        <td>{{ number_format((float)$s->total_amount, 2) }}</td>
                    </tr>
                    @if($s->payments && $s->payments->count())
                    <tr>
                        <td>{{ trans('sales.payment_method') ?? 'طريقة الدفع' }}</td>
                        <td>{{ trans('sales.' . $s->payments->first()->payment_method) ?? $s->payments->first()->payment_method }}</td>
                    </tr>
                    @endif
                </table>
            </div>

        </div>

        {{-- Footer --}}
        <div class="footer-note">
            <strong>{{ $setting->name ?? config('app.name', 'NeoGym Pro') }}</strong>
            &mdash;
            {{ trans('sales.invoice_footer') ?? 'شكرًا لاشتراكك وثقتك بنا' }}
            &nbsp;&nbsp;|&nbsp;&nbsp;
            {{ now()->format('Y-m-d h:i A') }}
        </div>

    </div>

</body>
</html>
