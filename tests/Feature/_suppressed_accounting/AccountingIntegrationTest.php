<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Accounting\Voucher;
use App\Models\Accounting\LedgerEntry;
use App\Models\Accounting\Outstanding;
use App\Models\Freight;
use Illuminate\Support\Facades\DB;

class AccountingIntegrationTest extends TestCase
{
    private function superAdmin(): User
    {
        return User::where('role', 'super_admin')->first();
    }

    /**
     * When a PAID GR is created, a Receipt Voucher should be auto-generated.
     * Debit: Cash (1101), Credit: Freight Income (3001)
     */
    public function test_paid_gr_creates_receipt_voucher(): void
    {
        $before = Voucher::count();

        $this->actingAs($this->superAdmin())->post(route('gr.store'), [
            'from_dest' => 'Rajkot - PN', 'to_dest' => 'Navagam',
            'copy_date' => now()->format('d-m-y'),
            'consignor' => 'Test Corp', 'consignor_address' => 'Addr', 'consignor_gst_no' => '',
            'consignee' => 'Buyer Ltd', 'consignee_address' => 'Addr2', 'consignee_gst_no' => '',
            'nugs' => 5, 'meth' => 'Box', 'description' => 'Accounting test goods',
            'pm' => '', 'weight' => 50, 'eway_bill_number' => '',
            'frieght_amount' => 2000, 'sur_ch' => 100, 'c_r' => 0, 'other' => 0, 'bc_amount' => 0,
            'bill_amount' => 0, 'paid' => 1, 'to_pay' => 0,
        ])->assertRedirect(route('gr.index'));

        $after = Voucher::count();
        $this->assertGreaterThan($before, $after, 'Voucher should be created for paid GR');

        // Verify the latest voucher is a receipt for this GR
        $voucher = Voucher::orderByDesc('id')->first();
        $this->assertEquals('receipt', $voucher->voucher_type);
        $this->assertStringContains('Freight received', $voucher->narration);
        $this->assertEquals(2100, (float) $voucher->total_amount); // 2000 + 100 surcharge

        // Cleanup
        LedgerEntry::where('voucher_id', $voucher->id)->delete();
        $voucher->forceDelete();
        DB::table('grs')->where('description', 'Accounting test goods')->delete();
    }

    /**
     * When a TO-PAY GR is created, a Journal Voucher should be auto-generated.
     * Debit: Receivable (1202), Credit: Freight Income (3001)
     */
    public function test_topay_gr_creates_journal_voucher(): void
    {
        $before = Voucher::count();

        $this->actingAs($this->superAdmin())->post(route('gr.store'), [
            'from_dest' => 'Rajkot - PN', 'to_dest' => 'Navagam',
            'copy_date' => now()->format('d-m-y'),
            'consignor' => 'Sender Co', 'consignor_address' => 'A', 'consignor_gst_no' => '',
            'consignee' => 'Receiver Co', 'consignee_address' => 'B', 'consignee_gst_no' => '',
            'nugs' => 3, 'meth' => 'Bag', 'description' => 'Topay accounting test',
            'pm' => '', 'weight' => 30, 'eway_bill_number' => '',
            'frieght_amount' => 1500, 'sur_ch' => 0, 'c_r' => 0, 'other' => 0, 'bc_amount' => 0,
            'bill_amount' => 0, 'paid' => 0, 'to_pay' => 1,
        ])->assertRedirect(route('gr.index'));

        $after = Voucher::count();
        $this->assertGreaterThan($before, $after, 'Voucher should be created for TO-PAY GR');

        $voucher = Voucher::orderByDesc('id')->first();
        $this->assertEquals('journal', $voucher->voucher_type);
        $this->assertStringContains('To-Pay', $voucher->narration);
        $this->assertEquals(1500, (float) $voucher->total_amount);

        // Cleanup
        LedgerEntry::where('voucher_id', $voucher->id)->delete();
        $voucher->forceDelete();
        DB::table('grs')->where('description', 'Topay accounting test')->delete();
    }

    /**
     * When a Freight Memo is created, accounting entries are auto-generated.
     * Lorry Hire expense and Truck Owner payable.
     */
    public function test_freight_memo_creates_accounting_entries(): void
    {
        $beforeVouchers = Voucher::count();
        $beforeOutstanding = Outstanding::where('reference_type', 'freight_memo')->count();

        $this->actingAs($this->superAdmin())->post(route('frieghtmemo.store'), [
            'fm_date' => now()->format('Y-m-d'),
            'from_dest' => 'Rajkot - PN', 'to_dest' => 'Navagam',
            'truck_no' => 'GJ-03-TEST-001',
            'truck_freight' => 18000,
            'advance' => 5000,
            'commission' => 1000,
            'hamali' => 500,
            'detention' => 0,
            'tds' => 180,
            'other_charges' => 0,
            'note' => 'Accounting integration test',
        ])->assertRedirect(route('frieghtmemo.index'));

        $afterVouchers = Voucher::count();
        $afterOutstanding = Outstanding::where('reference_type', 'freight_memo')->count();

        // Should have created 2 vouchers (lorry hire + advance)
        $this->assertGreaterThanOrEqual($beforeVouchers + 2, $afterVouchers);

        // Should have created outstanding payable
        $this->assertGreaterThan($beforeOutstanding, $afterOutstanding);

        // Verify outstanding amount = balance_due = 18000 - 5000 - 1000 - 500 - 0 - 180 - 0 = 11320
        $fm = Freight::orderByDesc('id')->first();
        $outstanding = Outstanding::where('reference_type', 'freight_memo')->where('reference_id', $fm->id)->first();
        $this->assertNotNull($outstanding);
        $this->assertEquals(11320, (float) $outstanding->pending_amount);
        $this->assertEquals('payable', $outstanding->type);
        $this->assertEquals('truck_owner', $outstanding->party_type);

        // Cleanup
        LedgerEntry::whereIn('voucher_id', Voucher::where('reference_id', $fm->id)->where('reference_type', 'freight_memo')->pluck('id'))->delete();
        Voucher::where('reference_id', $fm->id)->where('reference_type', 'freight_memo')->forceDelete();
        $outstanding->forceDelete();
        $fm->forceDelete();
    }

    private function assertStringContains(string $needle, string $haystack): void
    {
        $this->assertTrue(str_contains($haystack, $needle), "'{$haystack}' does not contain '{$needle}'");
    }
}
