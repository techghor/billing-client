<x-techghor-billing::layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div>
                <h2 class="text-xl font-bold tracking-tight text-slate-900">
                    {{ __('Billing & License') }}
                </h2>
                <p class="text-xs text-slate-500 mt-0.5">Subscription status, license validity, grace period, and payment details.</p>
            </div>

            <div class="flex items-center space-x-2">
                @if ($status['valid'])
                    <span class="inline-flex items-center px-2.5 py-1 rounded-md text-xs font-semibold {{ $status['reason'] === 'grace_period' ? 'bg-amber-50 text-amber-700 border border-amber-200' : 'bg-emerald-50 text-emerald-700 border border-emerald-200' }}">
                        <span class="w-1.5 h-1.5 rounded-full {{ $status['reason'] === 'grace_period' ? 'bg-amber-500' : 'bg-emerald-500' }} mr-1.5"></span>
                        {{ $status['reason'] === 'grace_period' ? 'Grace Period' : 'License Active' }}
                    </span>
                @else
                    <span class="inline-flex items-center px-2.5 py-1 rounded-md text-xs font-semibold bg-red-50 text-red-700 border border-red-200">
                        <span class="w-1.5 h-1.5 rounded-full bg-red-500 mr-1.5"></span>
                        Suspended / Expired
                    </span>
                @endif

                <form method="POST" action="{{ route('billing.refresh') }}" class="inline">
                    @csrf
                    <button type="submit"
                            class="inline-flex items-center px-2.5 py-1 rounded-md text-xs font-medium bg-white text-slate-700 border border-slate-300 hover:bg-slate-50 transition shadow-2xs">
                        <svg class="w-3.5 h-3.5 mr-1 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                        </svg>
                        Re-check Status
                    </button>
                </form>
            </div>
        </div>
    </x-slot>

    <div class="py-4 space-y-5 max-w-7xl mx-auto">
        {{-- Notification Alert (Expiration countdown / Dues / Grace banner) --}}
        <x-techghor-billing::notice />

        {{-- Flash messages --}}
        @if (session('status'))
            <div class="rounded-lg bg-emerald-50 border border-emerald-200 p-3 text-xs text-emerald-800" role="status">
                {{ session('status') }}
            </div>
        @endif

        @if (session('billing_notice'))
            <div class="rounded-lg bg-amber-50 border border-amber-200 p-3 text-xs text-amber-900" role="status">
                {{ session('billing_notice') }}
            </div>
        @endif

        {{-- Overview Cards --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            {{-- Status Card --}}
            <div class="bg-white rounded-xl border border-slate-200 p-4 shadow-2xs">
                <div class="flex items-center justify-between text-slate-500 text-xs font-medium">
                    <span>License State</span>
                    <span class="p-1 rounded-md {{ $status['valid'] ? 'bg-emerald-50 text-emerald-600' : 'bg-red-50 text-red-600' }}">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                    </span>
                </div>
                <div class="mt-2 flex items-baseline gap-2">
                    <span class="text-2xl font-bold tracking-tight {{ $status['valid'] ? 'text-slate-900' : 'text-red-600' }}">
                        {{ ucfirst(str_replace('_', ' ', $status['reason'])) }}
                    </span>
                </div>
                <p class="mt-1 text-[11px] text-slate-500 truncate" title="{{ $status['message'] }}">
                    {{ $status['message'] }}
                </p>
            </div>

            {{-- Days Remaining Card --}}
            <div class="bg-white rounded-xl border border-slate-200 p-4 shadow-2xs">
                <div class="flex items-center justify-between text-slate-500 text-xs font-medium">
                    <span>Days Remaining</span>
                    <span class="p-1 rounded-md bg-indigo-50 text-indigo-600">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                    </span>
                </div>
                <div class="mt-2 flex items-baseline gap-2">
                    <span class="text-2xl font-bold tabular-nums {{ ($status['days_remaining'] !== null && $status['days_remaining'] <= 7) ? 'text-amber-600' : 'text-slate-900' }}">
                        {{ $status['days_remaining'] !== null ? $status['days_remaining'] : '—' }}
                    </span>
                    @if ($status['days_remaining'] !== null)
                        <span class="text-xs text-slate-500 font-medium">days left</span>
                    @endif
                </div>
                <p class="mt-1 text-[11px] text-slate-500">
                    Expires on: <span class="font-medium text-slate-700">{{ $status['licence_end_date'] ?? 'No expiry' }}</span>
                </p>
            </div>

            {{-- Grace Period Card --}}
            <div class="bg-white rounded-xl border border-slate-200 p-4 shadow-2xs">
                <div class="flex items-center justify-between text-slate-500 text-xs font-medium">
                    <span>Grace Period Ends</span>
                    <span class="p-1 rounded-md bg-amber-50 text-amber-600">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </span>
                </div>
                <div class="mt-2 flex items-baseline gap-2">
                    <span class="text-xl font-bold text-slate-900">
                        {{ $status['grace_ends_at'] ?? 'None' }}
                    </span>
                </div>
                <p class="mt-1 text-[11px] text-slate-500">
                    Grace buffer: <span class="font-medium text-slate-700">{{ config('billing.grace_period_days', 0) }} days</span>
                </p>
            </div>

            {{-- Outstanding Dues Card --}}
            <div class="bg-white rounded-xl border border-slate-200 p-4 shadow-2xs">
                <div class="flex items-center justify-between text-slate-500 text-xs font-medium">
                    <span>Amount Due</span>
                    <span class="p-1 rounded-md bg-rose-50 text-rose-600">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                    </span>
                </div>
                <div class="mt-2 flex items-baseline gap-1">
                    <span class="text-xs font-medium text-slate-500">{{ $status['currency'] }}</span>
                    <span class="text-2xl font-bold tabular-nums {{ (float) $status['outstanding'] > 0 ? 'text-rose-600' : 'text-emerald-600' }}">
                        {{ number_format((float) $status['outstanding'], 2) }}
                    </span>
                </div>
                <div class="mt-1 flex items-center justify-between">
                    <span class="text-[11px] text-slate-500">
                        {{ (float) $status['outstanding'] > 0 ? 'Payment required' : 'All clear' }}
                    </span>
                    @if (! empty($status['pay_url']))
                        <a href="{{ $status['pay_url'] }}" target="_blank" rel="noopener noreferrer"
                           class="text-[11px] font-semibold text-indigo-600 hover:text-indigo-800 hover:underline">
                            Pay Now &rarr;
                        </a>
                    @endif
                </div>
            </div>
        </div>

        {{-- Details & Subscription Specs --}}
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
            {{-- Account & Client Specs --}}
            <div class="lg:col-span-2 bg-white rounded-xl border border-slate-200 overflow-hidden shadow-2xs">
                <div class="px-5 py-3.5 border-b border-slate-100 flex items-center justify-between">
                    <h3 class="text-xs font-bold uppercase tracking-wider text-slate-600">Account & License Profile</h3>
                    <span class="text-[10px] text-slate-400">Verified at {{ $status['checked_at'] }}</span>
                </div>

                <div class="divide-y divide-slate-100 text-xs">
                    <div class="px-5 py-3 flex flex-col sm:flex-row sm:items-center justify-between gap-1">
                        <span class="text-slate-500">Customer Account</span>
                        <span class="font-semibold text-slate-800">{{ $status['customer_name'] ?? 'Not configured / Unknown' }}</span>
                    </div>

                    <div class="px-5 py-3 flex flex-col sm:flex-row sm:items-center justify-between gap-1">
                        <span class="text-slate-500">Key Type</span>
                        <span class="font-mono text-[11px] bg-slate-100 px-2 py-0.5 rounded text-slate-700">
                            {{ config('billing.key_type', 'customer') }}
                            @if (config('billing.key_type') === 'invoice')
                                (X-Payment-Hash)
                            @elseif (config('billing.key_type') === 'recurring')
                                (X-Recurring-Group-Key)
                            @else
                                (X-Customer-Api-Key)
                            @endif
                        </span>
                    </div>

                    <div class="px-5 py-3 flex flex-col sm:flex-row sm:items-center justify-between gap-1">
                        <span class="text-slate-500">Billing Server</span>
                        <span class="font-mono text-[11px] text-slate-600 truncate max-w-sm">{{ config('billing.server_url') }}</span>
                    </div>

                    <div class="px-5 py-3 flex flex-col sm:flex-row sm:items-center justify-between gap-1">
                        <span class="text-slate-500">Fail Open Strategy</span>
                        <span class="font-medium {{ config('billing.fail_open') ? 'text-emerald-700' : 'text-amber-700' }}">
                            {{ config('billing.fail_open') ? 'Active (Permits access when server unreachable)' : 'Locked (Locks app when unverified)' }}
                        </span>
                    </div>

                    <div class="px-5 py-3 flex flex-col sm:flex-row sm:items-center justify-between gap-1">
                        <span class="text-slate-500">Cache TTL</span>
                        <span class="text-slate-700">{{ config('billing.cache_ttl', 3600) }} seconds</span>
                    </div>
                </div>
            </div>

            {{-- Quick Actions & Payment Portal --}}
            <div class="bg-white rounded-xl border border-slate-200 p-5 space-y-4 shadow-2xs flex flex-col justify-between">
                <div>
                    <h3 class="text-xs font-bold uppercase tracking-wider text-slate-600 mb-3">Subscription Actions</h3>
                    <p class="text-xs text-slate-500 leading-relaxed mb-4">
                        Payments made to TechGhor billing are synced automatically. Use the button below to force an immediate refresh.
                    </p>

                    @if (! empty($status['pay_url']))
                        <div class="rounded-lg bg-indigo-50 border border-indigo-100 p-3 mb-4">
                            <p class="text-xs font-medium text-indigo-900 mb-2">Direct Payment Link Available</p>
                            <a href="{{ $status['pay_url'] }}" target="_blank" rel="noopener noreferrer"
                               class="w-full inline-flex items-center justify-center px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-semibold rounded-lg shadow-xs transition">
                                Pay via Portal
                                <svg class="w-3.5 h-3.5 ml-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                            </a>
                        </div>
                    @endif
                </div>

                <div class="pt-3 border-t border-slate-100 flex flex-col gap-2">
                    <form method="POST" action="{{ route('billing.refresh') }}">
                        @csrf
                        <button type="submit"
                                class="w-full inline-flex items-center justify-center px-4 py-2 border border-slate-300 bg-white hover:bg-slate-50 text-slate-700 text-xs font-semibold rounded-lg transition shadow-2xs">
                            Sync Status Now
                        </button>
                    </form>

                    <a href="{{ url('/') }}"
                       class="w-full inline-flex items-center justify-center px-4 py-2 text-xs font-medium text-slate-500 hover:text-slate-800 transition">
                        Return to Dashboard
                    </a>
                </div>
            </div>
        </div>
    </div>
</x-techghor-billing::layout>

