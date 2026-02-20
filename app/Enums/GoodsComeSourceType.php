<?php

namespace App\Enums;

enum GoodsComeSourceType: string
{
    case PurchaseOrder = 'purchase_order';
    case Manual = 'manual';
    case StockTransfer = 'stock_transfer';
    case Adjustment = 'adjustment';
    case StockOpname = 'stock_opname';

    public function label(): string
    {
        return match ($this) {
            self::PurchaseOrder => 'Pesanan Pembelian',
            self::Manual => 'Manual',
            self::StockTransfer => 'Mutasi Stok',
            self::Adjustment => 'Penyesuaian',
            self::StockOpname => 'Stok Opname',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::PurchaseOrder => 'Barang datang dari pesanan pembelian.',
            self::Manual => 'Barang dicatat manual tanpa dokumen sumber.',
            self::StockTransfer => 'Barang datang dari mutasi antar stok.',
            self::Adjustment => 'Barang akibat penyesuaian stok.',
            self::StockOpname => 'Barang dari hasil stok opname.',
        };
    }

    public static function values(): array
    {
        return array_map(static fn(self $c): string => $c->value, self::cases());
    }
}
