<?php

namespace App\Enums;

enum SupplierCreditNoteStatus: string
{
    case Draft = 'draft';
    case Posted = 'posted';
    case FullyApplied = 'fully_applied';
    case Canceled = 'canceled';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Posted => 'Tercatat',
            self::FullyApplied => 'Terpakai Penuh',
            self::Canceled => 'Dibatalkan',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Draft => 'Nota kredit pemasok belum dibukukan.',
            self::Posted => 'Nota kredit pemasok telah dibukukan.',
            self::FullyApplied => 'Nota kredit telah diaplikasikan sepenuhnya.',
            self::Canceled => 'Nota kredit dibatalkan.',
        };
    }

    public static function values(): array
    {
        return array_map(static fn(self $c): string => $c->value, self::cases());
    }
}
