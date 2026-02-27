<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\DTOs\Checkout\ProcessOrderData;
use App\Models\{Customer, DropPoint, Order, OrderItem, OrderItemOption, OrderSetting, PaymentMethod, Product};
use App\Services\{DailySummaryService, OrderService};
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\{DB, Mail, Notification};
use Faker\Factory as Faker;

class OrderSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @param DailySummaryService $summaryService
     * @param OrderService $orderService
     */
    public function run(DailySummaryService $summaryService, OrderService $orderService): void
    {
        Mail::fake();
        Notification::fake();

        $faker = Faker::create('id_ID');
        $customers = Customer::all();
        $dropPoints = DropPoint::all();
        // Filter out gateway payment methods to avoid side effects during seeding
        $paymentMethods = PaymentMethod::where('type', '!=', 'gateway')->get();
        $products = Product::with(['productOptions.items'])->get();
        $settings = OrderSetting::pluck('value', 'key')->toArray();

        if ($customers->isEmpty() || $dropPoints->isEmpty() || $paymentMethods->isEmpty() || $products->isEmpty()) {
            $this->command->warn('Missing required related data (Customers, DropPoints, PaymentMethods, or Products). Skipping OrderSeeder.');
            return;
        }

        $dates = [];

        for ($i = 0; $i < 50; $i++) {
            $createdAt = Carbon::now()->subDays(rand(0, 30))->subHours(rand(0, 23))->subMinutes(rand(0, 59));
            $dates[$createdAt->toDateString()] = $createdAt->copy()->startOfDay();

            DB::transaction(function () use ($faker, $customers, $dropPoints, $paymentMethods, $products, $settings, $createdAt, $orderService) {
                $customer = $customers->random();
                $dropPoint = $dropPoints->random();
                $paymentMethod = $paymentMethods->random();

                $itemCount = rand(1, 4);
                $selectedProducts = $products->random($itemCount);
                $cart = [];

                foreach ($selectedProducts as $product) {
                    $quantity = rand(1, 3);
                    $selectedOptions = [];
                    $extraPriceTotal = 0;

                    foreach ($product->productOptions as $option) {
                        if ($option->isRequired || $faker->boolean(50)) {
                            $optionItems = $option->items;
                            if ($optionItems->isNotEmpty()) {
                                $selectedOptionItems = $option->isMultiple
                                    ? $optionItems->random(rand(1, min(2, $optionItems->count())))
                                    : collect([$optionItems->random()]);

                                foreach ($selectedOptionItems as $optItem) {
                                    $selectedOptions[$option->id][] = $optItem->id;
                                    $extraPriceTotal += $optItem->extra_price;
                                }
                            }
                        }
                    }

                    $cart[] = [
                        'product' => [
                            'id' => $product->id,
                            'options' => $product->productOptions->toArray(), // Needed for resolveOptionExtraPrice
                        ],
                        'quantity' => $quantity,
                        'basePrice' => (int) $product->price,
                        'totalPrice' => ((int) $product->price + $extraPriceTotal) * $quantity,
                        'selectedOptions' => $selectedOptions,
                        'notes' => $faker->boolean(20) ? $faker->sentence() : null,
                    ];
                }

                $processData = new ProcessOrderData(
                    name: $customer->name,
                    phone: $customer->phone,
                    email: $customer->email,
                    schoolClass: $customer->school_class,
                    paymentMethodId: (string) $paymentMethod->id,
                    cart: $cart,
                    dropPoint: $dropPoint->toArray(),
                    deliveryDate: $createdAt->copy()->addDay()->format('Y-m-d'),
                    deliveryTime: $faker->randomElement(['08:00', '10:00', '13:00', '15:00']),
                );

                // Use the service to process the order
                $order = $orderService->processOrder($processData);

                // Post-process: Randomize status and backdate timestamps
                $orderStatus = $faker->randomElement(['pending', 'confirmed', 'shipped', 'delivered', 'cancelled']);
                $paymentStatus = $orderStatus === 'delivered' ? 'paid' : ($orderStatus === 'cancelled' ? 'failed' : 'pending');

                if ($paymentMethod->category === 'cash' && $orderStatus !== 'pending') {
                    $paymentStatus = 'paid';
                }

                $deliveredAt = $orderStatus === 'delivered' ? $createdAt->copy()->addHours(rand(2, 24)) : null;

                // Update with backdated information and randomized status
                $order->updateQuietly([
                    'order_status' => $orderStatus,
                    'payment_status' => $paymentStatus,
                    'number' => $this->generateOrderNumber($createdAt),
                    'created_at' => $createdAt,
                    'updated_at' => $createdAt,
                    'delivered_at' => $deliveredAt,
                ]);

                // Also backdate items and options
                $order->items()->update(['created_at' => $createdAt]);
                foreach ($order->items as $item) {
                    $item->options()->update(['created_at' => $createdAt]);
                }
            });
        }

        // Generate summaries for all affected dates
        foreach ($dates as $date) {
            $summaryService->generateForDate($date);
            $summaryService->generateProductSummaryForDate($date);
        }
    }

    /**
     * Generate a unique order number with format ORD/MMYYYY/XXXXXX.
     */
    private function generateOrderNumber(Carbon $date): string
    {
        $prefix = "ORD/" . $date->format('mY') . "/";

        $lastOrder = Order::where('number', 'like', "{$prefix}%")
            ->orderBy('number', 'desc')
            ->first();

        $sequence = 1;
        if ($lastOrder) {
            $lastNumber = $lastOrder->number;
            $lastSequence = (int) substr($lastNumber, strrpos($lastNumber, '/') + 1);
            $sequence = $lastSequence + 1;
        }

        return $prefix . str_pad((string) $sequence, 6, '0', STR_PAD_LEFT);
    }
}
