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

Route::group(['middleware' => 'auth'], function () {
    //Profit Loss Report
    Route::get('/profit-loss-report', 'ReportsController@profitLossReport')
        ->name('profit-loss-report.index');
    //Sales Report
    Route::get('/sales-report', 'ReportsController@salesReport')
        ->name('sales-report.index');
    //Purchases Report
    Route::get('/purchases-report', 'ReportsController@purchasesReport')
        ->name('purchases-report.index');
});
