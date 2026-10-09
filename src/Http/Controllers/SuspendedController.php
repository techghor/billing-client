<?php

declare(strict_types=1);

namespace TechGhor\BillingClient\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use TechGhor\BillingClient\Facades\Billing;

class SuspendedController extends Controller
{
    public function show(): Response|RedirectResponse
    {
        $status = Billing::verify();

        if ($status['valid']) {
            return redirect(config('billing.redirect_after_valid', '/'));
        }

        return response()->view('techghor-billing::suspended', ['status' => $status]);
    }

    public function refresh(): RedirectResponse
    {
        Billing::clearCache();
        Billing::getOverview(true);

        $status = Billing::verify();

        if ($status['valid']) {
            return redirect(config('billing.redirect_after_valid', '/'))
                ->with('status', 'Your license is active again.');
        }

        return redirect()->route('billing.suspended')
            ->with('billing_notice', 'Status refreshed. Your account still has outstanding dues.');
    }
}
