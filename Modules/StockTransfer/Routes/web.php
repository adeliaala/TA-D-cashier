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
    Route::prefix('stock-transfers')->group(function () {
        Route::get('/', [StockTransferController::class, 'index'])->name('stock-transfers.index');
        Route::get('/create', [StockTransferController::class, 'create'])->name('stock-transfers.create');
        Route::post('/', [StockTransferController::class, 'store'])->name('stock-transfers.store');
        Route::get('/{stockTransfer}', [StockTransferController::class, 'show'])->name('stock-transfers.show');
        Route::get('/batches/{productId}/{branchId}', [StockTransferController::class, 'getBatches'])->name('stock-transfers.batches');
    });
});
