@php
    $customer         = $overview['customer'] ?? [];
    $recurringProfile = $overview['recurring_profile'] ?? [];
    $license          = $overview['license'] ?? [];
    $summary          = $overview['summary'] ?? [];
    $authType         = $overview['auth_type'] ?? config('billing.key_type', 'customer');
    $currencySymbol   = $status['currency'] ?? config('billing.currency', 'Tk.');
@endphp

<x-techghor-billing::layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div>
                <h2 class="text-xl font-bold tracking-tight text-slate-900">
                    {{ __('Billing & License') }}
                </h2>
                <p class="text-xs text-slate-500 mt-0.5">Subscription overview, recurring profile, customer details, and invoice history.</p>
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

        {{-- Top 4 Overview Metric Cards --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            {{-- Card 1: License Status --}}
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

            {{-- Card 2: Days Remaining --}}
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
                    Expires on: <span class="font-medium text-slate-700">{{ $license['licence_end_date'] ?? $status['licence_end_date'] ?? 'No expiry' }}</span>
                </p>
            </div>

            {{-- Card 3: Total Billed & Paid --}}
            <div class="bg-white rounded-xl border border-slate-200 p-4 shadow-2xs">
                <div class="flex items-center justify-between text-slate-500 text-xs font-medium">
                    <span>Billed / Paid</span>
                    <span class="p-1 rounded-md bg-emerald-50 text-emerald-600">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                    </span>
                </div>
                <div class="mt-2 flex items-baseline gap-1">
                    <span class="text-xs font-medium text-slate-500">{{ $currencySymbol }}</span>
                    <span class="text-2xl font-bold tabular-nums text-slate-900">
                        {{ number_format((float) ($summary['total_billed'] ?? $summary['total_outstanding'] ?? $status['outstanding']), 2) }}
                    </span>
                </div>
                <p class="mt-1 text-[11px] text-slate-500 flex items-center justify-between">
                    <span>Paid: <span class="font-semibold text-emerald-700">{{ $currencySymbol }} {{ number_format((float) ($summary['total_paid'] ?? 0), 2) }}</span></span>
                    <span>{{ $summary['total_invoices'] ?? count($invoices) }} invoices</span>
                </p>
            </div>

            {{-- Card 4: Outstanding Dues --}}
            <div class="bg-white rounded-xl border border-slate-200 p-4 shadow-2xs">
                <div class="flex items-center justify-between text-slate-500 text-xs font-medium">
                    <span>Outstanding Dues</span>
                    <span class="p-1 rounded-md bg-rose-50 text-rose-600">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                    </span>
                </div>
                <div class="mt-2 flex items-baseline gap-1">
                    <span class="text-xs font-medium text-slate-500">{{ $currencySymbol }}</span>
                    <span class="text-2xl font-bold tabular-nums {{ (float) $status['outstanding'] > 0 ? 'text-rose-600' : 'text-emerald-600' }}">
                        {{ number_format((float) $status['outstanding'], 2) }}
                    </span>
                </div>
                <div class="mt-1 flex items-center justify-between">
                    <span class="text-[11px] text-slate-500">
                        {{ (float) $status['outstanding'] > 0 ? 'Payment pending' : 'Zero balance' }}
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

        {{-- Returned Reference Profile Tables --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
            {{-- Customer Profile Reference Table --}}
            <div class="bg-white rounded-xl border border-slate-200 overflow-hidden shadow-2xs">
                <div class="px-4 py-3 bg-slate-50/80 border-b border-slate-100 flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <svg class="w-4 h-4 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                        <h3 class="text-xs font-bold uppercase tracking-wider text-slate-700">Customer Reference</h3>
                    </div>
                    @if (! empty($customer['id']))
                        <span class="text-[10px] font-semibold bg-white border border-slate-200 text-slate-600 px-2 py-0.5 rounded-full">
                            ID: #{{ $customer['id'] }}
                        </span>
                    @endif
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-100 text-xs">
                        <tbody class="divide-y divide-slate-100 text-slate-700">
                            <tr>
                                <td class="px-4 py-2.5 font-medium text-slate-500 bg-slate-50/40 w-1/3">Full Name</td>
                                <td class="px-4 py-2.5 font-semibold text-slate-900">{{ $customer['name'] ?? $status['customer_name'] ?? '—' }}</td>
                            </tr>
                            <tr>
                                <td class="px-4 py-2.5 font-medium text-slate-500 bg-slate-50/40">Email Address</td>
                                <td class="px-4 py-2.5 text-slate-800">
                                    @if (! empty($customer['email']))
                                        <a href="mailto:{{ $customer['email'] }}" class="text-indigo-600 hover:underline">{{ $customer['email'] }}</a>
                                    @else
                                        <span class="text-slate-400">—</span>
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <td class="px-4 py-2.5 font-medium text-slate-500 bg-slate-50/40">Phone Number</td>
                                <td class="px-4 py-2.5 text-slate-800">{{ $customer['phone'] ?? '—' }}</td>
                            </tr>
                            <tr>
                                <td class="px-4 py-2.5 font-medium text-slate-500 bg-slate-50/40">Company / Org</td>
                                <td class="px-4 py-2.5 text-slate-800">{{ $customer['company'] ?? '—' }}</td>
                            </tr>
                            <tr>
                                <td class="px-4 py-2.5 font-medium text-slate-500 bg-slate-50/40">Customer Portal Link</td>
                                <td class="px-4 py-2.5">
                                    @if (! empty($customer['public_dues_link']))
                                        <a href="{{ $customer['public_dues_link'] }}" target="_blank" rel="noopener noreferrer"
                                           class="inline-flex items-center gap-1 font-medium text-indigo-600 hover:text-indigo-800 hover:underline truncate max-w-xs"
                                           title="{{ $customer['public_dues_link'] }}">
                                            <span>Open Customer Portal</span>
                                            <svg class="w-3 h-3 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                                        </a>
                                    @else
                                        <span class="text-slate-400">—</span>
                                    @endif
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Recurring Profile / Subscription Reference Table --}}
            <div class="bg-white rounded-xl border border-slate-200 overflow-hidden shadow-2xs">
                <div class="px-4 py-3 bg-slate-50/80 border-b border-slate-100 flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <svg class="w-4 h-4 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                        <h3 class="text-xs font-bold uppercase tracking-wider text-slate-700">Recurring Profile & License Ref</h3>
                    </div>
                    @if (! empty($recurringProfile['id']))
                        <span class="text-[10px] font-semibold bg-indigo-50 border border-indigo-200 text-indigo-700 px-2 py-0.5 rounded-full">
                            Profile #{{ $recurringProfile['id'] }}
                        </span>
                    @endif
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-100 text-xs">
                        <tbody class="divide-y divide-slate-100 text-slate-700">
                            <tr>
                                <td class="px-4 py-2.5 font-medium text-slate-500 bg-slate-50/40 w-1/3">Profile Title</td>
                                <td class="px-4 py-2.5 font-semibold text-slate-900">
                                    {{ $recurringProfile['title'] ?? ('Profile #' . ($recurringProfile['id'] ?? '1')) }}
                                </td>
                            </tr>
                            <tr>
                                <td class="px-4 py-2.5 font-medium text-slate-500 bg-slate-50/40">Auth / Plan Type</td>
                                <td class="px-4 py-2.5">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-semibold bg-slate-100 text-slate-700 border border-slate-200 font-mono">
                                        {{ $authType }}
                                    </span>
                                </td>
                            </tr>
                            <tr>
                                <td class="px-4 py-2.5 font-medium text-slate-500 bg-slate-50/40">Recurring Group Key</td>
                                <td class="px-4 py-2.5">
                                    @php
                                        $groupKey = $recurringProfile['recurring_group_key'] ?? config('billing.api_key');
                                    @endphp
                                    @if (! empty($groupKey))
                                        <div class="flex items-center gap-1.5 font-mono text-[11px] text-slate-800">
                                            <span class="truncate max-w-[200px]" title="{{ $groupKey }}">{{ substr($groupKey, 0, 14) }}...{{ substr($groupKey, -6) }}</span>
                                            <button type="button" onclick="navigator.clipboard.writeText('{{ $groupKey }}')"
                                                    class="text-slate-400 hover:text-indigo-600 transition p-0.5" title="Copy full key">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                                            </button>
                                        </div>
                                    @else
                                        <span class="text-slate-400">—</span>
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <td class="px-4 py-2.5 font-medium text-slate-500 bg-slate-50/40">License Expiry Date</td>
                                <td class="px-4 py-2.5 font-semibold text-slate-800">
                                    {{ $license['licence_end_date'] ?? $status['licence_end_date'] ?? 'No Expiry' }}
                                    @if ($license['permit_date'] ?? null)
                                        <span class="text-[10px] text-amber-600 font-normal ml-1">(Permitted until {{ $license['permit_date'] }})</span>
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <td class="px-4 py-2.5 font-medium text-slate-500 bg-slate-50/40">Recurring Pay Link</td>
                                <td class="px-4 py-2.5">
                                    @php
                                        $recurringLink = $recurringProfile['public_dues_link'] ?? $status['pay_url'];
                                    @endphp
                                    @if (! empty($recurringLink))
                                        <a href="{{ $recurringLink }}" target="_blank" rel="noopener noreferrer"
                                           class="inline-flex items-center gap-1 font-semibold text-indigo-600 hover:text-indigo-800 hover:underline truncate max-w-xs"
                                           title="{{ $recurringLink }}">
                                            <span>Open Recurring Dues Portal</span>
                                            <svg class="w-3 h-3 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                                        </a>
                                    @else
                                        <span class="text-slate-400">—</span>
                                    @endif
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- Invoice History DataTable --}}
        <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-2xs">
            <x-techghor-billing::invoices-table :invoices="$invoices ?? ($overview['invoices'] ?? [])" :currency="$currencySymbol" />
        </div>

        {{-- Technical & Client Configuration Bar --}}
        <div class="bg-white rounded-xl border border-slate-200 p-4 shadow-2xs text-xs text-slate-500 flex flex-wrap items-center justify-between gap-3">
            <div class="flex flex-wrap items-center gap-4">
                <div>
                    <span class="font-medium text-slate-400">Server:</span>
                    <span class="font-mono text-slate-700 ml-1">{{ config('billing.server_url') }}</span>
                </div>
                <div>
                    <span class="font-medium text-slate-400">Header:</span>
                    <span class="font-mono text-slate-700 ml-1">
                        @if (config('billing.key_type') === 'invoice')
                            X-Payment-Hash
                        @elseif (config('billing.key_type') === 'recurring')
                            X-Recurring-Group-Key
                        @else
                            X-Customer-Api-Key
                        @endif
                    </span>
                </div>
                <div>
                    <span class="font-medium text-slate-400">Cache:</span>
                    <span class="text-slate-700 ml-1">{{ config('billing.cache_ttl', 3600) }}s</span>
                </div>
            </div>

            <div class="flex items-center gap-2">
                <span>Verified: {{ $status['checked_at'] }}</span>
                <span class="text-slate-300">|</span>
                <a href="{{ url('/') }}" class="text-indigo-600 hover:underline font-medium">Return to Dashboard</a>
            </div>
        </div>
    </div>
</x-techghor-billing::layout>
