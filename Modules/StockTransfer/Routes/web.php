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

use Modules\StockTransfer\Http\Controllers\StockTransferController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth'])->group(function () {
    Route::prefix('stock-transfers')->name('stocktransfers.')->group(function () {
        Route::get('/', [StockTransferController::class, 'index'])->name('index');
        Route::get('/create', [StockTransferController::class, 'create'])->name('create');
        Route::post('/', [StockTransferController::class, 'store'])->name('store');
        Route::get('/{stockTransfer}', [StockTransferController::class, 'show'])->name('show');
        Route::post('/{stockTransfer}/approve', [StockTransferController::class, 'approve'])->name('approve');
        Route::post('/{stockTransfer}/cancel', [StockTransferController::class, 'cancel'])->name('cancel');
        Route::get('/batches/{productId}/{branchId}', [StockTransferController::class, 'getBatches'])->name('batches');
    });
});
