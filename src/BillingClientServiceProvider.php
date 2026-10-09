<?php

declare(strict_types=1);

namespace TechGhor\BillingClient;

use Illuminate\Routing\Router;
use Illuminate\Support\ServiceProvider;
use TechGhor\BillingClient\Commands\SyncLicenseCommand;
use TechGhor\BillingClient\Http\Middleware\CheckBillingLicense;
use TechGhor\BillingClient\Services\BillingService;

class BillingClientServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/billing.php', 'billing');

        $this->app->singleton(BillingService::class, function ($app) {
            return new BillingService((array) $app['config']->get('billing', []));
        });

        $this->app->alias(BillingService::class, 'techghor.billing');
    }

    public function boot(Router $router): void
    {
        $this->loadRoutesFrom(__DIR__ . '/../routes/web.php');
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'techghor-billing');

        $router->aliasMiddleware('techghor.license', CheckBillingLicense::class);

        if ($this->app->runningInConsole()) {
            $this->commands([SyncLicenseCommand::class]);

            $this->publishes([
                __DIR__ . '/../config/billing.php' => config_path('billing.php'),
            ], 'techghor-billing-config');

            $this->publishes([
                __DIR__ . '/../resources/views' => resource_path('views/vendor/techghor-billing'),
            ], 'techghor-billing-views');
        }
    }
}
