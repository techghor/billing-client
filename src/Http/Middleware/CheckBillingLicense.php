<?php

declare(strict_types=1);

namespace TechGhor\BillingClient\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use TechGhor\BillingClient\Services\BillingService;

class CheckBillingLicense
{
    public function __construct(protected BillingService $billing)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        if (! config('billing.enabled', true) || $this->isExempt($request)) {
            return $next($request);
        }

        $status = $this->billing->verify();

        if ($status['valid']) {
            return $next($request);
        }

        if ($request->expectsJson() || $request->wantsJson() || $request->is('api/*')) {
            return response()->json([
                'success'     => false,
                'error'       => 'license_suspended',
                'reason'      => $status['reason'],
                'message'     => $status['message'],
                'currency'    => $status['currency'],
                'outstanding' => $status['outstanding'],
                'pay_url'     => $status['pay_url'],
            ], 402);
        }

        return redirect()->route('billing.suspended');
    }

    protected function isExempt(Request $request): bool
    {
        $patterns   = (array) config('billing.exempt_routes', []);
        $patterns[] = 'billing.*';

        foreach ($patterns as $pattern) {
            if (! is_string($pattern) || $pattern === '') {
                continue;
            }

            if ($request->routeIs($pattern) || $request->is(ltrim($pattern, '/'))) {
                return true;
            }
        }

        return false;
    }
}
