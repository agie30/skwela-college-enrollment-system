<?php

require_once __DIR__ . '/../config/database.php';

class EnrollmentHistoryController
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function recordStatusChange(
        int $enrollmentId,
        int $updatedBy,
        ?string $oldStatus,
        string $newStatus,
        ?string $remarks = null
    ): bool {
        $stmt = $this->pdo->prepare("
            INSERT INTO enrollment_status_history
            (
                enrollment_id,
                updated_by,
                old_status,
                new_status,
                remarks
            )
            VALUES
            (
                :enrollment_id,
                :updated_by,
                :old_status,
                :new_status,
                :remarks
            )
        ");

        return $stmt->execute([
            'enrollment_id' => $enrollmentId,
            'updated_by' => $updatedBy,
            'old_status' => $oldStatus,
            'new_status' => $newStatus,
            'remarks' => $remarks
        ]);
    }

    public function getEnrollmentHistory(int $enrollmentId): array
    {
        $stmt = $this->pdo->prepare("
            SELECT
                h.*,
                u.full_name AS updated_by_name
            FROM enrollment_status_history h
            INNER JOIN users u ON u.user_id = h.updated_by
            WHERE h.enrollment_id = :enrollment_id
            ORDER BY h.updated_at DESC
        ");

        $stmt->execute([
            'enrollment_id' => $enrollmentId
        ]);

        return $stmt->fetchAll();
    }

    public function getAllHistory(): array
    {
        $sql = "
            SELECT
                h.*,
                u.full_name AS updated_by_name,
                e.student_id
            FROM enrollment_status_history h
            INNER JOIN users u ON u.user_id = h.updated_by
            INNER JOIN enrollments e ON e.enrollment_id = h.enrollment_id
            ORDER BY h.updated_at DESC
        ";

        return $this->pdo->query($sql)->fetchAll();
    }
}

$enrollmentHistoryController = new EnrollmentHistoryController($pdo);
