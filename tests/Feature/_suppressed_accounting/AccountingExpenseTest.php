<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Accounting\Account;
use App\Models\Accounting\Expense;
use App\Models\Accounting\Voucher;
use App\Models\Accounting\LedgerEntry;

class AccountingExpenseTest extends TestCase
{
    private function superAdmin(): User
    {
        return User::where('role', 'super_admin')->first();
    }

    private function staff(): User
    {
        return User::where('role', 'agent')->first();
    }

    public function test_expense_list_loads(): void
    {
        $this->actingAs($this->superAdmin())
            ->get(route('accounting.expenses.index'))
            ->assertStatus(200)
            ->assertSee('Expense Management');
    }

    public function test_expense_create_form_loads(): void
    {
        $this->actingAs($this->superAdmin())
            ->get(route('accounting.expenses.create'))
            ->assertStatus(200)
            ->assertSee('Record Expense')
            ->assertSee('Diesel');
    }

    public function test_staff_cannot_access_expenses(): void
    {
        $response = $this->actingAs($this->staff())->get(route('accounting.expenses.index'));
        $this->assertContains($response->getStatusCode(), [403, 302]);
    }

    public function test_can_create_expense_with_auto_voucher(): void
    {
        $dieselAccount = Account::where('code', '4101')->first();
        $cashAccount = Account::where('code', '1101')->first();

        $beforeVouchers = Voucher::count();

        $response = $this->actingAs($this->superAdmin())
            ->post(route('accounting.expenses.store'), [
                'expense_date' => now()->format('Y-m-d'),
                'expense_type' => 'diesel',
                'description' => 'Diesel for truck integration test',
                'amount' => 4500,
                'paid_to' => 'Rajkot Petrol Pump',
                'account_id' => $dieselAccount->id,
                'paid_from_account_id' => $cashAccount->id,
                'notes' => '',
            ]);

        if ($response->getStatusCode() === 500) {
            $this->fail('Store returned 500');
        }

        $response->assertRedirect(route('accounting.expenses.index'));

        $expense = Expense::orderByDesc('id')->first();
        $this->assertNotNull($expense);
        $this->assertEquals('diesel', $expense->expense_type);
        $this->assertEquals(4500, (float) $expense->amount);
        $this->assertEquals('approved', $expense->status);

        // Voucher should be auto-created
        $afterVouchers = Voucher::count();
        $this->assertGreaterThan($beforeVouchers, $afterVouchers);
        $this->assertNotNull($expense->voucher_id);

        // Cleanup
        if ($expense->voucher_id) {
            LedgerEntry::where('voucher_id', $expense->voucher_id)->delete();
            Voucher::where('id', $expense->voucher_id)->forceDelete();
        }
        $expense->forceDelete();
    }

    public function test_expense_report_loads(): void
    {
        $this->actingAs($this->superAdmin())
            ->get(route('accounting.expenses.report'))
            ->assertStatus(200)
            ->assertSee('Expense Summary');
    }

    public function test_expense_report_shows_categories(): void
    {
        $dieselAccount = Account::where('code', '4101')->first();
        $cashAccount = Account::where('code', '1101')->first();

        // Create test expenses
        $this->actingAs($this->superAdmin());
        $this->post(route('accounting.expenses.store'), [
            'expense_date' => now()->format('Y-m-d'),
            'expense_type' => 'diesel',
            'description' => 'Report test diesel',
            'amount' => 2000,
            'paid_to' => 'Pump',
            'account_id' => $dieselAccount->id,
            'paid_from_account_id' => $cashAccount->id,
        ]);

        $response = $this->get(route('accounting.expenses.report', [
            'from_date' => now()->format('Y-m-d'),
            'to_date' => now()->format('Y-m-d'),
        ]));

        $response->assertStatus(200)->assertSee('Diesel');

        // Cleanup
        $exp = Expense::where('description', 'Report test diesel')->first();
        if ($exp) {
            if ($exp->voucher_id) {
                LedgerEntry::where('voucher_id', $exp->voucher_id)->delete();
                Voucher::where('id', $exp->voucher_id)->forceDelete();
            }
            $exp->forceDelete();
        }
    }
}
