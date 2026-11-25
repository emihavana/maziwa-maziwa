<?php

namespace App\Providers;

use App\Http\Responses\LoginResponse as CustomLoginResponse;
use Filament\Auth\Http\Responses\Contracts\LoginResponse as LoginResponseContract;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
       $this->app->bind(LoginResponseContract::class, CustomLoginResponse::class);
       $this->app->bind(\App\Services\HostPinnacleSms::class, fn () => new class extends \App\Services\HostPinnacleSms {
    public function send(string $phone, string $message): bool
    {
        \Log::info("FAKE SMS to {$phone}: {$message}");
        return true;
    }
});
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
    
    // Tell Laravel: whenever MilkCollection events fire, use this observer.
    \App\Models\MilkCollection::observe(\App\Observers\MilkCollectionObserver::class);
}
    }