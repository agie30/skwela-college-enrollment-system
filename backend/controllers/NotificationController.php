<?php

require_once __DIR__ . '/../config/database.php';

class NotificationController
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function createNotification(
        int $userId,
        string $title,
        string $message
    ): bool {
        if ($userId < 1 || trim($title) === '' || trim($message) === '') {
            return false;
        }

        $stmt = $this->pdo->prepare("
            INSERT INTO notifications
            (user_id, title, message, is_read)
            VALUES
            (:user_id, :title, :message, 0)
        ");

        return $stmt->execute([
            'user_id' => $userId,
            'title' => trim($title),
            'message' => trim($message)
        ]);
    }

    public function getUserNotifications(int $userId): array
    {
        $stmt = $this->pdo->prepare("
            SELECT *
            FROM notifications
            WHERE user_id = :user_id
            ORDER BY created_at DESC
        ");

        $stmt->execute([
            'user_id' => $userId
        ]);

        return $stmt->fetchAll();
    }

    public function getUnreadCount(int $userId): int
    {
        $stmt = $this->pdo->prepare("
            SELECT COUNT(*)
            FROM notifications
            WHERE user_id = :user_id
            AND is_read = 0
        ");

        $stmt->execute([
            'user_id' => $userId
        ]);

        return (int) $stmt->fetchColumn();
    }

    public function markAsRead(int $notificationId, int $userId): array
    {
        if ($notificationId < 1 || $userId < 1) {
            return [
                'success' => false,
                'message' => 'Invalid notification details.'
            ];
        }

        $stmt = $this->pdo->prepare("
            UPDATE notifications
            SET is_read = 1
            WHERE notification_id = :notification_id
            AND user_id = :user_id
        ");

        $stmt->execute([
            'notification_id' => $notificationId,
            'user_id' => $userId
        ]);

        if ($stmt->rowCount() === 0) {
            return [
                'success' => false,
                'message' => 'Notification not found or already marked as read.'
            ];
        }

        return [
            'success' => true,
            'message' => 'Notification marked as read.'
        ];
    }

    public function markAllAsRead(int $userId): array
    {
        $stmt = $this->pdo->prepare("
            UPDATE notifications
            SET is_read = 1
            WHERE user_id = :user_id
            AND is_read = 0
        ");

        $stmt->execute([
            'user_id' => $userId
        ]);

        return [
            'success' => true,
            'message' => 'All notifications marked as read.'
        ];
    }
}

$notificationController = new NotificationController($pdo);
