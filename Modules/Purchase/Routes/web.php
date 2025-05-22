<?php

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

use Illuminate\Support\Facades\Route;
use Modules\Purchase\Http\Controllers\PurchaseController;
use Modules\Purchase\Http\Livewire\CreatePurchase;

Route::middleware(['web', 'auth'])->group(function () {
    // Purchases
    Route::prefix('purchases')->group(function () {
        Route::get('/', [PurchaseController::class, 'index'])->name('purchases.index');
        Route::get('/create', [PurchaseController::class, 'create'])->name('purchases.create');
        Route::post('/', [PurchaseController::class, 'store'])->name('purchases.store');
        Route::get('/{purchase}', [PurchaseController::class, 'show'])->name('purchases.show');
        Route::get('/{purchase}/edit', [PurchaseController::class, 'edit'])->name('purchases.edit');
        Route::put('/{purchase}', [PurchaseController::class, 'update'])->name('purchases.update');
        Route::delete('/{purchase}', [PurchaseController::class, 'destroy'])->name('purchases.destroy');
        Route::get('/pdf/{id}', [PurchaseController::class, 'pdf'])->name('purchases.pdf');
        Route::get('/stock/{productId}/{branchId}', [PurchaseController::class, 'getStock'])->name('purchases.stock');
    });

    // Purchase Payments - dinonaktifkan karena tabel sudah dihapus
    /*
    Route::prefix('purchase-payments')->group(function () {
        Route::get('/{purchase_id}', 'PurchasePaymentsController@index')->name('purchase-payments.index');
        Route::get('/{purchase_id}/create', 'PurchasePaymentsController@create')->name('purchase-payments.create');
        Route::post('/store', 'PurchasePaymentsController@store')->name('purchase-payments.store');
        Route::get('/{purchase_id}/edit/{purchasePayment}', 'PurchasePaymentsController@edit')->name('purchase-payments.edit');
        Route::patch('/update/{purchasePayment}', 'PurchasePaymentsController@update')->name('purchase-payments.update');
        Route::delete('/destroy/{purchasePayment}', 'PurchasePaymentsController@destroy')->name('purchase-payments.destroy');
    });
    */

    // Livewire Routes
    Route::get('/purchases/create', function () {
        return view('purchase::create');
    })->name('purchases.create');
});
