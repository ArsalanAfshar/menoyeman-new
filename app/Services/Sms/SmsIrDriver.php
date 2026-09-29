<?php

declare(strict_types=1);

namespace App\Services\Sms;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * sms.ir verify-template API driver.
 *
 * Uses the "verify" template endpoint (variables are rendered by sms.ir's
 * template, which the admin can manage). Failures are logged and retried
 * once; never throws to the user flow (auth degrades gracefully).
 */
class SmsIrDriver
{
    private const MAX_ATTEMPTS = 2;

    /**
     * @param  array<string, string>  $vars
     */
    public function send(string $phone, string $message, array $vars = []): bool
    {
        $config = config('sms.smsir');

        if (empty($config['api_key']) || empty($config['template_id'])) {
            Log::error('[sms.ir] Missing SMSIR_API_KEY / SMSIR_TEMPLATE_ID configuration.');

            return false;
        }

        $payload = [
            'mobile' => $phone,
            'templateId' => (int) $config['template_id'],
            'parameters' => collect($vars)
                ->map(fn (string $value, string $name) => ['name' => $name, 'value' => $value])
                ->values()
                ->all(),
        ];

        for ($attempt = 1; $attempt <= self::MAX_ATTEMPTS; $attempt++) {
            try {
                $response = Http::withHeaders([
                    'x-api-key' => $config['api_key'],
                    'Accept' => 'application/json',
                ])
                    ->timeout(15)
                    ->post(rtrim((string) $config['base_url'], '/') . '/send/verify', $payload);

                if ($response->successful() && (int) $response->json('status') === 1) {
                    return true;
                }

                Log::warning('[sms.ir] send failed', [
                    'attempt' => $attempt,
                    'status' => $response->status(),
                    'body' => $response->json(),
                ]);
            } catch (\Throwable $e) {
                Log::warning('[sms.ir] send exception', [
                    'attempt' => $attempt,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return false;
    }
}
