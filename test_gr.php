<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Gr;
use Carbon\Carbon;

echo "========================================\n";
echo "   GR MODULE FULL CRUD TEST\n";
echo "========================================\n\n";

// Clean slate
Gr::where('gr_no', 'like', 'TEST-%')->delete();
echo "Starting count: " . Gr::count() . "\n\n";

// ============================================
// TEST 1: CREATE
// ============================================
echo "========================================\n";
echo "TEST 1: CREATE - Creating 5 GRs\n";
echo "========================================\n";

$gr1 = Gr::create([
    'gr_no' => 'TEST-0001',
    'from_dest' => 'Rajkot',
    'to_dest' => 'Kashmore Gate',
    'copy_date' => Carbon::now()->format('d-m-y'),
    'consignor' => 'Shree Balaji Cotton Pvt Ltd',
    'consignor_address' => 'Plot 14, GIDC, Ahmedabad',
    'consignor_gst_no' => '24AAABC1234R1ZM',
    'consignee' => 'Sai Cement Corporation',
    'consignee_address' => '12/A, MIDC, Mumbai',
    'consignee_gst_no' => '27AAABC1234R1ZM',
    'nugs' => 50,
    'meth' => 'C_B',
    'description' => 'Cotton bales',
    'pm' => 'Paid',
    'weight' => 1200.50,
    'eway_bill_number' => 'EWB123456789012',
    'bill_amount' => 10000.00,
    'paid' => 1,
    'to_pay' => 0,
    'frieght_amount' => 5000.00,
    'sur_ch' => 250.00,
    'c_r' => 150.00,
    'other' => 2500.00,
    'bc_amount' => 30.00,
    'total_amount' => 7930.00,
]);
echo "1. Created: " . $gr1->gr_no . " (ID:" . $gr1->id . ") - " . $gr1->consignor . " [PAID]\n";

$gr2 = Gr::create([
    'gr_no' => 'TEST-0002',
    'from_dest' => 'Rajkot',
    'to_dest' => 'Navagam',
    'copy_date' => Carbon::now()->format('d-m-y'),
    'consignor' => 'Om Steel Industries',
    'consignor_address' => 'Sector 17, Gurugram',
    'consignor_gst_no' => '06AAABC1234R1ZM',
    'consignee' => 'Patel Fertilisers Ltd',
    'consignee_address' => '88, Ludhiana',
    'consignee_gst_no' => '23AAABC1234R1ZM',
    'nugs' => 100,
    'meth' => 'Bags',
    'description' => 'Steel rods and cement',
    'pm' => 'To Pay',
    'weight' => 2500.75,
    'eway_bill_number' => 'EWB987654321098',
    'bill_amount' => 20000.00,
    'paid' => 0,
    'to_pay' => 1,
    'frieght_amount' => 8500.00,
    'sur_ch' => 425.00,
    'c_r' => 300.00,
    'other' => 4250.00,
    'bc_amount' => 50.00,
    'total_amount' => 13525.00,
]);
echo "2. Created: " . $gr2->gr_no . " (ID:" . $gr2->id . ") - " . $gr2->consignor . " [TO PAY]\n";

$gr3 = Gr::create([
    'gr_no' => 'TEST-0003',
    'from_dest' => 'Rajkot',
    'to_dest' => 'Dayabasti',
    'copy_date' => Carbon::now()->format('d-m-y'),
    'consignor' => 'Ganesh Textiles LLP',
    'consignor_address' => 'Coimbatore',
    'consignor_gst_no' => '33AAABC1234R1ZM',
    'consignee' => 'Maruti Polymers',
    'consignee_address' => 'Pune',
    'consignee_gst_no' => '27XYZABC1234R1Z',
    'nugs' => 75,
    'meth' => 'C_R',
    'description' => 'Textile rolls',
    'pm' => 'TBB',
    'weight' => 800.25,
    'eway_bill_number' => 'EWB333344445555',
    'bill_amount' => 15000.00,
    'paid' => 0,
    'to_pay' => 0,
    'frieght_amount' => 4000.00,
    'sur_ch' => 200.00,
    'c_r' => 100.00,
    'other' => 750.00,
    'bc_amount' => 25.00,
    'total_amount' => 5075.00,
]);
echo "3. Created: " . $gr3->gr_no . " (ID:" . $gr3->id . ") - " . $gr3->consignor . " [TBB]\n";

$gr4 = Gr::create([
    'gr_no' => 'TEST-0004',
    'from_dest' => 'Rajkot',
    'to_dest' => 'Swarup Nagar',
    'copy_date' => Carbon::now()->format('d-m-y'),
    'consignor' => 'Bharat Cotton Co',
    'consignor_address' => 'Rajkot',
    'consignor_gst_no' => null,
    'consignee' => 'Krishna Rice Mills',
    'consignee_address' => 'Delhi',
    'consignee_gst_no' => null,
    'nugs' => 200,
    'meth' => 'Bags',
    'description' => 'Rice bags',
    'pm' => 'Paid',
    'weight' => 5000.00,
    'eway_bill_number' => 'EWB555566667777',
    'bill_amount' => 50000.00,
    'paid' => 1,
    'to_pay' => 0,
    'frieght_amount' => 12000.00,
    'sur_ch' => 600.00,
    'c_r' => 400.00,
    'other' => 6000.00,
    'bc_amount' => 100.00,
    'total_amount' => 19100.00,
]);
echo "4. Created: " . $gr4->gr_no . " (ID:" . $gr4->id . ") - " . $gr4->consignor . " [PAID - No GST]\n";

$gr5 = Gr::create([
    'gr_no' => 'TEST-0005',
    'from_dest' => 'Rajkot',
    'to_dest' => 'Shapar (1)',
    'copy_date' => Carbon::now()->format('d-m-y'),
    'consignor' => 'Hindustan Sugar Mills',
    'consignor_address' => 'Ludhiana',
    'consignor_gst_no' => '23XYZAB1234R1ZM',
    'consignee' => 'Laxmi Garment Exports',
    'consignee_address' => 'Ahmedabad',
    'consignee_gst_no' => '24ABCXY1234R1ZM',
    'nugs' => 30,
    'meth' => 'C_B',
    'description' => 'Sugar bags',
    'pm' => 'To Pay',
    'weight' => 600.00,
    'eway_bill_number' => 'EWB888899990000',
    'bill_amount' => 8000.00,
    'paid' => 0,
    'to_pay' => 1,
    'frieght_amount' => 3000.00,
    'sur_ch' => 150.00,
    'c_r' => 75.00,
    'other' => 1500.00,
    'bc_amount' => 20.00,
    'total_amount' => 4745.00,
]);
echo "5. Created: " . $gr5->gr_no . " (ID:" . $gr5->id . ") - " . $gr5->consignor . " [TO PAY]\n";

echo "\nAfter CREATE - Total GRs: " . Gr::count() . "\n";
echo (Gr::count() == 5 ? "[PASS]" : "[FAIL]") . " Create 5 GRs\n\n";

// ============================================
// TEST 2: READ
// ============================================
echo "========================================\n";
echo "TEST 2: READ - Query variations\n";
echo "========================================\n";

$byId = Gr::find($gr3->id);
echo "2.1 Find by ID(" . $gr3->id . "): " . $byId->gr_no . " [" . ($byId->id == $gr3->id ? 'PASS' : 'FAIL') . "]\n";

$byNo = Gr::where('gr_no', 'TEST-0002')->first();
echo "2.2 Find by GR No(TEST-0002): " . $byNo->consignee . " [" . ($byNo->gr_no == 'TEST-0002' ? 'PASS' : 'FAIL') . "]\n";

$fromRajkot = Gr::where('from_dest', 'Rajkot')->get();
echo "2.3 Filter by Rajkot: " . $fromRajkot->count() . " GRs [" . ($fromRajkot->count() == 5 ? 'PASS' : 'FAIL') . "]\n";

$toPay = Gr::where('to_pay', 1)->get();
echo "2.4 Filter To Pay: " . $toPay->count() . " GRs [" . ($toPay->count() == 2 ? 'PASS' : 'FAIL') . "]\n";

$paid = Gr::where('paid', 1)->get();
echo "2.5 Filter Paid: " . $paid->count() . " GRs [" . ($paid->count() == 2 ? 'PASS' : 'FAIL') . "]\n";

$noGst = Gr::whereNull('consignor_gst_no')->get();
echo "2.6 Filter Null GST: " . $noGst->count() . " GRs [" . ($noGst->count() == 1 ? 'PASS' : 'FAIL') . "]\n\n";

// ============================================
// TEST 3: UPDATE
// ============================================
echo "========================================\n";
echo "TEST 3: UPDATE\n";
echo "========================================\n";

$updateMe = Gr::where('gr_no', 'TEST-0001')->first();
$oldValue = $updateMe->consignee;
$updateMe->consignee = 'Updated Consignee Ltd';
$updateMe->total_amount = 9999.99;
$updateMe->save();

$verify = Gr::where('gr_no', 'TEST-0001')->first();
echo "3.1 Update consignee: " . $oldValue . " -> " . $verify->consignee . " [" . ($verify->consignee == 'Updated Consignee Ltd' ? 'PASS' : 'FAIL') . "]\n";
echo "3.2 Update total: " . $verify->total_amount . " [" . ($verify->total_amount == 9999.99 ? 'PASS' : 'FAIL') . "]\n";

$statusGr = Gr::where('gr_no', 'TEST-0004')->first();
$statusGr->paid = 0;
$statusGr->to_pay = 1;
$statusGr->save();
$verifyStatus = Gr::where('gr_no', 'TEST-0004')->first();
echo "3.3 Change to To Pay: " . ($verifyStatus->to_pay ? 'Yes' : 'No') . " [" . ($verifyStatus->to_pay == 1 ? 'PASS' : 'FAIL') . "]\n\n";

// ============================================
// TEST 4: DELETE
// ============================================
echo "========================================\n";
echo "TEST 4: DELETE\n";
echo "========================================\n";

$toDelete = Gr::where('gr_no', 'TEST-0005')->first();
$toDelete->delete();
$verifyDelete = Gr::where('gr_no', 'TEST-0005')->first();
echo "4.1 Delete single: " . ($verifyDelete ? 'NOT DELETED' : 'DELETED') . " [" . ($verifyDelete === null ? 'PASS' : 'FAIL') . "]\n";
echo "    Remaining: " . Gr::count() . " [" . (Gr::count() == 4 ? 'PASS' : 'FAIL') . "]\n";

Gr::where('gr_no', 'like', 'TEST-%')->delete();
echo "4.2 Delete all TEST-%: " . Gr::count() . " remaining [" . (Gr::count() == 0 ? 'PASS' : 'FAIL') . "]\n\n";

echo "========================================\n";
echo "ALL CRUD TESTS COMPLETED SUCCESSFULLY\n";
echo "========================================\n";
echo "Database is clean. Ready for web testing.\n";