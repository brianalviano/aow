<?php

declare(strict_types=1);

namespace App\Enums;

enum StockDocumentStatus: string
{
    case Draft = 'draft';
    case PendingHoApproval = 'pending_ho_approval';
    case RejectedByHo = 'rejected_by_ho';
    case Completed = 'completed';
    case Canceled = 'canceled';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::PendingHoApproval => 'Menunggu Persetujuan HO',
            self::RejectedByHo => 'Ditolak HO',
            self::Completed => 'Selesai',
            self::Canceled => 'Dibatalkan',
        };
    }

    public static function values(): array
    {
        return array_map(static fn(self $c): string => $c->value, self::cases());
    }
}
