<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class Notification
{
    public static function create(int $userId, string $type, string $title, string $message, ?string $link = null): void
    {
        if ($userId <= 0) {
            return;
        }

        try {
            $db = Database::pdo();
            $stmt = $db->prepare('
                INSERT INTO notifications (id_user, type, titre, message, lien_url, lu, date_creation)
                VALUES (?, ?, ?, ?, ?, 0, NOW())
            ');
            $stmt->execute([$userId, $type, $title, $message, $link]);
        } catch (\Throwable $e) {
            error_log('[Notification::create] ' . $e->getMessage());
        }
    }

    public static function createForUsers(array $userIds, string $type, string $title, string $message, ?string $link = null): void
    {
        foreach (array_unique(array_map('intval', $userIds)) as $userId) {
            self::create($userId, $type, $title, $message, $link);
        }
    }

    public static function unreadCount(int $userId): int
    {
        try {
            $db = Database::pdo();
            $stmt = $db->prepare('SELECT COUNT(*) FROM notifications WHERE id_user = ? AND lu = 0');
            $stmt->execute([$userId]);
            return (int)$stmt->fetchColumn();
        } catch (\Throwable $e) {
            return 0;
        }
    }

    public static function listForUser(int $userId): array
    {
        try {
            $db = Database::pdo();
            $stmt = $db->prepare('SELECT * FROM notifications WHERE id_user = ? ORDER BY date_creation DESC LIMIT 100');
            $stmt->execute([$userId]);
            return $stmt->fetchAll();
        } catch (\Throwable $e) {
            return [];
        }
    }

    public static function markAllRead(int $userId): void
    {
        self::markAllReadForUser($userId);
    }

    public static function markAllReadForUser(int $userId): void
    {
        try {
            $db = Database::pdo();
            $stmt = $db->prepare('UPDATE notifications SET lu = 1 WHERE id_user = ? AND lu = 0');
            $stmt->execute([$userId]);
        } catch (\Throwable $e) {
            error_log('[Notification::markAllRead] ' . $e->getMessage());
        }
    }
}
