<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/ActivityLogController.php';

class CourseController
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

    public function getAllCourses(): array
    {
        $stmt = $this->pdo->query(
            "SELECT * FROM courses ORDER BY course_id ASC"
        );

        return $stmt->fetchAll();
    }

    public function addCourse(array $data): array
    {
        $courseCode = strtoupper(trim($data['course_code'] ?? ''));
        $courseName = trim($data['course_name'] ?? '');
        $description = trim($data['description'] ?? '');
        $durationYears = (int) ($data['duration_years'] ?? 0);
        $userId = (int) ($_SESSION['user_id'] ?? 0);

        if (
            $courseCode === '' ||
            $courseName === '' ||
            $durationYears < 1 ||
            $userId < 1
        ) {
            return [
                'success' => false,
                'message' => 'Please provide valid course details.'
            ];
        }

        $stmt = $this->pdo->prepare(
            "INSERT INTO courses
            (course_code, course_name, description, duration_years, status)
            VALUES
            (:course_code, :course_name, :description, :duration_years, 'active')"
        );

        $stmt->execute([
            'course_code' => $courseCode,
            'course_name' => $courseName,
            'description' => $description,
            'duration_years' => $durationYears
        ]);

        $courseId = (int) $this->pdo->lastInsertId();

        $this->logActivity(
            $userId,
            'Course Created',
            'Course ' . $courseCode . ' - ' . $courseName .
            ' was created. Course ID: ' . $courseId . '.'
        );

        return [
            'success' => true,
            'message' => 'Course added successfully.'
        ];
    }

    public function updateCourse(array $data): array
    {
        $courseId = (int) ($data['course_id'] ?? 0);
        $courseCode = strtoupper(trim($data['course_code'] ?? ''));
        $courseName = trim($data['course_name'] ?? '');
        $description = trim($data['description'] ?? '');
        $durationYears = (int) ($data['duration_years'] ?? 0);
        $userId = (int) ($_SESSION['user_id'] ?? 0);

        if (
            $courseId < 1 ||
            $courseCode === '' ||
            $courseName === '' ||
            $durationYears < 1 ||
            $userId < 1
        ) {
            return [
                'success' => false,
                'message' => 'Please provide valid course details.'
            ];
        }

        $stmt = $this->pdo->prepare(
            "UPDATE courses
            SET course_code = :course_code,
                course_name = :course_name,
                description = :description,
                duration_years = :duration_years
            WHERE course_id = :course_id"
        );

        $stmt->execute([
            'course_code' => $courseCode,
            'course_name' => $courseName,
            'description' => $description,
            'duration_years' => $durationYears,
            'course_id' => $courseId
        ]);

        if ($stmt->rowCount() > 0) {
            $this->logActivity(
                $userId,
                'Course Updated',
                'Course ID ' . $courseId . ' was updated. New course code: ' .
                $courseCode . ', course name: ' . $courseName . '.'
            );
        }

        return [
            'success' => true,
            'message' => 'Course updated successfully.'
        ];
    }

    public function updateCourseStatus(array $data): array
    {
        $courseId = (int) ($data['course_id'] ?? 0);
        $status = $data['status'] ?? '';
        $userId = (int) ($_SESSION['user_id'] ?? 0);

        if (
            $courseId < 1 ||
            !in_array($status, ['active', 'inactive'], true) ||
            $userId < 1
        ) {
            return [
                'success' => false,
                'message' => 'Invalid course status.'
            ];
        }

        $stmt = $this->pdo->prepare(
            "UPDATE courses SET status = :status WHERE course_id = :course_id"
        );

        $stmt->execute([
            'status' => $status,
            'course_id' => $courseId
        ]);

        if ($stmt->rowCount() > 0) {
            $this->logActivity(
                $userId,
                'Course Status Updated',
                'Course ID ' . $courseId . ' status was changed to ' .
                $status . '.'
            );
        }

        return [
            'success' => true,
            'message' => 'Course status updated successfully.'
        ];
    }
}

$courseController = new CourseController($pdo);
