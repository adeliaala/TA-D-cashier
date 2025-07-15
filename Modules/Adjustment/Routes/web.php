<?php

use Modules\Adjustment\Http\Controllers\AdjustmentController;

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

Route::group(['middleware' => 'auth'], function () {
    //Product Adjustment
    Route::resource('adjustments', 'AdjustmentController');
    Route::post('/adjustments/quick', [AdjustmentController::class, 'quickAdjustment'])->name('adjustments.quick');

});
