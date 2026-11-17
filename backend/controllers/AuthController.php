<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/ActivityLogController.php';

class AuthController
{
    private PDO $pdo;
    private ActivityLogController $activityLogger;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
        $this->activityLogger = new ActivityLogController($pdo);
    }

    private function logActivity(
        ?int $userId,
        string $activity,
        ?string $details = null
    ): void {
        try {
            $this->activityLogger->recordActivity(
                $userId,
                $activity,
                $details
            );
        } catch (PDOException $e) {
            error_log($e->getMessage());
        }
    }

    public function login(string $email, string $password): array
    {
        $sql = "
            SELECT
                u.user_id,
                u.role_id,
                u.full_name,
                u.email,
                u.password,
                u.account_status,
                r.role_name
            FROM users u
            INNER JOIN roles r ON r.role_id = u.role_id
            WHERE u.email = :email
            LIMIT 1
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'email' => trim($email)
        ]);

        $user = $stmt->fetch();

        if (!$user || !password_verify($password, $user['password'])) {
            $this->logActivity(
                null,
                'Failed Login',
                'Unsuccessful login attempt for email: ' . trim($email)
            );

            return [
                'success' => false,
                'message' => 'Invalid email or password.'
            ];
        }

        if ($user['account_status'] !== 'active') {
            $this->logActivity(
                (int) $user['user_id'],
                'Blocked Login',
                'Login attempt on an inactive account.'
            );

            return [
                'success' => false,
                'message' => 'Your account is not active.'
            ];
        }

        session_regenerate_id(true);

        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['role_id'] = $user['role_id'];
        $_SESSION['role'] = $user['role_name'];
        $_SESSION['full_name'] = $user['full_name'];
        $_SESSION['email'] = $user['email'];

        $this->logActivity(
            (int) $user['user_id'],
            'Successful Login',
            $user['full_name'] . ' logged in successfully.'
        );

        return [
            'success' => true,
            'message' => 'Login successful.',
            'role' => $user['role_name']
        ];
    }

    public function logout(): void
    {
        $userId = isset($_SESSION['user_id'])
            ? (int) $_SESSION['user_id']
            : null;

        $fullName = $_SESSION['full_name'] ?? 'User';

        if ($userId !== null) {
            $this->logActivity(
                $userId,
                'Logout',
                $fullName . ' logged out successfully.'
            );
        }

        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();

            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }

        session_destroy();
    }
}

$authController = new AuthController($pdo);
