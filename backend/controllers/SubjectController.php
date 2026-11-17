<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/ActivityLogController.php';

class SubjectController
{
    private PDO $pdo;
    private ActivityLogController $activityLogger;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
        $this->activityLogger = new ActivityLogController($pdo);
    }

    private function logActivity(
        int $userId,
        string $activity,
        string $details
    ): void {
        try {
            $this->activityLogger->recordActivity(
                $userId,
                $activity,
                $details
            );
        } catch (PDOException $e) {
            error_log($e->getMessage());
        }
    }

    public function getAllSubjects(): array
    {
        $sql = "
            SELECT
                s.*,
                c.course_code,
                c.course_name,
                sem.semester_name
            FROM subjects s
            INNER JOIN courses c ON c.course_id = s.course_id
            INNER JOIN semesters sem ON sem.semester_id = s.semester_id
            ORDER BY c.course_code, s.year_level, s.subject_code
        ";

        return $this->pdo->query($sql)->fetchAll();
    }

    public function addSubject(array $data): array
    {
        $courseId = (int) ($data['course_id'] ?? 0);
        $semesterId = (int) ($data['semester_id'] ?? 0);
        $yearLevel = (int) ($data['year_level'] ?? 0);
        $subjectCode = strtoupper(trim($data['subject_code'] ?? ''));
        $subjectName = trim($data['subject_name'] ?? '');
        $units = (float) ($data['units'] ?? 3);
        $description = trim($data['description'] ?? '');
        $userId = (int) ($_SESSION['user_id'] ?? 0);

        if (
            $courseId < 1 ||
            $semesterId < 1 ||
            $yearLevel < 1 ||
            $yearLevel > 4 ||
            $subjectCode === '' ||
            $subjectName === '' ||
            $units <= 0 ||
            $userId < 1
        ) {
            return [
                'success' => false,
                'message' => 'Please provide valid subject details.'
            ];
        }

        $stmt = $this->pdo->prepare("
            INSERT INTO subjects
            (course_id, semester_id, year_level, subject_code, subject_name, units, description, status)
            VALUES
            (:course_id, :semester_id, :year_level, :subject_code, :subject_name, :units, :description, 'active')
        ");

        $stmt->execute([
            'course_id' => $courseId,
            'semester_id' => $semesterId,
            'year_level' => $yearLevel,
            'subject_code' => $subjectCode,
            'subject_name' => $subjectName,
            'units' => $units,
            'description' => $description
        ]);

        $subjectId = (int) $this->pdo->lastInsertId();

        $this->logActivity(
            $userId,
            'Subject Created',
            'Subject ' . $subjectCode . ' - ' . $subjectName .
            ' was created. Subject ID: ' . $subjectId . '.'
        );

        return [
            'success' => true,
            'message' => 'Subject added successfully.'
        ];
    }

    public function updateSubject(array $data): array
    {
        $subjectId = (int) ($data['subject_id'] ?? 0);
        $courseId = (int) ($data['course_id'] ?? 0);
        $semesterId = (int) ($data['semester_id'] ?? 0);
        $yearLevel = (int) ($data['year_level'] ?? 0);
        $subjectCode = strtoupper(trim($data['subject_code'] ?? ''));
        $subjectName = trim($data['subject_name'] ?? '');
        $units = (float) ($data['units'] ?? 0);
        $description = trim($data['description'] ?? '');
        $userId = (int) ($_SESSION['user_id'] ?? 0);

        if (
            $subjectId < 1 ||
            $courseId < 1 ||
            $semesterId < 1 ||
            $yearLevel < 1 ||
            $yearLevel > 4 ||
            $subjectCode === '' ||
            $subjectName === '' ||
            $units <= 0 ||
            $userId < 1
        ) {
            return [
                'success' => false,
                'message' => 'Please provide valid subject details.'
            ];
        }

        $stmt = $this->pdo->prepare("
            UPDATE subjects
            SET course_id = :course_id,
                semester_id = :semester_id,
                year_level = :year_level,
                subject_code = :subject_code,
                subject_name = :subject_name,
                units = :units,
                description = :description
            WHERE subject_id = :subject_id
        ");

        $stmt->execute([
            'course_id' => $courseId,
            'semester_id' => $semesterId,
            'year_level' => $yearLevel,
            'subject_code' => $subjectCode,
            'subject_name' => $subjectName,
            'units' => $units,
            'description' => $description,
            'subject_id' => $subjectId
        ]);

        if ($stmt->rowCount() > 0) {
            $this->logActivity(
                $userId,
                'Subject Updated',
                'Subject ID ' . $subjectId . ' was updated. New subject code: ' .
                $subjectCode . ', subject name: ' . $subjectName . '.'
            );
        }

        return [
            'success' => true,
            'message' => 'Subject updated successfully.'
        ];
    }

    public function updateSubjectStatus(array $data): array
    {
        $subjectId = (int) ($data['subject_id'] ?? 0);
        $status = $data['status'] ?? '';
        $userId = (int) ($_SESSION['user_id'] ?? 0);

        if (
            $subjectId < 1 ||
            !in_array($status, ['active', 'inactive'], true) ||
            $userId < 1
        ) {
            return [
                'success' => false,
                'message' => 'Invalid subject status.'
            ];
        }

        $stmt = $this->pdo->prepare("
            UPDATE subjects
            SET status = :status
            WHERE subject_id = :subject_id
        ");

        $stmt->execute([
            'status' => $status,
            'subject_id' => $subjectId
        ]);

        if ($stmt->rowCount() > 0) {
            $this->logActivity(
                $userId,
                'Subject Status Updated',
                'Subject ID ' . $subjectId . ' status was changed to ' .
                $status . '.'
            );
        }

        return [
            'success' => true,
            'message' => 'Subject status updated successfully.'
        ];
    }
}

$subjectController = new SubjectController($pdo);
