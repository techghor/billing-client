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
BILLING_API_KEY=your-customer-or-recurring-group-key-or-invoice-hash
BILLING_KEY_TYPE=customer   # customer | recurring | invoice
BILLING_GRACE_DAYS=0
BILLING_WARNING_DAYS=14
```

## Protect routes

```php
// Laravel 11/12/13 (bootstrap/app.php)
->withMiddleware(function (Middleware $middleware) {
    $middleware->appendToGroup('web', 'techghor.license');
    $middleware->appendToGroup('api', 'techghor.license');
})

// Or per route group
Route::middleware(['auth', 'techghor.license'])->group(...);
```

Laravel 10: add `'techghor.license'` to the `web` / `api` groups in `app/Http/Kernel.php`.

## Billing Info Module & Menu

The package includes a full Billing & License dashboard at `/billing` (`billing.index`).

### Menu Registration

If your application uses `App\Modules\Core\Services\MenuService`, the menu item is automatically registered during boot:

```php
$menu->register([
    'section' => 'Core System',
    'label'   => 'Billing & License',
    'icon'    => 'receipt',
    'order'   => 95,
    'route'   => 'billing.index',
    'active'  => 'billing.*',
    'badge'   => 'billing.badge',
]);
```

You can customize this in `config/billing.php` under `'menu'`.

### Expiration Notification Banner

Display the warning banner anywhere in your layouts (e.g., `layouts/app.blade.php`):

```blade
<x-techghor-billing::notice />
{{-- Or via include: --}}
@include('techghor-billing::notice')
```

The banner automatically alerts users when the license has expired, is in grace period, or expires within `BILLING_WARNING_DAYS` (default: 14 days), with a days-left countdown and direct payment link.

### Livewire Badge

The sidebar badge `@livewire('billing.badge')` dynamically shows:
- `Expired` (red badge) when expired
- `Grace (Xd)` (amber badge) during grace period
- `Xd left` (amber/red badge) when expiring within warning days
- Hidden when healthy and active

### Invoice History DataTable

The billing overview page includes a full interactive DataTable for invoice history with live search, status filtering, column sorting, pagination, and direct payment / PDF download links.

You can also embed the table component independently anywhere:

```blade
<x-techghor-billing::invoices-table :invoices="$invoices" />
```

## Usage

```php
use TechGhor\BillingClient\Facades\Billing;

Billing::verify();            // ['valid' => bool, 'reason' => '...', ...]
Billing::getOverview(true);   // force refresh
Billing::getInvoices();       // array of invoices from central server
Billing::clearCache();
```

```bash
php artisan techghor:sync
```