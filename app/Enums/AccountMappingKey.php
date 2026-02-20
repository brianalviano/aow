<?php

namespace App\Enums;

enum AccountMappingKey: string
{
    case Inventory = 'inventory';
    case Cogs = 'cogs';
    case SalesRevenue = 'sales_revenue';
    case SalesDiscount = 'sales_discount';
    case SalesRounding = 'sales_rounding';
    case SalesShipping = 'sales_shipping';
    case PpnOutput = 'ppn_output';
    case PpnInput = 'ppn_input';
    case GoodsReceiptAccrual = 'goods_receipt_accrual';
    case AccountsReceivable = 'accounts_receivable';
    case AccountsPayable = 'accounts_payable';

    public function label(): string
    {
        return match ($this) {
            self::Inventory => 'Persediaan',
            self::Cogs => 'Harga Pokok Penjualan',
            self::SalesRevenue => 'Pendapatan Penjualan',
            self::SalesDiscount => 'Diskon Penjualan',
            self::SalesRounding => 'Pembulatan Penjualan',
            self::SalesShipping => 'Pengiriman Penjualan',
            self::PpnOutput => 'PPN Keluaran',
            self::PpnInput => 'PPN Masukan',
            self::GoodsReceiptAccrual => 'Akrual Penerimaan Barang',
            self::AccountsReceivable => 'Piutang Usaha',
            self::AccountsPayable => 'Utang Usaha',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Inventory => 'Akun untuk nilai persediaan barang.',
            self::Cogs => 'Akun biaya pokok atas penjualan.',
            self::SalesRevenue => 'Akun pendapatan dari penjualan.',
            self::SalesDiscount => 'Akun potongan/diskon penjualan.',
            self::SalesRounding => 'Akun pembulatan nilai transaksi penjualan.',
            self::SalesShipping => 'Akun biaya pengiriman penjualan.',
            self::PpnOutput => 'Akun pajak keluaran (penjualan).',
            self::PpnInput => 'Akun pajak masukan (pembelian).',
            self::GoodsReceiptAccrual => 'Akun akrual penerimaan barang.',
            self::AccountsReceivable => 'Akun piutang dagang pelanggan.',
            self::AccountsPayable => 'Akun utang dagang pemasok.',
        };
    }

    public static function values(): array
    {
        return array_map(static fn(self $c): string => $c->value, self::cases());
    }
}
