@php
    $status = \TechGhor\BillingClient\Facades\Billing::verify();
    $warningDays = (int) config('billing.warning_days', 14);
    $days = $status['days_remaining'] ?? null;
    $valid = (bool) ($status['valid'] ?? true);
    $reason = (string) ($status['reason'] ?? 'active');

    $showAlert = false;
    $type = 'warning'; // 'danger' | 'warning'
    $title = '';
    $message = '';

    if (! $valid || $reason === 'expired_with_dues') {
        $showAlert = true;
        $type = 'danger';
        $title = 'License Expired';
        $message = sprintf(
            'Your software license expired on %s. An outstanding balance of %s %s remains.',
            $status['licence_end_date'] ?? 'N/A',
            $status['currency'] ?? 'Tk.',
            number_format((float) ($status['outstanding'] ?? 0), 2)
        );
    } elseif ($reason === 'grace_period') {
        $showAlert = true;
        $type = 'warning';
        $title = 'License Grace Period';
        $message = sprintf(
            'Your software license has expired and is operating within grace period until %s (%s days remaining). Please clear your dues to avoid service interruption.',
            $status['grace_ends_at'] ?? 'N/A',
            $days ?? 0
        );
    } elseif ($days !== null && $days <= $warningDays) {
        $showAlert = true;
        $type = $days <= 3 ? 'danger' : 'warning';
        $title = $days <= 0 ? 'License Expires Today' : "License Expiring Soon ({$days} " . \Illuminate\Support\Str::plural('day', $days) . ' left)';
        $message = sprintf(
            'Your subscription will expire on %s. Please renew before the expiration date to maintain continuous access.',
            $status['licence_end_date'] ?? 'N/A'
        );
    }
@endphp

@if ($showAlert)
    <div x-data="{ dismissed: false }" x-show="!dismissed"
         class="mb-4 rounded-xl border p-4 transition-all shadow-xs {{ $type === 'danger' ? 'bg-red-50 border-red-200 text-red-900 dark:bg-red-950/40 dark:border-red-900/60 dark:text-red-200' : 'bg-amber-50 border-amber-200 text-amber-900 dark:bg-amber-950/40 dark:border-amber-900/60 dark:text-amber-200' }}"
         role="alert">
        <div class="flex items-start justify-between gap-3">
            <div class="flex items-start gap-3">
                <div class="mt-0.5 shrink-0">
                    @if ($type === 'danger')
                        <svg class="h-5 w-5 text-red-600 dark:text-red-400" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.28 7.22a.75.75 0 00-1.06 1.06L8.94 10l-1.72 1.72a.75.75 0 101.06 1.06L10 11.06l1.72 1.72a.75.75 0 101.06-1.06L11.06 10l1.72-1.72a.75.75 0 00-1.06-1.06L10 8.94 8.28 7.22z" clip-rule="evenodd" />
                        </svg>
                    @else
                        <svg class="h-5 w-5 text-amber-600 dark:text-amber-400" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M8.485 2.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.875c.673 1.167-.17 2.625-1.516 2.625H3.72c-1.347 0-2.189-1.458-1.515-2.625L8.485 2.495zM10 5a.75.75 0 01.75.75v3.5a.75.75 0 01-1.5 0v-3.5A.75.75 0 0110 5zm0 9a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd" />
                        </svg>
                    @endif
                </div>

                <div class="space-y-1">
                    <h4 class="text-sm font-semibold tracking-tight">{{ $title }}</h4>
                    <p class="text-xs leading-relaxed opacity-90">{{ $message }}</p>

                    <div class="mt-2.5 flex flex-wrap items-center gap-2 pt-1">
                        @if (! empty($status['pay_url']))
                            <a href="{{ $status['pay_url'] }}" target="_blank" rel="noopener noreferrer"
                               class="inline-flex items-center justify-center rounded-md px-3 py-1.5 text-xs font-semibold shadow-xs transition {{ $type === 'danger' ? 'bg-red-600 hover:bg-red-700 text-white' : 'bg-amber-600 hover:bg-amber-700 text-white' }}">
                                Pay Dues Now
                            </a>
                        @endif

                        @if (\Illuminate\Support\Facades\Route::has('billing.index') && ! request()->routeIs('billing.index'))
                            <a href="{{ route('billing.index') }}"
                               class="inline-flex items-center justify-center rounded-md border px-3 py-1.5 text-xs font-medium transition {{ $type === 'danger' ? 'border-red-300 bg-white/80 hover:bg-white text-red-800 dark:border-red-800 dark:bg-stone-900 dark:text-red-200' : 'border-amber-300 bg-white/80 hover:bg-white text-amber-900 dark:border-amber-800 dark:bg-stone-900 dark:text-amber-200' }}">
                                View Billing Details
                            </a>
                        @endif
                    </div>
                </div>
            </div>

            <button type="button" @click="dismissed = true"
                    class="rounded-md p-1 opacity-60 transition hover:opacity-100 focus:outline-none"
                    aria-label="Dismiss alert">
                <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                    <path d="M6.28 5.22a.75.75 0 00-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 101.06 1.06L10 11.06l3.72 3.72a.75.75 0 101.06-1.06L11.06 10l3.72-3.72a.75.75 0 00-1.06-1.06L10 8.94 6.28 5.22z" />
                </svg>
            </button>
        </div>
    </div>
@endif
