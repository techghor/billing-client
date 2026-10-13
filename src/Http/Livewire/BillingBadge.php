<?php

declare(strict_types=1);

namespace TechGhor\BillingClient\Http\Livewire;

use Livewire\Attributes\Lazy;
use Livewire\Component;
use TechGhor\BillingClient\Facades\Billing;

#[Lazy]
class BillingBadge extends Component
{
    public function placeholder(): string
    {
        return '<span></span>';
    }

    public function render()
    {
        $status = Billing::verify();
        $days = $status['days_remaining'] ?? null;
        $valid = (bool) ($status['valid'] ?? true);
        $reason = (string) ($status['reason'] ?? 'active');
        $warningDays = (int) config('billing.warning_days', 14);

        $badge = null;

        if (! $valid || $reason === 'expired_with_dues') {
            $badge = [
                'type'  => 'danger',
                'label' => 'Expired',
            ];
        } elseif ($reason === 'grace_period') {
            $badge = [
                'type'  => 'warning',
                'label' => 'Grace' . ($days !== null ? " ({$days}d)" : ''),
            ];
        } elseif ($days !== null && $days <= $warningDays) {
            $badge = [
                'type'  => $days <= 3 ? 'danger' : 'warning',
                'label' => $days <= 0 ? 'Today' : "{$days}d",
            ];
        }

        return view('techghor-billing::livewire.badge', [
            'badge' => $badge,
        ]);
    }
}
