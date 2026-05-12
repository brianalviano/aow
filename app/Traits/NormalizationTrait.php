<?php

namespace App\Traits;

use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

trait NormalizationTrait
{
    /**
     * Normalize date string to Y-m-d format.
     *
     * @param  mixed  $value
     */
    private function normalizeDate($value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }
        if (is_numeric($value)) {
            try {
                $dt = ExcelDate::excelToDateTimeObject((float) $value);

                return Carbon::instance($dt)->toDateString();
            } catch (\Exception $e) {
                return null;
            }
        }
        $s = trim((string) $value);
        $s = str_replace(['/', '.'], '-', $s);
        $formats = [
            'Y-m-d',
            'd-m-Y',
            'm-d-Y',
            'd-m-y',
            'm-d-y',
            'Ymd',
            'dmY',
            'mdY',
        ];
        foreach ($formats as $fmt) {
            try {
                $d = Carbon::createFromFormat($fmt, $s);

                return $d->toDateString();
            } catch (\Exception $e) {
            }
        }
        try {
            return Carbon::parse($s)->toDateString();
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Normalize gender string to 'male' or 'female'.
     *
     * @param  mixed  $value
     */
    private function normalizeGender(?string $value): ?string
    {
        if (! $value) {
            return null;
        }
        $v = preg_replace('/[^a-z]/', '', Str::lower(trim($value)));
        $female = ['p', 'perempuan', 'wanita', 'female', 'f', 'girl', 'cewek', 'cewerk', 'woman'];
        $male = ['l', 'lakilaki', 'pria', 'cowok', 'male', 'm', 'boy', 'man'];
        if (in_array($v, $female, true)) {
            return 'female';
        }
        if (in_array($v, $male, true)) {
            return 'male';
        }

        return null;
    }

    /**
     * Normalize phone number to start with '0'.
     */
    public function normalizePhone(?string $input): string
    {
        if ($input === null || $input === '') {
            return '';
        }

        $digits = preg_replace('/\D+/', '', $input);
        if ($digits === null || $digits === '') {
            return '';
        }
        if (str_starts_with($digits, '62')) {
            return '0'.substr($digits, 2);
        }
        if (str_starts_with($digits, '0')) {
            return $digits;
        }

        return '0'.$digits;
    }
}
