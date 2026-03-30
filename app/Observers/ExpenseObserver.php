<?php

namespace App\Observers;

use App\Models\accounting\Expense;
use App\Services\TreasuryService;
use Illuminate\Support\Facades\Log;

class ExpenseObserver
{
    protected TreasuryService $treasury;

    public function __construct(TreasuryService $treasury)
    {
        $this->treasury = $treasury;
    }

    /**
     * Handle the Expense "created" event.
     */
    public function created(Expense $expense): void
    {
        if ($expense->iscancelled) {
            return;
        }

        $this->treasury->record(
            type:            'out',
            amount:          (float) $expense->amount,
            branchId:        (int) $expense->branchid,
            sourceType:      'expense',
            sourceId:        $expense->id,
            description:     $expense->description ?? $expense->recipientname ?? 'مصروف #' . $expense->id,
            transactionDate: optional($expense->expensedate)->toDateString()
        );
    }

    /**
     * Handle the Expense "updated" event.
     */
    public function updated(Expense $expense): void
    {
        $wasJustCancelled = $expense->isDirty('iscancelled') && $expense->iscancelled;
        $wasJustRestored  = $expense->isDirty('iscancelled') && !$expense->iscancelled;

        // Case 2: cancellation → reverse
        if ($wasJustCancelled) {
            try {
                $this->treasury->reverse('expense', $expense->id, (int) $expense->branchid);
            } catch (\Throwable $e) {
                Log::warning('ExpenseObserver::updated — reverse failed: ' . $e->getMessage(), [
                    'expense_id' => $expense->id,
                ]);
                throw $e;
            }
            return;
        }

        // Case 3: restored from cancellation → re-record
        if ($wasJustRestored) {
            $this->treasury->record(
                type:            'out',
                amount:          (float) $expense->amount,
                branchId:        (int) $expense->branchid,
                sourceType:      'expense',
                sourceId:        $expense->id,
                description:     '(استعادة) ' . ($expense->description ?? 'مصروف #' . $expense->id),
                transactionDate: optional($expense->expensedate)->toDateString()
            );
            return;
        }

        // Case 1: amount changed (and not cancelled)
        if ($expense->isDirty('amount') && !$expense->iscancelled) {
            $oldAmount = (float) $expense->getOriginal('amount');
            $newAmount = (float) $expense->amount;
            $diff      = $newAmount - $oldAmount;

            if (abs($diff) < 0.001) {
                return;
            }

            if ($diff > 0) {
                // Amount increased → extra 'out'
                $this->treasury->record(
                    type:            'out',
                    amount:          $diff,
                    branchId:        (int) $expense->branchid,
                    sourceType:      'expense',
                    sourceId:        $expense->id,
                    description:     'تعديل مصروف (زيادة) #' . $expense->id,
                    transactionDate: optional($expense->expensedate)->toDateString()
                );
            } else {
                // Amount decreased → extra 'in'
                $this->treasury->record(
                    type:            'in',
                    amount:          abs($diff),
                    branchId:        (int) $expense->branchid,
                    sourceType:      'expense',
                    sourceId:        $expense->id,
                    description:     'تعديل مصروف (نقص) #' . $expense->id,
                    transactionDate: optional($expense->expensedate)->toDateString()
                );
            }
        }
    }

    /**
     * Handle the Expense "deleted" event (soft-delete).
     */
    public function deleted(Expense $expense): void
    {
        if ($expense->iscancelled) {
            return;
        }

        try {
            $this->treasury->reverse('expense', $expense->id, (int) $expense->branchid);
        } catch (\Throwable $e) {
            Log::warning('ExpenseObserver::deleted — reverse failed: ' . $e->getMessage(), [
                'expense_id' => $expense->id,
            ]);
            throw $e;
        }
    }
}
