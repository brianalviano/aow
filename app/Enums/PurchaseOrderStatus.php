<?php

namespace App\Enums;

enum PurchaseOrderStatus: string
{
    case Draft = 'draft';
    case PendingHoApproval = 'pending_ho_approval';
    case HeadOfficeApproved = 'head_office_approved';
    case RejectedByHo = 'rejected_by_ho';
    case PendingSupplierApproval = 'pending_supplier_approval';
    case SupplierConfirmed = 'supplier_confirmed';
    case RejectedBySupplier = 'rejected_by_supplier';
    case InDelivery = 'in_delivery';
    case PartiallyDelivered = 'partially_delivered';
    case Completed = 'completed';
    case Canceled = 'canceled';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::PendingHoApproval => 'Menunggu Persetujuan HO',
            self::HeadOfficeApproved => 'Disetujui Kantor Pusat',
            self::RejectedByHo => 'Ditolak HO',
            self::PendingSupplierApproval => 'Menunggu Persetujuan Pemasok',
            self::SupplierConfirmed => 'Dikonfirmasi Pemasok',
            self::RejectedBySupplier => 'Ditolak Pemasok',
            self::InDelivery => 'Dalam Pengiriman',
            self::PartiallyDelivered => 'Terkirim Sebagian',
            self::Completed => 'Selesai',
            self::Canceled => 'Dibatalkan',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Draft => 'PO disusun dan belum diajukan.',
            self::PendingHoApproval => 'PO menunggu persetujuan kantor pusat.',
            self::HeadOfficeApproved => 'PO telah disetujui kantor pusat.',
            self::RejectedByHo => 'PO ditolak oleh kantor pusat.',
            self::PendingSupplierApproval => 'PO menunggu persetujuan pemasok.',
            self::SupplierConfirmed => 'PO dikonfirmasi oleh pemasok.',
            self::RejectedBySupplier => 'PO ditolak oleh pemasok.',
            self::InDelivery => 'Barang sedang dikirim oleh pemasok.',
            self::PartiallyDelivered => 'Sebagian barang sudah diterima.',
            self::Completed => 'Seluruh barang diterima dan PO selesai.',
            self::Canceled => 'PO dibatalkan.',
        };
    }

    public static function values(): array
    {
        return array_map(static fn(self $c): string => $c->value, self::cases());
    }
}
