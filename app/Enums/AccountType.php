<?php

namespace App\Enums;

enum AccountType: string
{
    case Asset = 'asset';
    case Liability = 'liability';
    case Equity = 'equity';
    case Revenue = 'revenue';
    case Expense = 'expense';

    public function label(): string
    {
        return match ($this) {
            self::Asset => 'Aset',
            self::Liability => 'Liabilitas',
            self::Equity => 'Ekuitas',
            self::Revenue => 'Pendapatan',
            self::Expense => 'Beban',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Asset => 'Akun untuk sumber daya yang dimiliki.',
            self::Liability => 'Akun untuk kewajiban/utang.',
            self::Equity => 'Akun untuk modal pemilik.',
            self::Revenue => 'Akun untuk pendapatan usaha.',
            self::Expense => 'Akun untuk beban/biaya.',
        };
    }

    public static function values(): array
    {
        return array_map(static fn(self $c): string => $c->value, self::cases());
    }
}
