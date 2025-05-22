<?php

namespace Modules\StockTransfer\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Eloquent\Factory;

class StockTransferServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application events.
     */
    public function boot()
    {
        $this->loadTranslationsFrom(module_path('StockTransfer', 'Resources/lang'), 'stocktransfer');
        $this->loadViewsFrom(module_path('StockTransfer', 'Resources/views'), 'stocktransfer');
        $this->loadMigrationsFrom(module_path('StockTransfer', 'Database/Migrations'));
        $this->loadRoutesFrom(module_path('StockTransfer', 'Routes/web.php'));
    }

    /**
     * Register the service provider.
     */
    public function register()
    {
        //
    }
} 