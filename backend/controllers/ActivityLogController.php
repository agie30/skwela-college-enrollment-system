<?php

require_once __DIR__ . '/../config/database.php';

class ActivityLogController
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function recordActivity(
        ?int $userId,
        string $activity,
        ?string $details = null
    ): bool {
        if (trim($activity) === '') {
            return false;
        }

        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? null;

        $stmt = $this->pdo->prepare("
            INSERT INTO activity_logs
            (user_id, activity, details, ip_address)
            VALUES
            (:user_id, :activity, :details, :ip_address)
        ");

        return $stmt->execute([
            'user_id' => $userId,
            'activity' => trim($activity),
            'details' => $details,
            'ip_address' => $ipAddress
        ]);
    }

    public function getAllActivities(): array
    {
        $stmt = $this->pdo->query("
            SELECT
                a.*,
                u.full_name,
                u.email
            FROM activity_logs a
            LEFT JOIN users u ON u.user_id = a.user_id
            ORDER BY a.created_at DESC
        ");

        return $stmt->fetchAll();
    }

    public function getUserActivities(int $userId): array
    {
        $stmt = $this->pdo->prepare("
            SELECT *
            FROM activity_logs
            WHERE user_id = :user_id
            ORDER BY created_at DESC
        ");

        $stmt->execute([
            'user_id' => $userId
        ]);

        return $stmt->fetchAll();
    }
}

$activityLogController = new ActivityLogController($pdo);
