<?php

namespace Database\Seeders;

use App\Models\Gr;
use App\Models\gatepass;
use App\Models\challan;
use App\Models\ChallanItem;
use App\Models\Freight;
use App\Models\Branch;
use App\Models\Vehicle;
use App\Models\truckdriver;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * Full Workflow Test Seeder — 500 entries with realistic Indian transport data.
 *
 * Simulates the complete lifecycle:
 * GR → Gatepass → Challan → (POD) → Freight Memo
 *
 * Uses real Gujarat/Rajasthan consignor/consignee names, Indian goods descriptions,
 * realistic weights and freight amounts for LTL (Less Than Truckload) transport.
 */
class FullWorkflowTestSeeder extends Seeder
{
    // Real Indian business names (Gujarat region)
    private array $consignors = [
        'Tata Motors Ltd', 'Ambuja Cements', 'Reliance Industries', 'Asian Paints',
        'Godrej Consumer', 'Pidilite Industries', 'Torrent Pharma', 'Adani Wilmar',
        'Havells India', 'Bajaj Electricals', 'Crompton Greaves', 'V-Guard Industries',
        'Supreme Industries', 'Astral Pipes', 'Finolex Cables', 'Polycab India',
        'Kajaria Ceramics', 'Somany Ceramics', 'Orient Electric', 'Whirlpool India',
        'Marico Ltd', 'Dabur India', 'Emami Ltd', 'Jyothy Labs',
        'Berger Paints', 'Kansai Nerolac', 'Shalimar Paints', 'Nippon Paint',
        'Ultratech Cement', 'Shree Cement', 'ACC Cement', 'Dalmia Bharat',
        'JSW Steel', 'Tata Steel', 'Hindalco Industries', 'Vedanta Ltd',
        'Mahindra Auto Parts', 'Hero MotoCorp Spares', 'Bajaj Auto Parts', 'TVS Motor Parts',
    ];

    private array $consignees = [
        'Patel Hardware Store', 'Shah Electricals', 'Rajkot Auto Parts', 'Mehta Trading Co',
        'Navagam Cement Depot', 'Shapar Industrial Supplies', 'Swarup Nagar Traders',
        'Dayabasti General Store', 'Kashmore Gate Agency', 'Gujarat Pharma Distributors',
        'Saurashtra Paint House', 'Jay Ambe Traders', 'Shree Krishna Enterprises',
        'Balaji Trading Company', 'Om Sai Distributors', 'Mahadev Steel Agency',
        'Shivam Electricals', 'Pancham Hardware', 'Ravi Cement Depot', 'Laxmi Auto Parts',
        'Krishna Pipe Agency', 'Ganesh Trading Co', 'Bharat Electronics Store',
        'Saraswati Pharma', 'Shanti General Stores', 'Khodiyar Traders',
        'Nilkanth Enterprises', 'Dwarkesh Trading', 'Santosh Hardware',
        'Ambika Industrial Supplies', 'Parmar Brothers', 'Solanki Trading Co',
        'Chauhan Electricals', 'Jadeja Auto Spares', 'Vaghela & Sons',
        'Gohil Trading Agency', 'Rathod Cement House', 'Zala Pipe Centre',
        'Makwana Hardware', 'Doshi Steel Traders',
    ];

    private array $addresses = [
        'Ring Road, Rajkot - 360001', 'GIDC Phase-1, Shapar - 360024',
        'Station Road, Navagam - 360110', 'Main Bazaar, Swarup Nagar - 360003',
        'Industrial Area, Dayabasti - 360004', 'Market Yard, Kashmore Gate - 360005',
        'Highway Road, Shapar Phase-2 - 360024', 'Amin Marg, Rajkot - 360001',
        'Kalawad Road, Rajkot - 360005', 'Gondal Road, Rajkot - 360002',
        '150 Feet Ring Road, Rajkot - 360007', 'University Road, Rajkot - 360005',
    ];

    private array $descriptions = [
        'Cement Bags 50kg', 'Steel Rods 12mm', 'PVC Pipes 4inch', 'Electric Cables',
        'Paint Buckets 20L', 'Ceramic Tiles 2x2', 'Auto Spare Parts', 'Pharma Medicines',
        'FMCG Products', 'Electronics Items', 'Plastic Granules', 'Cotton Bales',
        'Oil Drums 200L', 'Hardware Fittings', 'Sanitary Wares', 'Plywood Sheets',
        'Glass Bottles', 'Chemical Drums', 'Food Grains Bags', 'Textile Rolls',
        'Motor Parts', 'Battery Cases', 'Pump Sets', 'Transformer Oil',
        'Copper Wire Rolls', 'Aluminium Sheets', 'SS Pipes Bundle', 'Iron Angles',
        'Marble Slabs', 'Granite Blocks', 'Rubber Sheets', 'Paper Reels',
    ];

    private array $methods = ['Bag', 'Box', 'Bundle', 'Drum', 'Roll', 'Carton', 'Loose'];

    public function run(): void
    {
        $this->command->info('Starting Full Workflow Test Seeder — 500 entries...');

        $branches = Branch::active()->pluck('branch_name')->toArray();
        if (empty($branches)) {
            $this->command->error('No active branches found. Cannot seed.');
            return;
        }

        $vehicles = Vehicle::where('status', 'active')->get();
        $drivers = truckdriver::where('status', 1)->get();

        if ($vehicles->isEmpty() || $drivers->isEmpty()) {
            $this->command->error('No active vehicles/drivers found. Cannot seed.');
            return;
        }

        $grCount = 0;
        $gatepassCount = 0;
        $challanCount = 0;
        $freightCount = 0;

        // Generate 500 GRs across branches over last 90 days
        $this->command->info('Creating 500 GRs...');

        for ($i = 0; $i < 500; $i++) {
            $office = $branches[array_rand($branches)];
            $otherBranches = array_diff($branches, [$office]);
            $toDest = $otherBranches[array_rand($otherBranches)];
            $date = Carbon::now()->subDays(rand(1, 90));

            $consignor = $this->consignors[array_rand($this->consignors)];
            $consignee = $this->consignees[array_rand($this->consignees)];
            $desc = $this->descriptions[array_rand($this->descriptions)];
            $meth = $this->methods[array_rand($this->methods)];
            $nugs = rand(1, 50);
            $weight = round(rand(5, 5000) / 10, 2); // 0.5 to 500 kg
            $freight = round(rand(100, 15000) / 1, 0); // ₹100 to ₹15,000
            $surCh = rand(0, 5) > 3 ? rand(50, 500) : 0;
            $cr = rand(0, 5) > 3 ? rand(20, 200) : 0;
            $other = rand(0, 5) > 4 ? rand(10, 100) : 0;
            $bc = rand(0, 5) > 4 ? rand(50, 300) : 0;
            $total = $freight + $surCh + $cr + $other + $bc;
            $isPaid = rand(0, 1);

            // Generate GR number
            $branch = Branch::where('branch_name', $office)->first();
            $prefix = $branch->gr_prefix ?? 'XX';
            $lastGr = Gr::withTrashed()->where('office', $office)->orderByDesc('id')->first();
            $nextNum = 1;
            if ($lastGr && $lastGr->gr_no) {
                $parts = explode('-', $lastGr->gr_no);
                if (count($parts) === 2) {
                    $nextNum = ((int) $parts[1]) + 1;
                }
            }
            $grNo = $prefix . '-' . str_pad($nextNum, 5, '0', STR_PAD_LEFT);

            $grId = DB::table('grs')->insertGetId([
                'gr_no' => $grNo,
                'from_dest' => $office,
                'to_dest' => $toDest,
                'copy_date' => $date->format('Y-m-d'),
                'consignor' => $consignor,
                'consignor_address' => $this->addresses[array_rand($this->addresses)],
                'consignor_gst_no' => '24' . strtoupper(substr(md5($consignor), 0, 10)) . '1Z' . rand(1, 9),
                'consignee' => $consignee,
                'consignee_address' => $this->addresses[array_rand($this->addresses)],
                'consignee_gst_no' => '24' . strtoupper(substr(md5($consignee), 0, 10)) . '1Z' . rand(1, 9),
                'nugs' => $nugs,
                'meth' => $meth,
                'description' => $desc,
                'pm' => rand(0, 5) > 3 ? 'PM-' . rand(100, 999) : '',
                'eway_bill_number' => rand(0, 3) > 1 ? (string) rand(100000000000, 999999999999) : '',
                'bill_amount' => rand(0, 5) > 3 ? rand(1000, 50000) : 0,
                'weight' => $weight,
                'frieght_amount' => $freight,
                'sur_ch' => $surCh,
                'c_r' => $cr,
                'other' => $other,
                'bc_amount' => $bc,
                'total_amount' => $total,
                'paid' => $isPaid,
                'to_pay' => $isPaid ? 0 : 1,
                'topay_collected' => 0,
                'office' => $office,
                'status' => 'created',
                'created_by_id' => 1,
                'created_at' => $date,
                'updated_at' => $date,
            ]);

            $grCount++;
        }

        $this->command->info("✓ Created {$grCount} GRs");

        // Create Gatepasses — dispatch 350 of the 500 GRs
        $this->command->info('Creating Gatepasses (dispatching ~350 GRs)...');

        $createdGrs = Gr::where('status', 'created')->orderBy('copy_date')->get();
        $grBatches = $createdGrs->groupBy('office');

        foreach ($grBatches as $office => $grs) {
            $grsToDispatch = $grs->take((int) ($grs->count() * 0.7)); // 70% of each branch
            $chunks = $grsToDispatch->chunk(rand(2, 6)); // 2-6 GRs per gatepass

            foreach ($chunks as $chunk) {
                $vehicle = $vehicles->random();
                $driver = $drivers->random();
                $gpDate = Carbon::parse($chunk->first()->copy_date)->addDays(rand(0, 2));

                // Generate GP number (globally unique integer)
                $lastGp = gatepass::withTrashed()->orderByDesc('gp_no')->first();
                $gpNo = $lastGp ? $lastGp->gp_no + 1 : 1;

                $gpId = DB::table('gatepasses')->insertGetId([
                    'gp_no' => $gpNo,
                    'gp_date' => $gpDate->format('Y-m-d'),
                    'from_dest' => $office,
                    'to_dest' => $chunk->first()->to_dest,
                    'vehicle_id' => $vehicle->id,
                    'driver_id' => $driver->id,
                    'consignor' => $chunk->pluck('consignor')->first(),
                    'nugs' => $chunk->sum('nugs'),
                    'weight' => $chunk->sum('weight'),
                    'pm' => '',
                    'frieght_amount' => $chunk->sum('frieght_amount'),
                    'labour_amount' => 0,
                    'other' => 0,
                    'delivery_charge' => 0,
                    'total_amount' => $chunk->sum('total_amount'),
                    'gr_no' => $chunk->first()->gr_no,
                    'note' => '',
                    'office' => $office,
                    'created_by_id' => 1,
                    'created_at' => $gpDate,
                    'updated_at' => $gpDate,
                ]);

                // Link GRs and update status
                foreach ($chunk as $gr) {
                    DB::table('gatepass_gr')->insert([
                        'gatepass_id' => $gpId,
                        'gr_id' => $gr->id,
                        'gr_no' => $gr->gr_no,
                    ]);
                    DB::table('grs')->where('id', $gr->id)->update([
                        'status' => 'dispatched',
                        'status_updated_at' => $gpDate,
                    ]);
                }

                $gatepassCount++;
            }
        }

        $this->command->info("✓ Created {$gatepassCount} Gatepasses");

        // Create Challans — truck trips for dispatched GRs
        $this->command->info('Creating Challans...');

        $dispatchedGrs = Gr::where('status', 'dispatched')->orderBy('copy_date')->get();
        $gpGroups = $dispatchedGrs->groupBy(function ($gr) {
            return $gr->office . '|' . $gr->to_dest;
        });

        foreach ($gpGroups as $key => $grs) {
            $chunks = $grs->chunk(rand(3, 8)); // 3-8 GRs per challan

            foreach ($chunks as $chunk) {
                $office = $chunk->first()->office;
                $vehicle = $vehicles->random();
                $driver = $drivers->random();
                $challanDate = Carbon::parse($chunk->first()->copy_date)->addDays(rand(0, 1));

                // Generate Challan No (globally unique)
                $lastCh = challan::withTrashed()->orderByDesc('id')->first();
                $chNext = $lastCh ? ((int) substr($lastCh->challan_no, 3)) + 1 : 1;
                $challanNo = 'CH-' . str_pad($chNext, 5, '0', STR_PAD_LEFT);

                $totalWeight = $chunk->sum('weight');

                $challanId = DB::table('challans')->insertGetId([
                    'challan_no' => $challanNo,
                    'challan_date' => $challanDate->format('Y-m-d'),
                    'from_dest' => $office,
                    'to_dest' => $chunk->first()->to_dest,
                    'vehicle_id' => $vehicle->id,
                    'driver_id' => $driver->id,
                    'truck_no' => $vehicle->vehicle_number,
                    'driver_name' => $driver->driver_name,
                    'license' => $driver->license ?? '',
                    'owner_name' => $vehicle->owner_name ?? '',
                    'total_weight' => $totalWeight,
                    'office' => $office,
                    'created_at' => $challanDate,
                    'updated_at' => $challanDate,
                ]);

                foreach ($chunk as $gr) {
                    DB::table('challan_items')->insert([
                        'challan_id' => $challanId,
                        'challan_no' => $challanNo,
                        'gr_no' => $gr->gr_no,
                        'nugs' => $gr->nugs,
                        'meth' => $gr->meth,
                        'description' => $gr->description,
                        'weight' => $gr->weight,
                        'paid' => $gr->paid ? $gr->total_amount : 0,
                        'to_pay' => $gr->to_pay ? $gr->total_amount : 0,
                        'sur_ch' => $gr->sur_ch,
                        'c_r' => $gr->c_r,
                        'other' => $gr->other,
                        'created_at' => $challanDate,
                        'updated_at' => $challanDate,
                    ]);

                    // Move some GRs to in_transit
                    DB::table('grs')->where('id', $gr->id)->update([
                        'status' => 'in_transit',
                        'status_updated_at' => $challanDate->addHours(rand(1, 6)),
                    ]);
                }

                $challanCount++;
            }
        }

        $this->command->info("✓ Created {$challanCount} Challans");

        // Deliver 60% of in_transit GRs and create Freight Memos
        $this->command->info('Delivering GRs and creating Freight Memos...');

        $inTransitGrs = Gr::where('status', 'in_transit')->get();
        $toDeliver = $inTransitGrs->take((int) ($inTransitGrs->count() * 0.6));

        foreach ($toDeliver as $gr) {
            $deliveryDate = Carbon::parse($gr->status_updated_at ?? $gr->copy_date)->addDays(rand(1, 3));

            DB::table('grs')->where('id', $gr->id)->update([
                'status' => 'delivered',
                'delivered_at' => $deliveryDate,
                'status_updated_at' => $deliveryDate,
            ]);
        }

        // Create Freight Memos for delivered GRs (group by challan)
        $deliveredGrs = Gr::where('status', 'delivered')->get();
        $challanItems = ChallanItem::whereIn('gr_no', $deliveredGrs->pluck('gr_no'))->get();
        $challanIds = $challanItems->pluck('challan_id')->unique();
        $challans = challan::whereIn('id', $challanIds)->get();

        foreach ($challans->take(50) as $ch) { // 50 freight memos
            $fmDate = Carbon::parse($ch->challan_date)->addDays(rand(3, 7));
            $truckFreight = rand(5000, 35000);
            $commission = round($truckFreight * rand(5, 15) / 100);
            $loading = rand(200, 1500);
            $unloading = rand(200, 1000);
            $advance = rand(0, 5) > 2 ? rand(2000, 10000) : 0;
            $otherCh = rand(0, 5) > 3 ? rand(100, 500) : 0;
            $balance = $truckFreight - $commission - $loading - $unloading - $advance - $otherCh;

            // FM number (globally unique)
            $lastFm = Freight::withTrashed()->orderByDesc('id')->first();
            $fmNext = 1;
            if ($lastFm && $lastFm->fm_no) {
                $fmNext = ((int) preg_replace('/[^0-9]/', '', $lastFm->fm_no)) + 1;
            }
            $fmNo = str_pad($fmNext, 5, '0', STR_PAD_LEFT);

            DB::table('frieghts')->insert([
                'fm_no' => $fmNo,
                'fm_date' => $fmDate->format('Y-m-d'),
                'memo_no' => '',
                'memo_date' => $fmDate->format('Y-m-d'),
                'from_dest' => $ch->from_dest,
                'to_dest' => $ch->to_dest,
                'truck_no' => $ch->truck_no,
                'truck_freight' => $truckFreight,
                'commission' => $commission,
                'entry_1' => 'Loading / Hamali',
                'entry_1_amount' => $loading,
                'entry_2' => 'Unloading',
                'entry_2_amount' => $unloading,
                'entry_3' => 'Advance / Diesel',
                'entry_3_amount' => $advance,
                'entry_4' => '',
                'entry_4_amount' => 0,
                'total_amount' => $loading + $unloading + $advance,
                'other_charges' => $otherCh,
                'extra' => 0,
                'balance_due' => $balance,
                'note' => 'Trip ' . $ch->from_dest . ' to ' . $ch->to_dest . ' completed.',
                'consignor' => '',
                'consignee' => '',
                'office' => $ch->office,
                'created_by_id' => 1,
                'created_at' => $fmDate,
                'updated_at' => $fmDate,
            ]);

            $freightCount++;
        }

        // Mark some TO-PAY as collected
        $topayGrs = Gr::where('to_pay', 1)->where('topay_collected', 0)
            ->where('status', 'delivered')->take(50)->get();
        foreach ($topayGrs as $gr) {
            DB::table('grs')->where('id', $gr->id)->update([
                'topay_collected' => 1,
                'topay_collected_date' => Carbon::parse($gr->delivered_at ?? now())->addDays(rand(1, 5)),
                'topay_collected_by' => 1,
            ]);
        }

        $this->command->info("✓ Created {$freightCount} Freight Memos");
        $this->command->info("✓ Marked 50 TO-PAY as collected");

        // Summary
        $this->command->info('');
        $this->command->info('═══════════════════════════════════════════');
        $this->command->info('  FULL WORKFLOW SEEDING COMPLETE');
        $this->command->info('═══════════════════════════════════════════');
        $this->command->info("  GRs Created:       {$grCount}");
        $this->command->info("  Gatepasses:        {$gatepassCount}");
        $this->command->info("  Challans:          {$challanCount}");
        $this->command->info("  Freight Memos:     {$freightCount}");
        $this->command->info("  TO-PAY Collected:  50");
        $this->command->info('');
        $this->command->info('  Status Distribution:');
        $this->command->info('    Created:     ' . Gr::where('status', 'created')->count());
        $this->command->info('    Dispatched:  ' . Gr::where('status', 'dispatched')->count());
        $this->command->info('    In Transit:  ' . Gr::where('status', 'in_transit')->count());
        $this->command->info('    Delivered:   ' . Gr::where('status', 'delivered')->count());
        $this->command->info('═══════════════════════════════════════════');
    }
}
