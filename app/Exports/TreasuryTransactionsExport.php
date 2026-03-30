<?php

namespace App\Exports;

use App\Models\accounting\TreasuryPeriod;
use App\Models\accounting\TreasuryTransaction;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class TreasuryTransactionsExport implements FromQuery, WithHeadings, WithMapping, WithStyles, WithTitle, ShouldAutoSize
{
    protected int   $branchId;
    protected ?int  $periodId;
    protected array $filters;

    public function __construct(int $branchId, ?int $periodId = null, array $filters = [])
    {
        $this->branchId = $branchId;
        $this->periodId = $periodId;
        $this->filters  = $filters;
    }

    public function query()
    {
        $query = TreasuryTransaction::with(['period', 'user', 'branch'])
            ->when($this->branchId, fn($q) => $q->where('branch_id', $this->branchId));

        if ($this->periodId) {
            $query->where('period_id', $this->periodId);
        } elseif ($this->branchId) {
            $period = TreasuryPeriod::where('branch_id', $this->branchId)
                ->where('status', 'open')
                ->latest('id')
                ->first();
            if ($period) {
                $query->where('period_id', $period->id);
            }
        }

        if (!empty($this->filters['type'])) {
            $query->where('type', $this->filters['type']);
        }
        if (!empty($this->filters['source_type'])) {
            $query->where('source_type', $this->filters['source_type']);
        }
        if (!empty($this->filters['date_from'])) {
            $query->where('transaction_date', '>=', $this->filters['date_from']);
        }
        if (!empty($this->filters['date_to'])) {
            $query->where('transaction_date', '<=', $this->filters['date_to']);
        }

        return $query->orderByDesc('id');
    }

    public function title(): string
    {
        return 'حركات الخزينة';
    }

    public function headings(): array
    {
        return [
            '#',
            'التاريخ',
            'النوع',
            'المبلغ',
            'المصدر',
            'رقم المصدر',
            'الوصف',
            'المستخدم',
            'الفترة',
            'قيد عكسي',
        ];
    }

    public function map($tx): array
    {
        return [
            $tx->id,
            optional($tx->transaction_date)->format('Y-m-d'),
            $tx->type === 'in' ? 'وارد' : 'صادر',
            number_format((float)$tx->amount, 2),
            $tx->source_type ?? 'يدوي',
            $tx->source_id ?? '-',
            $tx->description ?? '-',
            optional($tx->user)->name ?? '-',
            optional($tx->period)->name ?? '-',
            $tx->is_reversal ? 'نعم' : 'لا',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font'      => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
                'fill'      => ['fillType' => 'solid', 'startColor' => ['argb' => 'FF1F3A5F']],
                'alignment' => ['horizontal' => 'center'],
            ],
        ];
    }
}
