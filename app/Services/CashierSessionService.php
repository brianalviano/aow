<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\CashSessionStatus;
use App\Models\{CashierSession, PaymentAllocation, Sales};
use App\Traits\RetryableTransactionsTrait;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Layanan domain untuk mengelola sesi kasir (buka/tutup).
 *
 * - Membuka sesi kasir dengan saldo awal laci.
 * - Menutup sesi kasir dengan saldo kas aktual yang diterima.
 * - Menghitung ekspektasi saldo penutupan berdasarkan akumulasi pembayaran POS.
 *
 * Seluruh proses berjalan dalam transaksi dan dilindungi retry untuk deadlock.
 *
 * @author PJD
 */
final class CashierSessionService
{
    use RetryableTransactionsTrait;

    /**
     * Buka sesi kasir baru untuk user.
     *
     * @param string $userId
     * @param int $openingBalance
     * @return CashierSession
     * @throws \Throwable
     */
    public function openShift(string $userId, int $openingBalance): CashierSession
    {
        try {
            return $this->runWithRetry(function () use ($userId, $openingBalance) {
                return DB::transaction(function () use ($userId, $openingBalance) {
                    $active = CashierSession::query()
                        ->where('user_id', $userId)
                        ->whereNull('closed_at')
                        ->where('status', CashSessionStatus::Open->value)
                        ->latest('opened_at')
                        ->lockForUpdate()
                        ->first();
                    if ($active) {
                        return $active;
                    }
                    $closedToday = CashierSession::query()
                        ->where('user_id', $userId)
                        ->whereNotNull('closed_at')
                        ->whereDate('closed_at', now()->toDateString())
                        ->latest('closed_at')
                        ->lockForUpdate()
                        ->first();
                    if ($closedToday) {
                        return $closedToday;
                    }

                    $session = new CashierSession();
                    $session->user_id = $userId;
                    $session->opened_at = now()->toDateTimeString();
                    $session->starting_cash = (int) $openingBalance;
                    $session->expected_cash = (int) $openingBalance;
                    $session->actual_cash = 0;
                    $session->variance = 0;
                    $session->status = CashSessionStatus::Open->value;
                    $session->note = null;
                    $session->save();

                    return $session;
                }, 5);
            }, 3);
        } catch (\Throwable $e) {
            Log::error('Gagal membuka sesi kasir', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => $userId,
                'opening_balance' => $openingBalance,
            ]);
            throw $e;
        }
    }

    /**
     * Tutup sesi kasir aktif milik user.
     *
     * @param string $userId
     * @param string $shiftId
     * @param int $actualClosingBalance
     * @return CashierSession
     * @throws \Throwable
     */
    public function closeShift(string $userId, string $shiftId, int $actualClosingBalance): CashierSession
    {
        try {
            return $this->runWithRetry(function () use ($userId, $shiftId, $actualClosingBalance) {
                return DB::transaction(function () use ($userId, $shiftId, $actualClosingBalance) {
                    /** @var CashierSession|null $shift */
                    $shift = CashierSession::query()
                        ->where('id', $shiftId)
                        ->lockForUpdate()
                        ->first();
                    if (!$shift) {
                        throw new \RuntimeException('Sesi kasir tidak ditemukan.');
                    }
                    if ((string) $shift->user_id !== $userId) {
                        throw new \RuntimeException('Tidak berhak menutup sesi kasir pengguna lain.');
                    }
                    if ($shift->closed_at !== null || $shift->status === CashSessionStatus::Closed) {
                        return $shift;
                    }

                    $totalCashIn = (int) PaymentAllocation::query()
                        ->join('sales', 'payment_allocations.referencable_id', '=', 'sales.id')
                        ->where('payment_allocations.referencable_type', Sales::class)
                        ->where('sales.cashier_session_id', $shiftId)
                        ->whereNull('payment_allocations.voided_at')
                        ->toBase()
                        ->sum('payment_allocations.amount');

                    $expected = (int) $shift->starting_cash + (int) $totalCashIn;
                    $shift->expected_cash = $expected;
                    $shift->actual_cash = (int) $actualClosingBalance;
                    $shift->variance = (int) $shift->actual_cash - (int) $shift->expected_cash;
                    $shift->closed_at = now()->toDateTimeString();
                    $shift->status = CashSessionStatus::Closed->value;
                    $shift->save();

                    return $shift;
                }, 5);
            }, 3);
        } catch (\Throwable $e) {
            Log::error('Gagal menutup sesi kasir', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => $userId,
                'shift_id' => $shiftId,
                'actual_closing_balance' => $actualClosingBalance,
            ]);
            throw $e;
        }
    }
}
