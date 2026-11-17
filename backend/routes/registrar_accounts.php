<?php

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../middleware/AuthMiddleware.php';

AuthMiddleware::requireRole('admin');

function registrarAccountResponse(int $statusCode, array $payload): void
{
    http_response_code($statusCode);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
    exit;
}

function registrarAccountLog(PDO $pdo, int $adminId, string $activity, string $details): void
{
    try {
        $stmt = $pdo->prepare(
            'INSERT INTO activity_logs (user_id, activity, details, ip_address) VALUES (?, ?, ?, ?)'
        );
        $stmt->execute([
            $adminId,
            $activity,
            $details,
            $_SERVER['REMOTE_ADDR'] ?? null
        ]);
    } catch (Throwable $e) {
        // Account management should still succeed if activity logging is unavailable.
        error_log('Registrar account activity log: ' . $e->getMessage());
    }
}

$adminId = (int) ($_SESSION['user_id'] ?? 0);
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$input = json_decode(file_get_contents('php://input'), true);
if (!is_array($input)) {
    $input = $_POST;
}

try {
    $roleStmt = $pdo->prepare("SELECT role_id FROM roles WHERE role_name = 'registrar' LIMIT 1");
    $roleStmt->execute();
    $registrarRoleId = (int) $roleStmt->fetchColumn();

    if ($registrarRoleId < 1) {
        registrarAccountResponse(500, [
            'success' => false,
            'message' => 'The registrar role is not configured in the database.'
        ]);
    }

    if ($method === 'GET') {
        $stmt = $pdo->prepare(
            'SELECT user_id, full_name, email, account_status, last_login, created_at
             FROM users
             WHERE role_id = :role_id
             ORDER BY created_at DESC, user_id DESC'
        );
        $stmt->execute(['role_id' => $registrarRoleId]);
        registrarAccountResponse(200, [
            'success' => true,
            'registrars' => $stmt->fetchAll(PDO::FETCH_ASSOC)
        ]);
    }

    if ($method !== 'POST') {
        registrarAccountResponse(405, [
            'success' => false,
            'message' => 'Method not allowed.'
        ]);
    }

    $action = trim((string) ($input['action'] ?? ''));

    if ($action === 'add') {
        $fullName = trim((string) ($input['full_name'] ?? ''));
        $email = strtolower(trim((string) ($input['email'] ?? '')));
        $password = $input['password'] ?? '';

        if (
            $fullName === '' || strlen($fullName) > 150 ||
            $email === '' || strlen($email) > 150 || !filter_var($email, FILTER_VALIDATE_EMAIL) ||
            !is_string($password) || strlen($password) < 10
        ) {
            registrarAccountResponse(422, [
                'success' => false,
                'message' => 'Enter a valid full name, email address, and password of at least 10 characters.'
            ]);
        }

        $check = $pdo->prepare('SELECT user_id FROM users WHERE email = :email LIMIT 1');
        $check->execute(['email' => $email]);
        if ($check->fetchColumn()) {
            registrarAccountResponse(409, [
                'success' => false,
                'message' => 'This email address is already registered.'
            ]);
        }

        $insert = $pdo->prepare(
            "INSERT INTO users (role_id, full_name, email, password, account_status)
             VALUES (:role_id, :full_name, :email, :password, 'active')"
        );
        $insert->execute([
            'role_id' => $registrarRoleId,
            'full_name' => $fullName,
            'email' => $email,
            'password' => password_hash($password, PASSWORD_DEFAULT)
        ]);

        $newId = (int) $pdo->lastInsertId();
        registrarAccountLog($pdo, $adminId, 'Created registrar account', 'Created registrar account #' . $newId . ' for ' . $email . '.');

        registrarAccountResponse(201, [
            'success' => true,
            'message' => 'Registrar account created successfully.',
            'registrar_id' => $newId
        ]);
    }

    if ($action === 'update_status') {
        $userId = filter_var($input['user_id'] ?? null, FILTER_VALIDATE_INT);
        $status = strtolower(trim((string) ($input['account_status'] ?? '')));
        $allowedStatuses = ['active', 'inactive', 'suspended'];

        if (!$userId || !in_array($status, $allowedStatuses, true)) {
            registrarAccountResponse(422, [
                'success' => false,
                'message' => 'A valid registrar account and account status are required.'
            ]);
        }

        $find = $pdo->prepare(
            'SELECT user_id, full_name, account_status
             FROM users
             WHERE user_id = :user_id AND role_id = :role_id
             LIMIT 1'
        );
        $find->execute([
            'user_id' => (int) $userId,
            'role_id' => $registrarRoleId
        ]);
        $registrar = $find->fetch(PDO::FETCH_ASSOC);

        if (!$registrar) {
            registrarAccountResponse(404, [
                'success' => false,
                'message' => 'Registrar account not found.'
            ]);
        }

        $update = $pdo->prepare(
            'UPDATE users SET account_status = :status WHERE user_id = :user_id AND role_id = :role_id'
        );
        $update->execute([
            'status' => $status,
            'user_id' => (int) $userId,
            'role_id' => $registrarRoleId
        ]);

        registrarAccountLog(
            $pdo,
            $adminId,
            'Updated registrar account status',
            'Changed ' . $registrar['full_name'] . ' account status from ' . $registrar['account_status'] . ' to ' . $status . '.'
        );

        registrarAccountResponse(200, [
            'success' => true,
            'message' => 'Registrar account status updated successfully.'
        ]);
    }

    registrarAccountResponse(400, [
        'success' => false,
        'message' => 'Invalid account action.'
    ]);
} catch (PDOException $e) {
    error_log('Registrar account API: ' . $e->getMessage());
    if ((string) $e->getCode() === '23000') {
        registrarAccountResponse(409, [
            'success' => false,
            'message' => 'This email address is already registered.'
        ]);
    }
    registrarAccountResponse(500, [
        'success' => false,
        'message' => 'A database error occurred while managing registrar accounts.'
    ]);
} catch (Throwable $e) {
    error_log('Registrar account API: ' . $e->getMessage());
    registrarAccountResponse(500, [
        'success' => false,
        'message' => 'An unexpected error occurred while managing registrar accounts.'
    ]);
}
