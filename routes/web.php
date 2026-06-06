<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('auth.login');
});
Auth::routes();
Route::get('/dash', [App\Http\Controllers\dash\DashboardController::class,'index'])->name('dash');

Route::group(['prefix'=>'/dash'],function(){
	Route:: get('/',[App\Http\Controllers\dash\DashboardController::class,'index']);
	//copy
	Route:: get('/gr',[App\Http\Controllers\dash\GrController::class,'index']);
	Route:: post('/gr/store',[App\Http\Controllers\dash\GrController::class,'store']);
	Route:: get('/gr/create',[App\Http\Controllers\dash\GrController::class,'create']);
	Route:: get('/gr/{id}/edit',[App\Http\Controllers\dash\GrController::class,'edit'])->name('copies_edit');
	Route:: patch('/gr/{id}/update',[App\Http\Controllers\dash\GrController::class,'update'])->name('copies_update');
	Route:: delete('/gr/{id}/delete',[App\Http\Controllers\dash\GrController::class,'destroy'])->name('copies_destroy');
	Route:: get('/gr/{id}/print',[App\Http\Controllers\dash\GrController::class,'show'])->name('copies_priny');

	Route:: get('/gatepass',[App\Http\Controllers\dash\GatepassController::class,'index']);
	Route:: get('/gatepass/create',[App\Http\Controllers\dash\GatepassController::class,'create']);
	Route:: post('/gatepass/store',[App\Http\Controllers\dash\GatepassController::class,'store']);
	Route:: get('/gatepass/{id}/edit',[App\Http\Controllers\dash\GatepassController::class,'edit'])->name('gatepass_edit');
	Route:: patch('/gatepass/{id}/update',[App\Http\Controllers\dash\GatepassController::class,'update'])->name('gatepass_update');
	Route:: delete('/gatepass/{id}/delete',[App\Http\Controllers\dash\GatepassController::class,'destroy'])->name('gatepass_destroy');
	Route:: get('/gatepass/{id}/print',[App\Http\Controllers\dash\GatepassController::class,'show'])->name('gatepass_priny');


	Route:: get('/truckdriver',[App\Http\Controllers\TruckdriverController::class,'index']);
	Route:: get('/truckdriver/create',[App\Http\Controllers\TruckdriverController::class,'create']);
	Route:: post('/truckdriver/store',[App\Http\Controllers\TruckdriverController::class,'store']);
	Route:: get('/truckdriver/{id}/edit',[App\Http\Controllers\TruckdriverController::class,'edit'])->name('truckdriver_edit');
	Route:: patch('/truckdriver/{id}/update',[App\Http\Controllers\TruckdriverController::class,'update'])->name('truckdriver_update');
	Route:: delete('/truckdriver/{id}/delete',[App\Http\Controllers\TruckdriverController::class,'destroy'])->name('truckdriver_destroy');
	Route:: get('/truckdriver/{id}/view',[App\Http\Controllers\TruckdriverController::class,'show'])->name('truckdriver_view');

	Route:: get('/frieghtmemo',[App\Http\Controllers\dash\FrieghtController::class,'index']);
	Route:: get('/frieghtmemo/create',[App\Http\Controllers\dash\FrieghtController::class,'create']);
	Route:: post('/frieghtmemo/store',[App\Http\Controllers\dash\FrieghtController::class,'store']);
	Route:: get('/frieghtmemo/{id}/edit',[App\Http\Controllers\dash\FrieghtController::class,'edit'])->name('frieght_edit');
	Route:: patch('/frieghtmemo/{id}/update',[App\Http\Controllers\dash\FrieghtController::class,'update'])->name('frieght_update');
	Route:: delete('/frieghtmemo/{id}/delete',[App\Http\Controllers\dash\FrieghtController::class,'destroy'])->name('frieght_destroy');
	Route:: get('/frieghtmemo/{id}/view',[App\Http\Controllers\dash\FrieghtController::class,'show'])->name('frieght_view');


	
	Route:: get('/challan',[App\Http\Controllers\dash\ChallanController::class,'index']);
	Route:: get('/challan/create',[App\Http\Controllers\dash\ChallanController::class,'create']);
	Route:: get('/challan/{id}/getData',[App\Http\Controllers\dash\ChallanController::class,'getData']);
	Route:: get('/challan/{id}/challanfetchdata',[App\Http\Controllers\dash\ChallanController::class,'challanfetchdata']);
	Route:: post('/challan/challanIteams',[App\Http\Controllers\dash\ChallanController::class,'challanIteamStore']);
	Route:: post('/challan/store',[App\Http\Controllers\dash\ChallanController::class,'store']);
	Route:: get('/challan/{id}/edit',[App\Http\Controllers\dash\ChallanController::class,'edit'])->name('challan_edit');
	Route:: get('/challan/edit/{id}',[App\Http\Controllers\dash\ChallanController::class,'challandelete']);
	Route:: patch('/challan/{id}/update',[App\Http\Controllers\dash\ChallanController::class,'update'])->name('challan_update');
	Route:: delete('/challan/{id}/delete',[App\Http\Controllers\dash\ChallanController::class,'destroy'])->name('challan_destroy');
	Route:: get('/challan/{id}/view',[App\Http\Controllers\dash\ChallanController::class,'show'])->name('challan_view');
	// //register
	// Route:: get('/register',[App\Http\Controllers\Admin\RegisterController::class,'index']);
	// Route:: post('/register/store',[App\Http\Controllers\Admin\RegisterController::class,'store']);
	// Route:: get('/register/create',[App\Http\Controllers\Admin\RegisterController::class,'create']);
	// Route:: get('/register/{id}/edit',[App\Http\Controllers\Admin\RegisterController::class,'edit']);
	// Route:: patch('/register/{id}/update',[App\Http\Controllers\Admin\RegisterController::class,'update']);
	// Route:: delete('/register/{id}/delete',[App\Http\Controllers\Admin\RegisterController::class,'destroy']);
	// //Permisssion
	// Route:: get('/permission',[App\Http\Controllers\Admin\PermissionController::class,'index']);
	// Route:: post('/permission/store',[App\Http\Controllers\Admin\PermissionController::class,'store']);
	// Route:: get('/permission/create',[App\Http\Controllers\Admin\PermissionController::class,'create']);
	// Route:: get('/permission/{id}/edit',[App\Http\Controllers\Admin\PermissionController::class,'edit']);
	// Route:: patch('/permission/{id}/update',[App\Http\Controllers\Admin\PermissionController::class,'update']);
	// Route:: delete('/permission/{id}/delete',[App\Http\Controllers\Admin\PermissionController::class,'destroy']);
	// //Role
	// Route:: get('/role',[App\Http\Controllers\Admin\RoleController::class,'index']);
	// Route:: post('/role/store',[App\Http\Controllers\Admin\RoleController::class,'store']);
	// Route:: get('/role/create',[App\Http\Controllers\Admin\RoleController::class,'create']);
	// Route:: get('/role/{id}/edit',[App\Http\Controllers\Admin\RoleController::class,'edit']);
	// Route:: patch('/role/{id}/update',[App\Http\Controllers\Admin\RoleController::class,'update']);
	// Route:: delete('/role/{id}/delete',[App\Http\Controllers\Admin\RoleController::class,'destroy']);

	// Route:: get('/category/create',[CategoryController::class,'create']);
	// Route:: get('/category/ edit',[CategoryController::class,'edit']);
	Route::resource('/users', 'App\Http\Controllers\UserController');

Route::resource('/roles', 'App\Http\Controllers\RoleController');

Route::resource('/permissions', 'App\Http\Controllers\PermissionController');

Route::resource('/posts', 'App\Http\Controllers\PostController');

});