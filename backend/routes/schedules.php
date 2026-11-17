<?php

header('Content-Type: application/json');

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../middleware/AuthMiddleware.php';
require_once __DIR__ . '/../controllers/ScheduleController.php';

AuthMiddleware::requireLogin();

$role = $_SESSION['role'] ?? '';
$method = $_SERVER['REQUEST_METHOD'];

$input = json_decode(file_get_contents('php://input'), true);

if (!is_array($input)) {
    $input = $_POST;
}

function scheduleRouteResponse(int $statusCode, array $data): void
{
    http_response_code($statusCode);
    echo json_encode($data);
    exit;
}

try {

    if ($method === 'GET') {

        $action = $_GET['action'] ?? '';

        if ($role === 'student') {

            if ($action !== 'my_schedule') {
                scheduleRouteResponse(403, [
                    'success' => false,
                    'message' => 'Students may only view their own class schedule.'
                ]);
            }

            $userId = (int) ($_SESSION['user_id'] ?? 0);

            $profileStmt = $pdo->prepare("
                SELECT student_id
                FROM student_profiles
                WHERE user_id = ?
                LIMIT 1
            ");

            $profileStmt->execute([$userId]);
            $studentId = (int) $profileStmt->fetchColumn();

            if ($studentId < 1) {
                scheduleRouteResponse(404, [
                    'success' => false,
                    'message' => 'Student profile not found.'
                ]);
            }

            $stmt = $pdo->prepare("
                SELECT
                    e.enrollment_id,
                    cs.schedule_id,
                    cs.day_of_week,
                    cs.start_time,
                    cs.end_time,
                    cs.room,
                    sec.section_id,
                    sec.section_name,
                    sec.year_level,
                    sub.subject_id,
                    sub.subject_code,
                    sub.subject_name,
                    sub.units,
                    c.course_code,
                    c.course_name,
                    ay.year_name,
                    sem.semester_name
                FROM enrollments e
                INNER JOIN sections sec
                    ON sec.section_id = e.section_id
                INNER JOIN section_subjects ss
                    ON ss.section_id = sec.section_id
                INNER JOIN class_schedules cs
                    ON cs.section_subject_id = ss.section_subject_id
                INNER JOIN subjects sub
                    ON sub.subject_id = ss.subject_id
                INNER JOIN courses c
                    ON c.course_id = e.course_id
                INNER JOIN academic_years ay
                    ON ay.academic_year_id = e.academic_year_id
                INNER JOIN semesters sem
                    ON sem.semester_id = e.semester_id
                WHERE e.student_id = :student_id
                  AND e.status = 'approved'
                  AND e.academic_year_id = sec.academic_year_id
                  AND e.semester_id = sec.semester_id
                ORDER BY
                    e.academic_year_id DESC,
                    e.semester_id,
                    FIELD(cs.day_of_week, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'),
                    cs.start_time
            ");

            $stmt->execute([
                'student_id' => $studentId
            ]);

            scheduleRouteResponse(200, [
                'success' => true,
                'schedules' => $stmt->fetchAll(PDO::FETCH_ASSOC)
            ]);
        }

        if (!in_array($role, ['admin', 'registrar'], true)) {
            scheduleRouteResponse(403, [
                'success' => false,
                'message' => 'Access denied.'
            ]);
        }

        if ($action === 'all_assignments') {
            $stmt = $pdo->prepare("
                SELECT
                    ss.section_subject_id,
                    ss.section_id,
                    ss.subject_id,
                    sec.section_name,
                    sec.year_level,
                    sec.academic_year_id,
                    sec.semester_id,
                    sec.status AS section_status,
                    sub.subject_code,
                    sub.subject_name,
                    sub.status AS subject_status,
                    c.course_code,
                    c.course_name,
                    c.status AS course_status,
                    ay.year_name,
                    sem.semester_name
                FROM section_subjects ss
                INNER JOIN sections sec ON sec.section_id = ss.section_id
                INNER JOIN subjects sub ON sub.subject_id = ss.subject_id
                INNER JOIN courses c ON c.course_id = sec.course_id
                INNER JOIN academic_years ay ON ay.academic_year_id = sec.academic_year_id
                INNER JOIN semesters sem ON sem.semester_id = sec.semester_id
                ORDER BY c.course_code, sec.year_level, ay.start_date DESC,
                         sem.semester_name, sec.section_name, sub.subject_name, sub.subject_code
            ");
            $stmt->execute();
            scheduleRouteResponse(200, [
                'success' => true,
                'assignments' => $stmt->fetchAll(PDO::FETCH_ASSOC)
            ]);
        }

        if ($action === 'assignments') {

            $stmt = $pdo->prepare("
                SELECT
                    ss.section_subject_id,
                    ss.section_id,
                    ss.subject_id,
                    sec.section_name,
                    sec.year_level,
                    sec.academic_year_id,
                    sec.semester_id,
                    sub.subject_code,
                    sub.subject_name,
                    c.course_code,
                    c.course_name,
                    ay.year_name,
                    sem.semester_name
                FROM section_subjects ss
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
                WHERE sec.status = 'active'
                  AND sub.status = 'active'
                  AND c.status = 'active'
                  AND ay.status = 'open'
                  AND sem.status = 'active'
                ORDER BY
                    c.course_code,
                    sec.section_name,
                    sub.subject_code
            ");

            $stmt->execute();

            scheduleRouteResponse(200, [
                'success' => true,
                'assignments' => $stmt->fetchAll(PDO::FETCH_ASSOC)
            ]);
        }

        scheduleRouteResponse(200, [
            'success' => true,
            'schedules' => $scheduleController->getAllSchedules()
        ]);
    }

    if ($method === 'POST') {

        if (!in_array($role, ['admin', 'registrar'], true)) {
            scheduleRouteResponse(403, [
                'success' => false,
                'message' => 'Only administrators and registrars may manage class schedules.'
            ]);
        }

 $result = [
    'success' => false,
    'message' => 'Invalid action.'
];

        if ($action === 'add') {
            $result = $scheduleController->addSchedule($input);
        } elseif ($action === 'update') {
            $result = $scheduleController->updateSchedule($input);
        } else {
            scheduleRouteResponse(400, [
                'success' => false,
                'message' => 'Invalid action.'
            ]);
        }

        if (!$result['success']) {
            scheduleRouteResponse(422, $result);
        }

        scheduleRouteResponse(200, $result);
    }

    scheduleRouteResponse(405, [
        'success' => false,
        'message' => 'Method not allowed.'
    ]);

} catch (PDOException $e) {

    error_log($e->getMessage());

    scheduleRouteResponse(500, [
        'success' => false,
        'message' => 'A database error occurred.'
    ]);

} catch (Throwable $e) {

    error_log($e->getMessage());

    scheduleRouteResponse(500, [
        'success' => false,
        'message' => 'An unexpected server error occurred.'
    ]);
}
