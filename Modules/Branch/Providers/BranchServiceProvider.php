<?php

namespace Modules\Branch\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Branch\Providers\RouteServiceProvider;

class BranchServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->register(RouteServiceProvider::class);
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        $this->loadMigrationsFrom(module_path('Branch', 'Database/Migrations'));
        $this->loadViewsFrom(module_path('Branch', 'Resources/views'), 'branch');
    }
} 