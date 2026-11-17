<?php

header('Content-Type: application/json');

require_once __DIR__ . '/../middleware/AuthMiddleware.php';
require_once __DIR__ . '/../controllers/SubjectController.php';

AuthMiddleware::requireLogin();

$role = $_SESSION['role'] ?? '';
$method = $_SERVER['REQUEST_METHOD'];

if (!in_array($role, ['admin', 'registrar'], true)) {
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'message' => 'Access denied.'
    ]);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

if (!is_array($input)) {
    $input = $_POST;
}

try {
    if ($method === 'GET') {
        $action = $_GET['action'] ?? '';

        if ($action === 'section_assignments') {
            $stmt = $pdo->query("
                SELECT
                    ss.section_subject_id,
                    ss.section_id,
                    ss.subject_id,
                    sec.course_id,
                    sec.section_name,
                    sec.year_level,
                    sec.academic_year_id,
                    sec.semester_id,
                    sec.status AS section_status,
                    sub.subject_code,
                    sub.subject_name,
                    sub.units,
                    sub.status AS subject_status,
                    c.course_code,
                    c.course_name,
                    ay.year_name,
                    sem.semester_name
                FROM section_subjects ss
                INNER JOIN sections sec ON sec.section_id = ss.section_id
                INNER JOIN subjects sub ON sub.subject_id = ss.subject_id
                INNER JOIN courses c ON c.course_id = sec.course_id
                INNER JOIN academic_years ay ON ay.academic_year_id = sec.academic_year_id
                INNER JOIN semesters sem ON sem.semester_id = sec.semester_id
                ORDER BY
                    c.course_code,
                    sec.year_level,
                    sec.section_name,
                    ay.year_name DESC,
                    sem.semester_name,
                    sub.subject_name,
                    sub.subject_code
            ");

            echo json_encode([
                'success' => true,
                'assignments' => $stmt->fetchAll(PDO::FETCH_ASSOC)
            ]);
            exit;
        }

        echo json_encode([
            'success' => true,
            'subjects' => $subjectController->getAllSubjects()
        ]);
        exit;
    }

    if ($method === 'POST') {
        $action = $input['action'] ?? '';

        if ($action === 'add') {
            $result = $subjectController->addSubject($input);
        } elseif ($action === 'update') {
            $result = $subjectController->updateSubject($input);
        } elseif ($action === 'update_status') {
            $result = $subjectController->updateSubjectStatus($input);
        } else {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'Invalid action.'
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
