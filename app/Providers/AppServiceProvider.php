<?php

namespace App\Providers;

use App\Services\WhatsApp\WaMeSender;
use App\Services\WhatsApp\WhatsAppSenderInterface;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(WhatsAppSenderInterface::class, WaMeSender::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
