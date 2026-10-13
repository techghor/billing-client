<?php

declare(strict_types=1);

namespace TechGhor\BillingClient\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use TechGhor\BillingClient\Facades\Billing;

class BillingController extends Controller
{
    public function index(Request $request): Response
    {
        $status = Billing::verify();
        $overview = Billing::getOverview();

        return response()->view('techghor-billing::index', [
            'status'   => $status,
            'overview' => $overview,
        ]);
    }
}

