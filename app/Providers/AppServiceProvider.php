<?php

namespace App\Providers;

use App\Models\IdCardRequest;
use App\Models\TransportSubscription;
use App\Models\TransportSubscriptionRequest;
use App\Policies\IdCardRequestPolicy;
use App\Policies\TransportSubscriptionPolicy;
use App\Policies\TransportSubscriptionRequestPolicy;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
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
        // Register policies
        Gate::policy(TransportSubscriptionRequest::class, TransportSubscriptionRequestPolicy::class);
        Gate::policy(TransportSubscription::class, TransportSubscriptionPolicy::class);
        Gate::policy(IdCardRequest::class, IdCardRequestPolicy::class);

        // SECURITY: Define rate limiters (relaxed for development/testing)
        \Illuminate\Support\Facades\RateLimiter::for('sso', function (\Illuminate\Http\Request $request) {
            return \Illuminate\Cache\RateLimiting\Limit::perMinute(60)->by($request->ip());
        });

        \Illuminate\Support\Facades\RateLimiter::for('uploads', function (\Illuminate\Http\Request $request) {
            return \Illuminate\Cache\RateLimiting\Limit::perMinute(30)->by($request->user()?->id ?: $request->ip());
        });

        \Illuminate\Support\Facades\RateLimiter::for('login', function (\Illuminate\Http\Request $request) {
            return \Illuminate\Cache\RateLimiting\Limit::perMinute(10)->by($request->ip());
        });
    }
}

