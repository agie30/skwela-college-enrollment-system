<?php

header('Content-Type: application/json');

require_once __DIR__ . '/../middleware/AuthMiddleware.php';
require_once __DIR__ . '/../controllers/AcademicYearController.php';

AuthMiddleware::requireRole('admin');

$method = $_SERVER['REQUEST_METHOD'];

$input = json_decode(file_get_contents('php://input'), true);

if (!is_array($input)) {
    $input = $_POST;
}

try {
    if ($method === 'GET') {
        echo json_encode([
            'success' => true,
            'academic_years' => $academicYearController->getAllAcademicYears()
        ]);
        exit;
    }

    if ($method === 'POST') {
        $action = $input['action'] ?? '';

        if ($action === 'add') {
            $result = $academicYearController->addAcademicYear($input);
        } elseif ($action === 'update') {
            $result = $academicYearController->updateAcademicYear($input);
        } elseif ($action === 'update_status') {
            $result = $academicYearController->updateAcademicYearStatus($input);
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
