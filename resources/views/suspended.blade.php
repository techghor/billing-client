<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>Service suspended</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-stone-100 text-stone-900 antialiased dark:bg-stone-950 dark:text-stone-100">
<main class="mx-auto flex min-h-screen max-w-xl items-center px-4 py-10">
    <div class="w-full overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-stone-200 dark:bg-stone-900 dark:ring-stone-800">

        <div class="border-b-4 border-amber-500 px-6 pb-6 pt-8 sm:px-8">
            <h1 class="text-2xl font-semibold tracking-tight sm:text-3xl">Service suspended</h1>
            <p class="mt-3 text-base leading-relaxed text-stone-600 dark:text-stone-400">
                {{ $status['message'] }}
            </p>
        </div>

        <div class="space-y-6 px-6 py-6 sm:px-8">

            @if (session('billing_notice'))
                <div role="status" class="rounded-lg bg-amber-50 px-4 py-3 text-sm text-amber-900 ring-1 ring-amber-200 dark:bg-amber-950/40 dark:text-amber-200 dark:ring-amber-900">
                    {{ session('billing_notice') }}
                </div>
            @endif

            <div>
                <p class="text-sm text-stone-500 dark:text-stone-400">Amount due</p>
                <p class="mt-1 text-4xl font-bold tabular-nums text-amber-600 dark:text-amber-400">
                    {{ $status['currency'] }} {{ number_format((float) $status['outstanding'], 2) }}
                </p>
            </div>

            <dl class="grid grid-cols-1 gap-4 text-sm sm:grid-cols-2">
                @if (! empty($status['customer_name']))
                    <div>
                        <dt class="text-stone-500 dark:text-stone-400">Account</dt>
                        <dd class="mt-0.5 font-medium">{{ $status['customer_name'] }}</dd>
                    </div>
                @endif
                @if (! empty($status['licence_end_date']))
                    <div>
                        <dt class="text-stone-500 dark:text-stone-400">License ended</dt>
                        <dd class="mt-0.5 font-medium">{{ $status['licence_end_date'] }}</dd>
                    </div>
                @endif
            </dl>

            <div class="flex flex-col gap-3 sm:flex-row">
                @if (! empty($status['pay_url']))
                    <a href="{{ $status['pay_url'] }}" target="_blank" rel="noopener noreferrer"
                       class="inline-flex flex-1 items-center justify-center rounded-lg bg-stone-900 px-5 py-3 text-sm font-semibold text-white transition hover:bg-stone-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-500 focus-visible:ring-offset-2 dark:bg-amber-500 dark:text-stone-950 dark:hover:bg-amber-400">
                        Pay outstanding dues
                    </a>
                @endif

                <form method="POST" action="{{ route('billing.refresh') }}" class="flex-1">
                    @csrf
                    <button type="submit"
                            class="inline-flex w-full items-center justify-center rounded-lg bg-white px-5 py-3 text-sm font-semibold text-stone-800 ring-1 ring-stone-300 transition hover:bg-stone-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-500 dark:bg-stone-800 dark:text-stone-100 dark:ring-stone-700 dark:hover:bg-stone-700">
                        I've paid, re-check status
                    </button>
                </form>
            </div>

            <p class="text-xs leading-relaxed text-stone-500 dark:text-stone-400">
                After paying, select "I've paid, re-check status". Access returns as soon as the payment is recorded.
            </p>
        </div>
    </div>
</main>
</body>
</html>
