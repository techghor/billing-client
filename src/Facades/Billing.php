<?php

declare(strict_types=1);

namespace TechGhor\BillingClient\Facades;

use Illuminate\Support\Facades\Facade;
use TechGhor\BillingClient\Services\BillingService;

/**
 * @method static array|null getOverview(bool $forceRefresh = false)
 * @method static array{
 *     valid: bool,
 *     reason: string,
 *     message: string,
 *     currency: string,
 *     customer_name: string|null,
 *     licence_end_date: string|null,
 *     grace_ends_at: string|null,
 *     days_remaining: int|null,
 *     outstanding: float,
 *     pay_url: string|null,
 *     checked_at: string
 * } verify()
 * @method static void clearCache()
 *
 * @see \TechGhor\BillingClient\Services\BillingService
 */
class Billing extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return BillingService::class;
    }
}
