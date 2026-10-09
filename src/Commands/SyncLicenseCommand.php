<?php

declare(strict_types=1);

namespace TechGhor\BillingClient\Commands;

use Illuminate\Console\Command;
use TechGhor\BillingClient\Services\BillingService;

class SyncLicenseCommand extends Command
{
    protected $signature = 'techghor:sync {--cached : Use cached data instead of forcing a fresh sync}';

    protected $description = 'Check the license and subscription status against the TechGhor billing server';

    public function handle(BillingService $billing): int
    {
        $force = ! $this->option('cached');

        $this->info($force ? 'Syncing with billing server...' : 'Reading license status (cache allowed)...');

        $overview = $billing->getOverview($force);

        if ($overview === null) {
            $this->warn('Could not retrieve data from the billing server (check BILLING_API_KEY, network, or logs).');
        }

        $status = $billing->verify();

        $this->table(['Field', 'Value'], [
            ['Customer', $status['customer_name'] ?? '-'],
            ['Status', $status['valid'] ? 'VALID' : 'SUSPENDED'],
            ['Reason', $status['reason']],
            ['Licence end date', $status['licence_end_date'] ?? '-'],
            ['Grace ends', $status['grace_ends_at'] ?? '-'],
            ['Days remaining', $status['days_remaining'] ?? '-'],
            ['Outstanding', $status['currency'] . ' ' . number_format((float) $status['outstanding'], 2)],
            ['Pay link', $status['pay_url'] ?? '-'],
            ['Checked at', $status['checked_at']],
        ]);

        if ($status['valid']) {
            $this->info($status['message']);

            return self::SUCCESS;
        }

        $this->error($status['message']);

        return self::FAILURE;
    }
}
