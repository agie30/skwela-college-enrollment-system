
<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/ActivityLogController.php';

class EnrollmentController
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
        } catch (Throwable $e) {
            error_log($e->getMessage());
        }
    }

    public function getAvailableSections(array $filters = []): array
    {
        $conditions = [
            "s.status = 'active'",
            "c.status = 'active'",
            "ay.status = 'open'",
            "sem.status = 'active'"
        ];

        $params = [];

        $optionalFilters = [
            'course_id' => 's.course_id',
            'year_level' => 's.year_level',
            'academic_year_id' => 's.academic_year_id',
            'semester_id' => 's.semester_id'
        ];

        foreach ($optionalFilters as $filter => $column) {
            if (
                isset($filters[$filter]) &&
                (int) $filters[$filter] > 0
            ) {
                $conditions[] = $column . ' = :' . $filter;
                $params[$filter] = (int) $filters[$filter];
            }
        }

        $sql = "
            SELECT
                s.section_id,
                s.section_name,
                s.course_id,
                s.year_level,
                s.academic_year_id,
                s.semester_id,
                s.capacity,
                c.course_code,
                c.course_name,
                ay.year_name,
                sem.semester_name,
                (
                    SELECT COUNT(*)
                    FROM enrollments e
                    WHERE e.section_id = s.section_id
                    AND e.status IN ('pending', 'under_review', 'approved')
                ) AS enrolled_count
            FROM sections s
            INNER JOIN courses c
                ON c.course_id = s.course_id
            INNER JOIN academic_years ay
                ON ay.academic_year_id = s.academic_year_id
            INNER JOIN semesters sem
                ON sem.semester_id = s.semester_id
            WHERE " . implode(' AND ', $conditions) . "
            ORDER BY
                c.course_code ASC,
                s.year_level ASC,
                s.section_name ASC
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        $sections = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($sections as &$section) {
            $section['capacity'] = (int) $section['capacity'];
            $section['enrolled_count'] = (int) $section['enrolled_count'];

            $section['remaining_slots'] = max(
                0,
                $section['capacity'] - $section['enrolled_count']
            );

            $section['is_full'] = $section['remaining_slots'] <= 0;
        }

        unset($section);

        return $sections;
    }

    public function getProgramScheduleOptions(
        int $courseId,
        int $academicYearId = 0,
        int $semesterId = 0
    ): array {
        if ($courseId < 1) {
            return [];
        }

        $conditions = [
            's.course_id = :course_id',
            "s.status = 'active'",
            "c.status = 'active'",
            "ay.status = 'open'",
            "sem.status = 'active'"
        ];
        $parameters = ['course_id' => $courseId];

        if ($academicYearId > 0) {
            $conditions[] = 's.academic_year_id = :academic_year_id';
            $parameters['academic_year_id'] = $academicYearId;
        }

        if ($semesterId > 0) {
            $conditions[] = 's.semester_id = :semester_id';
            $parameters['semester_id'] = $semesterId;
        }

        $sectionSql = "
            SELECT
                s.section_id,
                s.section_name,
                s.course_id,
                s.year_level,
                s.academic_year_id,
                s.semester_id,
                s.capacity,
                c.course_code,
                c.course_name,
                ay.year_name,
                sem.semester_name,
                (
                    SELECT COUNT(*)
                    FROM enrollments e
                    WHERE e.section_id = s.section_id
                      AND e.status IN ('pending', 'under_review', 'approved')
                ) AS enrolled_count
            FROM sections s
            INNER JOIN courses c
                ON c.course_id = s.course_id
            INNER JOIN academic_years ay
                ON ay.academic_year_id = s.academic_year_id
            INNER JOIN semesters sem
                ON sem.semester_id = s.semester_id
            WHERE " . implode(' AND ', $conditions) . "
            ORDER BY
                c.course_code ASC,
                s.year_level ASC,
                s.section_name ASC
        ";

        $sectionStatement = $this->pdo->prepare($sectionSql);
        $sectionStatement->execute($parameters);
        $sections = $sectionStatement->fetchAll(PDO::FETCH_ASSOC);

        if (!$sections) {
            return [];
        }

        $scheduleStatement = $this->pdo->prepare("
            SELECT
                cs.schedule_id,
                cs.section_subject_id,
                cs.day_of_week,
                cs.start_time,
                cs.end_time,
                cs.room,
                ss.subject_id,
                sub.subject_code,
                sub.subject_name,
                sub.units
            FROM section_subjects ss
            INNER JOIN subjects sub
                ON sub.subject_id = ss.subject_id
               AND sub.status = 'active'
            INNER JOIN class_schedules cs
                ON cs.section_subject_id = ss.section_subject_id
            WHERE ss.section_id = :section_id
            ORDER BY
                FIELD(
                    cs.day_of_week,
                    'Monday', 'Tuesday', 'Wednesday',
                    'Thursday', 'Friday', 'Saturday', 'Sunday'
                ),
                cs.start_time ASC,
                sub.subject_code ASC
        ");

        foreach ($sections as &$section) {
            $sectionId = (int) $section['section_id'];
            $scheduleStatement->execute(['section_id' => $sectionId]);
            $section['schedules'] = $scheduleStatement->fetchAll(PDO::FETCH_ASSOC);

            $section['capacity'] = (int) $section['capacity'];
            $section['enrolled_count'] = (int) $section['enrolled_count'];
            $section['remaining_slots'] = max(
                0,
                $section['capacity'] - $section['enrolled_count']
            );
            $section['is_full'] = $section['remaining_slots'] <= 0;
        }
        unset($section);

        return $sections;
    }

    public function submitEnrollment(int $studentId, array $data): array
    {
        $userId = (int) ($_SESSION['user_id'] ?? 0);

        $sectionId = (int) ($data['section_id'] ?? 0);

        $studentType = $data['student_type'] ?? 'new';
        $remarks = trim($data['remarks'] ?? '');

        if (
            $studentId < 1 ||
            $userId < 1 ||
            $sectionId < 1 ||
            !in_array(
                $studentType,
                ['new', 'old', 'transferee', 'returning'],
                true
            )
        ) {
            return [
                'success' => false,
                'message' => 'Please provide valid enrollment details.'
            ];
        }

        try {
            $this->pdo->beginTransaction();

            $profileStmt = $this->pdo->prepare("
                SELECT student_id
                FROM student_profiles
                WHERE student_id = ?
                AND user_id = ?
                LIMIT 1
            ");

            $profileStmt->execute([
                $studentId,
                $userId
            ]);

            if (!$profileStmt->fetch()) {
                $this->pdo->rollBack();

                return [
                    'success' => false,
                    'message' => 'Student profile does not belong to this account.'
                ];
            }

            $sectionStmt = $this->pdo->prepare("
                SELECT
                    s.*,
                    c.status AS course_status,
                    c.course_code,
                    c.course_name,
                    ay.status AS academic_year_status,
                    ay.year_name,
                    sem.status AS semester_status,
                    sem.semester_name
                FROM sections s
                INNER JOIN courses c
                    ON c.course_id = s.course_id
                INNER JOIN academic_years ay
                    ON ay.academic_year_id = s.academic_year_id
                INNER JOIN semesters sem
                    ON sem.semester_id = s.semester_id
                WHERE s.section_id = :section_id
                FOR UPDATE
            ");

            $sectionStmt->execute([
                'section_id' => $sectionId
            ]);

            $section = $sectionStmt->fetch(PDO::FETCH_ASSOC);

            if (!$section) {
                $this->pdo->rollBack();

                return [
                    'success' => false,
                    'message' => 'Selected section does not exist.'
                ];
            }

            if (
                $section['status'] !== 'active' ||
                $section['course_status'] !== 'active' ||
                $section['academic_year_status'] !== 'open' ||
                $section['semester_status'] !== 'active'
            ) {
                $this->pdo->rollBack();

                return [
                    'success' => false,
                    'message' => 'This section is not available for enrollment.'
                ];
            }

            $courseId = (int) $section['course_id'];
            $yearLevel = (int) $section['year_level'];
            $academicYearId = (int) $section['academic_year_id'];
            $semesterId = (int) $section['semester_id'];

            $duplicateStmt = $this->pdo->prepare("
                SELECT enrollment_id
                FROM enrollments
                WHERE student_id = :student_id
                AND academic_year_id = :academic_year_id
                AND semester_id = :semester_id
                LIMIT 1
            ");

            $duplicateStmt->execute([
                'student_id' => $studentId,
                'academic_year_id' => $academicYearId,
                'semester_id' => $semesterId
            ]);

            if ($duplicateStmt->fetch()) {
                $this->pdo->rollBack();

                return [
                    'success' => false,
                    'message' => 'You already have an enrollment application for this semester.'
                ];
            }

            $countStmt = $this->pdo->prepare("
                SELECT COUNT(*)
                FROM enrollments
                WHERE section_id = :section_id
                AND status IN ('pending', 'under_review', 'approved')
            ");

            $countStmt->execute([
                'section_id' => $sectionId
            ]);

            $enrolledCount = (int) $countStmt->fetchColumn();
            $capacity = (int) $section['capacity'];

            if ($enrolledCount >= $capacity) {
                $this->pdo->rollBack();

                return [
                    'success' => false,
                    'message' => 'This section is already full.'
                ];
            }

            $insertStmt = $this->pdo->prepare("
                INSERT INTO enrollments
                (
                    student_id,
                    academic_year_id,
                    semester_id,
                    course_id,
                    section_id,
                    year_level,
                    student_type,
                    status,
                    remarks
                )
                VALUES
                (
                    :student_id,
                    :academic_year_id,
                    :semester_id,
                    :course_id,
                    :section_id,
                    :year_level,
                    :student_type,
                    'pending',
                    :remarks
                )
            ");

            $insertStmt->execute([
                'student_id' => $studentId,
                'academic_year_id' => $academicYearId,
                'semester_id' => $semesterId,
                'course_id' => $courseId,
                'section_id' => $sectionId,
                'year_level' => $yearLevel,
                'student_type' => $studentType,
                'remarks' => $remarks !== '' ? $remarks : null
            ]);

            $enrollmentId = (int) $this->pdo->lastInsertId();

            $historyStmt = $this->pdo->prepare("
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
                    NULL,
                    'pending',
                    :remarks
                )
            ");

            $historyStmt->execute([
                'enrollment_id' => $enrollmentId,
                'updated_by' => $userId,
                'remarks' => 'Enrollment application submitted.'
            ]);

            $this->logActivity(
                $userId,
                'Enrollment Submitted',
                'Student submitted enrollment application #' .
                $enrollmentId .
                ' for section ' . $section['section_name'] . '.'
            );

            $this->pdo->commit();

            return [
                'success' => true,
                'message' => 'Enrollment application submitted successfully.',
                'enrollment_id' => $enrollmentId,
                'status' => 'pending'
            ];

        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            error_log($e->getMessage());

            return [
                'success' => false,
                'message' => 'Unable to submit enrollment application.'
            ];
        }
    }

    public function getStudentEnrollments(int $studentId): array
    {
        $stmt = $this->pdo->prepare("
            SELECT
                e.*,
                c.course_code,
                c.course_name,
                s.section_name,
                ay.year_name,
                sem.semester_name
            FROM enrollments e
            INNER JOIN courses c
                ON c.course_id = e.course_id
            INNER JOIN sections s
                ON s.section_id = e.section_id
            INNER JOIN academic_years ay
                ON ay.academic_year_id = e.academic_year_id
            INNER JOIN semesters sem
                ON sem.semester_id = e.semester_id
            WHERE e.student_id = :student_id
            ORDER BY e.submitted_at DESC
        ");

        $stmt->execute([
            'student_id' => $studentId
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getAllEnrollments(): array
    {
        $sql = "
            SELECT
                e.*,
                sp.student_number,
                sp.first_name,
                sp.middle_name,
                sp.last_name,
                u.user_id,
                u.full_name,
                u.email,
                c.course_code,
                c.course_name,
                s.section_name,
                ay.year_name,
                sem.semester_name
            FROM enrollments e
            INNER JOIN student_profiles sp
                ON sp.student_id = e.student_id
            INNER JOIN users u
                ON u.user_id = sp.user_id
            INNER JOIN courses c
                ON c.course_id = e.course_id
            INNER JOIN sections s
                ON s.section_id = e.section_id
            INNER JOIN academic_years ay
                ON ay.academic_year_id = e.academic_year_id
            INNER JOIN semesters sem
                ON sem.semester_id = e.semester_id
            ORDER BY e.submitted_at DESC
        ";

        return $this->pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    public function updateEnrollmentStatus(array $data): array
    {
        $enrollmentId = (int) ($data['enrollment_id'] ?? 0);
        $newStatus = $data['status'] ?? '';
        $updatedBy = (int) ($_SESSION['user_id'] ?? 0);
        $remarks = trim($data['remarks'] ?? '');

        $allowedStatuses = [
            'under_review',
            'approved',
            'rejected'
        ];

        if (
            $enrollmentId < 1 ||
            $updatedBy < 1 ||
            !in_array($newStatus, $allowedStatuses, true)
        ) {
            return [
                'success' => false,
                'message' => 'Invalid enrollment status details.'
            ];
        }

        try {
            $this->pdo->beginTransaction();

            $stmt = $this->pdo->prepare("
                SELECT
                    e.*,
                    sp.user_id AS student_user_id
                FROM enrollments e
                INNER JOIN student_profiles sp
                    ON sp.student_id = e.student_id
                WHERE e.enrollment_id = :enrollment_id
                FOR UPDATE
            ");

            $stmt->execute([
                'enrollment_id' => $enrollmentId
            ]);

            $enrollment = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$enrollment) {
                $this->pdo->rollBack();

                return [
                    'success' => false,
                    'message' => 'Enrollment record not found.'
                ];
            }

            $oldStatus = $enrollment['status'];

            $allowedTransitions = [
                'pending' => ['under_review', 'rejected'],
                'under_review' => ['approved', 'rejected']
            ];

            if (
                !isset($allowedTransitions[$oldStatus]) ||
                !in_array($newStatus, $allowedTransitions[$oldStatus], true)
            ) {
                $this->pdo->rollBack();

                return [
                    'success' => false,
                    'message' => 'This enrollment status transition is not allowed.'
                ];
            }

            if ($newStatus === 'approved') {
                $sectionStmt = $this->pdo->prepare("
                    SELECT *
                    FROM sections
                    WHERE section_id = :section_id
                    FOR UPDATE
                ");

                $sectionStmt->execute([
                    'section_id' => $enrollment['section_id']
                ]);

                $section = $sectionStmt->fetch(PDO::FETCH_ASSOC);

                if (!$section || $section['status'] !== 'active') {
                    $this->pdo->rollBack();

                    return [
                        'success' => false,
                        'message' => 'The selected section is not active.'
                    ];
                }

                $countStmt = $this->pdo->prepare("
                    SELECT COUNT(*)
                    FROM enrollments
                    WHERE section_id = :section_id
                    AND status IN ('pending', 'under_review', 'approved')
                    AND enrollment_id != :enrollment_id
                ");

                $countStmt->execute([
                    'section_id' => $enrollment['section_id'],
                    'enrollment_id' => $enrollmentId
                ]);

                $enrolledCount = (int) $countStmt->fetchColumn();

                if ($enrolledCount >= (int) $section['capacity']) {
                    $this->pdo->rollBack();

                    return [
                        'success' => false,
                        'message' => 'Cannot approve. The section has reached its capacity.'
                    ];
                }
            }

            $updateStmt = $this->pdo->prepare("
                UPDATE enrollments
                SET status = :status,
                    remarks = :remarks
                WHERE enrollment_id = :enrollment_id
            ");

            $updateStmt->execute([
                'status' => $newStatus,
                'remarks' => $remarks !== '' ? $remarks : null,
                'enrollment_id' => $enrollmentId
            ]);

            $historyStmt = $this->pdo->prepare("
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

            $historyStmt->execute([
                'enrollment_id' => $enrollmentId,
                'updated_by' => $updatedBy,
                'old_status' => $oldStatus,
                'new_status' => $newStatus,
                'remarks' => $remarks !== '' ? $remarks : null
            ]);

            $notificationMessages = [
                'under_review' => 'Your enrollment application is now under review.',
                'approved' => 'Your enrollment application has been approved.',
                'rejected' => 'Your enrollment application has been rejected.'
            ];

            $notificationStmt = $this->pdo->prepare("
                INSERT INTO notifications
                (
                    user_id,
                    title,
                    message
                )
                VALUES
                (
                    :user_id,
                    :title,
                    :message
                )
            ");

            $notificationStmt->execute([
                'user_id' => $enrollment['student_user_id'],
                'title' => 'Enrollment Status Updated',
                'message' => $notificationMessages[$newStatus]
            ]);

            $this->logActivity(
                $updatedBy,
                'Enrollment Status Updated',
                'Enrollment #' . $enrollmentId .
                ' status changed from ' . $oldStatus .
                ' to ' . $newStatus . '.'
            );

            $this->pdo->commit();

            return [
                'success' => true,
                'message' => 'Enrollment status updated successfully.',
                'old_status' => $oldStatus,
                'new_status' => $newStatus
            ];

        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            error_log($e->getMessage());

            return [
                'success' => false,
                'message' => 'Unable to update enrollment status.'
            ];
        }
    }
}

$enrollmentController = new EnrollmentController($pdo);
