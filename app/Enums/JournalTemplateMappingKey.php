<?php

namespace App\Enums;

enum JournalTemplateMappingKey: string
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
    case Other = 'other';

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
            self::Other => 'Lainnya',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Inventory => 'Mapping ke akun persediaan.',
            self::Cogs => 'Mapping ke akun HPP.',
            self::SalesRevenue => 'Mapping ke akun pendapatan penjualan.',
            self::SalesDiscount => 'Mapping ke akun diskon penjualan.',
            self::SalesRounding => 'Mapping ke akun pembulatan penjualan.',
            self::SalesShipping => 'Mapping ke akun biaya pengiriman penjualan.',
            self::PpnOutput => 'Mapping ke akun PPN keluaran.',
            self::PpnInput => 'Mapping ke akun PPN masukan.',
            self::GoodsReceiptAccrual => 'Mapping ke akun akrual penerimaan barang.',
            self::AccountsReceivable => 'Mapping ke akun piutang dagang.',
            self::AccountsPayable => 'Mapping ke akun utang dagang.',
            self::Other => 'Mapping ke akun lainnya.',
        };
    }

    public static function values(): array
    {
        return array_map(static fn(self $c): string => $c->value, self::cases());
    }
}
