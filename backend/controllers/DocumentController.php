<?php

require_once __DIR__ . '/../config/database.php';

class DocumentController
{
    private PDO $pdo;
    private string $uploadDirectory;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
        $this->uploadDirectory = dirname(__DIR__, 2) . '/uploads/enrollment_documents';
    }

    public function uploadDocument(
        int $userId,
        int $enrollmentId,
        string $documentName,
        array $file
    ): array {
        if (
            $userId < 1 ||
            $enrollmentId < 1 ||
            trim($documentName) === '' ||
            !isset(
                $file['tmp_name'],
                $file['error'],
                $file['size'],
                $file['name']
            ) ||
            $file['error'] !== UPLOAD_ERR_OK ||
            !is_uploaded_file($file['tmp_name'])
        ) {
            return [
                'success' => false,
                'message' => 'Invalid document upload.'
            ];
        }

        if (
            !is_numeric($file['size']) ||
            (int) $file['size'] < 1 ||
            (int) $file['size'] > 5 * 1024 * 1024
        ) {
            return [
                'success' => false,
                'message' => 'File size must be between 1 byte and 5 MB.'
            ];
        }

        $stmt = $this->pdo->prepare("
            SELECT e.enrollment_id
            FROM enrollments e
            INNER JOIN student_profiles sp
                ON sp.student_id = e.student_id
            WHERE e.enrollment_id = :enrollment_id
            AND sp.user_id = :user_id
            LIMIT 1
        ");

        $stmt->execute([
            'enrollment_id' => $enrollmentId,
            'user_id' => $userId
        ]);

        if (!$stmt->fetch()) {
            return [
                'success' => false,
                'message' => 'Enrollment record not found.'
            ];
        }

        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->file($file['tmp_name']);

        $allowedTypes = [
            'application/pdf' => 'pdf',
            'image/jpeg' => 'jpg',
            'image/png' => 'png'
        ];

        if (!isset($allowedTypes[$mimeType])) {
            return [
                'success' => false,
                'message' => 'Only PDF, JPG, and PNG files are allowed.'
            ];
        }

        if (!is_dir($this->uploadDirectory)) {
            if (
                !mkdir($this->uploadDirectory, 0750, true) &&
                !is_dir($this->uploadDirectory)
            ) {
                return [
                    'success' => false,
                    'message' => 'Unable to prepare upload directory.'
                ];
            }
        }

        $extension = $allowedTypes[$mimeType];
        $storedFilename = bin2hex(random_bytes(16)) . '.' . $extension;
        $destination = $this->uploadDirectory . '/' . $storedFilename;

        if (!move_uploaded_file($file['tmp_name'], $destination)) {
            return [
                'success' => false,
                'message' => 'Unable to save uploaded document.'
            ];
        }

        $relativePath = 'uploads/enrollment_documents/' . $storedFilename;

        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO enrollment_documents
                (
                    enrollment_id,
                    document_name,
                    original_filename,
                    stored_filename,
                    file_path,
                    mime_type,
                    file_size,
                    verification_status
                )
                VALUES
                (
                    :enrollment_id,
                    :document_name,
                    :original_filename,
                    :stored_filename,
                    :file_path,
                    :mime_type,
                    :file_size,
                    'pending'
                )
            ");

            $stmt->execute([
                'enrollment_id' => $enrollmentId,
                'document_name' => trim($documentName),
                'original_filename' => basename($file['name']),
                'stored_filename' => $storedFilename,
                'file_path' => $relativePath,
                'mime_type' => $mimeType,
                'file_size' => (int) $file['size']
            ]);

            return [
                'success' => true,
                'message' => 'Document uploaded successfully.',
                'document_id' => (int) $this->pdo->lastInsertId()
            ];

        } catch (Throwable $e) {
            if (is_file($destination)) {
                unlink($destination);
            }

            throw $e;
        }
    }

    public function getStudentDocuments(int $userId): array
    {
        $stmt = $this->pdo->prepare("
            SELECT
                d.*,
                e.enrollment_id
            FROM enrollment_documents d
            INNER JOIN enrollments e
                ON e.enrollment_id = d.enrollment_id
            INNER JOIN student_profiles sp
                ON sp.student_id = e.student_id
            WHERE sp.user_id = :user_id
            ORDER BY d.uploaded_at DESC
        ");

        $stmt->execute([
            'user_id' => $userId
        ]);

        return $stmt->fetchAll();
    }

    public function getAllDocuments(): array
    {
        $sql = "
            SELECT
                d.*,
                e.student_id,
                sp.user_id,
                u.full_name,
                e.enrollment_id
            FROM enrollment_documents d
            INNER JOIN enrollments e
                ON e.enrollment_id = d.enrollment_id
            INNER JOIN student_profiles sp
                ON sp.student_id = e.student_id
            INNER JOIN users u
                ON u.user_id = sp.user_id
            ORDER BY d.uploaded_at DESC
        ";

        return $this->pdo->query($sql)->fetchAll();
    }

    public function updateVerificationStatus(array $data): array
    {
        $documentId = (int) ($data['document_id'] ?? 0);
        $status = $data['verification_status'] ?? '';

        if (
            $documentId < 1 ||
            !in_array($status, ['verified', 'rejected'], true)
        ) {
            return [
                'success' => false,
                'message' => 'Invalid document verification details.'
            ];
        }

        $stmt = $this->pdo->prepare("
            UPDATE enrollment_documents
            SET verification_status = :status
            WHERE document_id = :document_id
        ");

        $stmt->execute([
            'status' => $status,
            'document_id' => $documentId
        ]);

        if ($stmt->rowCount() === 0) {
            $check = $this->pdo->prepare("
                SELECT document_id
                FROM enrollment_documents
                WHERE document_id = :document_id
            ");

            $check->execute([
                'document_id' => $documentId
            ]);

            if (!$check->fetch()) {
                return [
                    'success' => false,
                    'message' => 'Document not found.'
                ];
            }
        }

        return [
            'success' => true,
            'message' => 'Document verification status updated.'
        ];
    }
}

$documentController = new DocumentController($pdo);
