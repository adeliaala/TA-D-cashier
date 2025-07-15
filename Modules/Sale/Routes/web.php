<?php

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Log;
use Modules\Sale\Entities\Sale;
use Modules\Sale\Entities\SaleDetail;
use Modules\Sale\Entities\SalePayment;
use Modules\Sale\Entities\SalePaymentMethod;



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

    //POS
    Route::get('/app/pos', 'PosController@index')->name('app.pos.index');
    Route::post('/app/pos', 'PosController@store')->name('app.pos.store');

    // Generate PDF Regular Invoice
Route::get('/sales/pdf/{id}', function ($id) {
    try {
        // Pastikan menggunakan model yang benar
        $sale = \Modules\Sale\Entities\Sale::with(['branch', 'saleDetails'])->findOrFail($id);
        
        // Cek customer
        $customer = null;
        if ($sale->customer_id) {
            $customer = \Modules\People\Entities\Customer::find($sale->customer_id);
        }

        // Gunakan view dengan namespace module
        $pdf = Pdf::loadView('sale::print', [
            'sale' => $sale,
            'customer' => $customer,
        ])->setPaper('a4');

        return $pdf->stream('sale-'. $sale->reference .'.pdf');
        
    } catch (\Exception $e) {
        return response()->json(['error' => 'Error generating PDF: ' . $e->getMessage()], 500);
    }
})->name('sales.pdf');

// Generate PDF POS Invoice - DIPERBAIKI
Route::get('/sales/pos/pdf/{id}', function ($id) {
    try {
        // Pastikan menggunakan model yang benar dari module
        $sale = \Modules\Sale\Entities\Sale::with(['branch', 'saleDetails', 'salePayments'])->findOrFail($id);

        // Cek apakah view tersedia, jika tidak gunakan alternatif
        $viewName = 'sale::print-pos';
        
        // Jika view module tidak ditemukan, coba buat view sederhana
        if (!view()->exists($viewName)) {
            // Buat view inline sebagai fallback
            $html = view()->make('sale::print-pos-inline', [
                'sale' => $sale,
            ])->render();
            
            $pdf = Pdf::loadHTML($html);
        } else {
            $pdf = Pdf::loadView($viewName, [
                'sale' => $sale,
            ]);
        }
        
        $pdf->setPaper('a7', 'portrait')
            ->setOptions([
                'margin-top' => 8,
                'margin-bottom' => 8,
                'margin-left' => 5,
                'margin-right' => 5,
                'enable-local-file-access' => true,
            ]);

        return $pdf->stream('sale-'. $sale->reference .'.pdf');
        
    } catch (\Exception $e) {
        return response()->json(['error' => 'Error generating POS PDF: ' . $e->getMessage()], 500);
    }
})->name('sales.pos.pdf');

// Route alternatif dengan debug
Route::get('/sales/pos/pdf/{id}/debug', function ($id) {
    try {
        $sale = \Modules\Sale\Entities\Sale::with(['branch', 'saleDetails', 'salePayments'])->findOrFail($id);
        
        // Debug info
        return response()->json([
            'sale_id' => $sale->id,
            'reference' => $sale->reference,
            'branch' => $sale->branch->name ?? 'No Branch',
            'items_count' => $sale->saleDetails->count(),
            'view_exists' => view()->exists('sale::print-pos'),
            'view_paths' => config('view.paths'),
        ]);
        
    } catch (\Exception $e) {
        return response()->json(['error' => $e->getMessage()], 500);
    }
});

    //Sales
    Route::resource('sales', 'SaleController');

    //Payments
    Route::prefix('sale-payments')->name('sale-payments.')->group(function () {
        Route::get('/{sale_id}', 'SalePaymentsController@index')->name('index');
        Route::get('/{sale_id}/create', 'SalePaymentsController@create')->name('create');
        Route::post('/store', 'SalePaymentsController@store')->name('store');
        Route::get('/{sale_id}/edit/{salePayment}', 'SalePaymentsController@edit')->name('edit');
        Route::patch('/update/{salePayment}', 'SalePaymentsController@update')->name('update');
        Route::delete('/destroy/{salePayment}', 'SalePaymentsController@destroy')->name('destroy');
    });
});