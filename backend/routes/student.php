<?php

header('Content-Type: application/json');

require_once __DIR__ . '/../middleware/AuthMiddleware.php';

AuthMiddleware::requireRole('student');

echo json_encode([
    'success' => true,
    'message' => 'Student access granted.',
    'user' => [
        'user_id' => $_SESSION['user_id'],
        'full_name' => $_SESSION['full_name'],
        'role' => $_SESSION['role']
    ]
]);
