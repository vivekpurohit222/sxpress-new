<?php

namespace App\Listeners;

use App\Models\Accounting\Account;
use App\Models\Accounting\Outstanding;
use App\Models\Gr;
use App\Services\AccountingService;
use Illuminate\Support\Facades\Log;

/**
 * When TO-PAY is collected (markTopayCollected), create accounting entry:
 *
 * Debit:  Cash (1101)
 * Credit: Accounts Receivable - To-Pay (1202)
 *
 * Also updates the outstanding record if it exists.
 */
class CreateTopayCollectionEntry
{
    /**
     * Called from GrController::markTopayCollected after status is updated.
     */
    public static function handle(Gr $gr): void
    {
        try {
            if (($gr->total_amount ?? 0) <= 0) return;

            $service = app(AccountingService::class);

            $cashAccount = Account::where('code', '1101')->first();
            $receivable = Account::where('code', '1202')->first();

            if (!$cashAccount || !$receivable) {
                Log::warning("Accounting: Cash/Receivable account not found for TO-PAY collection.");
                return;
            }

            $service->createVoucher(
                type: 'receipt',
                date: now()->format('Y-m-d'),
                narration: "TO-PAY collected — GR {$gr->gr_no} | {$gr->consignee}",
                entries: [
                    ['account_id' => $cashAccount->id, 'debit' => $gr->total_amount, 'credit' => 0],
                    ['account_id' => $receivable->id, 'debit' => 0, 'credit' => $gr->total_amount],
                ],
                branch: $gr->office,
                meta: [
                    'reference_type' => 'gr',
                    'reference_id' => $gr->id,
                    'created_by_id' => auth()->id(),
                ]
            );

            // Also update outstanding record if exists
            $outstanding = Outstanding::where('reference_type', 'gr')
                ->where('reference_id', $gr->id)
                ->first();

            if ($outstanding && $outstanding->status !== 'paid') {
                $outstanding->recordPayment($gr->total_amount);
            }

        } catch (\Throwable $e) {
            Log::error("Accounting: Failed to create TO-PAY collection entry: " . $e->getMessage());
        }
    }
}
