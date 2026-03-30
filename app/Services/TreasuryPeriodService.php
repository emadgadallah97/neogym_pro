<?php

namespace App\Services;

use App\Models\accounting\TreasuryPeriod;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class TreasuryPeriodService
{
    /**
     * Open a new treasury period for a branch.
     *
     * @param  int          $branchId
     * @param  string       $name
     * @param  string       $startDate  Y-m-d
     * @param  float|null   $openingBalance  If null, inherits carried_forward from last period
     * @return TreasuryPeriod
     * @throws ValidationException  if an open period already exists for this branch
     */
    public function open(int $branchId, string $name, string $startDate, ?float $openingBalance = null): TreasuryPeriod
    {
        // Validate: only one open period per branch
        $existingOpen = TreasuryPeriod::where('branch_id', $branchId)
            ->where('status', 'open')
            ->exists();

        if ($existingOpen) {
            throw ValidationException::withMessages([
                'branch_id' => [trans('accounting.treasury_period_already_open')],
            ]);
        }

        // Determine opening balance
        if ($openingBalance === null) {
            $lastPeriod = TreasuryPeriod::where('branch_id', $branchId)
                ->where('status', 'closed')
                ->latest('id')
                ->first();

            $openingBalance = $lastPeriod ? (float) $lastPeriod->carried_forward : 0.0;
        }

        return TreasuryPeriod::create([
            'branch_id'       => $branchId,
            'name'            => $name,
            'start_date'      => $startDate,
            'end_date'        => null,
            'opening_balance' => $openingBalance,
            'status'          => 'open',
            'opened_by'       => Auth::id(),
        ]);
    }

    /**
     * Close an open treasury period.
     *
     * @param  int          $periodId
     * @param  string       $endDate        Y-m-d
     * @param  float        $handedOver     Amount handed over (must be <= closing_balance)
     * @param  string|null  $notes
     * @return TreasuryPeriod
     * @throws ValidationException
     */
    public function close(int $periodId, string $endDate, float $handedOver, ?string $notes = null): TreasuryPeriod
    {
        $period = TreasuryPeriod::findOrFail($periodId);

        if ($period->status !== 'open') {
            throw ValidationException::withMessages([
                'period_id' => [trans('accounting.treasury_period_not_open')],
            ]);
        }

        // Compute closing balance
        $totalIn  = (float) $period->transactions()->where('type', 'in')->sum('amount');
        $totalOut = (float) $period->transactions()->where('type', 'out')->sum('amount');
        $closing  = (float) $period->opening_balance + $totalIn - $totalOut;

        // Validate handed_over
        if ($handedOver > $closing) {
            throw ValidationException::withMessages([
                'handed_over' => [trans('accounting.treasury_handed_over_exceeds_balance')],
            ]);
        }

        $carriedForward = $closing - $handedOver;

        $period->update([
            'end_date'        => $endDate,
            'closing_balance' => $closing,
            'handed_over'     => $handedOver,
            'carried_forward' => $carriedForward,
            'status'          => 'closed',
            'closed_by'       => Auth::id(),
            'closed_at'       => Carbon::now(),
            'close_notes'     => $notes,
        ]);

        return $period->fresh();
    }
}
