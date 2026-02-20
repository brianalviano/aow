<?php

declare(strict_types=1);

namespace App\Traits;

use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Log;

/**
 * RetryableTransactionsTrait.
 *
 * Menyediakan bounded retry untuk unit kerja yang rentan deadlock/serialization
 * serta utilitas pendeteksian error yang aman untuk di-retry.
 */
trait RetryableTransactionsTrait
{
    /**
     * Jalankan fungsi dengan bounded retry untuk deadlock/serialization failures.
     *
     * Retry dilakukan maksimal $maxAttempts, dengan backoff + jitter menggunakan usleep.
     * Hanya error yang teridentifikasi sebagai retryable yang diulang.
     *
     * @param callable $fn Unit kerja yang akan dijalankan.
     * @param int $maxAttempts Jumlah percobaan maksimal.
     * @return mixed Hasil eksekusi fungsi.
     *
     * @throws QueryException Bila tetap gagal atau error tidak dapat di-retry.
     */
    private function runWithRetry(callable $fn, int $maxAttempts = 3)
    {
        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            try {
                return $fn();
            } catch (QueryException $e) {
                if ($attempt >= $maxAttempts || !$this->isRetryable($e)) {
                    $info = $e->errorInfo ?? [];
                    $sqlstate = $info[0] ?? null;
                    $driverCode = $info[1] ?? null;
                    Log::error('db_transaction_error', [
                        'service' => get_class($this),
                        'action' => 'db_transaction',
                        'attempt' => $attempt,
                        'max_attempts' => $maxAttempts,
                        'sqlstate' => $sqlstate,
                        'driver_code' => $driverCode,
                        'exception' => get_class($e),
                        'message' => $e->getMessage(),
                    ]);
                    throw $e;
                }
                $delay = random_int(100000, 400000) * $attempt;
                usleep($delay);
            }
        }
        return null;
    }

    /**
     * Deteksi apakah QueryException dapat di-retry (deadlock/serialization/timeouts).
     *
     * @param QueryException $e Exception database.
     * @return bool True bila dapat di-retry.
     */
    private function isRetryable(QueryException $e): bool
    {
        $info = $e->errorInfo ?? [];
        $sqlstate = $info[0] ?? null;
        $driverCode = $info[1] ?? null;
        $msg = strtolower($e->getMessage());
        if (in_array($sqlstate, ['40001', '40p01'], true)) {
            return true;
        }
        if (is_int($driverCode) && in_array($driverCode, [1213, 1205], true)) {
            return true;
        }
        if (str_contains($msg, 'deadlock') || str_contains($msg, 'serialization') || str_contains($msg, 'lock wait timeout')) {
            return true;
        }
        return false;
    }
}
