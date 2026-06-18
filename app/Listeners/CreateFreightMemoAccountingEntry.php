<?php

namespace App\Listeners;

use App\Models\Accounting\Account;
use App\Models\Accounting\Outstanding;
use App\Models\Freight;
use App\Services\AccountingService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * When a Freight Memo is created, automatically post accounting entries:
 *
 * Debit:  Lorry Hire Expense (4201)
 * Credit: Truck Owner Payable (2101)
 *
 * If advance was paid:
 * Debit:  Truck Owner Payable (2101)  — partial reduction
 * Credit: Advance to Truck Owners (1302)
 *
 * Also creates an Outstanding entry for the truck owner balance.
 */
class CreateFreightMemoAccountingEntry
{
    public static function handle(Freight $fm): void
    {
        try {
            if (($fm->truck_freight ?? 0) <= 0) return;

            $service = app(AccountingService::class);

            $lorryHire = Account::where('code', '4201')->first(); // Lorry Hire Expense
            $truckPayable = Account::where('code', '2101')->first(); // Truck Owner Payables

            if (!$lorryHire || !$truckPayable) {
                Log::warning("Accounting: Lorry Hire/Truck Payable accounts not found for FM {$fm->fm_no}.");
                return;
            }

            // Main entry: full truck freight as expense, create payable
            $service->createVoucher(
                type: 'journal',
                date: $fm->fm_date ? Carbon::parse($fm->fm_date)->format('Y-m-d') : now()->format('Y-m-d'),
                narration: "Lorry hire — FM {$fm->fm_no} | Truck {$fm->truck_no} | {$fm->from_dest} → {$fm->to_dest}",
                entries: [
                    ['account_id' => $lorryHire->id, 'debit' => $fm->truck_freight, 'credit' => 0],
                    ['account_id' => $truckPayable->id, 'debit' => 0, 'credit' => $fm->truck_freight],
                ],
                branch: $fm->office,
                meta: [
                    'reference_type' => 'freight_memo',
                    'reference_id' => $fm->id,
                    'created_by_id' => $fm->created_by_id ?? auth()->id(),
                ]
            );

            // If advance was paid (entry_1_amount = Advance), reduce payable
            $advance = (float) ($fm->entry_1_amount ?? 0);
            if ($advance > 0) {
                $advanceAccount = Account::where('code', '1302')->first(); // Advance to Truck Owners
                if ($advanceAccount) {
                    $service->createVoucher(
                        type: 'payment',
                        date: $fm->fm_date ? Carbon::parse($fm->fm_date)->format('Y-m-d') : now()->format('Y-m-d'),
                        narration: "Advance paid to truck owner — FM {$fm->fm_no} | Truck {$fm->truck_no}",
                        entries: [
                            ['account_id' => $truckPayable->id, 'debit' => $advance, 'credit' => 0],
                            ['account_id' => $advanceAccount->id, 'debit' => 0, 'credit' => $advance],
                        ],
                        branch: $fm->office,
                        meta: [
                            'reference_type' => 'freight_memo',
                            'reference_id' => $fm->id,
                            'created_by_id' => $fm->created_by_id ?? auth()->id(),
                        ]
                    );
                }
            }

            // Create outstanding entry for balance payable to truck owner
            $balanceDue = (float) ($fm->balance_due ?? 0);
            if ($balanceDue > 0) {
                Outstanding::create([
                    'party_type' => 'truck_owner',
                    'party_name' => $fm->consignor ?: ($fm->truck_no ?: 'Truck Owner'),
                    'type' => 'payable',
                    'invoice_ref' => $fm->fm_no ?: $fm->memo_no,
                    'invoice_date' => $fm->fm_date ?? $fm->memo_date ?? now(),
                    'total_amount' => $balanceDue,
                    'paid_amount' => 0,
                    'pending_amount' => $balanceDue,
                    'due_date' => Carbon::parse($fm->fm_date ?? now())->addDays(7),
                    'status' => 'pending',
                    'branch' => $fm->office,
                    'reference_type' => 'freight_memo',
                    'reference_id' => $fm->id,
                ]);
            }

        } catch (\Throwable $e) {
            Log::error("Accounting: Failed to create FM entry: " . $e->getMessage());
        }
    }
}
