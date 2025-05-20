<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProductBatchController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});

// Product Batch Routes
Route::prefix('product-batches')->group(function () {
    Route::post('/', [ProductBatchController::class, 'store']);
    Route::get('/stock/{productId}/{branchId}', [ProductBatchController::class, 'getStock']);
    Route::post('/deduct', [ProductBatchController::class, 'deductStock']);
});
