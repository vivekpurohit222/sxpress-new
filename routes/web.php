<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| SXpress Logistics - Transport Management System
| Permission-based access control using branch_permissions & user_permissions
|
*/

Route::get('/', function () {
    return view('auth.login');
});

Auth::routes(['register' => false]);

// Public tracking portal
Route::get('/track/{gr_no}', [App\Http\Controllers\PublicController::class, 'track'])->name('public.track');

// All authenticated routes
Route::middleware(['auth'])->group(function () {

    Route::get('/dash', [App\Http\Controllers\dash\DashboardController::class, 'index'])->name('dash');

    // ═══════════════════════════════════════════════════════════════════════════
    // OFFICE IMPERSONATION (super_admin only)
    // ═══════════════════════════════════════════════════════════════════════════
    Route::post('/impersonate-office', function (\Illuminate\Http\Request $request) {
        if (!auth()->user()->isSuperAdmin()) abort(403);
        $office = $request->validate(['office' => 'required|exists:branches,branch_name'])['office'];
        session(['impersonating_office' => $office]);
        return redirect()->back();
    })->name('impersonate.office');

    Route::post('/stop-impersonating', function () {
        if (!auth()->user()->isSuperAdmin()) abort(403);
        session()->forget('impersonating_office');
        return redirect()->back();
    })->name('impersonate.stop');

    // ═══════════════════════════════════════════════════════════════════════════
    // SUPER ADMIN ONLY — Settings, Branches, Users, Vehicles, Drivers, etc.
    // ═══════════════════════════════════════════════════════════════════════════
    Route::middleware(['role:SuperAdmin'])->group(function () {
        // Branch CRUD (with manager + permissions)
        Route::get('/branch', [App\Http\Controllers\BranchController::class, 'index'])->name('branch.index');
        Route::get('/branch/create', [App\Http\Controllers\BranchController::class, 'create'])->name('branch.create');
        Route::post('/branch/store', [App\Http\Controllers\BranchController::class, 'store'])->name('branch.store');
        Route::get('/branch/{id}/edit', [App\Http\Controllers\BranchController::class, 'edit'])->name('branch.edit');
        Route::put('/branch/{id}', [App\Http\Controllers\BranchController::class, 'update'])->name('branch.update');
        Route::delete('/branch/{id}', [App\Http\Controllers\BranchController::class, 'destroy'])->name('branch.destroy');
        Route::get('/branch/{id}', [App\Http\Controllers\BranchController::class, 'show'])->name('branch.show');

        // User (Agent) Management
        Route::resource('/users', App\Http\Controllers\UserController::class);
        Route::patch('/users/{id}/toggle-active', [App\Http\Controllers\UserController::class, 'toggleActive'])->name('users.toggle-active');

        // GR Serial Assignment
        Route::get('/serial-assign', [App\Http\Controllers\SuperAdmin\SerialController::class, 'index'])->name('serial.index');
        Route::post('/serial-assign', [App\Http\Controllers\SuperAdmin\SerialController::class, 'assign'])->name('serial.assign');

        // Vehicle
        Route::get('/vehicle', [App\Http\Controllers\VehicleController::class, 'index']);
        Route::get('/vehicle/create', [App\Http\Controllers\VehicleController::class, 'create']);
        Route::post('/vehicle/store', [App\Http\Controllers\VehicleController::class, 'store']);
        Route::get('/vehicle/{id}/edit', [App\Http\Controllers\VehicleController::class, 'edit'])->name('vehicle.edit');
        Route::put('/vehicle/{id}', [App\Http\Controllers\VehicleController::class, 'update'])->name('vehicle.update');
        Route::delete('/vehicle/{id}', [App\Http\Controllers\VehicleController::class, 'destroy'])->name('vehicle.destroy');
        Route::get('/vehicle/{id}', [App\Http\Controllers\VehicleController::class, 'show'])->name('vehicle.show');

        // Truck Driver
        Route::get('/truckdriver', [App\Http\Controllers\TruckdriverController::class, 'index']);
        Route::get('/truckdriver/create', [App\Http\Controllers\TruckdriverController::class, 'create']);
        Route::post('/truckdriver/store', [App\Http\Controllers\TruckdriverController::class, 'store']);
        Route::get('/truckdriver/{id}/edit', [App\Http\Controllers\TruckdriverController::class, 'edit'])->name('truckdriver_edit');
        Route::patch('/truckdriver/{id}/update', [App\Http\Controllers\TruckdriverController::class, 'update'])->name('truckdriver_update');
        Route::delete('/truckdriver/{id}/delete', [App\Http\Controllers\TruckdriverController::class, 'destroy'])->name('truckdriver_destroy');
        Route::get('/truckdriver/{id}/view', [App\Http\Controllers\TruckdriverController::class, 'show'])->name('truckdriver_view');

        // Route
        Route::get('/route', [App\Http\Controllers\RouteController::class, 'index']);
        Route::get('/route/create', [App\Http\Controllers\RouteController::class, 'create']);
        Route::post('/route/store', [App\Http\Controllers\RouteController::class, 'store']);
        Route::get('/route/{id}/edit', [App\Http\Controllers\RouteController::class, 'edit'])->name('route.edit');
        Route::put('/route/{id}', [App\Http\Controllers\RouteController::class, 'update'])->name('route.update');
        Route::delete('/route/{id}', [App\Http\Controllers\RouteController::class, 'destroy'])->name('route.destroy');
        Route::get('/route/{id}', [App\Http\Controllers\RouteController::class, 'show'])->name('route.show');

        // Station
        Route::get('/station', [App\Http\Controllers\StationController::class, 'index']);
        Route::get('/station/create', [App\Http\Controllers\StationController::class, 'create']);
        Route::post('/station/store', [App\Http\Controllers\StationController::class, 'store']);
        Route::get('/station/{id}/edit', [App\Http\Controllers\StationController::class, 'edit'])->name('station.edit');
        Route::put('/station/{id}', [App\Http\Controllers\StationController::class, 'update'])->name('station.update');
        Route::delete('/station/{id}', [App\Http\Controllers\StationController::class, 'destroy'])->name('station.destroy');
        Route::get('/station/{id}', [App\Http\Controllers\StationController::class, 'show'])->name('station.show');

        // Customer, Consignor, Consignee
        Route::get('/customer', [App\Http\Controllers\CustomerController::class, 'index'])->name('customer.index');
        Route::get('/customer/create', [App\Http\Controllers\CustomerController::class, 'create'])->name('customer.create');
        Route::post('/customer/store', [App\Http\Controllers\CustomerController::class, 'store'])->name('customer.store');
        Route::get('/customer/{id}/edit', [App\Http\Controllers\CustomerController::class, 'edit'])->name('customer.edit');
        Route::put('/customer/{id}', [App\Http\Controllers\CustomerController::class, 'update'])->name('customer.update');
        Route::delete('/customer/{id}', [App\Http\Controllers\CustomerController::class, 'destroy'])->name('customer.destroy');
        Route::get('/customer/{id}', [App\Http\Controllers\CustomerController::class, 'show'])->name('customer.show');

        Route::get('/consignor', [App\Http\Controllers\ConsignorController::class, 'index'])->name('consignor.index');
        Route::get('/consignor/create', [App\Http\Controllers\ConsignorController::class, 'create'])->name('consignor.create');
        Route::post('/consignor/store', [App\Http\Controllers\ConsignorController::class, 'store'])->name('consignor.store');
        Route::get('/consignor/{id}/edit', [App\Http\Controllers\ConsignorController::class, 'edit'])->name('consignor.edit');
        Route::put('/consignor/{id}', [App\Http\Controllers\ConsignorController::class, 'update'])->name('consignor.update');
        Route::delete('/consignor/{id}', [App\Http\Controllers\ConsignorController::class, 'destroy'])->name('consignor.destroy');
        Route::get('/consignor/{id}', [App\Http\Controllers\ConsignorController::class, 'show'])->name('consignor.show');

        Route::get('/consignee', [App\Http\Controllers\ConsigneeController::class, 'index'])->name('consignee.index');
        Route::get('/consignee/create', [App\Http\Controllers\ConsigneeController::class, 'create'])->name('consignee.create');
        Route::post('/consignee/store', [App\Http\Controllers\ConsigneeController::class, 'store'])->name('consignee.store');
        Route::get('/consignee/{id}/edit', [App\Http\Controllers\ConsigneeController::class, 'edit'])->name('consignee.edit');
        Route::put('/consignee/{id}', [App\Http\Controllers\ConsigneeController::class, 'update'])->name('consignee.update');
        Route::delete('/consignee/{id}', [App\Http\Controllers\ConsigneeController::class, 'destroy'])->name('consignee.destroy');
        Route::get('/consignee/{id}', [App\Http\Controllers\ConsigneeController::class, 'show'])->name('consignee.show');
    });

    // ═══════════════════════════════════════════════════════════════════════════
    // MODULE ROUTES — protected by permission middleware
    // ═══════════════════════════════════════════════════════════════════════════

    // GR Module
    Route::middleware(['permission:gr'])->group(function () {
        Route::get('/gr', [App\Http\Controllers\dash\GrController::class, 'index'])->name('gr.index');
        Route::get('/gr/create', [App\Http\Controllers\dash\GrController::class, 'create'])->name('gr.create');
        Route::post('/gr/store', [App\Http\Controllers\dash\GrController::class, 'store'])->name('gr.store');
        Route::get('/gr/{id}/edit', [App\Http\Controllers\dash\GrController::class, 'edit'])->name('gr.edit');
        Route::patch('/gr/{id}/update', [App\Http\Controllers\dash\GrController::class, 'update'])->name('gr.update');
        Route::delete('/gr/{id}/delete', [App\Http\Controllers\dash\GrController::class, 'destroy'])->name('gr.destroy');
        Route::get('/gr/{id}/upload-pod', [App\Http\Controllers\dash\GrController::class, 'uploadPodForm']);
        Route::post('/gr/{id}/upload-pod', [App\Http\Controllers\dash\GrController::class, 'uploadPod']);
        Route::post('/gr/{id}/mark-delivered', [App\Http\Controllers\dash\GrController::class, 'markDelivered']);
        Route::post('/gr/{id}/update-delivery-status', [App\Http\Controllers\dash\GrController::class, 'updateDeliveryStatus']);
        Route::post('/gr/{id}/mark-topay-collected', [App\Http\Controllers\dash\GrController::class, 'markTopayCollected']);
        Route::post('/gr/{id}/undo-topay-collected', [App\Http\Controllers\dash\GrController::class, 'undoTopayCollected']);
    });

    // Challan Module
    Route::middleware(['permission:challan'])->group(function () {
        Route::get('/challan', [App\Http\Controllers\dash\ChallanController::class, 'index'])->name('challan.index');
        Route::get('/challan/create', [App\Http\Controllers\dash\ChallanController::class, 'create'])->name('challan.create');
        Route::post('/challan/store', [App\Http\Controllers\dash\ChallanController::class, 'store'])->name('challan.store');
        Route::get('/challan/{id}/getData', [App\Http\Controllers\dash\ChallanController::class, 'getData']);
        Route::get('/challan/{id}/challanfetchdata', [App\Http\Controllers\dash\ChallanController::class, 'challanfetchdata']);
        Route::post('/challan/challanIteams', [App\Http\Controllers\dash\ChallanController::class, 'challanIteamStore']);
        Route::get('/challan/{id}/edit', [App\Http\Controllers\dash\ChallanController::class, 'edit'])->name('challan.edit');
        Route::patch('/challan/{id}/update', [App\Http\Controllers\dash\ChallanController::class, 'update'])->name('challan.update');
        Route::delete('/challan/{id}/delete', [App\Http\Controllers\dash\ChallanController::class, 'destroy'])->name('challan.destroy');
        Route::get('/challan/{id}', [App\Http\Controllers\dash\ChallanController::class, 'show'])->name('challan.show');
        Route::delete('/challan-item/{id}', [App\Http\Controllers\dash\ChallanController::class, 'challandelete'])->name('challan.item.destroy');
    });

    // Freight Memo Module
    Route::middleware(['permission:freight_memo'])->group(function () {
        Route::get('/frieghtmemo', [App\Http\Controllers\dash\FreightController::class, 'index'])->name('frieghtmemo.index');
        Route::get('/frieghtmemo/create', [App\Http\Controllers\dash\FreightController::class, 'create'])->name('frieghtmemo.create');
        Route::post('/frieghtmemo/store', [App\Http\Controllers\dash\FreightController::class, 'store'])->name('frieghtmemo.store');
        Route::get('/frieghtmemo/challan-data/{id}', [App\Http\Controllers\dash\FreightController::class, 'getChallanData'])->name('frieghtmemo.challan-data');
        Route::get('/frieghtmemo/{id}/edit', [App\Http\Controllers\dash\FreightController::class, 'edit'])->name('frieghtmemo.edit');
        Route::patch('/frieghtmemo/{id}/update', [App\Http\Controllers\dash\FreightController::class, 'update'])->name('frieghtmemo.update');
        Route::delete('/frieghtmemo/{id}/delete', [App\Http\Controllers\dash\FreightController::class, 'destroy'])->name('frieghtmemo.destroy');
        Route::get('/frieghtmemo/{id}', [App\Http\Controllers\dash\FreightController::class, 'show'])->name('frieghtmemo.show');
        Route::get('/frieghtmemo/{id}/print', [App\Http\Controllers\dash\FreightController::class, 'print'])->name('frieghtmemo.print');
    });

    // Gate Pass Module
    Route::middleware(['permission:gate_pass'])->group(function () {
        Route::get('/gatepass', [App\Http\Controllers\dash\GatepassController::class, 'index'])->name('gatepass.index');
        Route::get('/gatepass/create', [App\Http\Controllers\dash\GatepassController::class, 'create'])->name('gatepass.create');
        Route::post('/gatepass/store', [App\Http\Controllers\dash\GatepassController::class, 'store'])->name('gatepass.store');
        Route::get('/gatepass/{id}/edit', [App\Http\Controllers\dash\GatepassController::class, 'edit'])->name('gatepass.edit');
        Route::patch('/gatepass/{id}/update', [App\Http\Controllers\dash\GatepassController::class, 'update'])->name('gatepass.update');
        Route::delete('/gatepass/{id}/delete', [App\Http\Controllers\dash\GatepassController::class, 'destroy'])->name('gatepass.destroy');
        Route::get('/gatepass/{id}', [App\Http\Controllers\dash\GatepassController::class, 'show'])->name('gatepass.show');
    });

    // Import Challan Module
    Route::middleware(['permission:import_challan'])->group(function () {
        Route::get('/import-challan', [App\Http\Controllers\dash\ImportChallanController::class, 'index'])->name('import_challan.index');
        Route::post('/import-challan/{id}/import', [App\Http\Controllers\dash\ImportChallanController::class, 'import'])->name('import_challan.import');
    });

    // DDS Module
    Route::middleware(['permission:dds'])->group(function () {
        Route::get('/dds', [App\Http\Controllers\dash\DdsController::class, 'index'])->name('dds.index');
    });

    // ═══════════════════════════════════════════════════════════════════════════
    // ALL AUTHENTICATED — Print, Autocomplete, Reports
    // ═══════════════════════════════════════════════════════════════════════════

    Route::get('/gr/{id}/print', [App\Http\Controllers\dash\GrController::class, 'show'])->name('gr.print');
    Route::get('/gr/{id}/pod', [App\Http\Controllers\dash\GrController::class, 'viewPod'])->name('gr.pod');
    Route::get('/gatepass/{id}/print', [App\Http\Controllers\dash\GatepassController::class, 'print'])->name('gatepass.print');
    Route::get('/challan/{id}/print', [App\Http\Controllers\dash\ChallanController::class, 'print'])->name('challan.print');

    Route::get('/gr/autocomplete', [App\Http\Controllers\dash\GrController::class, 'autocomplete'])->name('gr.autocomplete');
    Route::get('/gr/autocomplete/consignor', [App\Http\Controllers\dash\GrController::class, 'autocompleteConsignor']);
    Route::get('/gr/autocomplete/consignee', [App\Http\Controllers\dash\GrController::class, 'autocompleteConsignee']);
    Route::get('/gr/next-number/{office}', [App\Http\Controllers\dash\GrController::class, 'nextGrNumber']);
    Route::get('/route-rate', [App\Http\Controllers\RouteController::class, 'getRate'])->name('route.rate');

    Route::get('/dash/autocomplete/consignor', [App\Http\Controllers\ConsignorController::class, 'autocomplete'])->name('autocomplete.consignor');
    Route::get('/dash/autocomplete/consignee', [App\Http\Controllers\ConsigneeController::class, 'autocomplete'])->name('autocomplete.consignee');
    Route::get('/dash/autocomplete/gr', [App\Http\Controllers\dash\GrController::class, 'autocomplete'])->name('autocomplete.gr');

    // Reports (super_admin and branch_manager only)
    Route::middleware(['role:SuperAdmin|BranchManager'])->prefix('dash/reports')->name('reports.')->group(function () {
        Route::get('/', [App\Http\Controllers\ReportController::class, 'index'])->name('index');
        Route::get('/gr-register', [App\Http\Controllers\ReportController::class, 'grRegister'])->name('gr_register');
        Route::get('/daily-booking', [App\Http\Controllers\ReportController::class, 'dailyBooking'])->name('daily_booking');
        Route::get('/revenue', [App\Http\Controllers\ReportController::class, 'revenue'])->name('revenue');
        Route::get('/branch-performance', [App\Http\Controllers\ReportController::class, 'branchPerformance'])->name('branch_performance');
        Route::get('/pending-pod', [App\Http\Controllers\ReportController::class, 'pendingPod'])->name('pending_pod');
        Route::get('/pending-delivery', [App\Http\Controllers\ReportController::class, 'pendingDelivery'])->name('pending_delivery');
        Route::get('/pending-topay', [App\Http\Controllers\ReportController::class, 'pendingTopay'])->name('pending_topay');
        Route::get('/freight', [App\Http\Controllers\ReportController::class, 'freightReport'])->name('freight');
        Route::get('/vehicle', [App\Http\Controllers\ReportController::class, 'vehicleReport'])->name('vehicle');
        Route::get('/driver', [App\Http\Controllers\ReportController::class, 'driverReport'])->name('driver');
        Route::get('/print', [App\Http\Controllers\ReportController::class, 'print'])->name('print');
    });
});
