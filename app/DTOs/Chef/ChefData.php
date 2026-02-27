<?php

declare(strict_types=1);

namespace App\DTOs\Chef;

use App\Http\Requests\Admin\Chef\{StoreChefRequest, UpdateChefRequest};

/**
 * Data Transfer Object for Chef create/update operations.
 *
 * Transports validated chef data from Form Request to Service layer.
 * Product IDs are passed separately for many-to-many sync.
 */
class ChefData
{
    /**
     * @param string $name Nama chef
     * @param string $email Email chef (unique, digunakan untuk login)
     * @param string|null $password Password chef (null saat update jika tidak diubah)
     * @param string|null $phone Nomor telepon
     * @param string|null $bankName Nama bank untuk transfer
     * @param string|null $accountNumber Nomor rekening
     * @param string|null $accountName Nama pemegang rekening
     * @param string|null $note Catatan internal
     * @param float $feePercentage Persentase fee perusahaan (0–100)
     * @param string|null $address Alamat chef
     * @param float|null $longitude Koordinat longitude
     * @param float|null $latitude Koordinat latitude
     * @param bool $isActive Status aktif
     * @param array<string> $productIds UUID produk yang di-assign ke chef
     */
    public function __construct(
        public readonly string $name,
        public readonly string $email,
        public readonly ?string $password,
        public readonly ?string $phone,
        public readonly ?string $bankName,
        public readonly ?string $accountNumber,
        public readonly ?string $accountName,
        public readonly ?string $note,
        public readonly float $feePercentage,
        public readonly ?string $address,
        public readonly ?float $longitude,
        public readonly ?float $latitude,
        public readonly bool $isActive = true,
        public readonly array $productIds = [],
    ) {}

    /**
     * Create DTO from Store Form Request.
     */
    public static function fromStoreRequest(StoreChefRequest $request): self
    {
        return new self(
            name: (string) $request->validated('name'),
            email: (string) $request->validated('email'),
            password: (string) $request->validated('password'),
            phone: $request->validated('phone') === null ? null : (string) $request->validated('phone'),
            bankName: $request->validated('bank_name') === null ? null : (string) $request->validated('bank_name'),
            accountNumber: $request->validated('account_number') === null
                ? null : (string) $request->validated('account_number'),
            accountName: $request->validated('account_name') === null
                ? null : (string) $request->validated('account_name'),
            note: $request->validated('note') === null ? null : (string) $request->validated('note'),
            feePercentage: (float) $request->validated('fee_percentage'),
            address: $request->validated('address') === null ? null : (string) $request->validated('address'),
            longitude: $request->validated('longitude') === null ? null : (float) $request->validated('longitude'),
            latitude: $request->validated('latitude') === null ? null : (float) $request->validated('latitude'),
            isActive: (bool) $request->validated('is_active', true),
            productIds: (array) $request->validated('product_ids', []),
        );
    }

    /**
     * Create DTO from Update Form Request.
     */
    public static function fromUpdateRequest(UpdateChefRequest $request): self
    {
        return new self(
            name: (string) $request->validated('name'),
            email: (string) $request->validated('email'),
            password: $request->validated('password') === null ? null : (string) $request->validated('password'),
            phone: $request->validated('phone') === null ? null : (string) $request->validated('phone'),
            bankName: $request->validated('bank_name') === null ? null : (string) $request->validated('bank_name'),
            accountNumber: $request->validated('account_number') === null
                ? null : (string) $request->validated('account_number'),
            accountName: $request->validated('account_name') === null
                ? null : (string) $request->validated('account_name'),
            note: $request->validated('note') === null ? null : (string) $request->validated('note'),
            feePercentage: (float) $request->validated('fee_percentage'),
            address: $request->validated('address') === null ? null : (string) $request->validated('address'),
            longitude: $request->validated('longitude') === null ? null : (float) $request->validated('longitude'),
            latitude: $request->validated('latitude') === null ? null : (float) $request->validated('latitude'),
            isActive: (bool) $request->validated('is_active', true),
            productIds: (array) $request->validated('product_ids', []),
        );
    }
}
