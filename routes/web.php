<?php
use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\CalendarController;
use App\Http\Controllers\ConsignmentController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DriverController;
use App\Http\Controllers\LocationController;
use App\Http\Controllers\ParamController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\SubconController;
use App\Http\Controllers\TruckController;
use App\Http\Controllers\TruckSummaryController;
use App\Http\Controllers\TruckTypeController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\DriverHolidayController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.submit');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
Route::get('/', function () {
    if (Auth::check()) {
        return redirect()->route('dashboard.index'); } else {
        return redirect()->route('login'); } });
Route::middleware(['auth'])->group(function () {
    Route::resource('customer', CustomerController::class);
    Route::resource('dashboard', DashboardController::class);
    Route::resource('driver', DriverController::class);
    Route::resource('subcon', SubconController::class);
    Route::resource('truck', TruckController::class);
    Route::resource('activity-log', ActivityLogController::class);
    Route::resource('consignment-order', ConsignmentController::class);
    Route::post('/consignment-order/store-inline', [ConsignmentController::class, 'storeInline'])
    ->name('consignment-order.store-inline');
    Route::post('/consignment-order/bulk-update', [ConsignmentController::class, 'bulkUpdate'])
    ->name('consignment-order.bulk-update');
    Route::post('/consignment-order/{id}/update-inline', [ConsignmentController::class, 'updateInline'])
    ->name('consignment-order.update-inline');
    Route::resource('truck-summary', TruckSummaryController::class);
    Route::resource('user', UserController::class);
    Route::resource('role', RoleController::class);
    Route::resource('unit-param', ParamController::class);
    Route::resource('truck-type', TruckTypeController::class);
    Route::resource('location', LocationController::class);
    Route::resource('driver-holidays', DriverHolidayController::class);
    Route::post('/availability/update', [CalendarController::class, 'updateStatus'])->name('availability.updateStatus');
    Route::get('/calendar/cell-details', [CalendarController::class, 'getCellDetails'])->name('calendar.cell-details');
    Route::post('/availability/delete', [CalendarController::class, 'deleteAvailability'])->name('availability.delete');
    Route::delete('/calendar/{truck}/{location}/{date}', [CalendarController::class, 'destroy'])->name('calendar.destroy');
    Route::resource('calendar', CalendarController::class);
    Route::post('/calendar/update/{id}', [CalendarController::class, 'update']);
    Route::get('/archived-consignment-order', [ConsignmentController::class, 'archivedIndex'])->name('archived-consignment-order.index');
    Route::get('/draft-customer', [CustomerController::class, 'draftIndex'])->name('draft-customer.index');
    Route::get('/draft-customer/create', [CustomerController::class, 'draftCreate'])->name('draft-customer.create');
    Route::get('/draft-customer/edit/{draftCustomer}', [CustomerController::class, 'draftEdit'])->name('draft-customer.edit');
    Route::put('/draft-customer/update/{draftCustomer}', [CustomerController::class, 'draftUpdate'])->name('draft-customer.update');
    Route::delete('/draft-customer/destroy/{draftCustomer}', [CustomerController::class, 'draftDestroy'])->name('draft-customer.destroy');
    Route::post('/subcon/sync', [SubconController::class, 'syncFromLygions'])->name('subcon.sync');
    Route::post('/draft-customer/sync', [CustomerController::class, 'syncDraftToCustomer'])->name('draft-customer.sync');
    Route::get('/customers/{name}/locations', [ConsignmentController::class, 'getCustomerLocations'])->name('customers.locations');
});
