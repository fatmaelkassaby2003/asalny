<?php

namespace App\Providers;


use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

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
        if($this->app->environment('production')) {
            \Illuminate\Support\Facades\URL::forceScheme('https');
        }

        // Livewire fix for /public/ subdirectory
        \Livewire\Livewire::setUpdateRoute(function ($handle) {
            return Route::post('/public/livewire/update', $handle);
        });

        \Livewire\Livewire::setScriptRoute(function ($handle) {
            return Route::get('/public/livewire/livewire.js', $handle);
        });
    }
}
