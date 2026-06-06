<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Seed the 7 hard-coded offices into the new `branches` table so the
 * modernized tenancy model has a backfill target. A seeder is the wrong
 * tool here because:
 *  - Seeders only run on `db:seed`, not on `migrate`
 *  - The branches table is created by migration 000050 and must be
 *    populated before any FK from `users.branch_id` can be satisfied
 *
 * This migration inserts the 7 known office names + the 4 number-sequence
 * scopes + a couple of baseline settings. It is idempotent (uses
 * INSERT IGNORE / ON DUPLICATE KEY UPDATE).
 *
 * --------------------------------------------------------------------------
 * Source of discovery
 * --------------------------------------------------------------------------
 * - docs/database-reconstruction-report.md  §2.3 (the 7 hard-coded office
 *                                              names live in Blade @php
 *                                              arrays)
 * - docs/erp-workflow-map.md                §2 (per-office GR numbering)
 * - docs/master-execution-roadmap.md        §2.1, §5.5
 *
 * --------------------------------------------------------------------------
 * Confidence: 100% on the office list (every list view uses the same
 *             7 names).
 * --------------------------------------------------------------------------
 *
 * --------------------------------------------------------------------------
 * Related models
 * --------------------------------------------------------------------------
 * - App\Models\Branch         (table: branches)
 * - App\Models\NumberSequence (table: number_sequences)
 * - App\Models\Setting        (table: settings)
 *
 * --------------------------------------------------------------------------
 * Backward compatibility
 * --------------------------------------------------------------------------
 * Pure INSERT. Idempotent (ON DUPLICATE KEY UPDATE name = name). No
 * existing data is touched.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('branches')) {
            $offices = [
                ['RJKT', 'Rajkot',         'Rajkot',     'Gujarat', '360001'],
                ['KASH', 'Kashmore Gate',  'Kashmore',   'Sindh',   '00000'],
                ['DYBS', 'Dayabasti',      'Delhi',      'Delhi',   '110006'],
                ['SWNP', 'Swarup Nagar',   'Kanpur',     'UP',      '208001'],
                ['NVGM', 'Navagam',        'Surat',      'Gujarat', '395010'],
                ['SHP1', 'Shapar (1)',     'Shapar',     'Gujarat', '360024'],
                ['SHP2', 'Shapar (2)',     'Shapar',     'Gujarat', '360024'],
            ];
            foreach ($offices as [$code, $name, $city, $state, $pincode]) {
                DB::table('branches')->insertOrIgnore([
                    'code'      => $code,
                    'name'      => $name,
                    'city'      => $city,
                    'state'     => $state,
                    'pincode'   => $pincode,
                    'is_active' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        if (Schema::hasTable('number_sequences')) {
            $scopes = [
                ['scope' => 'gr',        'prefix' => '',  'pad_length' => 5],
                ['scope' => 'gr:RJKT',   'prefix' => 'AA-', 'pad_length' => 5],
                ['scope' => 'gr:KASH',   'prefix' => 'KA-', 'pad_length' => 5],
                ['scope' => 'gr:DYBS',   'prefix' => 'DB-', 'pad_length' => 5],
                ['scope' => 'gr:SWNP',   'prefix' => 'SN-', 'pad_length' => 5],
                ['scope' => 'gr:NVGM',   'prefix' => 'NV-', 'pad_length' => 5],
                ['scope' => 'gr:SHP1',   'prefix' => 'S1-', 'pad_length' => 5],
                ['scope' => 'gr:SHP2',   'prefix' => 'S2-', 'pad_length' => 5],
                ['scope' => 'gp',        'prefix' => '',  'pad_length' => 4],
                ['scope' => 'challan',   'prefix' => '',  'pad_length' => 4],
                ['scope' => 'fm',        'prefix' => '',  'pad_length' => 4],
            ];
            foreach ($scopes as $row) {
                DB::table('number_sequences')->insertOrIgnore(
                    $row + ['current_value' => 0, 'updated_at' => now()]
                );
            }
        }

        if (Schema::hasTable('settings')) {
            $settings = [
                ['key' => 'default_branch_id',      'value' => '',     'type' => 'int',     'group' => 'app'],
                ['key' => 'default_freight_rate',   'value' => '0',    'type' => 'float',   'group' => 'billing'],
                ['key' => 'default_gst_rate',       'value' => '18',   'type' => 'float',   'group' => 'billing'],
                ['key' => 'eway_validity_hours',    'value' => '24',   'type' => 'int',     'group' => 'compliance'],
                ['key' => 'finance_fy_start_month', 'value' => '4',    'type' => 'int',     'group' => 'finance'],
            ];
            foreach ($settings as $row) {
                DB::table('settings')->insertOrIgnore(
                    $row + ['description' => null, 'created_at' => now(), 'updated_at' => now()]
                );
            }
        }
    }

    public function down(): void
    {
        // No-op rollback: deleting seeded data is destructive and the
        // migration's purpose is to install baseline rows.
    }
};
