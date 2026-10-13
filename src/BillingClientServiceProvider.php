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

        if (class_exists(\Illuminate\Support\Facades\Blade::class)) {
            \Illuminate\Support\Facades\Blade::anonymousComponentPath(
                __DIR__ . '/../resources/views/components',
                'techghor-billing'
            );
        }

        $router->aliasMiddleware('techghor.license', CheckBillingLicense::class);

        // Register Livewire badge component if Livewire is present
        if (class_exists(\Livewire\Livewire::class)) {
            \Livewire\Livewire::component('billing.badge', \TechGhor\BillingClient\Http\Livewire\BillingBadge::class);
        }

        // Auto-register into Core MenuService if present in the host application
        $menuClass = 'App\\Modules\\Core\\Services\\MenuService';
        if (config('billing.menu.enabled', true) && class_exists($menuClass) && $this->app->bound($menuClass)) {
            try {
                $menu = $this->app->make($menuClass);
                $menu->register([
                    'section' => config('billing.menu.section', 'Core System'),
                    'label'   => config('billing.menu.label', 'Billing & License'),
                    'icon'    => config('billing.menu.icon', 'receipt'),
                    'order'   => (int) config('billing.menu.order', 95),
                    'route'   => config('billing.menu.route', 'billing.index'),
                    'active'  => config('billing.menu.active', 'billing.*'),
                    'badge'   => config('billing.menu.badge', 'billing.badge'),
                ]);
            } catch (\Throwable) {
                // Failsafe so menu registration never halts application booting
            }
        }

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
