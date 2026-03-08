<?php

declare(strict_types=1);

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;
use App\Models\OrderSetting;

class SendWhatsAppNotificationJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function __construct(
        public readonly string $phoneNumber,
        public readonly string $message,
    ) {
    }

    /**
     * Calculate the number of seconds to wait before retrying the job.
     */
    public function backoff(): array
    {
        return [10, 30, 60];
    }

    public function handle(): void
    {
        // Get settings, returning false early if it's disabled.
        $enabled = OrderSetting::where('key', 'whatsapp_enabled')->value('value');
        if ($enabled !== 'true') {
            return;
        }

        $token = OrderSetting::where('key', 'whatsapp_access_token')->value('value');
        if (empty($token)) {
            return;
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => $token,
            ])->timeout(15)->post('https://api.fonnte.com/send', [
                'target' => $this->phoneNumber,
                'message' => $this->message,
            ]);

            if ($response->failed()) {
                throw new \Exception('Fonnte API HTTP error -> ' . $response->body());
            }

            // Fonnte returns 200 OK even on some failures (e.g. invalid token), so we check 'status'
            $data = $response->json();
            if (isset($data['status']) && $data['status'] === false) {
                 throw new \Exception('Fonnte API business logic error -> ' . json_encode($data));
            }
        } catch (Throwable $e) {
            Log::error('SendWhatsAppNotificationJob failed', [
                'job_id' => $this->job?->getJobId(),
                'phone_number' => $this->phoneNumber,
                'message' => $this->message,
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    public function failed(Throwable $e): void
    {
        Log::error('SendWhatsAppNotificationJob ultimately failed after retries', [
            'job_id' => $this->job?->getJobId(),
            'phone_number' => $this->phoneNumber,
            'message' => $this->message,
            'exception' => $e->getMessage(),
        ]);
    }
}
