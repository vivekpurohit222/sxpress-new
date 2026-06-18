<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Accounting\Account;
use App\Models\Accounting\Voucher;
use App\Models\Accounting\LedgerEntry;
use App\Services\AccountingService;

class AccountingLedgerTest extends TestCase
{
    private function superAdmin(): User
    {
        return User::where('role', 'super_admin')->first();
    }

    private function staff(): User
    {
        return User::where('role', 'agent')->first();
    }

    // ─── Ledger Page Access ─────────────────────────────────────

    public function test_ledger_index_loads(): void
    {
        $this->actingAs($this->superAdmin())
            ->get(route('accounting.ledger.index'))
            ->assertStatus(200)
            ->assertSee('General Ledger');
    }

    public function test_staff_cannot_access_ledger(): void
    {
        $response = $this->actingAs($this->staff())
            ->get(route('accounting.ledger.index'));
        $this->assertContains($response->getStatusCode(), [403, 302]);
    }

    public function test_ledger_detail_loads(): void
    {
        $account = Account::where('code', '1101')->first(); // Cash In Hand
        $this->assertNotNull($account);

        $this->actingAs($this->superAdmin())
            ->get(route('accounting.ledger.show', $account->id))
            ->assertStatus(200)
            ->assertSee($account->name)
            ->assertSee('Opening Balance');
    }

    // ─── AccountingService — Double Entry ───────────────────────

    public function test_accounting_service_creates_balanced_voucher(): void
    {
        $service = app(AccountingService::class);

        $cashAccount = Account::where('code', '1101')->first();
        $freightIncome = Account::where('code', '3001')->first();

        $this->assertNotNull($cashAccount);
        $this->assertNotNull($freightIncome);

        $this->actingAs($this->superAdmin());

        $voucher = $service->createVoucher(
            type: 'receipt',
            date: now()->format('Y-m-d'),
            narration: 'Freight received for GR AA-00001',
            entries: [
                ['account_id' => $cashAccount->id, 'debit' => 1500, 'credit' => 0],
                ['account_id' => $freightIncome->id, 'debit' => 0, 'credit' => 1500],
            ],
            branch: 'Rajkot',
            meta: ['reference_type' => 'gr', 'reference_id' => 1]
        );

        $this->assertInstanceOf(Voucher::class, $voucher);
        $this->assertStringStartsWith('RV-', $voucher->voucher_no);
        $this->assertEquals(1500, (float) $voucher->total_amount);
        $this->assertEquals('approved', $voucher->status);
        $this->assertTrue($voucher->isBalanced());

        // Verify ledger entries
        $entries = LedgerEntry::where('voucher_id', $voucher->id)->get();
        $this->assertCount(2, $entries);
        $this->assertEquals(1500, $entries->sum('debit'));
        $this->assertEquals(1500, $entries->sum('credit'));

        // Cleanup
        LedgerEntry::where('voucher_id', $voucher->id)->delete();
        $voucher->forceDelete();
    }

    public function test_unbalanced_voucher_throws_exception(): void
    {
        $service = app(AccountingService::class);
        $cashAccount = Account::where('code', '1101')->first();

        $this->actingAs($this->superAdmin());

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('not balanced');

        $service->createVoucher(
            type: 'receipt',
            date: now()->format('Y-m-d'),
            narration: 'Unbalanced test',
            entries: [
                ['account_id' => $cashAccount->id, 'debit' => 1000, 'credit' => 0],
                ['account_id' => $cashAccount->id, 'debit' => 0, 'credit' => 500], // imbalanced
            ],
            branch: 'Rajkot'
        );
    }

    public function test_balance_calculation_for_asset_account(): void
    {
        $service = app(AccountingService::class);
        $cashAccount = Account::where('code', '1101')->first();
        $incomeAccount = Account::where('code', '3001')->first();

        $this->actingAs($this->superAdmin());

        $balanceBefore = LedgerEntry::getBalance($cashAccount->id);

        // Create a debit entry (cash received)
        $voucher = $service->createVoucher(
            type: 'receipt',
            date: now()->format('Y-m-d'),
            narration: 'Balance test entry',
            entries: [
                ['account_id' => $cashAccount->id, 'debit' => 5000, 'credit' => 0],
                ['account_id' => $incomeAccount->id, 'debit' => 0, 'credit' => 5000],
            ],
            branch: 'Rajkot'
        );

        // Asset balance should increase by 5000
        $balanceAfter = LedgerEntry::getBalance($cashAccount->id);
        $this->assertEquals(5000, $balanceAfter - $balanceBefore);

        // Cleanup
        LedgerEntry::where('voucher_id', $voucher->id)->delete();
        $voucher->forceDelete();
    }

    public function test_ledger_statement_shows_running_balance(): void
    {
        $service = app(AccountingService::class);
        $cashAccount = Account::where('code', '1101')->first();
        $incomeAccount = Account::where('code', '3001')->first();

        $this->actingAs($this->superAdmin());

        // Use a future date to isolate from other test entries
        $testDate = now()->addDays(30)->format('Y-m-d');

        $v1 = $service->createVoucher('receipt', $testDate, 'Statement test 1', [
            ['account_id' => $cashAccount->id, 'debit' => 1000, 'credit' => 0],
            ['account_id' => $incomeAccount->id, 'debit' => 0, 'credit' => 1000],
        ], 'Rajkot');

        $v2 = $service->createVoucher('receipt', $testDate, 'Statement test 2', [
            ['account_id' => $cashAccount->id, 'debit' => 2000, 'credit' => 0],
            ['account_id' => $incomeAccount->id, 'debit' => 0, 'credit' => 2000],
        ], 'Rajkot');

        $statement = $service->getLedgerStatement($cashAccount->id, $testDate, $testDate);

        $this->assertCount(2, $statement['entries']);
        $this->assertEquals(3000, $statement['total_debit']);
        $this->assertEquals(0, $statement['total_credit']);

        // Cleanup
        LedgerEntry::where('voucher_id', $v1->id)->delete();
        LedgerEntry::where('voucher_id', $v2->id)->delete();
        $v1->forceDelete();
        $v2->forceDelete();
    }

    public function test_voucher_number_format(): void
    {
        $service = app(AccountingService::class);
        $cashAccount = Account::where('code', '1101')->first();
        $incomeAccount = Account::where('code', '3001')->first();

        $this->actingAs($this->superAdmin());

        $voucher = $service->createVoucher('payment', now()->format('Y-m-d'), 'Format test', [
            ['account_id' => $incomeAccount->id, 'debit' => 100, 'credit' => 0],
            ['account_id' => $cashAccount->id, 'debit' => 0, 'credit' => 100],
        ], 'Rajkot');

        // Should be PV-RA-YYMMDD-001 format
        $this->assertStringStartsWith('PV-RA-', $voucher->voucher_no);
        $this->assertMatchesRegularExpression('/^PV-RA-\d{6}-\d{3}$/', $voucher->voucher_no);

        LedgerEntry::where('voucher_id', $voucher->id)->delete();
        $voucher->forceDelete();
    }
}
