
<?php

require_once __DIR__ . '/../controllers/AuthController.php';

$method = $_SERVER['REQUEST_METHOD'];

$isJsonRequest =
    stripos($_SERVER['CONTENT_TYPE'] ?? '', 'application/json') !== false ||
    stripos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') !== false ||
    strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'xmlhttprequest';

function authResponse(int $statusCode, array $data): void
{
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

if ($method !== 'POST') {
    authResponse(405, [
        'success' => false,
        'message' => 'Method not allowed.'
    ]);
}

$input = json_decode(file_get_contents('php://input'), true);

if (!is_array($input)) {
    $input = $_POST;
}

$action = $input['action'] ?? '';

if ($action === 'login') {

    $email = trim($input['email'] ?? '');
    $password = $input['password'] ?? '';

    if ($email === '' || $password === '') {
        authResponse(422, [
            'success' => false,
            'message' => 'Email and password are required.'
        ]);
    }

    $result = $authController->login($email, $password);

    if (!$result['success']) {
        authResponse(401, $result);
    }

    authResponse(200, $result);
}

if ($action === 'logout') {

    $authController->logout();

    if ($isJsonRequest) {
        authResponse(200, [
            'success' => true,
            'message' => 'Logged out successfully.'
        ]);
    }

    header('Cache-Control: no-store, no-cache, must-revalidate');
    header('Location: ../../index.php');
    exit;
}

authResponse(400, [
    'success' => false,
    'message' => 'Invalid action.'
]);
