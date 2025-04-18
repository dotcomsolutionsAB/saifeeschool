<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

use Illuminate\Support\Facades\Route;
use App\Http\Middleware\CheckApiPermission;
use App\Http\Middleware\CheckTokenTimeout;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
        Route::aliasMiddleware('check-api-permission', CheckApiPermission::class);
        Route::aliasMiddleware('check-timeout', CheckTokenTimeout::class);

    }
}
