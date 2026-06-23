<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\OrderStatus;
use App\Models\Chef;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Console\Command;

class InspectOrder extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'orders:inspect {number? : Nomor order yang ingin diperiksa} {--chef= : Nama atau ID chef untuk menyaring item} {--dump-json : Dump raw JSON data yang dikirim ke frontend} {--list-chefs : Tampilkan semua chef yang terdaftar} {--simulate-inertia : Simulasikan grouping data untuk frontend Svelte} {--pending-payment : Tampilkan pesanan yang masuk ke approval pembayaran (badge count)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Periksa detail item pesanan dan alokasi chef/dapur';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        if ($this->option('list-chefs')) {
            $this->info('=== DAFTAR CHEF ===');
            $chefs = Chef::all();
            foreach ($chefs as $c) {
                $this->line("ID: {$c->id} | Nama: {$c->name} | Email: {$c->email} | No. Telp: {$c->phone} | Aktif: ".($c->is_active ? 'Ya' : 'Tidak'));
            }

            return Command::SUCCESS;
        }

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
        $chefQuery = $this->option('chef');
        $dumpJson = $this->option('dump-json');
        $simulateInertia = $this->option('simulate-inertia');

        $chef = null;
        if ($chefQuery) {
            $isUuid = preg_match('/^[a-f\d]{8}-(?:[a-f\d]{4}-){3}[a-f\d]{12}$/i', $chefQuery);
            $chef = Chef::query()
                ->when($isUuid, function ($query) use ($chefQuery) {
                    $query->where('id', $chefQuery);
                }, function ($query) use ($chefQuery) {
                    $query->where('name', 'ilike', "%{$chefQuery}%");
                })
                ->first();

            if (! $chef) {
                $this->error("Chef dengan ID atau nama '{$chefQuery}' tidak ditemukan.");

                return Command::FAILURE;
            }
            $this->info("Menyaring berdasarkan Chef: {$chef->name} (ID: {$chef->id})");
        }

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

            $itemsQuery = $order->items()->with(['product', 'chef', 'order.customer', 'order.dropPoint', 'order.pickUpPoint', 'order.items']);
            if ($chef) {
                $itemsQuery->where('chef_id', $chef->id);
            }
            $items = $itemsQuery->get();

            if ($simulateInertia) {
                $grouped = [];
                foreach ($items as $item) {
                    $orderId = $item->order->id;
                    if (! isset($grouped[$orderId])) {
                        $grouped[$orderId] = [
                            'order' => $item->order->toArray(),
                            'items' => [],
                        ];
                    }
                    $grouped[$orderId]['items'][] = $item->toArray();
                }
                $this->line(json_encode(array_values($grouped), JSON_PRETTY_PRINT));
            } elseif ($dumpJson) {
                $this->line(json_encode($items->toArray(), JSON_PRETTY_PRINT));
            } else {
                foreach ($items as $item) {
                    $this->line("- [Item ID: {$item->id}]");
                    $this->line('  Produk      : '.($item->product?->name ?? 'None')." (ID: {$item->product_id})");
                    $this->line('  Chef        : '.($item->chef?->name ?? 'Belum ditentukan')." (ID: {$item->chef_id})");
                    $this->line("  Jumlah      : {$item->quantity}x");
                    $this->line('  Status Chef : '.($item->chef_status?->value ?? 'None'));
                }
            }
        } else {
            if ($chef) {
                $this->info("Menampilkan item pesanan aktif untuk Chef: {$chef->name}");
                $items = OrderItem::where('chef_id', $chef->id)
                    ->whereHas('order', function ($query) {
                        $query->whereNotIn('order_status', [
                            OrderStatus::DELIVERED,
                            OrderStatus::CANCELLED,
                        ]);
                    })
                    ->with(['order.customer', 'order.dropPoint', 'order.pickUpPoint', 'order.items', 'product'])
                    ->latest()
                    ->get();

                if ($items->isEmpty()) {
                    $this->line('Tidak ada item pesanan aktif untuk chef ini.');
                } else {
                    if ($simulateInertia) {
                        $grouped = [];
                        foreach ($items as $item) {
                            $orderId = $item->order->id;
                            if (! isset($grouped[$orderId])) {
                                $grouped[$orderId] = [
                                    'order' => $item->order->toArray(),
                                    'items' => [],
                                ];
                            }
                            $grouped[$orderId]['items'][] = $item->toArray();
                        }
                        $this->line(json_encode(array_values($grouped), JSON_PRETTY_PRINT));
                    } elseif ($dumpJson) {
                        $this->line(json_encode($items->toArray(), JSON_PRETTY_PRINT));
                    } else {
                        foreach ($items as $item) {
                            $this->line("Order: {$item->order?->number} | Produk: {$item->product?->name} x{$item->quantity} | Status Chef: {$item->chef_status->value}");
                        }
                    }
                }
            } else {
                $this->info('Menampilkan 5 pesanan terbaru:');
                $orders = Order::latest()->take(5)->get();

                foreach ($orders as $order) {
                    $this->line("Order: {$order->number} | Status: {$order->order_status->value} | Customer: ".($order->customer?->name ?? '-'));
                    $items = $order->items()->with('product', 'chef')->get();
                    foreach ($items as $item) {
                        $this->line('  - '.($item->product?->name ?? 'None')." x{$item->quantity} | Chef: ".($item->chef?->name ?? 'None').' | Status: '.($item->chef_status?->value ?? 'None'));
                    }
                    $this->line(str_repeat('-', 50));
                }
            }
        }

        return Command::SUCCESS;
    }
}
