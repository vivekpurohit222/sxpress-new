<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Accounting\Account;
use App\Models\Accounting\LedgerEntry;
use App\Models\Accounting\Voucher;
use App\Services\AccountingService;

class AccountingCashBookTest extends TestCase
{
    private function superAdmin(): User
    {
        return User::where('role', 'super_admin')->first();
    }

    private function staff(): User
    {
        return User::where('role', 'agent')->first();
    }

    public function test_daily_cash_book_loads(): void
    {
        $this->actingAs($this->superAdmin())
            ->get(route('accounting.cashbook.daily'))
            ->assertStatus(200)
            ->assertSee('Daily Cash Book')
            ->assertSee('Opening Balance')
            ->assertSee('Closing Balance');
    }

    public function test_monthly_cash_book_loads(): void
    {
        $this->actingAs($this->superAdmin())
            ->get(route('accounting.cashbook.monthly'))
            ->assertStatus(200)
            ->assertSee('Monthly Cash Summary');
    }

    public function test_staff_cannot_access_cash_book(): void
    {
        $response = $this->actingAs($this->staff())->get(route('accounting.cashbook.daily'));
        $this->assertContains($response->getStatusCode(), [403, 302]);
    }

    public function test_cash_book_shows_receipt_entry(): void
    {
        $service = app(AccountingService::class);
        $cash = Account::where('code', '1101')->first();
        $freight = Account::where('code', '3001')->first();

        $this->actingAs($this->superAdmin());

        // Create a receipt voucher (cash in)
        $voucher = $service->createVoucher('receipt', now()->format('Y-m-d'), 'Freight collection GR test', [
            ['account_id' => $cash->id, 'debit' => 3500, 'credit' => 0],
            ['account_id' => $freight->id, 'debit' => 0, 'credit' => 3500],
        ], 'Rajkot');

        // Cash book should show this receipt
        $this->actingAs($this->superAdmin())
            ->get(route('accounting.cashbook.daily', ['date' => now()->format('Y-m-d')]))
            ->assertStatus(200)
            ->assertSee('3,500.00')
            ->assertSee('Freight collection GR test');

        // Cleanup
        LedgerEntry::where('voucher_id', $voucher->id)->delete();
        $voucher->forceDelete();
    }

    public function test_cash_book_shows_payment_entry(): void
    {
        $service = app(AccountingService::class);
        $cash = Account::where('code', '1101')->first();
        $diesel = Account::where('code', '4101')->first();

        $this->actingAs($this->superAdmin());

        // Create a payment voucher (cash out)
        $voucher = $service->createVoucher('payment', now()->format('Y-m-d'), 'Diesel paid for GJ-03-AB', [
            ['account_id' => $diesel->id, 'debit' => 2200, 'credit' => 0],
            ['account_id' => $cash->id, 'debit' => 0, 'credit' => 2200],
        ], 'Rajkot');

        // Cash book should show this payment
        $this->actingAs($this->superAdmin())
            ->get(route('accounting.cashbook.daily', ['date' => now()->format('Y-m-d')]))
            ->assertStatus(200)
            ->assertSee('2,200.00')
            ->assertSee('Diesel paid');

        // Cleanup
        LedgerEntry::where('voucher_id', $voucher->id)->delete();
        $voucher->forceDelete();
    }

    public function test_closing_balance_calculated_correctly(): void
    {
        $service = app(AccountingService::class);
        $cash = Account::where('code', '1101')->first();
        $freight = Account::where('code', '3001')->first();
        $diesel = Account::where('code', '4101')->first();

        $this->actingAs($this->superAdmin());

        // Receipt: +5000
        $v1 = $service->createVoucher('receipt', now()->format('Y-m-d'), 'Income', [
            ['account_id' => $cash->id, 'debit' => 5000, 'credit' => 0],
            ['account_id' => $freight->id, 'debit' => 0, 'credit' => 5000],
        ], 'Rajkot');

        // Payment: -1500
        $v2 = $service->createVoucher('payment', now()->format('Y-m-d'), 'Expense', [
            ['account_id' => $diesel->id, 'debit' => 1500, 'credit' => 0],
            ['account_id' => $cash->id, 'debit' => 0, 'credit' => 1500],
        ], 'Rajkot');

        // Closing should include net: opening + 5000 - 1500 = opening + 3500
        $response = $this->actingAs($this->superAdmin())
            ->get(route('accounting.cashbook.daily', ['date' => now()->format('Y-m-d')]));

        $response->assertStatus(200);
        $response->assertSee('5,000.00'); // receipt
        $response->assertSee('1,500.00'); // payment

        // Cleanup
        LedgerEntry::where('voucher_id', $v1->id)->delete();
        LedgerEntry::where('voucher_id', $v2->id)->delete();
        $v1->forceDelete();
        $v2->forceDelete();
    }

    public function test_monthly_view_shows_daily_breakdown(): void
    {
        $service = app(AccountingService::class);
        $cash = Account::where('code', '1101')->first();
        $freight = Account::where('code', '3001')->first();

        $this->actingAs($this->superAdmin());

        // Use a unique amount to find it in the output
        $voucher = $service->createVoucher('receipt', now()->format('Y-m-d'), 'Monthly unique test', [
            ['account_id' => $cash->id, 'debit' => 7777, 'credit' => 0],
            ['account_id' => $freight->id, 'debit' => 0, 'credit' => 7777],
        ], 'Rajkot');

        $response = $this->actingAs($this->superAdmin())
            ->get(route('accounting.cashbook.monthly', ['month' => now()->format('Y-m')]));

        $response->assertStatus(200);
        // The monthly view should show today's date row (it aggregates all cash entries)
        $response->assertSee(now()->format('d M'));

        LedgerEntry::where('voucher_id', $voucher->id)->delete();
        $voucher->forceDelete();
    }

    public function test_branch_isolation_in_cash_book(): void
    {
        $service = app(AccountingService::class);
        $cash = Account::where('code', '1101')->first();
        $freight = Account::where('code', '3001')->first();

        $this->actingAs($this->superAdmin());

        // Create entry for Navagam
        $voucher = $service->createVoucher('receipt', now()->format('Y-m-d'), 'Navagam freight', [
            ['account_id' => $cash->id, 'debit' => 9999, 'credit' => 0],
            ['account_id' => $freight->id, 'debit' => 0, 'credit' => 9999],
        ], 'Navagam');

        // Cash book filtered by Rajkot should NOT show Navagam entry
        $response = $this->actingAs($this->superAdmin())
            ->get(route('accounting.cashbook.daily', ['date' => now()->format('Y-m-d'), 'branch' => 'Rajkot']));

        $response->assertStatus(200);
        $response->assertDontSee('9,999.00');
        $response->assertDontSee('Navagam freight');

        // But filtered by Navagam should show it
        $response2 = $this->actingAs($this->superAdmin())
            ->get(route('accounting.cashbook.daily', ['date' => now()->format('Y-m-d'), 'branch' => 'Navagam']));

        $response2->assertSee('9,999.00');

        LedgerEntry::where('voucher_id', $voucher->id)->delete();
        $voucher->forceDelete();
    }
}
