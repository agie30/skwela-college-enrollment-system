
<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../middleware/AuthMiddleware.php';

AuthMiddleware::requireRole('registrar');

$documentId = (int) ($_GET['document_id'] ?? 0);

if ($documentId <= 0) {
    http_response_code(400);
    exit('Invalid document ID.');
}

try {
    $stmt = $pdo->prepare("
        SELECT file_path, document_name
        FROM enrollment_documents
        WHERE document_id = :document_id
        LIMIT 1
    ");

    $stmt->execute([
        'document_id' => $documentId
    ]);

    $document = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$document) {
        http_response_code(404);
        exit('Document not found.');
    }

    $relativePath = str_replace(
        '\\',
        '/',
        trim($document['file_path'] ?? '')
    );

    $relativePath = ltrim($relativePath, '/');

    if (!str_starts_with($relativePath, 'uploads/enrollment_documents/')) {
        http_response_code(403);
        exit('Invalid document location.');
    }

    $projectRoot = dirname(__DIR__, 2);

    $uploadDirectory = realpath(
        $projectRoot . DIRECTORY_SEPARATOR .
        'uploads' . DIRECTORY_SEPARATOR .
        'enrollment_documents'
    );

    $filePath = realpath(
        $projectRoot . DIRECTORY_SEPARATOR .
        str_replace('/', DIRECTORY_SEPARATOR, $relativePath)
    );

    if (
        $uploadDirectory === false ||
        $filePath === false ||
        !is_file($filePath) ||
        !is_readable($filePath)
    ) {
        http_response_code(404);
        exit('Document file not found.');
    }

    $baseDirectory = rtrim(
        $uploadDirectory,
        DIRECTORY_SEPARATOR
    ) . DIRECTORY_SEPARATOR;

    if (stripos($filePath, $baseDirectory) !== 0) {
        http_response_code(403);
        exit('Access denied.');
    }

    $extension = strtolower(
        pathinfo($filePath, PATHINFO_EXTENSION)
    );

    $allowedTypes = [
        'pdf' => 'application/pdf',
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png' => 'image/png'
    ];

    if (!isset($allowedTypes[$extension])) {
        http_response_code(415);
        exit('Unsupported document format.');
    }

    $mimeType = $allowedTypes[$extension];

    if (function_exists('finfo_open')) {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);

        if ($finfo) {
            $detectedType = finfo_file($finfo, $filePath);
            finfo_close($finfo);

            if ($detectedType !== $mimeType) {
                http_response_code(415);
                exit('The document file type is invalid.');
            }
        }
    }

    if (ob_get_level()) {
        while (ob_get_level()) {
            ob_end_clean();
        }
    }

    header('Content-Type: ' . $mimeType);
    header('Content-Length: ' . filesize($filePath));
    header(
        'Content-Disposition: inline; filename="' .
        basename($filePath) . '"'
    );
    header('X-Content-Type-Options: nosniff');
    header('Cache-Control: private, no-store, no-cache, must-revalidate');

    readfile($filePath);
    exit();

} catch (Throwable $e) {
    error_log($e->getMessage());

    http_response_code(500);
    exit('Unable to preview document.');
}
