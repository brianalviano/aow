<?php

declare(strict_types=1);

namespace App\Jobs;

use App\DTOs\Setting\OrderSettingsDTO;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class SendTelegramNotificationJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function __construct(
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
        if (! $settings->telegramEnabled) {
            return;
        }

        $botToken = $settings->telegramBotToken;
        $chatId = $settings->telegramAdminChatId;

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
                $statusCode = $response->status();

                // 4xx errors indicate invalid bot token, blocked bot, invalid chat ID, or bad HTML parse
                if ($statusCode >= 400 && $statusCode < 500) {
                    Log::error('SendTelegramNotificationJob permanent failure from Telegram API (not retrying)', [
                        'status' => $statusCode,
                        'chat_id' => $chatId,
                        'response' => $response->body(),
                    ]);

                    $this->fail(new \Exception("Telegram API HTTP {$statusCode} error -> ".$response->body()));

                    return;
                }

                // 5xx errors or network issues are transient and should trigger retry backoff
                throw new \Exception("Telegram API HTTP {$statusCode} server error -> ".$response->body());
            }
        } catch (Throwable $e) {
            if ($this->job?->hasFailed()) {
                return;
            }

            Log::warning('SendTelegramNotificationJob transient failure, will retry', [
                'attempt' => $this->attempts(),
                'chat_id' => $chatId,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    public function failed(Throwable $e): void
    {
        Log::error('SendTelegramNotificationJob permanently failed', [
            'job_id' => $this->job?->getJobId(),
            'message' => $this->message,
            'exception' => $e->getMessage(),
        ]);
    }
}
