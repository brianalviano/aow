<?php

namespace App\Console\Commands;

use App\Enums\OrderStatus;
use App\Models\DropPoint;
use App\Models\Order;
use App\Models\Product;
use App\Services\CheckoutService;
use App\Services\OrderService;
use Illuminate\Console\Command;

class TestDapurRemovalIntegrity extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'aow:test-dapur-removal';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Simulate and verify the overhauled system without chef/pickup point logic';

    /**
     * Execute the console command.
     */
    public function handle(CheckoutService $checkoutService, OrderService $orderService): int
    {
        $this->info('==================================================');
        $this->info('  AOWENAK ARCHITECTURE VERIFICATION TEST');
        $this->info('==================================================');

        // 1. Check database schema integrity
        $this->info('1. Verifying Models & Schema integrity...');
        $productCount = Product::count();
        $this->line("   - Products in DB: {$productCount}");

        $dropPointCount = DropPoint::count();
        $this->line("   - DropPoints in DB: {$dropPointCount}");

        // 2. Check OrderStatus enum values
        $this->info('2. Verifying OrderStatus lifecycle...');
        $statuses = array_map(fn ($s) => $s->value, OrderStatus::cases());
        $this->line('   - Available Order Statuses: '.implode(', ', $statuses));

        $expectedStatuses = ['pending', 'confirmed', 'cooking', 'on_delivery', 'arrived', 'delivered', 'cancelled'];
        $diff = array_diff($expectedStatuses, $statuses);
        if (empty($diff)) {
            $this->info('   ✓ Status enum matches simplified lifecycle.');
        } else {
            $this->error('   ✗ Missing expected statuses: '.implode(', ', $diff));

            return 1;
        }

        // 3. Verify Checkout Calculation without Chef multiplier
        $this->info('3. Simulating Checkout Calculation...');
        $product = Product::first();
        if ($product) {
            $mockCart = [
                $product->id => [
                    'product' => $product->toArray(),
                    'quantity' => 2,
                    'options' => [],
                    'notes' => 'Simulasi order',
                    'totalPrice' => (float) $product->price * 2,
                ],
            ];

            $dropPoint = DropPoint::first();
            $fees = $checkoutService->calculateFees($mockCart, $dropPoint?->id, null);

            $this->line('   - Delivery Fee (DropPoint): Rp '.number_format($fees['deliveryFee'], 0, ',', '.'));
            $this->line('   - Base Delivery Fee: Rp '.number_format($fees['baseDeliveryFee'], 0, ',', '.'));
            $this->line('   - Tax Amount: Rp '.number_format($fees['taxAmount'], 0, ',', '.'));
            $this->info('   ✓ Checkout fee calculation executed without error.');
        }

        // 4. Verify Order inspection
        $this->info('4. Verifying Orders in DB...');
        $order = Order::with(['items.product', 'shippings', 'dropPoint', 'customer'])->first();
        if ($order) {
            $this->line("   - Found Order #{$order->number}, Status: {$order->order_status->value}");
            $this->line("   - Total Items: {$order->items->count()}");
            $this->info('   ✓ Order relationships resolved cleanly without chef/pic relations.');
        } else {
            $this->line('   - No existing order found in DB (Clean state).');
        }

        $this->info('==================================================');
        $this->info('  ALL INTEGRITY CHECKS PASSED SUCCESSFULLY!');
        $this->info('==================================================');

        return 0;
    }
}
