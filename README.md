# techghor/billing-client

Verifies a Laravel project's license/subscription against https://billing.techghor.com.bd.

## Install

```bash
composer require techghor/billing-client
php artisan vendor:publish --tag=techghor-billing-config   # optional
php artisan vendor:publish --tag=techghor-billing-views    # optional
```

`.env`:

```
BILLING_API_KEY=your-customer-or-recurring-group-key
BILLING_GRACE_DAYS=0
```

## Protect routes

```php
// Laravel 11/12 (bootstrap/app.php)
->withMiddleware(function (Middleware $middleware) {
    $middleware->appendToGroup('web', 'techghor.license');
    $middleware->appendToGroup('api', 'techghor.license');
})

// Or per route group
Route::middleware(['auth', 'techghor.license'])->group(...);
```

Laravel 10: add `'techghor.license'` to the `web` / `api` groups in `app/Http/Kernel.php`.

## Usage

```php
use TechGhor\BillingClient\Facades\Billing;

Billing::verify();            // ['valid' => bool, 'reason' => '...', ...]
Billing::getOverview(true);   // force refresh
Billing::clearCache();
```

```bash
php artisan techghor:sync
```
