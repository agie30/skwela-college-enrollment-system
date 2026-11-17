<?php

class AuthMiddleware
{
    public static function requireLogin(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);
            header('Content-Type: application/json');

            echo json_encode([
                'success' => false,
                'message' => 'Authentication required.'
            ]);

            exit;
        }
    }

    public static function requireRole(string $role): void
    {
        self::requireLogin();

        if (($_SESSION['role'] ?? '') !== $role) {
            http_response_code(403);
            header('Content-Type: application/json');

            echo json_encode([
                'success' => false,
                'message' => 'Access denied.'
            ]);

            exit;
        }
    }
}