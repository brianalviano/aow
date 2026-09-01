<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Order;
use Illuminate\Console\Command;

class InspectOrder extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'orders:inspect {number? : Nomor order yang ingin diperiksa} {--dump-json : Dump raw JSON data yang dikirim ke frontend} {--pending-payment : Tampilkan pesanan yang masuk ke approval pembayaran (badge count)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Periksa detail item pesanan dan status';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        if ($this->option('pending-payment')) {
            $this->info('=== PESANAN MENUNGGU APPROVAL PEMBAYARAN (BADGE COUNT) ===');
            $orders = Order::query()
                ->where('payment_status', 'pending')
                ->where('order_status', '!=', 'cancelled')
                ->whereDoesntHave('paymentMethod', fn ($q) => $q->where('category', 'cash'))
                ->get();

            if ($orders->isEmpty()) {
                $this->line('Tidak ada pesanan menunggu approval pembayaran.');
            } else {
                foreach ($orders as $order) {
                    $this->line("Order: {$order->number} | ID: {$order->id} | Status: {$order->order_status->value} | Customer: ".($order->customer?->name ?? '-').' | Metode: '.($order->paymentMethod?->name ?? 'None'));
                }
            }

            return Command::SUCCESS;
        }

        $number = $this->argument('number');
        $dumpJson = $this->option('dump-json');

        if ($number) {
            $order = Order::where('number', $number)->first();
            if (! $order) {
                $this->error("Pesanan dengan nomor {$number} tidak ditemukan.");

                return Command::FAILURE;
            }

            $this->info('=== DETAIL PESANAN ===');
            $this->line("Nomor: {$order->number}");
            $this->line("ID: {$order->id}");
            $this->line("Status Pesanan: {$order->order_status->value}");
            $this->line("Status Pembayaran: {$order->payment_status->value}");
            $this->line('Customer: '.($order->customer?->name ?? '-').' ('.($order->customer?->phone ?? '-').')');
            $this->line('Tanggal Kirim: '.($order->delivery_date ? $order->delivery_date->format('Y-m-d') : '-'));
            $this->line('--- ITEM PESANAN ---');

            $items = $order->items()->with(['product', 'order.customer', 'order.dropPoint'])->get();

            if ($dumpJson) {
                $this->line(json_encode($items->toArray(), JSON_PRETTY_PRINT));
            } else {
                foreach ($items as $item) {
                    $this->line("- [Item ID: {$item->id}]");
                    $this->line('  Produk      : '.($item->product?->name ?? 'None')." (ID: {$item->product_id})");
                    $this->line("  Jumlah      : {$item->quantity}x");
                    $this->line('  Subtotal    : Rp '.number_format($item->subtotal, 0, ',', '.'));
                }
            }
        } else {
            $this->info('Menampilkan 5 pesanan terbaru:');
            $orders = Order::latest()->take(5)->get();

            foreach ($orders as $order) {
                $this->line("Order: {$order->number} | Status: {$order->order_status->value} | Customer: ".($order->customer?->name ?? '-'));
                $items = $order->items()->with('product')->get();
                foreach ($items as $item) {
                    $this->line('  - '.($item->product?->name ?? 'None')." x{$item->quantity}");
                }
                $this->line(str_repeat('-', 50));
            }
        }

        return Command::SUCCESS;
    }
}
