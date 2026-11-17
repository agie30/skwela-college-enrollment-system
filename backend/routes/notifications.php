
<?php

header('Content-Type: application/json');

require_once __DIR__ . '/../middleware/AuthMiddleware.php';
require_once __DIR__ . '/../controllers/NotificationController.php';

AuthMiddleware::requireLogin();

$userId = (int) $_SESSION['user_id'];
$method = $_SERVER['REQUEST_METHOD'];

try {
    if ($method === 'GET') {
        echo json_encode([
            'success' => true,
            'notifications' => $notificationController->getUserNotifications($userId),
            'unread_count' => $notificationController->getUnreadCount($userId)
        ]);
        exit;
    }

    if ($method === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);

        if (!is_array($input)) {
            $input = $_POST;
        }

        $action = $input['action'] ?? '';

        if ($action === 'mark_read') {
            $result = $notificationController->markAsRead(
                (int) ($input['notification_id'] ?? 0),
                $userId
            );
        } elseif ($action === 'mark_all_read') {
            $result = $notificationController->markAllAsRead($userId);
        } else {
            http_response_code(400);

            echo json_encode([
                'success' => false,
                'message' => 'Invalid notification action.'
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
}
