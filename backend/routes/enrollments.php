<?php

header('Content-Type: application/json');

require_once __DIR__ . '/../middleware/AuthMiddleware.php';
require_once __DIR__ . '/../controllers/EnrollmentController.php';

AuthMiddleware::requireLogin();

$role = $_SESSION['role'] ?? '';
$userId = (int) ($_SESSION['user_id'] ?? 0);
$method = $_SERVER['REQUEST_METHOD'];

$input = json_decode(file_get_contents('php://input'), true);

if (!is_array($input)) {
    $input = $_POST;
}

try {
    $studentId = 0;

    if ($role === 'student') {
        $stmt = $pdo->prepare("
            SELECT student_id
            FROM student_profiles
            WHERE user_id = ?
            LIMIT 1
        ");

        $stmt->execute([$userId]);
        $studentId = (int) $stmt->fetchColumn();

        if ($studentId <= 0) {
            http_response_code(404);

            echo json_encode([
                'success' => false,
                'message' => 'Student profile not found.'
            ]);

            exit;
        }
    }

    if ($method === 'GET') {

        if (
            $role === 'student' &&
            ($_GET['action'] ?? '') === 'available_sections'
        ) {
            echo json_encode([
                'success' => true,
                'sections' => $enrollmentController->getAvailableSections($_GET)
            ]);

            exit;
        }

        if (
            $role === 'student' &&
            ($_GET['action'] ?? '') === 'program_schedule_options'
        ) {
            $courseId = (int) ($_GET['course_id'] ?? 0);
            $academicYearId = (int) ($_GET['academic_year_id'] ?? 0);
            $semesterId = (int) ($_GET['semester_id'] ?? 0);

            if ($courseId < 1) {
                http_response_code(422);
                echo json_encode([
                    'success' => false,
                    'message' => 'Please select a valid program.'
                ]);
                exit;
            }

            echo json_encode([
                'success' => true,
                'sections' => $enrollmentController->getProgramScheduleOptions(
                    $courseId,
                    $academicYearId,
                    $semesterId
                )
            ]);

            exit;
        }

        if (
            $role === 'student' &&
            ($_GET['action'] ?? '') === 'history'
        ) {
            echo json_encode([
                'success' => true,
                'enrollments' => $enrollmentController->getStudentEnrollments($studentId)
            ]);

            exit;
        }
if (in_array($role, ['registrar', 'admin'], true)) {
    echo json_encode([
        'success' => true,
        'enrollments' => $enrollmentController->getAllEnrollments()
    ]);

    exit;
}

        http_response_code(403);

        echo json_encode([
            'success' => false,
            'message' => 'Access denied.'
        ]);

        exit;
    }

    if ($method === 'POST') {

        if (
            $role === 'student' &&
            ($input['action'] ?? '') === 'submit'
        ) {
            $result = $enrollmentController->submitEnrollment(
                $studentId,
                $input
            );

        } elseif (
            $role === 'registrar' &&
            ($input['action'] ?? '') === 'update_status'
        ) {
            $result = $enrollmentController->updateEnrollmentStatus($input);

        } else {
            http_response_code(403);

            echo json_encode([
                'success' => false,
                'message' => 'Access denied or invalid action.'
            ]);

            exit;
        }

        if (!$result['success']) {
            http_response_code(422);
        }

        echo json_encode($result);

        exit;
    }

    http_response_code(405);

    echo json_encode([
        'success' => false,
        'message' => 'Method not allowed.'
    ]);

} catch (PDOException $e) {
    error_log($e->getMessage());

    http_response_code(500);

    echo json_encode([
        'success' => false,
        'message' => 'A database error occurred.'
    ]);

} catch (Throwable $e) {
    error_log($e->getMessage());

    http_response_code(500);

    echo json_encode([
        'success' => false,
        'message' => 'An unexpected server error occurred.'
    ]);
}
