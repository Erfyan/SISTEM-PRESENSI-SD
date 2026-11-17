<?php

namespace App\Providers;

use App\Services\WhatsApp\WaMeSender;
use App\Services\WhatsApp\WhatsAppSenderInterface;
use Illuminate\Support\Facades\URL;
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
        if (app()->environment('production') || isset($_SERVER['VERCEL']) || isset($_ENV['VERCEL']) || getenv('VERCEL')) {
            URL::forceScheme('https');
        }
    }
}
