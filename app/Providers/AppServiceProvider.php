<?php

namespace App\Providers;

use App\Domain\Payments\Contracts\PaymentGatewayInterface;
use App\Domain\Payments\NullPaymentGateway;
use Illuminate\Http\Middleware\TrustProxies;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(PaymentGatewayInterface::class, NullPaymentGateway::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $proxies = config('app.trusted_proxies');
        if ($proxies) {
            TrustProxies::at($proxies === '*' ? '*' : array_map('trim', explode(',', $proxies)));
        }

        Paginator::defaultView('components.ui.pagination');
        Paginator::defaultSimpleView('components.ui.pagination');
    }
}
