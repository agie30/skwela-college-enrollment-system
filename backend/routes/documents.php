<?php
session_start();

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/database.php';

function documentResponse(int $statusCode, array $payload): void
{
    http_response_code($statusCode);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
    exit;
}

function documentInput(): array
{
    $contentType = $_SERVER['CONTENT_TYPE'] ?? '';

    if (stripos($contentType, 'application/json') !== false) {
        $raw = file_get_contents('php://input');
        $decoded = json_decode($raw ?: '[]', true);
        return is_array($decoded) ? $decoded : [];
    }

    return $_POST;
}

function documentColumns(PDO $pdo): array
{
    $columns = [];
    $statement = $pdo->query('SHOW COLUMNS FROM enrollment_documents');

    foreach ($statement->fetchAll(PDO::FETCH_ASSOC) as $column) {
        $columns[$column['Field']] = true;
    }

    return $columns;
}

if (!isset($_SESSION['user_id'], $_SESSION['role'])) {
    documentResponse(401, [
        'success' => false,
        'message' => 'Please log in to continue.'
    ]);
}

if (!isset($pdo) || !($pdo instanceof PDO)) {
    documentResponse(500, [
        'success' => false,
        'message' => 'Database connection is not available.'
    ]);
}

$userId = (int) $_SESSION['user_id'];
$role = strtolower(trim((string) $_SESSION['role']));
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

try {
    $columns = documentColumns($pdo);

    if ($method === 'GET') {
        if (!in_array($role, ['student', 'registrar', 'admin'], true)) {
            documentResponse(403, [
                'success' => false,
                'message' => 'You are not allowed to view enrollment documents.'
            ]);
        }

        $verificationSelect = isset($columns['verification_status'])
            ? 'ed.verification_status'
            : "'pending' AS verification_status";

        $sql = "
            SELECT
                ed.document_id,
                ed.enrollment_id,
                ed.document_name,
                ed.file_path,
                ed.uploaded_at,
                {$verificationSelect},
                e.status AS enrollment_status,
                e.student_id,
                sp.student_number,
                sp.first_name,
                sp.middle_name,
                sp.last_name,
                u.user_id,
                u.full_name,
                u.email,
                COALESCE(
                    NULLIF(u.full_name, ''),
                    TRIM(CONCAT_WS(' ', sp.first_name, sp.middle_name, sp.last_name))
                ) AS student_name
            FROM enrollment_documents ed
            INNER JOIN enrollments e
                ON e.enrollment_id = ed.enrollment_id
            INNER JOIN student_profiles sp
                ON sp.student_id = e.student_id
            INNER JOIN users u
                ON u.user_id = sp.user_id
        ";

        if ($role === 'student') {
            $sql .= ' WHERE sp.user_id = :user_id';
        }

        $sql .= ' ORDER BY ed.uploaded_at DESC, ed.document_id DESC';

        $statement = $pdo->prepare($sql);
        $statement->execute(
            $role === 'student' ? ['user_id' => $userId] : []
        );

        documentResponse(200, [
            'success' => true,
            'documents' => $statement->fetchAll(PDO::FETCH_ASSOC)
        ]);
    }

    if ($method !== 'POST') {
        header('Allow: GET, POST');

        documentResponse(405, [
            'success' => false,
            'message' => 'Method not allowed.'
        ]);
    }

    if (in_array($role, ['registrar', 'admin'], true)) {
        $input = documentInput();

        $documentId = (int) ($input['document_id'] ?? 0);
        $verificationStatus = strtolower(trim(
            (string) ($input['verification_status'] ?? '')
        ));

        if (
            $documentId < 1 ||
            !in_array($verificationStatus, ['verified', 'rejected'], true)
        ) {
            documentResponse(400, [
                'success' => false,
                'message' => 'Invalid document verification details.'
            ]);
        }

        if (!isset($columns['verification_status'])) {
            documentResponse(500, [
                'success' => false,
                'message' => 'The verification_status column is missing from enrollment_documents.'
            ]);
        }

        $findStatement = $pdo->prepare("
            SELECT document_id, enrollment_id, document_name
            FROM enrollment_documents
            WHERE document_id = :document_id
            LIMIT 1
        ");

        $findStatement->execute([
            'document_id' => $documentId
        ]);

        $document = $findStatement->fetch(PDO::FETCH_ASSOC);

        if (!$document) {
            documentResponse(404, [
                'success' => false,
                'message' => 'Document not found.'
            ]);
        }

        $setParts = [
            'verification_status = :verification_status'
        ];

        $parameters = [
            'verification_status' => $verificationStatus,
            'document_id' => $documentId
        ];

        if (isset($columns['verified_by'])) {
            $setParts[] = 'verified_by = :verified_by';
            $parameters['verified_by'] = $userId;
        }

        if (isset($columns['verified_at'])) {
            $setParts[] = 'verified_at = CURRENT_TIMESTAMP';
        }

        if (isset($columns['updated_at'])) {
            $setParts[] = 'updated_at = CURRENT_TIMESTAMP';
        }

        $updateSql = "
            UPDATE enrollment_documents
            SET " . implode(', ', $setParts) . "
            WHERE document_id = :document_id
        ";

        $updateStatement = $pdo->prepare($updateSql);
        $updateStatement->execute($parameters);

        if ($updateStatement->rowCount() === 0) {
            $checkStatement = $pdo->prepare("
                SELECT verification_status
                FROM enrollment_documents
                WHERE document_id = :document_id
            ");

            $checkStatement->execute([
                'document_id' => $documentId
            ]);

            $currentStatus = $checkStatement->fetchColumn();

            if ($currentStatus !== $verificationStatus) {
                documentResponse(500, [
                    'success' => false,
                    'message' => 'Unable to update document verification status.'
                ]);
            }
        }

        try {
            $activityStatement = $pdo->prepare("
                INSERT INTO activity_logs (user_id, activity)
                VALUES (:user_id, :activity)
            ");

            $activityStatement->execute([
                'user_id' => $userId,
                'activity' => ucfirst($verificationStatus)
                    . ' enrollment document #'
                    . $documentId
                    . ' ('
                    . $document['document_name']
                    . ').'
            ]);
        } catch (Throwable $activityError) {
            error_log('Document activity log: ' . $activityError->getMessage());
        }

        documentResponse(200, [
            'success' => true,
            'message' => 'Document verification status updated successfully.',
            'document_id' => $documentId,
            'verification_status' => $verificationStatus
        ]);
    }

    if ($role !== 'student') {
        documentResponse(403, [
            'success' => false,
            'message' => 'Only students may upload enrollment documents.'
        ]);
    }

    $enrollmentId = (int) ($_POST['enrollment_id'] ?? 0);
    $documentName = trim((string) ($_POST['document_name'] ?? ''));

    $uploadedFile = $_FILES['document'] ?? $_FILES['file'] ?? null;

    if ($enrollmentId < 1 || $documentName === '' || !$uploadedFile) {
        documentResponse(400, [
            'success' => false,
            'message' => 'Please provide an enrollment application, document name, and file.'
        ]);
    }

    if (
        !isset($uploadedFile['error']) ||
        $uploadedFile['error'] !== UPLOAD_ERR_OK
    ) {
        documentResponse(400, [
            'success' => false,
            'message' => 'The selected file could not be uploaded.'
        ]);
    }

    if (($uploadedFile['size'] ?? 0) > 5 * 1024 * 1024) {
        documentResponse(413, [
            'success' => false,
            'message' => 'The file must not exceed 5 MB.'
        ]);
    }

    $enrollmentStatement = $pdo->prepare("
        SELECT e.enrollment_id
        FROM enrollments e
        INNER JOIN student_profiles sp
            ON sp.student_id = e.student_id
        WHERE e.enrollment_id = :enrollment_id
          AND sp.user_id = :user_id
        LIMIT 1
    ");

    $enrollmentStatement->execute([
        'enrollment_id' => $enrollmentId,
        'user_id' => $userId
    ]);

    if (!$enrollmentStatement->fetchColumn()) {
        documentResponse(403, [
            'success' => false,
            'message' => 'You may only upload documents for your own enrollment application.'
        ]);
    }

    $temporaryPath = $uploadedFile['tmp_name'] ?? '';

    if (!$temporaryPath || !is_uploaded_file($temporaryPath)) {
        documentResponse(400, [
            'success' => false,
            'message' => 'Invalid uploaded file.'
        ]);
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mimeType = $finfo->file($temporaryPath);

    $allowedMimeTypes = [
        'application/pdf' => 'pdf',
        'image/jpeg' => 'jpg',
        'image/png' => 'png'
    ];

    if (!isset($allowedMimeTypes[$mimeType])) {
        documentResponse(415, [
            'success' => false,
            'message' => 'Only PDF, JPG, and PNG files are allowed.'
        ]);
    }

    $projectRoot = dirname(__DIR__, 2);

    $uploadDirectory = $projectRoot
        . DIRECTORY_SEPARATOR . 'uploads'
        . DIRECTORY_SEPARATOR . 'enrollment_documents';

    if (
        !is_dir($uploadDirectory) &&
        !mkdir($uploadDirectory, 0775, true) &&
        !is_dir($uploadDirectory)
    ) {
        documentResponse(500, [
            'success' => false,
            'message' => 'Unable to prepare the document upload folder.'
        ]);
    }

    $storedFileName = bin2hex(random_bytes(20))
        . '.'
        . $allowedMimeTypes[$mimeType];

    $absoluteFilePath = $uploadDirectory
        . DIRECTORY_SEPARATOR
        . $storedFileName;

    $relativeFilePath = 'uploads/enrollment_documents/' . $storedFileName;

    if (!move_uploaded_file($temporaryPath, $absoluteFilePath)) {
        documentResponse(500, [
            'success' => false,
            'message' => 'Unable to save the uploaded document.'
        ]);
    }

    try {
        $insertColumns = [
            'enrollment_id',
            'document_name',
            'file_path'
        ];

        $insertValues = [
            ':enrollment_id',
            ':document_name',
            ':file_path'
        ];

        $insertParameters = [
            'enrollment_id' => $enrollmentId,
            'document_name' => $documentName,
            'file_path' => $relativeFilePath
        ];

        if (isset($columns['verification_status'])) {
            $insertColumns[] = 'verification_status';
            $insertValues[] = ':verification_status';

            $insertParameters['verification_status'] = 'pending';
        }

        $insertSql = "
            INSERT INTO enrollment_documents (
                " . implode(', ', $insertColumns) . "
            )
            VALUES (
                " . implode(', ', $insertValues) . "
            )
        ";

        $insertStatement = $pdo->prepare($insertSql);
        $insertStatement->execute($insertParameters);

        $documentId = (int) $pdo->lastInsertId();

        try {
            $activityStatement = $pdo->prepare("
                INSERT INTO activity_logs (user_id, activity)
                VALUES (:user_id, :activity)
            ");

            $activityStatement->execute([
                'user_id' => $userId,
                'activity' => 'Uploaded enrollment document #'
                    . $documentId
                    . ' ('
                    . $documentName
                    . ').'
            ]);
        } catch (Throwable $activityError) {
            error_log('Document activity log: ' . $activityError->getMessage());
        }

        documentResponse(201, [
            'success' => true,
            'message' => 'Document uploaded successfully.',
            'document_id' => $documentId,
            'enrollment_id' => $enrollmentId,
            'document_name' => $documentName,
            'verification_status' => 'pending'
        ]);

    } catch (Throwable $insertError) {
        if (is_file($absoluteFilePath)) {
            unlink($absoluteFilePath);
        }

        throw $insertError;
    }

} catch (PDOException $error) {
    error_log('Documents route database error: ' . $error->getMessage());

    documentResponse(500, [
        'success' => false,
        'message' => 'A database error occurred while processing the document request.'
    ]);

} catch (Throwable $error) {
    error_log('Documents route error: ' . $error->getMessage());

    documentResponse(500, [
        'success' => false,
        'message' => 'An unexpected error occurred while processing the document request.'
    ]);
}