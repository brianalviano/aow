<?php

declare(strict_types=1);

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;
use App\Models\OrderSetting;

class SendTelegramNotificationJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function __construct(
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
        $enabled = OrderSetting::where('key', 'telegram_enabled')->value('value');
        if ($enabled !== 'true') {
            return;
        }

        $botToken = OrderSetting::where('key', 'telegram_bot_token')->value('value');
        $chatId = OrderSetting::where('key', 'telegram_admin_chat_id')->value('value');

        if (empty($botToken) || empty($chatId)) {
            return;
        }

        try {
            $response = Http::timeout(10)->post("https://api.telegram.org/bot{$botToken}/sendMessage", [
                'chat_id' => $chatId,
                'text' => $this->message,
                'parse_mode' => 'HTML',
            ]);

            if ($response->failed()) {
                throw new \Exception('Telegram API error -> ' . $response->body());
            }
        } catch (Throwable $e) {
            Log::error('SendTelegramNotificationJob failed', [
                'job_id' => $this->job?->getJobId(),
                'chat_id' => $chatId,
                'message' => $this->message,
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    public function failed(Throwable $e): void
    {
        Log::error('SendTelegramNotificationJob ultimately failed after retries', [
            'job_id' => $this->job?->getJobId(),
            'message' => $this->message,
            'exception' => $e->getMessage(),
        ]);
    }
}
