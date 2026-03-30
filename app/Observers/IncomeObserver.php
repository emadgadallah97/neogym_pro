<?php

namespace App\Observers;

use App\Models\accounting\income as Income;
use App\Services\TreasuryService;
use Illuminate\Support\Facades\Log;

class IncomeObserver
{
    protected TreasuryService $treasury;

    public function __construct(TreasuryService $treasury)
    {
        $this->treasury = $treasury;
    }

    /**
     * Handle the Income "created" event.
     */
    public function created(Income $income): void
    {
        // Skip cancelled records (shouldn't happen on creation, but guard anyway)
        if ($income->iscancelled) {
            return;
        }

        $this->treasury->record(
            type:            'in',
            amount:          (float) $income->amount,
            branchId:        (int) $income->branchid,
            sourceType:      'income',
            sourceId:        $income->id,
            description:     $income->description ?? $income->payername ?? 'إيراد #' . $income->id,
            transactionDate: optional($income->incomedate)->toDateString()
        );
    }

    /**
     * Handle the Income "updated" event.
     *
     * Handles three cases:
     * 1. Amount changed → record a diff adjustment
     * 2. iscancelled just became true → reverse the original entry
     * 3. iscancelled just became false → re-record the entry
     */
    public function updated(Income $income): void
    {
        $wasJustCancelled = $income->isDirty('iscancelled') && $income->iscancelled;
        $wasJustRestored  = $income->isDirty('iscancelled') && !$income->iscancelled;

        // Case 2: cancellation → reverse
        if ($wasJustCancelled) {
            try {
                $this->treasury->reverse('income', $income->id, (int) $income->branchid);
            } catch (\Throwable $e) {
                Log::warning('IncomeObserver::updated — reverse failed: ' . $e->getMessage(), [
                    'income_id' => $income->id,
                ]);
                throw $e;
            }
            return;
        }

        // Case 3: restored from cancellation → re-record
        if ($wasJustRestored) {
            $this->treasury->record(
                type:            'in',
                amount:          (float) $income->amount,
                branchId:        (int) $income->branchid,
                sourceType:      'income',
                sourceId:        $income->id,
                description:     '(استعادة) ' . ($income->description ?? 'إيراد #' . $income->id),
                transactionDate: optional($income->incomedate)->toDateString()
            );
            return;
        }

        // Case 1: amount changed (and not cancelled)
        if ($income->isDirty('amount') && !$income->iscancelled) {
            $oldAmount = (float) $income->getOriginal('amount');
            $newAmount = (float) $income->amount;
            $diff      = $newAmount - $oldAmount;

            if (abs($diff) < 0.001) {
                return; // negligible change
            }

            if ($diff > 0) {
                // Amount increased → extra 'in'
                $this->treasury->record(
                    type:            'in',
                    amount:          $diff,
                    branchId:        (int) $income->branchid,
                    sourceType:      'income',
                    sourceId:        $income->id,
                    description:     'تعديل إيراد (زيادة) #' . $income->id,
                    transactionDate: optional($income->incomedate)->toDateString()
                );
            } else {
                // Amount decreased → extra 'out'
                $this->treasury->record(
                    type:            'out',
                    amount:          abs($diff),
                    branchId:        (int) $income->branchid,
                    sourceType:      'income',
                    sourceId:        $income->id,
                    description:     'تعديل إيراد (نقص) #' . $income->id,
                    transactionDate: optional($income->incomedate)->toDateString()
                );
            }
        }
    }

    /**
     * Handle the Income "deleted" event (soft-delete).
     */
    public function deleted(Income $income): void
    {
        if ($income->iscancelled) {
            // Already reversed via the updated observer
            return;
        }

        try {
            $this->treasury->reverse('income', $income->id, (int) $income->branchid);
        } catch (\Throwable $e) {
            Log::warning('IncomeObserver::deleted — reverse failed: ' . $e->getMessage(), [
                'income_id' => $income->id,
            ]);
            throw $e;
        }
    }
}
