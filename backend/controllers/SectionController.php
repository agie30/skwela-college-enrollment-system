<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/ActivityLogController.php';

class SectionController
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

    public function getAllSections(): array
    {
        // One shared section catalogue for Admin and Registrar.
        // Includes capacity, current enrollment, academic term, and posted schedule count.
        $sql = "
            SELECT
                s.section_id,
                s.academic_year_id,
                s.semester_id,
                s.course_id,
                s.year_level,
                s.section_name,
                s.capacity,
                s.status,
                s.created_at,
                c.course_code,
                c.course_name,
                ay.year_name,
                sem.semester_name,
                (
                    SELECT COUNT(*)
                    FROM enrollments e
                    WHERE e.section_id = s.section_id
                      AND e.status IN ('pending', 'under_review', 'approved')
                ) AS enrolled_count,
                (
                    SELECT COUNT(*)
                    FROM section_subjects ss
                    WHERE ss.section_id = s.section_id
                ) AS assigned_subject_count,
                (
                    SELECT COUNT(*)
                    FROM class_schedules cs
                    INNER JOIN section_subjects ss2
                        ON ss2.section_subject_id = cs.section_subject_id
                    WHERE ss2.section_id = s.section_id
                ) AS schedule_count
            FROM sections s
            INNER JOIN courses c ON c.course_id = s.course_id
            INNER JOIN academic_years ay ON ay.academic_year_id = s.academic_year_id
            INNER JOIN semesters sem ON sem.semester_id = s.semester_id
            ORDER BY c.course_code, s.year_level, s.section_name
        ";

        $rows = $this->pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
        foreach ($rows as &$row) {
            $row['capacity'] = (int) $row['capacity'];
            $row['enrolled_count'] = (int) $row['enrolled_count'];
            $row['remaining_slots'] = max(0, $row['capacity'] - $row['enrolled_count']);
            $row['assigned_subject_count'] = (int) $row['assigned_subject_count'];
            $row['schedule_count'] = (int) $row['schedule_count'];
            $row['is_full'] = $row['remaining_slots'] <= 0;
        }
        unset($row);

        return $rows;
    }

    public function addSection(array $data): array
    {
        $academicYearId = (int) ($data['academic_year_id'] ?? 0);
        $semesterId = (int) ($data['semester_id'] ?? 0);
        $courseId = (int) ($data['course_id'] ?? 0);
        $yearLevel = (int) ($data['year_level'] ?? 0);
        $sectionName = strtoupper(trim($data['section_name'] ?? ''));
        $capacity = (int) ($data['capacity'] ?? 45);
        $userId = (int) ($_SESSION['user_id'] ?? 0);

        if (
            $academicYearId < 1 ||
            $semesterId < 1 ||
            $courseId < 1 ||
            $yearLevel < 1 ||
            $yearLevel > 4 ||
            $sectionName === '' ||
            $capacity < 1 ||
            $capacity > 45 ||
            $userId < 1
        ) {
            return [
                'success' => false,
                'message' => 'Please provide valid section details. Capacity must not exceed 45.'
            ];
        }

        $stmt = $this->pdo->prepare("
            INSERT INTO sections
            (academic_year_id, semester_id, course_id, year_level, section_name, capacity, status)
            VALUES
            (:academic_year_id, :semester_id, :course_id, :year_level, :section_name, :capacity, 'active')
        ");

        $stmt->execute([
            'academic_year_id' => $academicYearId,
            'semester_id' => $semesterId,
            'course_id' => $courseId,
            'year_level' => $yearLevel,
            'section_name' => $sectionName,
            'capacity' => $capacity
        ]);

        $sectionId = (int) $this->pdo->lastInsertId();

        $this->logActivity(
            $userId,
            'Section Created',
            'Section ' . $sectionName . ' was created. Section ID: ' .
            $sectionId . ', course ID: ' . $courseId .
            ', year level: ' . $yearLevel . '.'
        );

        return [
            'success' => true,
            'message' => 'Section added successfully.'
        ];
    }

    public function updateSection(array $data): array
    {
        $sectionId = (int) ($data['section_id'] ?? 0);
        $academicYearId = (int) ($data['academic_year_id'] ?? 0);
        $semesterId = (int) ($data['semester_id'] ?? 0);
        $courseId = (int) ($data['course_id'] ?? 0);
        $yearLevel = (int) ($data['year_level'] ?? 0);
        $sectionName = strtoupper(trim($data['section_name'] ?? ''));
        $capacity = (int) ($data['capacity'] ?? 45);
        $userId = (int) ($_SESSION['user_id'] ?? 0);

        if (
            $sectionId < 1 ||
            $academicYearId < 1 ||
            $semesterId < 1 ||
            $courseId < 1 ||
            $yearLevel < 1 ||
            $yearLevel > 4 ||
            $sectionName === '' ||
            $capacity < 1 ||
            $capacity > 45 ||
            $userId < 1
        ) {
            return [
                'success' => false,
                'message' => 'Please provide valid section details. Capacity must not exceed 45.'
            ];
        }

        $stmt = $this->pdo->prepare("
            UPDATE sections
            SET academic_year_id = :academic_year_id,
                semester_id = :semester_id,
                course_id = :course_id,
                year_level = :year_level,
                section_name = :section_name,
                capacity = :capacity
            WHERE section_id = :section_id
        ");

        $stmt->execute([
            'academic_year_id' => $academicYearId,
            'semester_id' => $semesterId,
            'course_id' => $courseId,
            'year_level' => $yearLevel,
            'section_name' => $sectionName,
            'capacity' => $capacity,
            'section_id' => $sectionId
        ]);

        if ($stmt->rowCount() > 0) {
            $this->logActivity(
                $userId,
                'Section Updated',
                'Section ID ' . $sectionId . ' was updated. Section name: ' .
                $sectionName . ', capacity: ' . $capacity . '.'
            );
        }

        return [
            'success' => true,
            'message' => 'Section updated successfully.'
        ];
    }

    public function updateSectionStatus(array $data): array
    {
        $sectionId = (int) ($data['section_id'] ?? 0);
        $status = $data['status'] ?? '';
        $userId = (int) ($_SESSION['user_id'] ?? 0);

        if (
            $sectionId < 1 ||
            !in_array($status, ['active', 'inactive', 'closed'], true) ||
            $userId < 1
        ) {
            return [
                'success' => false,
                'message' => 'Invalid section status.'
            ];
        }

        $stmt = $this->pdo->prepare("
            UPDATE sections
            SET status = :status
            WHERE section_id = :section_id
        ");

        $stmt->execute([
            'status' => $status,
            'section_id' => $sectionId
        ]);

        if ($stmt->rowCount() > 0) {
            $this->logActivity(
                $userId,
                'Section Status Updated',
                'Section ID ' . $sectionId .
                ' status was changed to ' . $status . '.'
            );
        }

        return [
            'success' => true,
            'message' => 'Section status updated successfully.'
        ];
    }
}

$sectionController = new SectionController($pdo);
