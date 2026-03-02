<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\{Order, OrderShipping};
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\{Cache, Http, Log};

/**
 * Service untuk berkomunikasi dengan Biteship API.
 *
 * Menyediakan method untuk mengambil tarif kurir instant (Grab/Gojek)
 * berdasarkan koordinat origin (chef) dan destination (customer).
 * Hasil di-cache selama 5 menit per kombinasi koordinat.
 */
class BiteshipService
{
    private const BASE_URL = 'https://api.biteship.com';
    private const CACHE_TTL_SECONDS = 300; // 5 menit
    private const TIMEOUT_SECONDS = 10;
    private const DEFAULT_COURIERS = 'grab,gojek';

    /**
     * Ambil tarif kurir dari Biteship Rates API.
     *
     * @param float $originLat Latitude origin (chef).
     * @param float $originLng Longitude origin (chef).
     * @param float $destLat Latitude destination (customer).
     * @param float $destLng Longitude destination (customer).
     * @param array $items Item list sesuai format Biteship (name, value, weight, quantity, dll).
     * @param string $couriers Comma-separated courier codes (default: grab,gojek).
     * @return array{success: bool, pricing: array, error: string|null}
     *   pricing berisi array dari available couriers dengan tarif.
     */
    public function getRates(
        float $originLat,
        float $originLng,
        float $destLat,
        float $destLng,
        array $items = [],
        string $couriers = self::DEFAULT_COURIERS,
    ): array {
        $cacheKey = $this->buildCacheKey($originLat, $originLng, $destLat, $destLng, $couriers);

        return Cache::remember($cacheKey, self::CACHE_TTL_SECONDS, function () use (
            $originLat,
            $originLng,
            $destLat,
            $destLng,
            $items,
            $couriers
        ) {
            return $this->fetchRatesFromApi($originLat, $originLng, $destLat, $destLng, $items, $couriers);
        });
    }

    /**
     * Ambil tarif termurah dari Biteship untuk kombinasi origin-destination.
     *
     * @param float $originLat Latitude origin (chef).
     * @param float $originLng Longitude origin (chef).
     * @param float $destLat Latitude destination (customer).
     * @param float $destLng Longitude destination (customer).
     * @param array $items Item list sesuai format Biteship.
     * @return array{success: bool, courier_company: string|null, courier_type: string|null, courier_name: string|null, fee: int, error: string|null}
     */
    public function getCheapestRate(
        float $originLat,
        float $originLng,
        float $destLat,
        float $destLng,
        array $items = [],
    ): array {
        $result = $this->getRates($originLat, $originLng, $destLat, $destLng, $items);

        if (!$result['success'] || empty($result['pricing'])) {
            return [
                'success' => false,
                'courier_company' => null,
                'courier_type' => null,
                'courier_name' => null,
                'fee' => 0,
                'error' => $result['error'] ?? 'Tidak ada kurir yang tersedia untuk rute ini.',
            ];
        }

        // Cari tarif termurah dari semua available couriers
        $cheapest = null;
        foreach ($result['pricing'] as $courier) {
            if ($cheapest === null || $courier['price'] < $cheapest['price']) {
                $cheapest = $courier;
            }
        }

        return [
            'success' => true,
            'courier_company' => $cheapest['courier_company'] ?? null,
            'courier_type' => $cheapest['courier_type'] ?? null,
            'courier_name' => $cheapest['courier_name'] ?? null,
            'fee' => (int) ($cheapest['price'] ?? 0),
            'error' => null,
        ];
    }

    /**
     * Fetch rates langsung dari Biteship API (tanpa cache).
     */
    private function fetchRatesFromApi(
        float $originLat,
        float $originLng,
        float $destLat,
        float $destLng,
        array $items,
        string $couriers,
    ): array {
        $apiKey = config('services.biteship.api_key');

        if (empty($apiKey)) {
            Log::error('BiteshipService: API key belum dikonfigurasi.');
            return ['success' => false, 'pricing' => [], 'error' => 'Biteship API key belum dikonfigurasi.'];
        }

        // Default items jika kosong (Biteship membutuhkan minimal 1 item)
        if (empty($items)) {
            $items = [
                [
                    'name' => 'Makanan',
                    'description' => 'Pesanan makanan',
                    'value' => 50000,
                    'length' => 20,
                    'width' => 20,
                    'height' => 15,
                    'weight' => 1000,
                    'quantity' => 1,
                ],
            ];
        }

        $payload = [
            'origin_latitude' => (string) $originLat,
            'origin_longitude' => (string) $originLng,
            'destination_latitude' => (string) $destLat,
            'destination_longitude' => (string) $destLng,
            'couriers' => $couriers,
            'items' => $items,
        ];

        try {
            $response = Http::withHeaders([
                'Authorization' => $apiKey,
                'Content-Type' => 'application/json',
            ])
                ->timeout(self::TIMEOUT_SECONDS)
                ->retry(2, 500, fn($exception) => $exception instanceof \Illuminate\Http\Client\ConnectionException)
                ->post(self::BASE_URL . '/v1/rates/couriers', $payload);

            if (!$response->successful()) {
                $errorBody = $response->json();
                $errorMessage = $errorBody['error'] ?? $response->body();

                Log::warning('BiteshipService: API returned non-success', [
                    'status' => $response->status(),
                    'error' => $errorMessage,
                    'origin' => "{$originLat},{$originLng}",
                    'destination' => "{$destLat},{$destLng}",
                ]);

                return ['success' => false, 'pricing' => [], 'error' => "Biteship API error: {$errorMessage}"];
            }

            $data = $response->json();
            $pricing = [];

            foreach ($data['pricing'] ?? [] as $courierGroup) {
                foreach ($courierGroup['costs'] ?? [] as $cost) {
                    $pricing[] = [
                        'courier_company' => $courierGroup['company'] ?? '',
                        'courier_type' => $cost['type'] ?? '',
                        'courier_name' => $courierGroup['courier_name'] ?? $cost['description'] ?? '',
                        'price' => (int) ($cost['price'] ?? 0),
                        'etd' => $cost['etd'] ?? null,
                        'description' => $cost['description'] ?? '',
                    ];
                }
            }

            return ['success' => true, 'pricing' => $pricing, 'error' => null];
        } catch (\Throwable $e) {
            Log::error('BiteshipService: Exception saat mengambil rates', [
                'error' => $e->getMessage(),
                'origin' => "{$originLat},{$originLng}",
                'destination' => "{$destLat},{$destLng}",
            ]);

            return ['success' => false, 'pricing' => [], 'error' => 'Gagal menghubungi layanan pengiriman.'];
        }
    }

    /**
     * Buat pesanan pengiriman ke Biteship (Cari driver Gojek/Grab).
     *
     * @param OrderShipping $shipping Data shipping per-chef.
     * @param Collection $items Koleksi OrderItem milik chef ini di order ini.
     * @return array{success: bool, order_id: string|null, tracking_id: string|null, waybill_id: string|null, status: string|null, error: string|null}
     */
    public function createOrder(OrderShipping $shipping, Collection $items): array
    {
        $apiKey = config('services.biteship.api_key');

        if (empty($apiKey)) {
            Log::error('BiteshipService: API key belum dikonfigurasi saat buat order.');
            return ['success' => false, 'error' => 'Biteship API key belum dikonfigurasi.'];
        }

        $order = current($items)->order;
        $customer = $order->customer;
        $chef = $shipping->chef;

        // Siapkan list barang sesuai format Biteship
        $biteshipItems = [];
        foreach ($items as $item) {
            $biteshipItems[] = [
                'name'        => mb_substr($item->product->name, 0, 50),
                'description' => mb_substr($item->note ?? 'Makanan', 0, 50),
                'value'       => (int) $item->price,
                'weight'      => 1000, // Asumsi 1kg/item jika produk tdk ada berat
                'quantity'    => $item->quantity,
            ];
        }

        $payload = [
            'shipper_contact_name'      => mb_substr($chef->business_name ?: $chef->name, 0, 50),
            'shipper_contact_phone'     => preg_replace('/[^0-9]/', '', $chef->phone ?: '081234567890'),
            'shipper_contact_email'     => $chef->email ?: 'admin@example.com',
            'shipper_organization'      => mb_substr($chef->business_name ?: $chef->name, 0, 50),

            'origin_contact_name'       => mb_substr($chef->name, 0, 50),
            'origin_contact_phone'      => preg_replace('/[^0-9]/', '', $chef->phone ?: '081234567890'),
            'origin_address'            => $shipping->origin_address ?: 'Alamat tidak diketahui',
            'origin_coordinate' => [
                'latitude'  => (float) $shipping->origin_latitude,
                'longitude' => (float) $shipping->origin_longitude,
            ],

            'destination_contact_name'  => mb_substr($customer->name, 0, 50),
            'destination_contact_phone' => preg_replace('/[^0-9]/', '', $customer->phone ?: '081234567890'),
            'destination_contact_email' => $customer->email ?: 'customer@example.com',
            'destination_address'       => $order->customerAddress?->address ?: 'Alamat tidak diketahui',
            'destination_note'          => mb_substr($order->customerAddress?->note ?? '', 0, 50),
            'destination_coordinate' => [
                'latitude'  => (float) $shipping->destination_latitude,
                'longitude' => (float) $shipping->destination_longitude,
            ],

            'courier_company' => $shipping->courier_company,
            'courier_type'    => $shipping->courier_type,
            'delivery_type'   => 'now', // Instant driver search
            'order_note'      => 'AOW Order #' . $order->number,
            'items'           => $biteshipItems,
        ];

        try {
            $response = Http::withHeaders([
                'Authorization' => $apiKey,
                'Content-Type'  => 'application/json',
            ])
                ->timeout(self::TIMEOUT_SECONDS)
                ->retry(2, 500, fn($exception) => $exception instanceof \Illuminate\Http\Client\ConnectionException)
                ->post(self::BASE_URL . '/v1/orders', $payload);

            if (!$response->successful()) {
                $errorBody = $response->json();
                $errorMessage = $errorBody['error'] ?? $response->body();

                Log::error('BiteshipService: API returned non-success for create order', [
                    'status'  => $response->status(),
                    'error'   => $errorMessage,
                    'payload' => $payload,
                ]);

                return ['success' => false, 'error' => "Biteship API error: {$errorMessage}"];
            }

            $data = $response->json();

            return [
                'success'     => true,
                'order_id'    => $data['id'] ?? null,
                'tracking_id' => $data['tracking_id'] ?? null,
                'waybill_id'  => $data['courier_waybill_id'] ?? $data['waybill_id'] ?? null,
                'status'      => $data['status'] ?? null,
                'error'       => null,
            ];
        } catch (\Throwable $e) {
            Log::error('BiteshipService: Exception saat membuat order API', [
                'error'   => $e->getMessage(),
                'payload' => $payload,
            ]);

            return ['success' => false, 'error' => 'Gagal menghubungi layanan pengiriman Biteship.'];
        }
    }

    /**
     * Build cache key berdasarkan koordinat (rounded ke 4 decimal).
     */
    private function buildCacheKey(
        float $originLat,
        float $originLng,
        float $destLat,
        float $destLng,
        string $couriers,
    ): string {
        return sprintf(
            'biteship_rates:%s:%s:%s:%s:%s',
            round($originLat, 4),
            round($originLng, 4),
            round($destLat, 4),
            round($destLng, 4),
            $couriers,
        );
    }
}
