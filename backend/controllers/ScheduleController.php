<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/ActivityLogController.php';

class ScheduleController
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

    private function normalizeTime(string $time): ?string
    {
        $time = trim($time);

        foreach (['H:i:s', 'H:i'] as $format) {
            $date = DateTimeImmutable::createFromFormat('!' . $format, $time);
            $errors = DateTimeImmutable::getLastErrors();

            if (
                $date !== false &&
                ($errors === false ||
                    (
                        $errors['warning_count'] === 0 &&
                        $errors['error_count'] === 0
                    )
                ) &&
                $date->format($format) === $time
            ) {
                return $date->format('H:i:s');
            }
        }

        return null;
    }

    private function getActiveAssignment(int $sectionSubjectId): ?array
    {
        $stmt = $this->pdo->prepare("
            SELECT
                ss.section_subject_id,
                ss.section_id,
                ss.subject_id,
                sec.section_name,
                sec.status AS section_status,
                sub.subject_code,
                sub.subject_name,
                sub.status AS subject_status,
                c.course_code,
                c.status AS course_status
            FROM section_subjects ss
            INNER JOIN sections sec
                ON sec.section_id = ss.section_id
            INNER JOIN subjects sub
                ON sub.subject_id = ss.subject_id
            INNER JOIN courses c
                ON c.course_id = sec.course_id
            WHERE ss.section_subject_id = :id
            LIMIT 1
        ");

        $stmt->execute(['id' => $sectionSubjectId]);

        $assignment = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$assignment) {
            return null;
        }

        if (
            $assignment['section_status'] !== 'active' ||
            $assignment['subject_status'] !== 'active' ||
            $assignment['course_status'] !== 'active'
        ) {
            return null;
        }

        return $assignment;
    }

    private function hasConflict(
        int $sectionSubjectId,
        string $day,
        string $startTime,
        string $endTime,
        ?string $room,
        int $excludeScheduleId = 0
    ): bool {
        $stmt = $this->pdo->prepare("
            SELECT COUNT(*)
            FROM class_schedules existing
            INNER JOIN section_subjects existing_ss
                ON existing_ss.section_subject_id = existing.section_subject_id
            INNER JOIN section_subjects requested_ss
                ON requested_ss.section_subject_id = :section_subject_id
            WHERE existing.day_of_week = :day
              AND existing.start_time < :end_time
              AND existing.end_time > :start_time
              AND existing.schedule_id != :exclude_id
              AND (
                    existing_ss.section_id = requested_ss.section_id
                    OR (
                        :room_check IS NOT NULL
                        AND existing.room IS NOT NULL
                        AND LOWER(TRIM(existing.room)) = LOWER(TRIM(:room_compare))
                    )
              )
        ");

        $stmt->execute([
            'section_subject_id' => $sectionSubjectId,
            'day' => $day,
            'start_time' => $startTime,
            'end_time' => $endTime,
            'exclude_id' => $excludeScheduleId,
            'room_check' => $room,
            'room_compare' => $room
        ]);

        return (int) $stmt->fetchColumn() > 0;
    }

    private function validateScheduleInput(array $data): array
    {
        $sectionSubjectId = (int) ($data['section_subject_id'] ?? 0);
        $day = trim($data['day_of_week'] ?? '');
        $startTime = $this->normalizeTime(
            (string) ($data['start_time'] ?? '')
        );
        $endTime = $this->normalizeTime(
            (string) ($data['end_time'] ?? '')
        );
        $room = trim($data['room'] ?? '');

        $days = [
            'Monday',
            'Tuesday',
            'Wednesday',
            'Thursday',
            'Friday',
            'Saturday'
        ];

        if (
            $sectionSubjectId < 1 ||
            !in_array($day, $days, true) ||
            $startTime === null ||
            $endTime === null ||
            $startTime >= $endTime ||
            strlen($room) > 80
        ) {
            return [
                'success' => false,
                'message' => 'Please provide valid schedule details.'
            ];
        }

        return [
            'success' => true,
            'section_subject_id' => $sectionSubjectId,
            'day_of_week' => $day,
            'start_time' => $startTime,
            'end_time' => $endTime,
            'room' => $room !== '' ? $room : null
        ];
    }

    public function getAllSchedules(): array
    {
        $sql = "
            SELECT
                cs.schedule_id,
                cs.section_subject_id,
                cs.day_of_week,
                cs.start_time,
                cs.end_time,
                cs.room,
                cs.created_at,
                ss.section_id,
                ss.subject_id,
                sec.section_name,
                sec.year_level,
                sub.subject_code,
                sub.subject_name,
                sub.units,
                c.course_code,
                c.course_name,
                ay.year_name,
                sem.semester_name
            FROM class_schedules cs
            INNER JOIN section_subjects ss
                ON ss.section_subject_id = cs.section_subject_id
            INNER JOIN sections sec
                ON sec.section_id = ss.section_id
            INNER JOIN subjects sub
                ON sub.subject_id = ss.subject_id
            INNER JOIN courses c
                ON c.course_id = sec.course_id
            INNER JOIN academic_years ay
                ON ay.academic_year_id = sec.academic_year_id
            INNER JOIN semesters sem
                ON sem.semester_id = sec.semester_id
            ORDER BY
                FIELD(
                    cs.day_of_week,
                    'Monday',
                    'Tuesday',
                    'Wednesday',
                    'Thursday',
                    'Friday',
                    'Saturday'
                ),
                cs.start_time,
                sec.section_name
        ";

        return $this->pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    public function addSchedule(array $data): array
    {
        $userId = (int) ($_SESSION['user_id'] ?? 0);

        $validated = $this->validateScheduleInput($data);

        if (!$validated['success'] || $userId < 1) {
            return [
                'success' => false,
                'message' => $validated['message'] ?? 'Invalid user session.'
            ];
        }

        $sectionSubjectId = $validated['section_subject_id'];
        $day = $validated['day_of_week'];
        $startTime = $validated['start_time'];
        $endTime = $validated['end_time'];
        $room = $validated['room'];

        try {
            $this->pdo->beginTransaction();

            $assignment = $this->getActiveAssignment($sectionSubjectId);

            if (!$assignment) {
                $this->pdo->rollBack();

                return [
                    'success' => false,
                    'message' => 'The selected section or subject assignment does not exist or is inactive.'
                ];
            }

            if (
                $this->hasConflict(
                    $sectionSubjectId,
                    $day,
                    $startTime,
                    $endTime,
                    $room
                )
            ) {
                $this->pdo->rollBack();

                return [
                    'success' => false,
                    'message' => 'Schedule conflict. The section or classroom already has a class during this time.'
                ];
            }

            $stmt = $this->pdo->prepare("
                INSERT INTO class_schedules
                (
                    section_subject_id,
                    day_of_week,
                    start_time,
                    end_time,
                    room
                )
                VALUES
                (
                    :section_subject_id,
                    :day,
                    :start_time,
                    :end_time,
                    :room
                )
            ");

            $stmt->execute([
                'section_subject_id' => $sectionSubjectId,
                'day' => $day,
                'start_time' => $startTime,
                'end_time' => $endTime,
                'room' => $room
            ]);

            $scheduleId = (int) $this->pdo->lastInsertId();

            $this->pdo->commit();

            $this->logActivity(
                $userId,
                'Schedule Created',
                'Schedule ID ' . $scheduleId .
                ' was created for ' .
                $assignment['course_code'] . ' ' .
                $assignment['section_name'] . ', subject ' .
                $assignment['subject_code'] . ', on ' .
                $day . ' from ' . $startTime .
                ' to ' . $endTime . '. Room: ' .
                ($room ?? 'Not assigned') . '.'
            );

            return [
                'success' => true,
                'message' => 'Class schedule added successfully.',
                'schedule_id' => $scheduleId
            ];

        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            error_log($e->getMessage());

            return [
                'success' => false,
                'message' => 'Unable to add class schedule.'
            ];
        }
    }

    public function updateSchedule(array $data): array
    {
        $userId = (int) ($_SESSION['user_id'] ?? 0);
        $scheduleId = (int) ($data['schedule_id'] ?? 0);

        if ($scheduleId < 1 || $userId < 1) {
            return [
                'success' => false,
                'message' => 'Invalid schedule or user information.'
            ];
        }

        $validated = $this->validateScheduleInput($data);

        if (!$validated['success']) {
            return $validated;
        }

        $sectionSubjectId = $validated['section_subject_id'];
        $day = $validated['day_of_week'];
        $startTime = $validated['start_time'];
        $endTime = $validated['end_time'];
        $room = $validated['room'];

        try {
            $this->pdo->beginTransaction();

            $existingStmt = $this->pdo->prepare("
                SELECT schedule_id
                FROM class_schedules
                WHERE schedule_id = :schedule_id
                FOR UPDATE
            ");

            $existingStmt->execute([
                'schedule_id' => $scheduleId
            ]);

            if (!$existingStmt->fetch()) {
                $this->pdo->rollBack();

                return [
                    'success' => false,
                    'message' => 'Class schedule not found.'
                ];
            }

            $assignment = $this->getActiveAssignment($sectionSubjectId);

            if (!$assignment) {
                $this->pdo->rollBack();

                return [
                    'success' => false,
                    'message' => 'The selected section or subject assignment does not exist or is inactive.'
                ];
            }

            if (
                $this->hasConflict(
                    $sectionSubjectId,
                    $day,
                    $startTime,
                    $endTime,
                    $room,
                    $scheduleId
                )
            ) {
                $this->pdo->rollBack();

                return [
                    'success' => false,
                    'message' => 'Schedule conflict. The section or classroom already has a class during this time.'
                ];
            }

            $stmt = $this->pdo->prepare("
                UPDATE class_schedules
                SET
                    section_subject_id = :section_subject_id,
                    day_of_week = :day,
                    start_time = :start_time,
                    end_time = :end_time,
                    room = :room
                WHERE schedule_id = :schedule_id
            ");

            $stmt->execute([
                'section_subject_id' => $sectionSubjectId,
                'day' => $day,
                'start_time' => $startTime,
                'end_time' => $endTime,
                'room' => $room,
                'schedule_id' => $scheduleId
            ]);

            $this->pdo->commit();

            $this->logActivity(
                $userId,
                'Schedule Updated',
                'Schedule ID ' . $scheduleId .
                ' was updated for ' .
                $assignment['course_code'] . ' ' .
                $assignment['section_name'] . ', subject ' .
                $assignment['subject_code'] . ', on ' .
                $day . ' from ' . $startTime .
                ' to ' . $endTime . '. Room: ' .
                ($room ?? 'Not assigned') . '.'
            );

            return [
                'success' => true,
                'message' => 'Class schedule updated successfully.'
            ];

        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            error_log($e->getMessage());

            return [
                'success' => false,
                'message' => 'Unable to update class schedule.'
            ];
        }
    }
}

$scheduleController = new ScheduleController($pdo);
