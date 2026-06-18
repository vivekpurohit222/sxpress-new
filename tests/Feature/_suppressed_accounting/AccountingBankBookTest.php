<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Accounting\Account;
use App\Models\Accounting\BankAccount;
use App\Models\Accounting\LedgerEntry;
use App\Models\Accounting\Voucher;
use App\Services\AccountingService;

class AccountingBankBookTest extends TestCase
{
    private function superAdmin(): User
    {
        return User::where('role', 'super_admin')->first();
    }

    private function getOrCreateBank(): BankAccount
    {
        $bank = BankAccount::first();
        if ($bank) return $bank;

        $bankAccount = Account::where('code', '1110')->first();
        return BankAccount::create([
            'bank_name' => 'State Bank of India',
            'account_number' => '39876543210',
            'ifsc_code' => 'SBIN0001234',
            'branch_name' => 'Rajkot Main',
            'account_type' => 'current',
            'account_id' => $bankAccount->id,
            'opening_balance' => 50000,
            'is_active' => true,
        ]);
    }

    public function test_bank_book_loads(): void
    {
        $this->actingAs($this->superAdmin())
            ->get(route('accounting.bankbook.index'))
            ->assertStatus(200)
            ->assertSee('Bank Book');
    }

    public function test_bank_book_with_bank_selected(): void
    {
        $bank = $this->getOrCreateBank();

        $this->actingAs($this->superAdmin())
            ->get(route('accounting.bankbook.index', ['bank_id' => $bank->id]))
            ->assertStatus(200)
            ->assertSee($bank->bank_name)
            ->assertSee($bank->account_number);
    }

    public function test_bank_statement_shows_deposits_and_withdrawals(): void
    {
        $bank = $this->getOrCreateBank();
        $service = app(AccountingService::class);
        $bankAccountId = $bank->account_id;
        $expenseAccount = Account::where('code', '4101')->first(); // Diesel

        $this->actingAs($this->superAdmin());

        // Deposit (debit to bank)
        $v1 = $service->createVoucher('receipt', now()->format('Y-m-d'), 'NEFT received from customer', [
            ['account_id' => $bankAccountId, 'debit' => 25000, 'credit' => 0],
            ['account_id' => Account::where('code', '3001')->first()->id, 'debit' => 0, 'credit' => 25000],
        ], 'Rajkot');

        // Withdrawal (credit from bank)
        $v2 = $service->createVoucher('payment', now()->format('Y-m-d'), 'Cheque issued for diesel', [
            ['account_id' => $expenseAccount->id, 'debit' => 8000, 'credit' => 0],
            ['account_id' => $bankAccountId, 'debit' => 0, 'credit' => 8000],
        ], 'Rajkot');

        $response = $this->actingAs($this->superAdmin())
            ->get(route('accounting.bankbook.index', [
                'bank_id' => $bank->id,
                'from_date' => now()->format('Y-m-d'),
                'to_date' => now()->format('Y-m-d'),
            ]));

        $response->assertStatus(200)
            ->assertSee('25,000.00')  // deposit
            ->assertSee('8,000.00')   // withdrawal
            ->assertSee('NEFT received');

        // Cleanup
        LedgerEntry::where('voucher_id', $v1->id)->delete();
        LedgerEntry::where('voucher_id', $v2->id)->delete();
        $v1->forceDelete();
        $v2->forceDelete();
    }

    public function test_manage_banks_page_loads(): void
    {
        $this->actingAs($this->superAdmin())
            ->get(route('accounting.bankbook.manage'))
            ->assertStatus(200)
            ->assertSee('Manage Bank Accounts');
    }

    public function test_can_add_bank_account(): void
    {
        $bankLedger = Account::where('code', '1110')->first();

        $this->actingAs($this->superAdmin())
            ->post(route('accounting.bankbook.store'), [
                'bank_name' => 'HDFC Bank',
                'account_number' => '50100123456789',
                'ifsc_code' => 'HDFC0001234',
                'branch_name' => 'Rajkot Branch',
                'account_type' => 'current',
                'account_id' => $bankLedger->id,
                'opening_balance' => 100000,
            ])
            ->assertRedirect(route('accounting.bankbook.manage'));

        $this->assertNotNull(BankAccount::where('account_number', '50100123456789')->first());

        // Cleanup
        BankAccount::where('account_number', '50100123456789')->forceDelete();
    }

    public function test_reconcile_entry(): void
    {
        $bank = $this->getOrCreateBank();
        $service = app(AccountingService::class);

        $this->actingAs($this->superAdmin());

        $voucher = $service->createVoucher('payment', now()->format('Y-m-d'), 'Cheque test', [
            ['account_id' => Account::where('code', '4101')->first()->id, 'debit' => 500, 'credit' => 0],
            ['account_id' => $bank->account_id, 'debit' => 0, 'credit' => 500],
        ], 'Rajkot');

        // Set cheque info on the bank entry
        $entry = LedgerEntry::where('voucher_id', $voucher->id)->where('account_id', $bank->account_id)->first();
        $entry->update(['cheque_no' => 'CHQ-001', 'cheque_date' => now()]);

        // Reconcile
        $this->actingAs($this->superAdmin())
            ->postJson(route('accounting.bankbook.reconcile', $entry->id), ['status' => 'cleared'])
            ->assertJson(['success' => true]);

        $entry->refresh();
        $this->assertEquals('cleared', $entry->reconciliation_status);
        $this->assertNotNull($entry->cleared_date);

        // Cleanup
        LedgerEntry::where('voucher_id', $voucher->id)->delete();
        $voucher->forceDelete();
    }
}
