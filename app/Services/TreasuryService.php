<?php

namespace App\Services;

use App\Models\accounting\TreasuryPeriod;
use App\Models\accounting\TreasuryTransaction;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class TreasuryService
{
    /**
     * Record a new treasury transaction.
     *
     * @param  string       $type         'in' | 'out'
     * @param  float        $amount
     * @param  int          $branchId
     * @param  string|null  $sourceType   'income'|'expense'|'salary'|'adjustment'|'manual'
     * @param  int|null     $sourceId
     * @param  string|null  $description
     * @param  bool         $isReversal
     * @param  int|null     $reversalOf   ID of original treasury_transaction
     * @param  string|null  $transactionDate  Defaults to today
     * @return TreasuryTransaction|null   null if no open period found
     */
    public function record(
        string  $type,
        float   $amount,
        int     $branchId,
        ?string $sourceType  = null,
        ?int    $sourceId    = null,
        ?string $description = null,
        bool    $isReversal  = false,
        ?int    $reversalOf  = null,
        ?string $transactionDate = null,
        ?string $category    = null
    ): ?TreasuryTransaction {
        $period = TreasuryPeriod::where('branch_id', $branchId)
            ->where('status', 'open')
            ->latest('id')
            ->first();

        if (!$period) {
            Log::info('TreasuryService::record — no open period found, auto-opening one', [
                'branch_id'   => $branchId,
                'source_type' => $sourceType,
            ]);
            
            try {
                // Fetch last closed period's carried forward balance directly if needed,
                // but open() already does that inside TreasuryPeriodService.
                $periodName = trans('accounting.treasury_default_period_name') . ' (آلي) ' . now()->format('m/Y');
                
                $period = app(TreasuryPeriodService::class)->open(
                    $branchId,
                    $periodName,
                    Carbon::today()->toDateString(),
                    null // Let the service resolve the carried forward balance automatically
                );
            } catch (\Exception $e) {
                Log::error('TreasuryService::record — Auto-open failed', ['error' => $e->getMessage()]);
                return null;
            }
        }

        return TreasuryTransaction::create([
            'period_id'        => $period->id,
            'branch_id'        => $branchId,
            'user_id'          => Auth::id() ?? 1,
            'type'             => $type,
            'amount'           => abs($amount),
            'source_type'      => $sourceType,
            'source_id'        => $sourceId,
            'is_reversal'      => $isReversal,
            'reversal_of'      => $reversalOf,
            'category'         => $category,
            'description'      => $description,
            'transaction_date' => $transactionDate ?? Carbon::today()->toDateString(),
        ]);
    }

    /**
     * Reverse an existing treasury transaction for the given source.
     *
     * @param  string  $sourceType  'income' | 'expense' | 'salary' etc.
     * @param  int     $sourceId    ID of the source record
     * @param  int     $branchId
     * @return void
     * @throws \Illuminate\Validation\ValidationException  if the original tx is in a closed period
     */
    public function reverse(string $sourceType, int $sourceId, int $branchId): void
    {
        // Find the original non-reversal transaction for this source
        $original = TreasuryTransaction::where('source_type', $sourceType)
            ->where('source_id', $sourceId)
            ->where('branch_id', $branchId)
            ->where('is_reversal', false)
            ->latest('id')
            ->first();

        if (!$original) {
            // Nothing to reverse — e.g. created before treasury was live
            Log::info('TreasuryService::reverse — no original tx found, skipping', [
                'source_type' => $sourceType,
                'source_id'   => $sourceId,
            ]);
            return;
        }

        // Validate that the original's period is still open
        $period = $original->period;
        if (!$period || $period->status !== 'open') {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'treasury' => [trans('accounting.treasury_closed_period_error')],
            ]);
        }

        // Opposite type
        $reverseType = $original->type === 'in' ? 'out' : 'in';

        TreasuryTransaction::create([
            'period_id'        => $period->id,
            'branch_id'        => $branchId,
            'user_id'          => Auth::id() ?? 1,
            'type'             => $reverseType,
            'amount'           => $original->amount,
            'source_type'      => $sourceType,
            'source_id'        => $sourceId,
            'is_reversal'      => true,
            'reversal_of'      => $original->id,
            'category'         => $original->category,
            'description'      => 'قيد عكسي: ' . ($original->description ?? ''),
            'transaction_date' => Carbon::today()->toDateString(),
        ]);
    }

    /**
     * Get treasury balance summary for a branch.
     *
     * @param  int  $branchId
     * @return array{opening: float, total_in: float, total_out: float, balance: float, period: TreasuryPeriod|null}
     */
    public function getBalance(int $branchId): array
    {
        $period = TreasuryPeriod::where('branch_id', $branchId)
            ->where('status', 'open')
            ->latest('id')
            ->first();

        if (!$period) {
            return [
                'opening'   => 0.0,
                'total_in'  => 0.0,
                'total_out' => 0.0,
                'balance'   => 0.0,
                'period'    => null,
            ];
        }

        $totalIn  = (float) $period->transactions()->where('type', 'in')->sum('amount');
        $totalOut = (float) $period->transactions()->where('type', 'out')->sum('amount');
        $opening  = (float) $period->opening_balance;
        $balance  = $opening + $totalIn - $totalOut;

        return [
            'opening'   => $opening,
            'total_in'  => $totalIn,
            'total_out' => $totalOut,
            'balance'   => $balance,
            'period'    => $period,
        ];
    }
}
