<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class HostPinnacleSms
{
    public function send(string $phone, string $message): bool
    {
        // asForm(): this API expects form-encoded fields, not a JSON body.
        // timeout(15): don't let a slow provider hang the queue worker forever.
        $response = Http::asForm()
            ->timeout(15)
            ->post(config('services.hostpinnacle.url'), [
                'userid'         => config('services.hostpinnacle.user_id'),
                'password'       => config('services.hostpinnacle.password'),
                'senderid'       => config('services.hostpinnacle.sender_id'),
                // Always send in 2547XXXXXXXX format (see normalizePhone below)
                'mobile'         => $this->normalizePhone($phone),
                'msg'            => $message,
                'msgType'        => 'text',
                'sendMethod'     => 'quick',
                'output'         => 'json',   // ask for a JSON reply so we can read the status
                // Provider-side guard: identical message to the same number is
                // rejected, so a retry cannot double-send.
                'duplicatecheck' => 'true',
            ]);

        // data_get safely digs into the JSON: response.status.
        // Lowercase it so "Success" and "success" both match.
        $status = strtolower((string) data_get($response->json(), 'response.status'));

        // Two things must be true for success: the HTTP call worked (2xx)
        // AND HostPinnacle itself says "success". An HTTP 200 can still
        // carry an error like "insufficient balance" or "invalid sender".
        if (! $response->successful() || $status !== 'success') {
            // Log the whole reply so you can see WHY it failed later.
            Log::error('HostPinnacle SMS failed', [
                'phone' => $phone,
                'http'  => $response->status(),
                'body'  => $response->body(),
            ]);
            return false;   // the job uses this to decide to retry
        }

        return true;
    }

    /** 0712345678 / +254712345678 / 712345678  ->  254712345678 */
    private function normalizePhone(string $phone): string
    {
        // Strip everything that is not a digit (+, spaces, dashes).
        $digits = preg_replace('/\D/', '', $phone);

        return match (true) {
            // Already in international form: leave it.
            str_starts_with($digits, '254') => $digits,
            // Local form 07...: drop the 0, add the country code.
            str_starts_with($digits, '0')   => '254' . substr($digits, 1),
            // Bare 712345678: just add the country code.
            default                         => '254' . $digits,
        };
    }
}