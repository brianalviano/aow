<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Alasan (reason) surat stok manual.
 */
enum StockDocumentReason: string
{
    case GoodsCome      = 'goods_come';
    case Purchase       = 'purchase';
    case Sales          = 'sales';
    case PurchaseReturn = 'purchase_return';
    case SalesReturn    = 'sales_return';
    case StockOpname    = 'stock_opname';
    case Damaged        = 'damaged';

    public function label(): string
    {
        return match ($this) {
            self::GoodsCome      => 'Barang Datang',
            self::Purchase       => 'Pembelian',
            self::Sales          => 'Penjualan',
            self::PurchaseReturn => 'Retur Pembelian',
            self::SalesReturn    => 'Retur Penjualan',
            self::StockOpname    => 'Stock Opname',
            self::Damaged        => 'Rusak',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::GoodsCome      => 'Penyesuaian terkait barang datang.',
            self::Purchase       => 'Penyesuaian stok terkait pembelian.',
            self::Sales          => 'Penyesuaian stok terkait penjualan.',
            self::PurchaseReturn => 'Penyesuaian stok terkait retur pembelian.',
            self::SalesReturn    => 'Penyesuaian stok terkait retur penjualan.',
            self::StockOpname    => 'Penyesuaian stok dari hasil stock opname.',
            self::Damaged        => 'Penyesuaian stok karena barang rusak.',
        };
    }

    public static function forType(StockDocumentType $type): array
    {
        return match ($type) {
            StockDocumentType::In => [
                self::GoodsCome,
                self::Purchase,
                self::SalesReturn,
                self::StockOpname,
            ],
            StockDocumentType::Out => [
                self::Sales,
                self::PurchaseReturn,
                self::Damaged,
                self::StockOpname,
            ],
        };
    }

    public static function values(): array
    {
        return array_map(static fn(self $c): string => $c->value, self::cases());
    }
}
