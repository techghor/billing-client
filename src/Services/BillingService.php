<?php

declare(strict_types=1);

namespace TechGhor\BillingClient\Services;

use Carbon\Carbon;
use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

class BillingService
{
    protected ClientInterface $http;

    public function __construct(protected array $config = [], ?ClientInterface $http = null)
    {
        $this->http = $http ?? new Client();
    }

    /**
     * Fetch the billing overview (the "data" node of the central response).
     *
     * Returns null when nothing could be retrieved (no key, server down and
     * no previous good response cached). Never throws.
     */
    public function getOverview(bool $forceRefresh = false): ?array
    {
        if ($forceRefresh) {
            $this->clearCache();
        }

        $cached = $this->cacheGet($this->key());
        if (is_array($cached)) {
            return $cached;
        }

        // Recent failure: don't hammer a dead server, serve last known good data.
        if (! $forceRefresh && $this->cacheGet($this->key('failed'))) {
            return $this->staleOverview();
        }

        $data = $this->fetchRemote();

        if ($data !== null) {
            $this->cachePut($this->key(), $data, (int) $this->cfg('cache_ttl', 3600));
            $this->cachePut($this->key('stale'), $data, (int) $this->cfg('stale_ttl', 604800));

            return $data;
        }

        $this->cachePut($this->key('failed'), true, max(1, (int) $this->cfg('failure_backoff', 60)));

        return $this->staleOverview();
    }

    /**
     * Evaluate the license.
     *
     * Result keys: valid, reason, message, currency, customer_name,
     * licence_end_date, grace_ends_at, days_remaining, outstanding,
     * pay_url, checked_at.
     *
     * Reasons: expired_with_dues (invalid), unverified (depends on fail_open),
     * permitted, no_expiry, active, grace_period, expired_no_dues.
     */
    public function verify(): array
    {
        $currency = (string) $this->cfg('currency', 'Tk.');

        $result = [
            'valid'            => true,
            'reason'           => 'active',
            'message'          => 'License is active.',
            'currency'         => $currency,
            'customer_name'    => null,
            'licence_end_date' => null,
            'grace_ends_at'    => null,
            'days_remaining'   => null,
            'outstanding'      => 0.0,
            'pay_url'          => null,
            'checked_at'       => Carbon::now()->toIso8601String(),
        ];

        $data = $this->getOverview();

        if ($data === null) {
            $failOpen = (bool) $this->cfg('fail_open', true);

            return array_merge($result, [
                'valid'   => $failOpen,
                'reason'  => 'unverified',
                'message' => $failOpen
                    ? 'License could not be verified right now; access is temporarily allowed.'
                    : 'License could not be verified. Please contact support.',
            ]);
        }

        $outstanding = (float) ($data['summary']['total_outstanding'] ?? 0);
        $endRaw      = $data['license']['licence_end_date'] ?? null;
        $permitRaw   = $data['license']['permit_date'] ?? null;

        $result = array_merge($result, [
            'customer_name'    => $data['customer']['name'] ?? null,
            'licence_end_date' => $endRaw,
            'outstanding'      => $outstanding,
            'pay_url'          => $this->resolvePayUrl($data),
        ]);

        $now = Carbon::now();

        // A permit date granted by the central server overrides expiry until that day ends.
        $permit = $this->parseDate($permitRaw);
        if ($permit !== null && $now->lessThanOrEqualTo($permit->copy()->endOfDay())) {
            return array_merge($result, [
                'valid'   => true,
                'reason'  => 'permitted',
                'message' => 'Access is permitted until ' . $permit->toDateString() . '.',
            ]);
        }

        $end = $this->parseDate($endRaw);
        if ($end === null) {
            return array_merge($result, [
                'valid'   => true,
                'reason'  => 'no_expiry',
                'message' => 'License has no expiry date.',
            ]);
        }

        $grace    = max(0, (int) $this->cfg('grace_period_days', 0));
        $graceEnd = $end->copy()->endOfDay()->addDays($grace);

        $result['grace_ends_at']  = $graceEnd->toDateString();
        $result['days_remaining'] = (int) floor($now->diffInDays($graceEnd, false));

        if ($now->greaterThan($graceEnd)) {
            if ($outstanding > 0) {
                return array_merge($result, [
                    'valid'   => false,
                    'reason'  => 'expired_with_dues',
                    'message' => sprintf(
                        'Your license expired on %s and an outstanding balance of %s %s remains. Please clear your dues to restore access.',
                        $end->toDateString(),
                        $currency,
                        number_format($outstanding, 2)
                    ),
                ]);
            }

            return array_merge($result, [
                'valid'   => true,
                'reason'  => 'expired_no_dues',
                'message' => 'License period ended on ' . $end->toDateString() . ' but there are no outstanding dues.',
            ]);
        }

        $inGrace = $now->greaterThan($end->copy()->endOfDay());

        return array_merge($result, [
            'valid'   => true,
            'reason'  => $inGrace ? 'grace_period' : 'active',
            'message' => $inGrace
                ? 'License is in its grace period until ' . $graceEnd->toDateString() . '.'
                : 'License is active until ' . $end->toDateString() . '.',
        ]);
    }

    /**
     * Purge every cached license entry.
     */
    public function clearCache(): void
    {
        foreach (['', 'stale', 'failed'] as $suffix) {
            try {
                Cache::forget($this->key($suffix));
            } catch (Throwable $e) {
                $this->log('warning', 'Could not clear billing cache: ' . $e->getMessage());
            }
        }
    }

    /**
     * Call the central server. Returns the "data" node or null on any failure.
     */
    protected function fetchRemote(): ?array
    {
        $apiKey = trim((string) $this->cfg('api_key', ''));

        if ($apiKey === '') {
            $this->log('warning', 'BILLING_API_KEY is not configured; license cannot be verified.');

            return null;
        }

        $url = rtrim((string) $this->cfg('server_url', 'https://billing.techghor.com.bd'), '/')
            . '/' . ltrim((string) $this->cfg('endpoint', '/api/v2/customer/billing-overview'), '/');

        try {
            $response = $this->http->request('GET', $url, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $apiKey,
                    'Accept'        => 'application/json',
                    'User-Agent'    => 'techghor-billing-client/1.0',
                ],
                'timeout'         => (float) $this->cfg('timeout', 8),
                'connect_timeout' => (float) $this->cfg('connect_timeout', 4),
                'http_errors'     => false,
            ]);

            $status = $response->getStatusCode();

            if ($status !== 200) {
                $this->log('error', "Billing server responded with HTTP {$status}.");

                return null;
            }

            $json = json_decode((string) $response->getBody(), true, 512, JSON_THROW_ON_ERROR);

            if (! is_array($json) || empty($json['success']) || ! isset($json['data']) || ! is_array($json['data'])) {
                $this->log('error', 'Billing server returned an unexpected payload.');

                return null;
            }

            return $json['data'];
        } catch (Throwable $e) {
            $this->log('error', 'Billing server request failed: ' . $e->getMessage());

            return null;
        }
    }

    protected function staleOverview(): ?array
    {
        $stale = $this->cacheGet($this->key('stale'));

        return is_array($stale) ? $stale : null;
    }

    protected function resolvePayUrl(array $data): ?string
    {
        $candidates = [
            $data['customer']['public_dues_link'] ?? null,
            $data['recurring_profile']['public_dues_link'] ?? null,
        ];

        foreach ($candidates as $url) {
            if (! is_string($url) || $url === '' || filter_var($url, FILTER_VALIDATE_URL) === false) {
                continue;
            }

            if (in_array(strtolower((string) parse_url($url, PHP_URL_SCHEME)), ['http', 'https'], true)) {
                return $url;
            }
        }

        return null;
    }

    protected function parseDate(mixed $value): ?Carbon
    {
        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        try {
            return Carbon::parse($value);
        } catch (Throwable) {
            return null;
        }
    }

    protected function key(string $suffix = ''): string
    {
        $base = (string) $this->cfg('cache_key', 'techghor_billing_overview');

        return $suffix === '' ? $base : $base . ':' . $suffix;
    }

    protected function cacheGet(string $key): mixed
    {
        try {
            return Cache::get($key);
        } catch (Throwable $e) {
            $this->log('warning', 'Billing cache read failed: ' . $e->getMessage());

            return null;
        }
    }

    protected function cachePut(string $key, mixed $value, int $ttl): void
    {
        if ($ttl <= 0) {
            return;
        }

        try {
            Cache::put($key, $value, $ttl);
        } catch (Throwable $e) {
            $this->log('warning', 'Billing cache write failed: ' . $e->getMessage());
        }
    }

    protected function cfg(string $key, mixed $default = null): mixed
    {
        return $this->config[$key] ?? $default;
    }

    protected function log(string $level, string $message): void
    {
        try {
            Log::{$level}('[techghor/billing-client] ' . $message);
        } catch (Throwable) {
            // Logging must never break the host application.
        }
    }
}
