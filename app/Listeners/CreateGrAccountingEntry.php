<?php

namespace App\Listeners;

use App\Events\GRCreated;
use App\Models\Accounting\Account;
use App\Services\AccountingService;
use Illuminate\Support\Facades\Log;

/**
 * When a GR is created, automatically post accounting entries:
 *
 * If PAID:   Debit Cash (1101) | Credit Freight Income (3001)
 * If TO-PAY: Debit Accounts Receivable (1202) | Credit Freight Income (3001)
 *
 * This is the core integration between operational and accounting systems.
 * Per SXPRESS_ACCOUNTING_MASTER_DOCUMENT Phase 8.
 */
class CreateGrAccountingEntry
{
    public function handle(GRCreated $event): void
    {
        try {
            $gr = $event->gr;

            // Only post if amount > 0
            if (($gr->total_amount ?? 0) <= 0) return;

            $service = app(AccountingService::class);

            $freightIncome = Account::where('code', '3001')->first(); // Freight Income
            if (!$freightIncome) {
                Log::warning("Accounting: Freight Income account (3001) not found. Skipping GR {$gr->gr_no}.");
                return;
            }

            if ($gr->paid) {
                // PAID GR: Cash received immediately
                $cashAccount = Account::where('code', '1101')->first(); // Cash In Hand
                if (!$cashAccount) return;

                $service->createVoucher(
                    type: 'receipt',
                    date: $gr->copy_date ? $gr->copy_date->format('Y-m-d') : now()->format('Y-m-d'),
                    narration: "Freight received (Paid) — GR {$gr->gr_no} | {$gr->consignor} → {$gr->consignee}",
                    entries: [
                        ['account_id' => $cashAccount->id, 'debit' => $gr->total_amount, 'credit' => 0],
                        ['account_id' => $freightIncome->id, 'debit' => 0, 'credit' => $gr->total_amount],
                    ],
                    branch: $gr->office,
                    meta: [
                        'reference_type' => 'gr',
                        'reference_id' => $gr->id,
                        'created_by_id' => $gr->created_by_id ?? auth()->id(),
                    ]
                );

            } elseif ($gr->to_pay) {
                // TO-PAY GR: Receivable created (will be collected later)
                $receivable = Account::where('code', '1202')->first(); // To-Pay Receivables
                if (!$receivable) return;

                $service->createVoucher(
                    type: 'journal',
                    date: $gr->copy_date ? $gr->copy_date->format('Y-m-d') : now()->format('Y-m-d'),
                    narration: "Freight booked (To-Pay) — GR {$gr->gr_no} | {$gr->consignor} → {$gr->consignee}",
                    entries: [
                        ['account_id' => $receivable->id, 'debit' => $gr->total_amount, 'credit' => 0],
                        ['account_id' => $freightIncome->id, 'debit' => 0, 'credit' => $gr->total_amount],
                    ],
                    branch: $gr->office,
                    meta: [
                        'reference_type' => 'gr',
                        'reference_id' => $gr->id,
                        'created_by_id' => $gr->created_by_id ?? auth()->id(),
                    ]
                );
            }

        } catch (\Throwable $e) {
            // Never block GR creation — log and continue
            Log::error("Accounting: Failed to create entry for GR: " . $e->getMessage());
        }
    }
}
