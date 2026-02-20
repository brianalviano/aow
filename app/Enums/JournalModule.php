<?php

namespace App\Enums;

enum JournalModule: string
{
    case Sales = 'sales';
    case Purchase = 'purchase';
    case GoodsReceipt = 'goods_receipt';
    case Adjustment = 'adjustment';
    case StockOpname = 'stock_opname';
    case StockTransfer = 'stock_transfer';
    case Payment = 'payment';
    case Refund = 'refund';

    public function label(): string
    {
        return match ($this) {
            self::Sales => 'Penjualan',
            self::Purchase => 'Pembelian',
            self::GoodsReceipt => 'Penerimaan Barang',
            self::Adjustment => 'Penyesuaian',
            self::StockOpname => 'Stok Opname',
            self::StockTransfer => 'Mutasi Stok',
            self::Payment => 'Pembayaran',
            self::Refund => 'Pengembalian Dana',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Sales => 'Jurnal terkait transaksi penjualan.',
            self::Purchase => 'Jurnal terkait transaksi pembelian.',
            self::GoodsReceipt => 'Jurnal penerimaan barang dari pemasok.',
            self::Adjustment => 'Jurnal penyesuaian persediaan atau nilai.',
            self::StockOpname => 'Jurnal hasil stok opname.',
            self::StockTransfer => 'Jurnal perpindahan antar stok.',
            self::Payment => 'Jurnal transaksi pembayaran.',
            self::Refund => 'Jurnal transaksi pengembalian dana.',
        };
    }

    public static function values(): array
    {
        return array_map(static fn(self $c): string => $c->value, self::cases());
    }
}
