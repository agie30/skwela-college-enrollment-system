
<?php

header('Content-Type: application/json');

require_once __DIR__ . '/../middleware/AuthMiddleware.php';
require_once __DIR__ . '/../controllers/CourseController.php';

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
        echo json_encode([
            'success' => true,
            'courses' => $courseController->getAllCourses()
        ]);
        exit;
    }

    if ($method === 'POST') {
        $action = $input['action'] ?? '';

        if ($action === 'add') {
            $result = $courseController->addCourse($input);
        } elseif ($action === 'update') {
            $result = $courseController->updateCourse($input);
        } elseif ($action === 'update_status') {
            $result = $courseController->updateCourseStatus($input);
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
