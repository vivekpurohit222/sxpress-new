<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;

/**
 * RealisticDataSeeder - Seeds 3 months (April-June 2025) of realistic
 * transport company data for SXpress Logistics across all 7 branches.
 *
 * Run: php artisan db:seed --class=RealisticDataSeeder
 */
class RealisticDataSeeder extends Seeder
{
    // ─── Configuration ───────────────────────────────────────────────
    const BRANCHES = ['Rajkot', 'Navagam', 'Shapar (1)', 'Shapar (2)', 'Dayabasti', 'Kashmore Gate', 'Swarup Nagar'];
    const GST_RATE = 5;
    const COMPANY_STATE = 'Gujarat';

    // Dynamic date range — set in run() to the current financial year's last 3 months
    private string $startDate;
    private string $endDate;

    // Account codes from Chart of Accounts
    const CASH_ACCOUNT_CODE = '1101';
    const FREIGHT_INCOME_CODE = '3001';
    const ACCOUNTS_RECEIVABLE_CODE = '1201';

    private array $customerIds = [];
    private array $vehicleIds = [];
    private array $driverIds = [];
    private array $grIds = [];
    private array $accountMap = []; // code => id

    public function run(): void
    {
        // Compute current financial year start (April 1) and seed up to today
        $today = Carbon::today();
        $fyStart = $today->month >= 4
            ? Carbon::create($today->year, 4, 1)
            : Carbon::create($today->year - 1, 4, 1);

        // Seed the last ~3 months within the current FY (but not before FY start)
        $start = $today->copy()->subMonths(3)->startOfMonth();
        if ($start->lt($fyStart)) {
            $start = $fyStart->copy();
        }

        $this->startDate = $start->format('Y-m-d');
        $this->endDate = $today->format('Y-m-d');

        $this->command->info('🚛 Starting SXpress Realistic Data Seeder...');
        $this->command->info("Period: {$this->startDate} – {$this->endDate} (current financial year)");
        $this->command->newLine();

        try {
            // Temporarily disable strict SQL mode to allow missing columns
            DB::statement("SET SESSION sql_mode = ''");

            $this->clearExistingData();
            $this->loadAccountMap();
            $this->seedCustomers();
            $this->seedVehicles();
            $this->seedDrivers();
            $this->seedGRs();
            $this->seedGatepasses();
            $this->seedChallans();
            $this->seedFreightMemos();
            $this->seedExpenses();
            $this->seedOutstanding();

            $this->command->newLine();
            $this->command->info('✅ Seeding complete!');
        } catch (\Exception $e) {
            $this->command->error('❌ Seeding failed: ' . $e->getMessage());
            throw $e;
        }
    }

    private function clearExistingData(): void
    {
        $this->command->info('Clearing existing data...');

        Schema::disableForeignKeyConstraints();

        DB::table('gst_entries')->truncate();
        DB::table('ledger_entries')->truncate();
        DB::table('vouchers')->truncate();
        DB::table('outstanding')->truncate();
        DB::table('expenses')->truncate();
        DB::table('challan_items')->truncate();
        DB::table('challans')->truncate();
        if (Schema::hasTable('gatepass_gr')) {
            DB::table('gatepass_gr')->truncate();
        }
        DB::table('gatepasses')->truncate();
        DB::table('frieghts')->truncate();
        DB::table('grs')->truncate();
        DB::table('customers')->truncate();
        DB::table('vehicles')->truncate();
        DB::table('truckdrivers')->truncate();

        Schema::enableForeignKeyConstraints();

        $this->command->info('  ✓ All transactional data cleared');
    }

    private function loadAccountMap(): void
    {
        $this->accountMap = DB::table('accounts')
            ->whereNotNull('code')
            ->pluck('id', 'code')
            ->toArray();
    }

    private function seedCustomers(): void
    {
        $this->command->info('Seeding customers...');

        $customers = [
            ['customer_name' => 'Shree Ganesh Industries', 'customer_code' => 'CUST001', 'customer_type' => 'company', 'gst_no' => '24AABCS1234A1Z5', 'billing_city' => 'Rajkot', 'billing_state' => 'Gujarat', 'phone' => '9825012345'],
            ['customer_name' => 'Patel Engineering Works', 'customer_code' => 'CUST002', 'customer_type' => 'company', 'gst_no' => '24AABCP5678B2Z3', 'billing_city' => 'Rajkot', 'billing_state' => 'Gujarat', 'phone' => '9898765432'],
            ['customer_name' => 'Gujarat Ceramics Pvt Ltd', 'customer_code' => 'CUST003', 'customer_type' => 'company', 'gst_no' => '24AABCG9012C3Z1', 'billing_city' => 'Navagam', 'billing_state' => 'Gujarat', 'phone' => '9427654321'],
            ['customer_name' => 'Rajkot Steel Traders', 'customer_code' => 'CUST004', 'customer_type' => 'party', 'gst_no' => '24AABCR3456D4Z9', 'billing_city' => 'Rajkot', 'billing_state' => 'Gujarat', 'phone' => '9825678901'],
            ['customer_name' => 'Bharat Auto Parts', 'customer_code' => 'CUST005', 'customer_type' => 'company', 'gst_no' => '24AABCB7890E5Z7', 'billing_city' => 'Shapar (1)', 'billing_state' => 'Gujarat', 'phone' => '9879012345'],
            ['customer_name' => 'Saurashtra Cotton Mills', 'customer_code' => 'CUST006', 'customer_type' => 'company', 'gst_no' => '24AABCS2345F6Z5', 'billing_city' => 'Shapar (2)', 'billing_state' => 'Gujarat', 'phone' => '9825234567'],
            ['customer_name' => 'Om Chemicals Ltd', 'customer_code' => 'CUST007', 'customer_type' => 'company', 'gst_no' => '24AABCO6789G7Z3', 'billing_city' => 'Dayabasti', 'billing_state' => 'Gujarat', 'phone' => '9898345678'],
            ['customer_name' => 'Mahavir Traders', 'customer_code' => 'CUST008', 'customer_type' => 'party', 'gst_no' => '24AABCM1234H8Z1', 'billing_city' => 'Kashmore Gate', 'billing_state' => 'Gujarat', 'phone' => '9820456789'],
            ['customer_name' => 'Krishna Warehouse', 'customer_code' => 'CUST009', 'customer_type' => 'company', 'gst_no' => '24AABCK5678I9Z9', 'billing_city' => 'Swarup Nagar', 'billing_state' => 'Gujarat', 'phone' => '9850567890'],
            ['customer_name' => 'Swarup Distribution', 'customer_code' => 'CUST010', 'customer_type' => 'company', 'gst_no' => '24AABCD9012J1Z7', 'billing_city' => 'Swarup Nagar', 'billing_state' => 'Gujarat', 'phone' => '9810678901'],
            ['customer_name' => 'Shapar Textile House', 'customer_code' => 'CUST011', 'customer_type' => 'party', 'gst_no' => '24AABCS3456K2Z5', 'billing_city' => 'Shapar (1)', 'billing_state' => 'Gujarat', 'phone' => '9825789012'],
            ['customer_name' => 'Dayabasti Marble Mart', 'customer_code' => 'CUST012', 'customer_type' => 'party', 'gst_no' => '24AABCJ7890L3Z3', 'billing_city' => 'Dayabasti', 'billing_state' => 'Gujarat', 'phone' => '9414890123'],
            ['customer_name' => 'Navagam Agro Products', 'customer_code' => 'CUST013', 'customer_type' => 'company', 'gst_no' => '24AABCI2345M4Z1', 'billing_city' => 'Navagam', 'billing_state' => 'Gujarat', 'phone' => '9826901234'],
            ['customer_name' => 'Kashmore Food Processing', 'customer_code' => 'CUST014', 'customer_type' => 'company', 'gst_no' => '24AABCN6789N5Z9', 'billing_city' => 'Kashmore Gate', 'billing_state' => 'Gujarat', 'phone' => '9850012345'],
            ['customer_name' => 'Rajkot Port Services', 'customer_code' => 'CUST015', 'customer_type' => 'company', 'gst_no' => '24AABCG1234O6Z7', 'billing_city' => 'Rajkot', 'billing_state' => 'Gujarat', 'phone' => '9427123456'],
            ['customer_name' => 'Navagam Dairy Cooperative', 'customer_code' => 'CUST016', 'customer_type' => 'company', 'gst_no' => '24AABCA5678P7Z5', 'billing_city' => 'Navagam', 'billing_state' => 'Gujarat', 'phone' => '9898234567'],
            ['customer_name' => 'Shapar Pharma Works', 'customer_code' => 'CUST017', 'customer_type' => 'company', 'gst_no' => '24AABCV9012Q8Z3', 'billing_city' => 'Shapar (2)', 'billing_state' => 'Gujarat', 'phone' => '9879345678'],
            ['customer_name' => 'Dayabasti Groundnut Oil', 'customer_code' => 'CUST018', 'customer_type' => 'party', 'gst_no' => '24AABCJ3456R9Z1', 'billing_city' => 'Dayabasti', 'billing_state' => 'Gujarat', 'phone' => '9825456789'],
            ['customer_name' => 'Kashmore Metal Works', 'customer_code' => 'CUST019', 'customer_type' => 'company', 'gst_no' => '24AABCT7890S1Z9', 'billing_city' => 'Kashmore Gate', 'billing_state' => 'Gujarat', 'phone' => '9820567890'],
            ['customer_name' => 'Swarup Handicrafts', 'customer_code' => 'CUST020', 'customer_type' => 'individual', 'gst_no' => '24AABCJ2345T2Z7', 'billing_city' => 'Swarup Nagar', 'billing_state' => 'Gujarat', 'phone' => '9414678901'],
        ];

        $now = now();
        foreach ($customers as &$c) {
            $c['billing_address'] = 'Industrial Area, ' . $c['billing_city'];
            $c['billing_pincode'] = '3' . rand(60000, 99999);
            $c['status'] = 1;
            $c['is_active'] = 1;
            $c['credit_limit'] = rand(50000, 500000);
            $c['payment_terms'] = rand(1, 4) * 7 . ' days';
            $c['code'] = $c['customer_code'];
            $c['name'] = $c['customer_name'];
            $c['address'] = $c['billing_address'] ?? '';
            $c['created_at'] = $now;
            $c['updated_at'] = $now;
        }

        DB::table('customers')->insert($customers);
        $this->customerIds = DB::table('customers')->pluck('id')->toArray();
        $this->command->info('  ✓ 20 customers seeded');
    }

    private function seedVehicles(): void
    {
        $this->command->info('Seeding vehicles...');

        $vehicles = [];
        $types = ['truck', 'truck', 'truck', 'mini-truck', 'container'];
        $owners = ['Ramesh Patel', 'Suresh Shah', 'Mahesh Joshi', 'Dinesh Solanki', 'Kamlesh Parmar', 'Hitesh Dave', 'Nilesh Bhatt', 'Rajesh Trivedi', 'Yogesh Mehta', 'Jignesh Chauhan', 'Ketan Rana', 'Bhavin Modi', 'Chirag Desai', 'Dharmesh Gajjar', 'Hardik Panchal'];

        for ($i = 1; $i <= 15; $i++) {
            $vehicles[] = [
                'vehicle_number' => 'GJ-03-' . chr(64 + rand(1, 26)) . chr(64 + rand(1, 26)) . '-' . str_pad(rand(1000, 9999), 4, '0', STR_PAD_LEFT),
                'vehicle_type' => $types[array_rand($types)],
                'capacity' => rand(5, 20),
                'capacity_unit' => 'MT',
                'owner_name' => $owners[$i - 1],
                'owner_phone' => '98' . rand(10000000, 99999999),
                'status' => 1,
                'is_own' => $i <= 5 ? 1 : 0,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        DB::table('vehicles')->insert($vehicles);
        $this->vehicleIds = DB::table('vehicles')->pluck('id')->toArray();
        $this->command->info('  ✓ 15 vehicles seeded');
    }

    private function seedDrivers(): void
    {
        $this->command->info('Seeding drivers...');

        $driverNames = ['Ramji Thakor', 'Bhura Rabari', 'Kalu Vaghela', 'Govind Solanki', 'Lakha Jadeja', 'Deva Chavda', 'Magan Parmar', 'Vala Koli', 'Jetha Gohil', 'Kana Mer'];
        $drivers = [];
        $vehicles = DB::table('vehicles')->pluck('vehicle_number', 'id')->toArray();

        for ($i = 0; $i < 10; $i++) {
            $vId = $this->vehicleIds[$i % count($this->vehicleIds)];
            $drivers[] = [
                'driver_name' => $driverNames[$i],
                'truck_no' => $vehicles[$vId] ?? 'GJ-03-XX-' . str_pad($i + 1, 4, '0', STR_PAD_LEFT),
                'license' => 'GJ-' . str_pad(rand(1000, 9999), 4, '0', STR_PAD_LEFT),
                'mobile_no1' => '98' . rand(10000000, 99999999),
                'mobile_no2' => rand(0, 1) ? '97' . rand(10000000, 99999999) : null,
                'driver_address' => 'Village ' . ['Kotharia', 'Paddhari', 'Gondal', 'Jasdan', 'Dhoraji', 'Upleta', 'Jetpur', 'Wankaner', 'Tankara', 'Lodhika'][$i] . ', Rajkot',
                'status' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        DB::table('truckdrivers')->insert($drivers);
        $this->driverIds = DB::table('truckdrivers')->pluck('id')->toArray();
        $this->command->info('  ✓ 10 drivers seeded');
    }

    private function seedGRs(): void
    {
        $this->command->info('Seeding GRs (Goods Receipts)...');

        $customers = DB::table('customers')->get();
        $consignors = $customers->take(10); // First 10 as consignors
        $consignees = $customers->skip(8);  // Last 12 as consignees (some overlap)

        $grs = [];
        $vouchers = [];
        $ledgerEntries = [];
        $gstEntries = [];
        $voucherSeq = 1;
        $grCount = 0;

        $startDate = Carbon::parse($this->startDate);
        $endDate = Carbon::parse($this->endDate);


        $branchPrefixes = DB::table('branches')->pluck('gr_prefix', 'name')->toArray();
        $branchCounters = array_fill_keys(array_values($branchPrefixes), 0);

        $cashAccountId = $this->accountMap[self::CASH_ACCOUNT_CODE] ?? null;
        $incomeAccountId = $this->accountMap[self::FREIGHT_INCOME_CODE] ?? null;
        $receivableAccountId = $this->accountMap[self::ACCOUNTS_RECEIVABLE_CODE] ?? null;

        for ($day = $startDate->copy(); $day->lte($endDate); $day->addDay()) {
            // 3-5 GRs per day (across all branches)
            $dailyCount = rand(3, 5);

            for ($i = 0; $i < $dailyCount; $i++) {
                $grCount++;
                $branch = self::BRANCHES[array_rand(self::BRANCHES)];
                $prefix = $branchPrefixes[$branch] ?? strtoupper(substr($branch, 0, 2));
                $branchCounters[$prefix] = ($branchCounters[$prefix] ?? 0) + 1;

                $consignor = $consignors->random();
                $consignee = $consignees->random();

                $fromCity = $branch; // from_dest = the office that creates the GR
                // to_dest = a DIFFERENT branch (destination)
                $toCity = self::BRANCHES[array_rand(self::BRANCHES)];
                while ($toCity === $fromCity) {
                    $toCity = self::BRANCHES[array_rand(self::BRANCHES)];
                }
                $toState = self::COMPANY_STATE; // All branches are in Gujarat

                $nugs = rand(1, 40);
                $weight = rand(50, 4000);
                // Realistic freight billing: rate per kg + base haulage charge.
                // Kept comfortably above operating cost so the company books a profit.
                $freightAmount = round($weight * rand(10, 18) / 10, 2) + rand(2500, 6500);
                $surCh = rand(0, 3) > 0 ? rand(50, 300) : 0;
                $cr = rand(0, 4) > 0 ? rand(20, 200) : 0;
                $other = rand(0, 5) > 0 ? rand(10, 150) : 0;
                $totalAmount = round($freightAmount + $surCh + $cr + $other, 2);

                $isPaid = rand(1, 10) <= 7; // 70% paid
                $isToPay = !$isPaid;
                $topayCollected = $isToPay && rand(1, 10) <= 4; // 40% of to-pay collected

                // GST calculation — all intra-state (Gujarat to Gujarat)
                $isIntraState = true;
                $gstType = 'cgst_sgst';
                $gstTotal = round($freightAmount * self::GST_RATE / 100, 2);
                $cgst = round($gstTotal / 2, 2);
                $sgst = $gstTotal - $cgst;
                $igst = 0;

                $grNo = $prefix . '-' . str_pad($branchCounters[$prefix], 5, '0', STR_PAD_LEFT);

                $status = 'booked';
                $daysDiff = $day->diffInDays(Carbon::parse($this->endDate));
                if ($daysDiff > 30) $status = 'delivered';
                elseif ($daysDiff > 15) $status = rand(0, 1) ? 'delivered' : 'dispatched';
                else $status = rand(0, 2) == 0 ? 'booked' : (rand(0, 1) ? 'dispatched' : 'delivered');

                $grData = [
                    'gr_no' => $grNo,
                    'from_dest' => $fromCity,
                    'to_dest' => $toCity,
                    'copy_date' => $day->format('Y-m-d'),
                    'consignor' => $consignor->customer_name,
                    'consignor_address' => $consignor->billing_address ?? 'Industrial Area, ' . $consignor->billing_city,
                    'consignor_gst_no' => $consignor->gst_no,
                    'consignee' => $consignee->customer_name,
                    'consignee_address' => $consignee->billing_address ?? 'Industrial Area, ' . $consignee->billing_city,
                    'consignee_gst_no' => $consignee->gst_no,
                    'consignor_id' => $consignor->id,
                    'consignee_id' => $consignee->id,
                    'nugs' => $nugs,
                    'weight' => $weight,
                    'meth' => ['Cardboard', 'Loose', 'Bundle', 'Drum', 'Bag'][array_rand(['Cardboard', 'Loose', 'Bundle', 'Drum', 'Bag'])],
                    'description' => ['General Goods', 'Machine Parts', 'Textiles', 'Chemicals', 'Food Items', 'Electronics', 'Tiles', 'Steel Bars', 'Cotton Bales', 'Auto Parts'][array_rand(['General Goods', 'Machine Parts', 'Textiles', 'Chemicals', 'Food Items', 'Electronics', 'Tiles', 'Steel Bars', 'Cotton Bales', 'Auto Parts'])],
                    'frieght_amount' => $freightAmount,
                    'sur_ch' => $surCh,
                    'c_r' => $cr,
                    'other' => $other,
                    'total_amount' => $totalAmount,
                    'paid' => $isPaid ? 1 : 0,
                    'to_pay' => $isToPay ? 1 : 0,
                    'topay_collected' => $topayCollected ? 1 : 0,
                    'topay_collected_date' => $topayCollected ? $day->copy()->addDays(rand(5, 20))->format('Y-m-d') : null,
                    'office' => $branch,
                    'status' => $status,
                    'delivery_status' => $status === 'delivered' ? 'delivered' : ($status === 'dispatched' ? 'in_transit' : 'pending'),
                    'gst_rate' => self::GST_RATE,
                    'gst_type' => $gstType,
                    'cgst_amount' => $cgst,
                    'sgst_amount' => $sgst,
                    'igst_amount' => $igst,
                    'gst_total' => $gstTotal,
                    'created_by_id' => 1,
                    'created_at' => $day->format('Y-m-d H:i:s'),
                    'updated_at' => $day->format('Y-m-d H:i:s'),
                ];

                $grs[] = $grData;

                // Create accounting voucher + ledger entries
                if ($cashAccountId && $incomeAccountId) {
                    $voucherNo = 'RV-' . substr($prefix, 0, 2) . '-' . $day->format('ymd') . '-' . str_pad($voucherSeq++, 3, '0', STR_PAD_LEFT);

                    $vouchers[] = [
                        'voucher_no' => $voucherNo,
                        'voucher_type' => 'receipt',
                        'voucher_date' => $day->format('Y-m-d'),
                        'narration' => "Freight Income - GR {$grNo} | {$consignor->customer_name} to {$consignee->customer_name}",
                        'total_amount' => $totalAmount,
                        'branch' => $branch,
                        'status' => 'approved',
                        'created_by_id' => 1,
                        'approved_by' => 1,
                        'approved_at' => $day->format('Y-m-d H:i:s'),
                        'reference_type' => 'gr',
                        'created_at' => $day->format('Y-m-d H:i:s'),
                        'updated_at' => $day->format('Y-m-d H:i:s'),
                    ];
                }
            }
        }

        // Batch insert GRs
        foreach (array_chunk($grs, 100) as $chunk) {
            DB::table('grs')->insert($chunk);
        }
        $this->grIds = DB::table('grs')->pluck('id', 'gr_no')->toArray();

        // Insert vouchers and ledger entries
        foreach (array_chunk($vouchers, 100) as $chunk) {
            DB::table('vouchers')->insert($chunk);
        }

        // Now create ledger entries for each voucher
        $allVouchers = DB::table('vouchers')->where('reference_type', 'gr')->get();
        $ledgerBatch = [];

        foreach ($allVouchers as $v) {
            $debitAccountId = $isPaid ? $cashAccountId : ($receivableAccountId ?? $cashAccountId);

            $ledgerBatch[] = [
                'voucher_id' => $v->id,
                'account_id' => $cashAccountId,
                'date' => $v->voucher_date,
                'debit' => $v->total_amount,
                'credit' => 0,
                'narration' => $v->narration,
                'branch' => $v->branch,
                'reference_type' => 'gr',
                'created_at' => $v->created_at,
                'updated_at' => $v->updated_at,
            ];
            $ledgerBatch[] = [
                'voucher_id' => $v->id,
                'account_id' => $incomeAccountId,
                'date' => $v->voucher_date,
                'debit' => 0,
                'credit' => $v->total_amount,
                'narration' => $v->narration,
                'branch' => $v->branch,
                'reference_type' => 'gr',
                'created_at' => $v->created_at,
                'updated_at' => $v->updated_at,
            ];
        }

        foreach (array_chunk($ledgerBatch, 200) as $chunk) {
            DB::table('ledger_entries')->insert($chunk);
        }

        // GST entries for GRs
        $allGrs = DB::table('grs')->whereNotNull('gst_rate')->get();
        $gstBatch = [];
        foreach ($allGrs as $gr) {
            $gstBatch[] = [
                'taxable_type' => 'App\\Models\\Gr',
                'taxable_id' => $gr->id,
                'transaction_date' => $gr->copy_date,
                'party_name' => $gr->consignee,
                'party_gst_number' => $gr->consignee_gst_no,
                'gst_rate' => $gr->gst_rate,
                'taxable_value' => $gr->frieght_amount,
                'cgst_amount' => $gr->cgst_amount,
                'sgst_amount' => $gr->sgst_amount,
                'igst_amount' => $gr->igst_amount,
                'total_tax' => $gr->gst_total,
                'tax_direction' => 'output',
                'gst_type' => $gr->gst_type,
                'branch' => $gr->office,
                'hsn_sac_code' => '996511',
                'created_at' => $gr->created_at,
                'updated_at' => $gr->updated_at,
            ];
        }
        foreach (array_chunk($gstBatch, 200) as $chunk) {
            DB::table('gst_entries')->insert($chunk);
        }

        $this->command->info("  ✓ {$grCount} GRs seeded with vouchers, ledger entries, and GST");
    }

    private function seedGatepasses(): void
    {
        $this->command->info('Seeding gatepasses...');

        $dispatchedGrs = DB::table('grs')->whereIn('status', ['dispatched', 'delivered'])->get();
        $gpSeq = 1;
        $gpCount = 0;

        foreach ($dispatchedGrs as $gr) {
            $gpCount++;
            $driverId = $this->driverIds[array_rand($this->driverIds)];
            $vehicleId = $this->vehicleIds[array_rand($this->vehicleIds)];

            $gpId = DB::table('gatepasses')->insertGetId([
                'gp_no' => $gpSeq++,
                'consignor' => $gr->consignor,
                'gp_date' => $gr->copy_date,
                'from_dest' => $gr->from_dest,
                'to_dest' => $gr->to_dest,
                'gr_no' => $gr->gr_no,
                'gr_id' => $gr->id,
                'weight' => $gr->weight,
                'nugs' => $gr->nugs,
                'pm' => 'By Road',
                'frieght_amount' => $gr->frieght_amount,
                'labour_amount' => 0,
                'other' => 0,
                'delivery_charge' => 0,
                'total_amount' => $gr->total_amount,
                'office' => $gr->office,
                'status' => 'released',
                'vehicle_id' => $vehicleId,
                'driver_id' => $driverId,
                'created_by_id' => 1,
                'created_at' => $gr->copy_date . ' 00:00:01',
                'updated_at' => $gr->copy_date . ' 00:00:01',
            ]);

            // Also insert into pivot table
            DB::table('gatepass_gr')->insert([
                'gatepass_id' => $gpId,
                'gr_id' => $gr->id,
                'gr_no' => $gr->gr_no,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $this->command->info("  ✓ {$gpCount} gatepasses seeded");
    }

    private function seedChallans(): void
    {
        $this->command->info('Seeding challans...');

        $dispatchedGrs = DB::table('grs')->whereIn('status', ['dispatched', 'delivered'])->get();
        $challanCount = 0;
        $challanSeq = 1;

        // Group GRs by office and date to batch into challans
        $grouped = $dispatchedGrs->groupBy(function ($gr) {
            return $gr->office . '|' . $gr->copy_date;
        });

        foreach ($grouped as $key => $batch) {
            $challanCount++;
            $firstGr = $batch->first();
            $driverId = $this->driverIds[array_rand($this->driverIds)];
            $vehicleId = $this->vehicleIds[array_rand($this->vehicleIds)];
            $driver = DB::table('truckdrivers')->find($driverId);
            $vehicle = DB::table('vehicles')->find($vehicleId);

            $challanNo = str_pad($challanSeq++, 5, '0', STR_PAD_LEFT);

            $challanId = DB::table('challans')->insertGetId([
                'challan_no' => $challanNo,
                'from_dest' => $firstGr->from_dest,
                'to_dest' => $firstGr->to_dest,
                'challan_date' => $firstGr->copy_date,
                'truck_no' => $vehicle->vehicle_number ?? '',
                'driver_name' => $driver->driver_name ?? '',
                'license' => $driver->license ?? '',
                'owner_name' => $vehicle->owner_name ?? '',
                'challan_total' => $batch->sum('total_amount'),
                'total_weight' => $batch->sum('weight'),
                'office' => $firstGr->office,
                'status' => 'completed',
                'vehicle_id' => $vehicleId,
                'driver_id' => $driverId,
                'created_by_id' => 1,
                'created_at' => $firstGr->copy_date . ' 00:00:01',
                'updated_at' => $firstGr->copy_date . ' 00:00:01',
            ]);

            // Insert challan items
            foreach ($batch as $gr) {
                DB::table('challan_items')->insert([
                    'challan_id' => $challanId,
                    'challan_no' => $challanNo,
                    'gr_no' => $gr->gr_no,
                    'gr_id' => $gr->id,
                    'nugs' => $gr->nugs,
                    'weight' => $gr->weight,
                    'paid' => $gr->paid ? $gr->total_amount : 0,
                    'to_pay' => $gr->to_pay ? $gr->total_amount : 0,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        $this->command->info("  ✓ {$challanCount} challans seeded with items");
    }

    private function seedFreightMemos(): void
    {
        $this->command->info('Seeding freight memos...');

        $fmCount = 0;
        $branchCounters = [];
        $startDate = Carbon::parse($this->startDate);
        $endDate = Carbon::parse($this->endDate);

        for ($day = $startDate->copy(); $day->lte($endDate); $day->addDays(rand(1, 3))) {
            $dailyCount = rand(1, 3);

            for ($i = 0; $i < $dailyCount; $i++) {
                $fmCount++;
                $branch = self::BRANCHES[array_rand(self::BRANCHES)];
                $prefix = strtoupper(substr($branch, 0, 2));
                $branchCounters[$prefix] = ($branchCounters[$prefix] ?? 0) + 1;

                $vehicleId = $this->vehicleIds[array_rand($this->vehicleIds)];
                $vehicle = DB::table('vehicles')->find($vehicleId);
                $truckFreight = rand(3000, 20000);
                $commission = rand(200, 1000);
                $balanceDue = rand(0, 3) > 0 ? rand(1000, $truckFreight) : 0;

                $fmNo = 'FM-' . $prefix . '-' . str_pad($branchCounters[$prefix], 4, '0', STR_PAD_LEFT);

                DB::table('frieghts')->insert([
                    'fm_no' => $fmNo,
                    'fm_date' => $day->format('Y-m-d'),
                    'from_dest' => $branch,
                    'to_dest' => self::BRANCHES[array_rand(self::BRANCHES)],
                    'truck_no' => $vehicle->vehicle_number ?? '',
                    'truck_freight' => $truckFreight,
                    'commission' => $commission,
                    'total_amount' => $truckFreight + $commission,
                    'balance_due' => $balanceDue,
                    'office' => $branch,
                    'created_by_id' => 1,
                    'created_at' => $day->format('Y-m-d H:i:s'),
                    'updated_at' => $day->format('Y-m-d H:i:s'),
                ]);
            }
        }

        $this->command->info("  ✓ {$fmCount} freight memos seeded");
    }

    private function seedExpenses(): void
    {
        $this->command->info('Seeding expenses...');

        $expCount = 0;
        $branchCounters = [];
        $startDate = Carbon::parse($this->startDate);
        $endDate = Carbon::parse($this->endDate);
        $voucherSeq = DB::table('vouchers')->count() + 1;

        $expenseAccountMap = [
            'diesel' => $this->accountMap['4101'] ?? null,
            'driver_salary' => $this->accountMap['4301'] ?? null,
            'repair' => $this->accountMap['4102'] ?? null,
            'tyre' => $this->accountMap['4103'] ?? null,
            'office' => $this->accountMap['4407'] ?? null,
            'branch' => $this->accountMap['4406'] ?? null,
            'misc' => $this->accountMap['4407'] ?? null,
        ];

        $cashAccountId = $this->accountMap[self::CASH_ACCOUNT_CODE] ?? null;

        for ($day = $startDate->copy(); $day->lte($endDate); $day->addDay()) {
            $dailyExpenses = rand(2, 4);

            for ($i = 0; $i < $dailyExpenses; $i++) {
                $expCount++;
                $branch = self::BRANCHES[array_rand(self::BRANCHES)];
                $prefix = strtoupper(substr($branch, 0, 2));
                $branchCounters[$prefix] = ($branchCounters[$prefix] ?? 0) + 1;

                // Weighted type selection
                $rand = rand(1, 100);
                if ($rand <= 40) $type = 'diesel';
                elseif ($rand <= 60) $type = 'driver_salary';
                elseif ($rand <= 75) $type = 'repair';
                elseif ($rand <= 85) $type = 'tyre';
                elseif ($rand <= 95) $type = 'office';
                else $type = 'misc';

                $amount = match($type) {
                    'diesel' => rand(2000, 8000),
                    'driver_salary' => rand(8000, 15000),
                    'repair' => rand(1000, 15000),
                    'tyre' => rand(4000, 18000),
                    'office' => rand(500, 5000),
                    'branch' => rand(1000, 8000),
                    'misc' => rand(200, 3000),
                };

                $vehicleId = in_array($type, ['diesel', 'repair', 'tyre']) ? $this->vehicleIds[array_rand($this->vehicleIds)] : null;
                $driverId = in_array($type, ['diesel', 'driver_salary']) ? $this->driverIds[array_rand($this->driverIds)] : null;

                // GST on ~30% of expenses (repairs, tyres, office)
                $hasGst = in_array($type, ['repair', 'tyre', 'office']) && rand(1, 10) <= 3;
                $gstRate = $hasGst ? 18 : null; // Most services at 18%
                $gstTotal = $hasGst ? round($amount * 18 / 100, 2) : 0;
                $expCgst = $hasGst ? round($gstTotal / 2, 2) : 0;
                $expSgst = $hasGst ? ($gstTotal - $expCgst) : 0;

                $expNo = 'EXP-' . $prefix . '-' . str_pad($branchCounters[$prefix], 4, '0', STR_PAD_LEFT);
                $paidTo = match($type) {
                    'diesel' => ['Indian Oil', 'HP Petrol Pump', 'Bharat Petroleum', 'Reliance Fuel'][array_rand(['Indian Oil', 'HP Petrol Pump', 'Bharat Petroleum', 'Reliance Fuel'])],
                    'driver_salary' => DB::table('truckdrivers')->find($driverId)?->driver_name ?? 'Driver',
                    'repair' => ['Shree Auto Garage', 'Patel Motor Works', 'Highway Service Centre'][array_rand(['Shree Auto Garage', 'Patel Motor Works', 'Highway Service Centre'])],
                    'tyre' => ['MRF Tyre Shop', 'Apollo Tyres Dealer', 'JK Tyre Centre'][array_rand(['MRF Tyre Shop', 'Apollo Tyres Dealer', 'JK Tyre Centre'])],
                    'office' => ['Stationery Shop', 'Electricity Board', 'Water Supply'][array_rand(['Stationery Shop', 'Electricity Board', 'Water Supply'])],
                    default => 'Miscellaneous',
                };

                $expenseId = DB::table('expenses')->insertGetId([
                    'expense_no' => $expNo,
                    'expense_date' => $day->format('Y-m-d'),
                    'expense_type' => $type,
                    'description' => ucfirst($type) . ' expense - ' . $paidTo,
                    'amount' => $amount,
                    'paid_to' => $paidTo,
                    'account_id' => $expenseAccountMap[$type] ?? ($this->accountMap['4407'] ?? 1),
                    'paid_from_account_id' => $cashAccountId ?? 1,
                    'vehicle_id' => $vehicleId,
                    'driver_id' => $driverId,
                    'branch' => $branch,
                    'status' => 'approved',
                    'approved_by' => 1,
                    'approved_at' => $day->format('Y-m-d H:i:s'),
                    'created_by_id' => 1,
                    'gst_rate' => $gstRate,
                    'gst_type' => $hasGst ? 'cgst_sgst' : null,
                    'cgst_amount' => $expCgst,
                    'sgst_amount' => $expSgst,
                    'igst_amount' => 0,
                    'gst_total' => $gstTotal,
                    'created_at' => $day->format('Y-m-d H:i:s'),
                    'updated_at' => $day->format('Y-m-d H:i:s'),
                ]);

                // Create expense voucher + ledger entries
                if ($cashAccountId && isset($expenseAccountMap[$type])) {
                    $voucherNo = 'PV-' . substr($prefix, 0, 2) . '-' . $day->format('ymd') . '-' . str_pad($voucherSeq++, 3, '0', STR_PAD_LEFT);

                    $vId = DB::table('vouchers')->insertGetId([
                        'voucher_no' => $voucherNo,
                        'voucher_type' => 'payment',
                        'voucher_date' => $day->format('Y-m-d'),
                        'narration' => ucfirst($type) . " - {$paidTo}",
                        'total_amount' => $amount,
                        'branch' => $branch,
                        'status' => 'approved',
                        'created_by_id' => 1,
                        'approved_by' => 1,
                        'approved_at' => $day->format('Y-m-d H:i:s'),
                        'reference_type' => 'expense',
                        'reference_id' => $expenseId,
                        'created_at' => $day->format('Y-m-d H:i:s'),
                        'updated_at' => $day->format('Y-m-d H:i:s'),
                    ]);

                    DB::table('expenses')->where('id', $expenseId)->update(['voucher_id' => $vId]);

                    DB::table('ledger_entries')->insert([
                        ['voucher_id' => $vId, 'account_id' => $expenseAccountMap[$type], 'date' => $day->format('Y-m-d'), 'debit' => $amount, 'credit' => 0, 'narration' => ucfirst($type) . " - {$paidTo}", 'branch' => $branch, 'reference_type' => 'expense', 'reference_id' => $expenseId, 'created_at' => now(), 'updated_at' => now()],
                        ['voucher_id' => $vId, 'account_id' => $cashAccountId, 'date' => $day->format('Y-m-d'), 'debit' => 0, 'credit' => $amount, 'narration' => ucfirst($type) . " - {$paidTo}", 'branch' => $branch, 'reference_type' => 'expense', 'reference_id' => $expenseId, 'created_at' => now(), 'updated_at' => now()],
                    ]);
                }

                // GST entry for expense (input tax)
                if ($hasGst) {
                    DB::table('gst_entries')->insert([
                        'taxable_type' => 'App\\Models\\Accounting\\Expense',
                        'taxable_id' => $expenseId,
                        'transaction_date' => $day->format('Y-m-d'),
                        'party_name' => $paidTo,
                        'gst_rate' => $gstRate,
                        'taxable_value' => $amount,
                        'cgst_amount' => $expCgst,
                        'sgst_amount' => $expSgst,
                        'igst_amount' => 0,
                        'total_tax' => $gstTotal,
                        'tax_direction' => 'input',
                        'gst_type' => 'cgst_sgst',
                        'branch' => $branch,
                        'hsn_sac_code' => '998719',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        }

        $this->command->info("  ✓ {$expCount} expenses seeded with vouchers and GST");
    }

    private function seedOutstanding(): void
    {
        $this->command->info('Seeding outstanding records...');

        $outCount = 0;

        // TO-PAY GRs not yet collected → receivable
        $topayGrs = DB::table('grs')->where('to_pay', 1)->where('topay_collected', 0)->get();
        foreach ($topayGrs as $gr) {
            $outCount++;
            DB::table('outstanding')->insert([
                'party_type' => 'consignee',
                'party_name' => $gr->consignee,
                'type' => 'receivable',
                'invoice_ref' => $gr->gr_no,
                'invoice_date' => $gr->copy_date,
                'total_amount' => $gr->total_amount,
                'paid_amount' => 0,
                'pending_amount' => $gr->total_amount,
                'due_date' => Carbon::parse($gr->copy_date)->addDays(30)->format('Y-m-d'),
                'status' => Carbon::parse($gr->copy_date)->addDays(30)->lt(now()) ? 'overdue' : 'pending',
                'branch' => $gr->office,
                'reference_type' => 'gr',
                'reference_id' => $gr->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Freight Memos with balance_due > 0 → payable
        $fms = DB::table('frieghts')->where('balance_due', '>', 0)->get();
        foreach ($fms as $fm) {
            $outCount++;
            DB::table('outstanding')->insert([
                'party_type' => 'truck_owner',
                'party_name' => DB::table('vehicles')->where('id', $fm->truck_id)->value('owner_name') ?? 'Truck Owner',
                'type' => 'payable',
                'invoice_ref' => $fm->fm_no,
                'invoice_date' => $fm->fm_date,
                'total_amount' => $fm->balance_due,
                'paid_amount' => 0,
                'pending_amount' => $fm->balance_due,
                'due_date' => Carbon::parse($fm->fm_date)->addDays(7)->format('Y-m-d'),
                'status' => Carbon::parse($fm->fm_date)->addDays(7)->lt(now()) ? 'overdue' : 'pending',
                'branch' => $fm->office,
                'reference_type' => 'freight_memo',
                'reference_id' => $fm->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $this->command->info("  ✓ {$outCount} outstanding records seeded");
    }
}
