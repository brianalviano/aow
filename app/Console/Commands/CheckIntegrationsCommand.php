<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\DTOs\Setting\OrderSettingsDTO;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class CheckIntegrationsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'aow:check-integrations 
                            {--test-wa= : Kirim pesan uji coba WhatsApp ke nomor tujuan}
                            {--test-telegram : Kirim pesan uji coba ke Telegram Admin}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Periksa status konektivitas dan kesehatan API WhatsApp (Fonnte) & Telegram Bot';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('====================================================');
        $this->info('      AOWENAK - INTEGRATION STATUS DIAGNOSTICS      ');
        $this->info('====================================================');
        $this->newLine();

        $settings = OrderSettingsDTO::load();

        $this->checkWhatsApp($settings);
        $this->newLine();
        $this->checkTelegram($settings);
        $this->newLine();

        return Command::SUCCESS;
    }

    /**
     * Check WhatsApp (Fonnte) Integration.
     */
    private function checkWhatsApp(OrderSettingsDTO $settings): void
    {
        $this->comment('--- [1] WHATSAPP (FONNTE) INTEGRATION ---');

        $enabled = $settings->whatsappEnabled;
        $token = $settings->whatsappAccessToken;

        $this->line('Enabled in System : '.($enabled ? '<fg=green>YES</>' : '<fg=yellow>NO</>'));
        $this->line('Access Token      : '.($token ? substr($token, 0, 6).'***' : '<fg=red>NOT SET</>'));

        if (! $token) {
            $this->warn('Access token WhatsApp belum dikonfigurasi di Settings.');

            return;
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => $token,
            ])->timeout(10)->post('https://api.fonnte.com/device');

            if ($response->failed()) {
                $this->error('Fonnte API HTTP Error: '.$response->status().' - '.$response->body());

                return;
            }

            $data = $response->json();
            $deviceStatus = $data['device_status'] ?? 'unknown';
            $isDeviceConnected = strtolower((string) $deviceStatus) === 'connect';

            $rows = [
                ['Status Endpoint', ($data['status'] ?? false) ? '<fg=green>OK</>' : '<fg=red>FAIL</>'],
                ['Device Status', $isDeviceConnected ? '<fg=green;options=bold>CONNECTED</>' : '<fg=red;options=bold>DISCONNECTED</>'],
                ['Device Number', $data['device'] ?? '-'],
                ['Device Name', $data['name'] ?? '-'],
                ['Package', $data['package'] ?? '-'],
                ['Quota Sisa', (string) ($data['quota'] ?? '-')],
                ['Expired Date', $data['expired'] ?? '-'],
            ];

            $this->table(['Parameter', 'Nilai'], $rows);

            if (! $isDeviceConnected) {
                $this->warn('PERHATIAN: Device WhatsApp di Fonnte berstatus DISCONNECTED!');
                $this->warn('Silakan scan ulang QR Code di dashboard https://fonnte.com agar pesan dapat terkirim.');
            } else {
                $this->info('Device WhatsApp terhubung dan siap mengirim pesan.');
            }

            // Test WhatsApp message if option passed
            $testPhone = $this->option('test-wa');
            if ($testPhone) {
                $this->sendTestWhatsApp($token, (string) $testPhone);
            }
        } catch (\Throwable $e) {
            $this->error('Gagal menghubungi Fonnte API: '.$e->getMessage());
        }
    }

    /**
     * Check Telegram Bot Integration.
     */
    private function checkTelegram(OrderSettingsDTO $settings): void
    {
        $this->comment('--- [2] TELEGRAM BOT INTEGRATION ---');

        $enabled = $settings->telegramEnabled;
        $token = $settings->telegramBotToken;
        $chatId = $settings->telegramAdminChatId;

        $this->line('Enabled in System : '.($enabled ? '<fg=green>YES</>' : '<fg=yellow>NO</>'));
        $this->line('Bot Token         : '.($token ? substr($token, 0, 10).'***' : '<fg=red>NOT SET</>'));
        $this->line('Admin Chat ID     : '.($chatId ?: '<fg=red>NOT SET</>'));

        if (! $token) {
            $this->warn('Telegram Bot Token belum dikonfigurasi di Settings.');

            return;
        }

        try {
            $response = Http::timeout(10)->get("https://api.telegram.org/bot{$token}/getMe");

            if ($response->failed()) {
                $this->error('Telegram API HTTP Error: '.$response->status().' - '.$response->body());

                return;
            }

            $data = $response->json();
            if (! ($data['ok'] ?? false)) {
                $this->error('Telegram API Error: '.($data['description'] ?? 'Unknown'));

                return;
            }

            $result = $data['result'] ?? [];
            $rows = [
                ['Bot ID', (string) ($result['id'] ?? '-')],
                ['Bot Name', $result['first_name'] ?? '-'],
                ['Bot Username', '@'.($result['username'] ?? '-')],
                ['Can Join Groups', ($result['can_join_groups'] ?? false) ? 'YES' : 'NO'],
            ];

            $this->table(['Parameter', 'Nilai'], $rows);
            $this->info('Telegram Bot terhubung dan valid.');

            // Test Telegram message if option passed
            if ($this->option('test-telegram')) {
                $this->sendTestTelegram($token, (string) $chatId);
            }
        } catch (\Throwable $e) {
            $this->error('Gagal menghubungi Telegram API: '.$e->getMessage());
        }
    }

    /**
     * Send test WhatsApp message.
     */
    private function sendTestWhatsApp(string $token, string $phone): void
    {
        $this->line("Mengirim test WhatsApp ke: {$phone}...");
        $cleanPhone = preg_replace('/\D+/', '', $phone) ?? '';

        try {
            $response = Http::withHeaders([
                'Authorization' => $token,
            ])->timeout(15)->post('https://api.fonnte.com/send', [
                'target' => $cleanPhone,
                'message' => 'Test notifikasi dari AOWenak Diagnostics pada '.now()->toDateTimeString(),
            ]);

            $data = $response->json();
            if ($response->successful() && ($data['status'] ?? false) === true) {
                $this->info('Test WhatsApp BERHASIL dikirim!');
            } else {
                $this->error('Test WhatsApp GAGAL: '.($data['reason'] ?? $response->body()));
            }
        } catch (\Throwable $e) {
            $this->error('Test WhatsApp Error: '.$e->getMessage());
        }
    }

    /**
     * Send test Telegram message.
     */
    private function sendTestTelegram(string $token, string $chatId): void
    {
        if (empty($chatId)) {
            $this->error('Gagal kirim test Telegram: Admin Chat ID kosong.');

            return;
        }

        $this->line("Mengirim test Telegram ke Chat ID: {$chatId}...");

        try {
            $response = Http::timeout(10)->post("https://api.telegram.org/bot{$token}/sendMessage", [
                'chat_id' => $chatId,
                'text' => "<b>Test Notifikasi AOWenak</b>\n\nDiagnostik integrasi Telegram berhasil pada ".now()->toDateTimeString(),
                'parse_mode' => 'HTML',
            ]);

            $data = $response->json();
            if ($response->successful() && ($data['ok'] ?? false) === true) {
                $this->info('Test Telegram BERHASIL dikirim!');
            } else {
                $this->error('Test Telegram GAGAL: '.($data['description'] ?? $response->body()));
            }
        } catch (\Throwable $e) {
            $this->error('Test Telegram Error: '.$e->getMessage());
        }
    }
}
