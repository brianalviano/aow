<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Contracts\Auth\Authenticatable;

class NotificationService
{
    /**
     * Get notification statistics for a user.
     *
     * @param Authenticatable|null $user
     * @return array{total:int,unread:int,read:int}
     */
    public function getStatsForUser(?Authenticatable $user): array
    {
        if (!$user) {
            return ['total' => 0, 'unread' => 0, 'read' => 0];
        }

        $query = DatabaseNotification::query()
            ->where('notifiable_type', get_class($user))
            ->where('notifiable_id', $user->getAuthIdentifier());

        $total = (int) $query->count();
        $unread = (int) $query->whereNull('read_at')->count();
        $read = $total - $unread;

        return ['total' => $total, 'unread' => $unread, 'read' => $read];
    }

    /**
     * Paginate notifications for a user with optional status filter.
     *
     * @param Authenticatable|null $user
     * @param int $perPage
     * @param string|null $status unread|read|null
     * @return LengthAwarePaginator
     */
    public function paginateForUser(?Authenticatable $user, int $perPage = 10, ?string $status = null): LengthAwarePaginator
    {
        $query = DatabaseNotification::query()
            ->where('notifiable_type', $user ? get_class($user) : '')
            ->where('notifiable_id', $user ? $user->getAuthIdentifier() : '')
            ->orderByDesc('created_at');

        $s = strtolower((string) ($status ?? ''));
        if ($s === 'unread') {
            $query->whereNull('read_at');
        } elseif ($s === 'read') {
            $query->whereNotNull('read_at');
        }

        return $query->paginate($perPage);
    }

    public function listLatestForUser(?Authenticatable $user, int $limit = 5)
    {
        return DatabaseNotification::query()
            ->where('notifiable_type', $user ? get_class($user) : '')
            ->where('notifiable_id', $user ? $user->getAuthIdentifier() : '')
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get();
    }

    /**
     * Mark a notification as read for the given user.
     *
     * @param Authenticatable|null $user
     * @param string $notificationId
     * @return void
     * @throws \Throwable
     */
    public function markAsRead(?Authenticatable $user, string $notificationId): void
    {
        try {
            DB::transaction(function () use ($user, $notificationId): void {
                $notification = DatabaseNotification::query()
                    ->where('id', $notificationId)
                    ->where('notifiable_type', $user ? get_class($user) : '')
                    ->where('notifiable_id', $user ? $user->getAuthIdentifier() : '')
                    ->firstOrFail();

                if ($notification->read_at === null) {
                    $notification->forceFill(['read_at' => Carbon::now()])->save();
                }
            });
        } catch (\Throwable $e) {
            Log::error('Failed to mark notification as read', [
                'user_id' => $user ? $user->getAuthIdentifier() : null,
                'notification_id' => $notificationId,
                'exception' => $e,
            ]);
            throw $e;
        }
    }

    /**
     * Mark all notifications as read for the given user.
     *
     * @param Authenticatable|null $user
     * @return void
     * @throws \Throwable
     */
    public function markAllAsRead(?Authenticatable $user): void
    {
        try {
            DB::transaction(function () use ($user): void {
                DatabaseNotification::query()
                    ->where('notifiable_type', $user ? get_class($user) : '')
                    ->where('notifiable_id', $user ? $user->getAuthIdentifier() : '')
                    ->whereNull('read_at')
                    ->update(['read_at' => Carbon::now()]);
            });
        } catch (\Throwable $e) {
            Log::error('Failed to mark all notifications as read', [
                'user_id' => $user ? $user->getAuthIdentifier() : null,
                'exception' => $e,
            ]);
            throw $e;
        }
    }
}
