<?php

namespace App\Services\sales;

use App\Models\employee\Employee;
use Illuminate\Support\Facades\DB;

class CommissionService
{
    public function computeCommission(
        ?Employee $employee,
        float $grossAmount,
        float $netAmount
    ): array {
        if (!$employee) {
            return [
                'base_amount' => 0.0,
                'value_type'  => null,
                'value'       => 0.0,
                'amount'      => 0.0,
            ];
        }

        $settingsRow = DB::table('commission_settings')->first();
        $beforeDiscount = $settingsRow ? (bool)($settingsRow->calculate_commission_before_discounts ?? 1) : true;

        $base = $beforeDiscount ? $grossAmount : $netAmount;
        $base = max(0, $base);

        $valueType = $employee->commission_value_type ?? null;
        $percent = (float)($employee->commission_percent ?? 0);
        $fixed = (float)($employee->commission_fixed ?? 0);

        $amount = 0.0;

        if ($valueType === 'percent') {
            $amount = ($base * $percent) / 100.0;
        } elseif ($valueType === 'fixed') {
            $amount = $fixed;
        }

        $amount = min($amount, $base);

        return [
            'base_amount' => round($base, 2),
            'value_type'  => $valueType,
            'value'       => $valueType === 'percent' ? $percent : $fixed,
            'amount'      => round($amount, 2),
        ];
    }
}
