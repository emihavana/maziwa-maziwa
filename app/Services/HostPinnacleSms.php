<?php

namespace App\Services;

use App\Models\SmsLog;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class HostPinnacleSms
{
    public function send(string $phone, string $message): bool
    {
        try {
            $response = Http::asForm()
                ->timeout(15)
                ->post(config('services.hostpinnacle.url'), [
                    'userid'         => config('services.hostpinnacle.user_id'),
                    'password'       => config('services.hostpinnacle.password'),
                    'senderid'       => config('services.hostpinnacle.sender_id'),
                    'mobile'         => $this->normalizePhone($phone),
                    'msg'            => $message,
                    'msgType'        => 'text',
                    'sendMethod'     => 'quick',
                    'output'         => 'json',
                    'duplicatecheck' => 'true',
                ]);
        } catch (ConnectionException $e) {
            // Provider down or too slow: no response object exists.
            Log::error('HostPinnacle SMS unreachable', [
                'phone' => $phone,
                'error' => $e->getMessage(),
            ]);
            $this->record($phone, $message, 'failed', $e->getMessage());

            return false;
        }

        $status = strtolower((string) data_get($response->json(), 'response.status'));

        if (! $response->successful() || $status !== 'success') {
            Log::error('HostPinnacle SMS failed', [
                'phone' => $phone,
                'http'  => $response->status(),
                'body'  => $response->body(),
            ]);
            $this->record($phone, $message, 'failed', $response->body());

            return false;
        }

        $this->record($phone, $message, 'sent', $response->body());

        return true;
    }

    /**
     * Save the outcome. A logging problem must never break SMS sending,
     * so any error here is swallowed and written to the log file instead.
     */
    private function record(string $phone, string $message, string $status, ?string $response): void
    {
        try {
            SmsLog::create([
                'phone'    => $phone,
                'message'  => $message,
                'status'   => $status,
                'response' => $response,
            ]);
        } catch (\Throwable $e) {
            Log::warning('Could not write sms_logs: ' . $e->getMessage());
        }
    }

    /** 0712345678 / +254712345678 / 712345678  ->  254712345678 */
    private function normalizePhone(string $phone): string
    {
        $digits = preg_replace('/\D/', '', $phone);

        return match (true) {
            str_starts_with($digits, '254') => $digits,
            str_starts_with($digits, '0')   => '254' . substr($digits, 1),
            default                         => '254' . $digits,
        };
    }
}