<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Accounting\Account;

class AccountingChartTest extends TestCase
{
    private function superAdmin(): User
    {
        return User::where('role', 'super_admin')->first();
    }

    private function staff(): User
    {
        return User::where('role', 'agent')->first();
    }

    public function test_chart_of_accounts_page_loads(): void
    {
        $this->actingAs($this->superAdmin())
            ->get(route('accounting.accounts.index'))
            ->assertStatus(200)
            ->assertSee('Chart of Accounts');
    }

    public function test_staff_cannot_access_accounting(): void
    {
        $response = $this->actingAs($this->staff())
            ->get(route('accounting.accounts.index'));
        $this->assertContains($response->getStatusCode(), [403, 302]);
    }

    public function test_seeder_created_accounts(): void
    {
        $this->assertTrue(Account::count() >= 50);
        $this->assertNotNull(Account::where('code', '1101')->first()); // Cash In Hand
        $this->assertNotNull(Account::where('code', '3001')->first()); // Freight Income
        $this->assertNotNull(Account::where('code', '4101')->first()); // Diesel Expense
    }

    public function test_can_create_account(): void
    {
        // Cleanup any leftover
        Account::where('code', '9901')->forceDelete();

        $response = $this->actingAs($this->superAdmin())->post(route('accounting.accounts.store'), [
            'code' => '9901',
            'name' => 'Test Account',
            'type' => 'expense',
            'parent_id' => null,
            'is_group' => 0,
            'opening_balance' => 0,
        ]);

        if ($response->getStatusCode() === 500) {
            $this->fail('Store returned 500. Response: ' . substr($response->getContent(), 0, 500));
        }

        $response->assertRedirect(route('accounting.accounts.index'));
        $this->assertNotNull(Account::where('code', '9901')->first());
        Account::where('code', '9901')->forceDelete();
    }

    public function test_can_edit_account(): void
    {
        $account = Account::where('is_system', false)->first()
            ?? Account::create(['code' => '9902', 'name' => 'Edit Test', 'type' => 'expense', 'is_system' => false]);

        $this->actingAs($this->superAdmin())
            ->get(route('accounting.accounts.edit', $account->id))
            ->assertStatus(200)
            ->assertSee($account->name);

        if ($account->code === '9902') $account->forceDelete();
    }

    public function test_cannot_delete_system_account(): void
    {
        $system = Account::where('is_system', true)->first();

        $this->actingAs($this->superAdmin())
            ->delete(route('accounting.accounts.destroy', $system->id))
            ->assertRedirect();

        // Should still exist
        $this->assertNotNull(Account::find($system->id));
    }

    public function test_account_tree_structure(): void
    {
        $assets = Account::where('code', '1000')->first();
        $this->assertNotNull($assets);
        $this->assertTrue($assets->is_group);
        $this->assertTrue($assets->children->count() > 0);
    }
}
