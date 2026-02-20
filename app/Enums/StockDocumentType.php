<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Jenis surat stok: IN (stok masuk) atau OUT (stok keluar).
 *
 * @internal Digunakan oleh StockDocument dan service inventory.
 */
enum StockDocumentType: string
{
    case In  = 'in';
    case Out = 'out';

    public function label(): string
    {
        return match ($this) {
            self::In  => 'Masuk',
            self::Out => 'Keluar',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::In  => 'Surat stok masuk.',
            self::Out => 'Surat stok keluar.',
        };
    }

    public static function values(): array
    {
        return array_map(static fn(self $c): string => $c->value, self::cases());
    }
}
