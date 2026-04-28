<?php

namespace App\Support;

use App\Models\PortalUser;
use App\Notifications\PortalNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Throwable;

/**
 * Inserts into notifications on the default DB connection (same as phpMyAdmin / .env DB).
 */
final class PortalDatabaseNotify
{
    public static function send(PortalUser $notifiable, PortalNotification $notification): void
    {
        $conn = DB::getDefaultConnection();

        if (! Schema::connection($conn)->hasTable('notifications')) {
            Log::warning('PortalDatabaseNotify: notifications table missing', ['connection' => $conn]);

            return;
        }

        $notifiableId = (int) $notifiable->getKey();
        if ($notifiableId < 1) {
            return;
        }

        $payload = [
            'title' => $notification->title,
            'message' => $notification->message,
            'type' => $notification->type,
            'meta' => $notification->meta,
        ];

        $flags = JSON_UNESCAPED_UNICODE;
        if (defined('JSON_INVALID_UTF8_SUBSTITUTE')) {
            $flags |= JSON_INVALID_UTF8_SUBSTITUTE;
        }
        $dataJson = json_encode($payload, $flags) ?: '{}';

        $id = (string) Str::uuid();
        $nowStr = now()->format('Y-m-d H:i:s');

        $row = [
            'id' => $id,
            'type' => PortalNotification::class,
            'notifiable_type' => $notifiable->getMorphClass(),
            'notifiable_id' => $notifiableId,
            'data' => $dataJson,
            'read_at' => null,
            'created_at' => $nowStr,
            'updated_at' => $nowStr,
        ];

        if (Schema::connection($conn)->hasColumn('notifications', 'deleted_at')) {
            $row['deleted_at'] = null;
        }

        try {
            DB::connection($conn)->table('notifications')->insert($row);
        } catch (Throwable $e) {
            Log::error('PortalDatabaseNotify: insert failed', [
                'connection' => $conn,
                'notifiable_id' => $notifiableId,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}
