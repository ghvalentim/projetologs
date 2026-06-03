<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Models\Syslog;
use App\Observers\SyslogObserver;

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
       Syslog::observe(SyslogObserver::class);
    }
}
