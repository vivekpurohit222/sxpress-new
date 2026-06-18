<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Accounting\Account;
use App\Models\Accounting\Voucher;
use App\Models\Accounting\LedgerEntry;

class AccountingVoucherTest extends TestCase
{
    private function superAdmin(): User
    {
        return User::where('role', 'super_admin')->first();
    }

    private function staff(): User
    {
        return User::where('role', 'agent')->first();
    }

    public function test_voucher_list_loads(): void
    {
        $this->actingAs($this->superAdmin())
            ->get(route('accounting.vouchers.index'))
            ->assertStatus(200)
            ->assertSee('Vouchers');
    }

    public function test_staff_cannot_access_vouchers(): void
    {
        $response = $this->actingAs($this->staff())
            ->get(route('accounting.vouchers.index'));
        $this->assertContains($response->getStatusCode(), [403, 302]);
    }

    public function test_create_receipt_voucher_form_loads(): void
    {
        $this->actingAs($this->superAdmin())
            ->get(route('accounting.vouchers.create', ['type' => 'receipt']))
            ->assertStatus(200)
            ->assertSee('Receipt Voucher');
    }

    public function test_create_payment_voucher_form_loads(): void
    {
        $this->actingAs($this->superAdmin())
            ->get(route('accounting.vouchers.create', ['type' => 'payment']))
            ->assertStatus(200)
            ->assertSee('Payment Voucher');
    }

    public function test_can_create_receipt_voucher(): void
    {
        $cash = Account::where('code', '1101')->first();
        $freight = Account::where('code', '3001')->first();

        $response = $this->actingAs($this->superAdmin())
            ->post(route('accounting.vouchers.store'), [
                'voucher_type' => 'receipt',
                'voucher_date' => now()->format('Y-m-d'),
                'narration' => 'Freight collected from consignee',
                'entries' => [
                    ['account_id' => $cash->id, 'debit' => 5000, 'credit' => 0],
                    ['account_id' => $freight->id, 'debit' => 0, 'credit' => 5000],
                ],
            ]);

        $response->assertRedirect(route('accounting.vouchers.index'));

        $voucher = Voucher::orderByDesc('id')->first();
        $this->assertNotNull($voucher);
        $this->assertStringStartsWith('RV-', $voucher->voucher_no);
        $this->assertEquals(5000, (float) $voucher->total_amount);
        $this->assertEquals('approved', $voucher->status);
        $this->assertEquals(2, $voucher->entries->count());

        // Cleanup
        LedgerEntry::where('voucher_id', $voucher->id)->delete();
        $voucher->forceDelete();
    }

    public function test_unbalanced_voucher_rejected(): void
    {
        $cash = Account::where('code', '1101')->first();
        $freight = Account::where('code', '3001')->first();

        $this->actingAs($this->superAdmin())
            ->post(route('accounting.vouchers.store'), [
                'voucher_type' => 'receipt',
                'voucher_date' => now()->format('Y-m-d'),
                'narration' => 'Unbalanced test',
                'entries' => [
                    ['account_id' => $cash->id, 'debit' => 5000, 'credit' => 0],
                    ['account_id' => $freight->id, 'debit' => 0, 'credit' => 3000],
                ],
            ])
            ->assertSessionHasErrors('entries');
    }

    public function test_voucher_detail_shows_entries(): void
    {
        $cash = Account::where('code', '1101')->first();
        $freight = Account::where('code', '3001')->first();

        // Create via service
        $service = app(\App\Services\AccountingService::class);
        $this->actingAs($this->superAdmin());

        $voucher = $service->createVoucher('receipt', now()->format('Y-m-d'), 'View test', [
            ['account_id' => $cash->id, 'debit' => 2000, 'credit' => 0],
            ['account_id' => $freight->id, 'debit' => 0, 'credit' => 2000],
        ], 'Rajkot');

        $this->actingAs($this->superAdmin())
            ->get(route('accounting.vouchers.show', $voucher->id))
            ->assertStatus(200)
            ->assertSee($voucher->voucher_no)
            ->assertSee('Cash In Hand')
            ->assertSee('Freight Income')
            ->assertSee('2,000.00');

        LedgerEntry::where('voucher_id', $voucher->id)->delete();
        $voucher->forceDelete();
    }

    public function test_can_cancel_voucher(): void
    {
        $cash = Account::where('code', '1101')->first();
        $freight = Account::where('code', '3001')->first();

        $service = app(\App\Services\AccountingService::class);
        $this->actingAs($this->superAdmin());

        $voucher = $service->createVoucher('receipt', now()->format('Y-m-d'), 'Cancel test', [
            ['account_id' => $cash->id, 'debit' => 1000, 'credit' => 0],
            ['account_id' => $freight->id, 'debit' => 0, 'credit' => 1000],
        ], 'Rajkot');

        $this->actingAs($this->superAdmin())
            ->post(route('accounting.vouchers.cancel', $voucher->id))
            ->assertRedirect(route('accounting.vouchers.index'));

        $voucher->refresh();
        $this->assertEquals('cancelled', $voucher->status);
        $this->assertEquals(0, LedgerEntry::where('voucher_id', $voucher->id)->count());

        $voucher->forceDelete();
    }

    public function test_print_voucher_loads(): void
    {
        $cash = Account::where('code', '1101')->first();
        $freight = Account::where('code', '3001')->first();

        $service = app(\App\Services\AccountingService::class);
        $this->actingAs($this->superAdmin());

        $voucher = $service->createVoucher('payment', now()->format('Y-m-d'), 'Print test', [
            ['account_id' => $freight->id, 'debit' => 800, 'credit' => 0],
            ['account_id' => $cash->id, 'debit' => 0, 'credit' => 800],
        ], 'Rajkot');

        $this->actingAs($this->superAdmin())
            ->get(route('accounting.vouchers.print', $voucher->id))
            ->assertStatus(200)
            ->assertSee($voucher->voucher_no)
            ->assertSee('SAURASHTRA EXPRESS');

        LedgerEntry::where('voucher_id', $voucher->id)->delete();
        $voucher->forceDelete();
    }
}
