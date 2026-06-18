<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Accounting\Outstanding;

class AccountingOutstandingTest extends TestCase
{
    private function superAdmin(): User
    {
        return User::where('role', 'super_admin')->first();
    }

    public function test_outstanding_index_loads(): void
    {
        $this->actingAs($this->superAdmin())
            ->get(route('accounting.outstanding.index'))
            ->assertStatus(200)
            ->assertSee('Outstanding Management');
    }

    public function test_ageing_report_loads(): void
    {
        $this->actingAs($this->superAdmin())
            ->get(route('accounting.outstanding.ageing'))
            ->assertStatus(200)
            ->assertSee('Ageing Report');
    }

    public function test_recovery_report_loads(): void
    {
        $this->actingAs($this->superAdmin())
            ->get(route('accounting.outstanding.recovery'))
            ->assertStatus(200)
            ->assertSee('Recovery Report');
    }

    public function test_sync_from_gr_creates_entries(): void
    {
        $beforeCount = Outstanding::count();

        // Delete all existing outstanding to force a fresh sync
        Outstanding::query()->forceDelete();

        $this->actingAs($this->superAdmin())
            ->post(route('accounting.outstanding.sync'))
            ->assertRedirect(route('accounting.outstanding.index'))
            ->assertSessionHas('success');

        // Should have created entries from TO-PAY GRs (we have 203+ in the database)
        $afterCount = Outstanding::count();
        $this->assertTrue($afterCount > 0, "Expected outstanding entries after sync, got {$afterCount}");
    }

    public function test_record_payment_reduces_pending(): void
    {
        $entry = Outstanding::create([
            'party_type' => 'consignee', 'party_name' => 'Test Party',
            'type' => 'receivable', 'invoice_ref' => 'TEST-PAY-001',
            'invoice_date' => now(), 'total_amount' => 5000,
            'paid_amount' => 0, 'pending_amount' => 5000,
            'status' => 'pending', 'branch' => 'Rajkot',
        ]);

        $this->actingAs($this->superAdmin())
            ->post(route('accounting.outstanding.payment', $entry->id), ['amount' => 2000])
            ->assertRedirect();

        $entry->refresh();
        $this->assertEquals(2000, (float) $entry->paid_amount);
        $this->assertEquals(3000, (float) $entry->pending_amount);
        $this->assertEquals('partial', $entry->status);

        // Pay remaining
        $this->actingAs($this->superAdmin())
            ->post(route('accounting.outstanding.payment', $entry->id), ['amount' => 3000]);

        $entry->refresh();
        $this->assertEquals(5000, (float) $entry->paid_amount);
        $this->assertEquals(0, (float) $entry->pending_amount);
        $this->assertEquals('paid', $entry->status);

        $entry->forceDelete();
    }

    public function test_ageing_shows_correct_buckets(): void
    {
        // Create entries with different ages
        $old = Outstanding::create([
            'party_type' => 'consignee', 'party_name' => 'Old Debtor',
            'type' => 'receivable', 'invoice_ref' => 'AGE-OLD',
            'invoice_date' => now()->subDays(45), 'total_amount' => 1000,
            'paid_amount' => 0, 'pending_amount' => 1000,
            'status' => 'pending', 'branch' => 'Rajkot',
        ]);
        $recent = Outstanding::create([
            'party_type' => 'consignee', 'party_name' => 'New Debtor',
            'type' => 'receivable', 'invoice_ref' => 'AGE-NEW',
            'invoice_date' => now()->subDays(3), 'total_amount' => 2000,
            'paid_amount' => 0, 'pending_amount' => 2000,
            'status' => 'pending', 'branch' => 'Rajkot',
        ]);

        $response = $this->actingAs($this->superAdmin())
            ->get(route('accounting.outstanding.ageing', ['type' => 'receivable']));

        $response->assertStatus(200)
            ->assertSee('Old Debtor')
            ->assertSee('New Debtor')
            ->assertSee('31-60 days')
            ->assertSee('0-7 days');

        $old->forceDelete();
        $recent->forceDelete();
    }
}
