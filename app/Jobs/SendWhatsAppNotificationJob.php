<?php

declare(strict_types=1);

namespace App\Jobs;

use App\DTOs\Setting\OrderSettingsDTO;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class SendWhatsAppNotificationJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function __construct(
        public readonly string $phoneNumber,
        public readonly string $message,
    ) {}

    /**
     * Calculate the number of seconds to wait before retrying the job.
     */
    public function backoff(): array
    {
        return [10, 30, 60];
    }

    public function handle(): void
    {
        $settings = OrderSettingsDTO::load();
        if (! $settings->whatsappEnabled) {
            return;
        }

        $token = $settings->whatsappAccessToken;
        if (empty($token)) {
            return;
        }

        $targetPhone = $this->sanitizePhoneNumber($this->phoneNumber);
        if ($targetPhone === '') {
            Log::warning('SendWhatsAppNotificationJob skipped: invalid or empty phone number', [
                'raw_phone' => $this->phoneNumber,
            ]);

            return;
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => $token,
            ])->timeout(15)->post('https://api.fonnte.com/send', [
                'target' => $targetPhone,
                'message' => $this->message,
            ]);

            if ($response->failed()) {
                $statusCode = $response->status();

                // 4xx errors indicate client/configuration problems (unauthorized token, bad payload, etc.)
                if ($statusCode >= 400 && $statusCode < 500) {
                    Log::error('SendWhatsAppNotificationJob permanent HTTP failure from Fonnte (not retrying)', [
                        'status' => $statusCode,
                        'phone_number' => $targetPhone,
                        'response' => $response->body(),
                    ]);

                    $this->fail(new \Exception("Fonnte API HTTP {$statusCode} error -> ".$response->body()));

                    return;
                }

                // 5xx errors or network issues are transient and should trigger retry backoff
                throw new \Exception("Fonnte API HTTP {$statusCode} server error -> ".$response->body());
            }

            // Fonnte returns 200 OK even on some business failures (e.g. disconnected device, invalid token)
            $data = $response->json();
            if (isset($data['status']) && $data['status'] === false) {
                $reason = (string) ($data['reason'] ?? 'Unknown Fonnte business error');

                Log::warning('SendWhatsAppNotificationJob failed due to Fonnte business logic error (not retrying)', [
                    'reason' => $reason,
                    'phone_number' => $targetPhone,
                    'fonnte_response' => $data,
                ]);

                // Permanent failure: mark job failed immediately without retrying disconnected device/token errors
                $this->fail(new \Exception("Fonnte API error -> {$reason}"));

                return;
            }
        } catch (Throwable $e) {
            if ($this->job?->hasFailed()) {
                return;
            }

            Log::warning('SendWhatsAppNotificationJob transient failure, will retry', [
                'attempt' => $this->attempts(),
                'phone_number' => $targetPhone,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    public function failed(Throwable $e): void
    {
        Log::error('SendWhatsAppNotificationJob permanently failed', [
            'job_id' => $this->job?->getJobId(),
            'phone_number' => $this->phoneNumber,
            'exception' => $e->getMessage(),
        ]);
    }

    /**
     * Sanitize phone number to digits only.
     */
    private function sanitizePhoneNumber(string $phone): string
    {
        return preg_replace('/\D+/', '', $phone) ?? '';
    }
}
