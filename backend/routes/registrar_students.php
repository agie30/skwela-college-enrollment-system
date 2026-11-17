<?php
session_start();

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/database.php';

function registrarStudentsResponse(int $status, array $data): void
{
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit();
}

if (
    !isset($_SESSION['user_id']) ||
    !in_array($_SESSION['role'] ?? '', ['registrar', 'admin'], true)
) {
    registrarStudentsResponse(403, [
        'success' => false,
        'message' => 'Access denied.'
    ]);
}

if (!isset($pdo) || !($pdo instanceof PDO)) {
    registrarStudentsResponse(500, [
        'success' => false,
        'message' => 'Database connection unavailable.'
    ]);
}

try {
    $profileColumns = [];
    foreach ($pdo->query("SHOW COLUMNS FROM student_profiles")->fetchAll(PDO::FETCH_ASSOC) as $column) {
        $profileColumns[$column['Field']] = true;
    }

    $documentColumns = [];
    foreach ($pdo->query("SHOW COLUMNS FROM enrollment_documents")->fetchAll(PDO::FETCH_ASSOC) as $column) {
        $documentColumns[$column['Field']] = true;
    }

    $userColumns = [];
    foreach ($pdo->query("SHOW COLUMNS FROM users")->fetchAll(PDO::FETCH_ASSOC) as $column) {
        $userColumns[$column['Field']] = true;
    }

    $statusExpression = isset($documentColumns['verification_status'])
        ? "ed.verification_status"
        : "'pending'";

    $optionalFields = [
        'birth_date',
        'gender',
        'phone',
        'address',
        'emergency_contact_name',
        'emergency_contact_number'
    ];

    $profileSelect = [];

    foreach ($optionalFields as $field) {
        $profileSelect[] = isset($profileColumns[$field])
            ? "sp.`$field`"
            : "NULL AS `$field`";
    }

    $userSelect = [];

    foreach (['account_status', 'created_at'] as $field) {
        $userSelect[] = isset($userColumns[$field])
            ? "u.`$field`"
            : "NULL AS `$field`";
    }

    $studentId = (int) ($_GET['student_id'] ?? 0);

    if ($studentId > 0) {

        $studentSql = "
            SELECT
                sp.student_id,
                sp.user_id,
                sp.student_number,
                sp.first_name,
                sp.middle_name,
                sp.last_name,
                u.full_name,
                u.email,
                " . implode(", ", $profileSelect) . ",
                " . implode(", ", $userSelect) . "
            FROM student_profiles sp
            INNER JOIN users u ON u.user_id = sp.user_id
            WHERE sp.student_id = :student_id
            LIMIT 1
        ";

        $studentStatement = $pdo->prepare($studentSql);
        $studentStatement->execute([
            'student_id' => $studentId
        ]);

        $student = $studentStatement->fetch(PDO::FETCH_ASSOC);

        if (!$student) {
            registrarStudentsResponse(404, [
                'success' => false,
                'message' => 'Student record not found.'
            ]);
        }

        $nameParts = [];

        foreach (['first_name', 'middle_name', 'last_name'] as $field) {
            $value = trim((string) ($student[$field] ?? ''));

            if ($value !== '') {
                $nameParts[] = $value;
            }
        }

        $studentName = trim(implode(' ', $nameParts));

        $student['display_name'] = $studentName !== ''
            ? $studentName
            : ($student['full_name'] ?? 'Student');

        $enrollmentStatement = $pdo->prepare("
            SELECT
                e.enrollment_id,
                e.student_id,
                e.course_id,
                e.section_id,
                e.academic_year_id,
                e.semester_id,
                e.year_level,
                e.student_type,
                e.status,
                e.remarks,
                e.submitted_at,
                c.course_code,
                c.course_name,
                s.section_name,
                ay.year_name,
                sem.semester_name
            FROM enrollments e
            LEFT JOIN courses c
                ON c.course_id = e.course_id
            LEFT JOIN sections s
                ON s.section_id = e.section_id
            LEFT JOIN academic_years ay
                ON ay.academic_year_id = e.academic_year_id
            LEFT JOIN semesters sem
                ON sem.semester_id = e.semester_id
            WHERE e.student_id = :student_id
            ORDER BY e.submitted_at DESC, e.enrollment_id DESC
        ");

        $enrollmentStatement->execute([
            'student_id' => $studentId
        ]);

        $enrollments = $enrollmentStatement->fetchAll(PDO::FETCH_ASSOC);

        $documentStatement = $pdo->prepare("
            SELECT
                ed.document_id,
                ed.enrollment_id,
                ed.document_name,
                ed.uploaded_at,
                {$statusExpression} AS verification_status
            FROM enrollment_documents ed
            INNER JOIN enrollments e
                ON e.enrollment_id = ed.enrollment_id
            WHERE e.student_id = :student_id
            ORDER BY ed.uploaded_at DESC, ed.document_id DESC
        ");

        $documentStatement->execute([
            'student_id' => $studentId
        ]);

        $documents = $documentStatement->fetchAll(PDO::FETCH_ASSOC);

        registrarStudentsResponse(200, [
            'success' => true,
            'student' => $student,
            'enrollments' => $enrollments,
            'documents' => $documents
        ]);
    }

    $listSql = "
        SELECT
            sp.student_id,
            sp.user_id,
            sp.student_number,
            sp.first_name,
            sp.middle_name,
            sp.last_name,
            u.full_name,
            u.email,
            current_course.course_code AS current_course_code,
            current_course.course_name AS current_course_name,
            current_section.section_name AS current_section_name,
            current_enrollment.year_level AS current_year_level,
            current_academic_year.year_name AS current_year_name,
            current_semester.semester_name AS current_semester_name,

            (
                SELECT COUNT(*)
                FROM enrollments e
                WHERE e.student_id = sp.student_id
            ) AS application_count,

            (
                SELECT COUNT(*)
                FROM enrollment_documents ed
                INNER JOIN enrollments e
                    ON e.enrollment_id = ed.enrollment_id
                WHERE e.student_id = sp.student_id
            ) AS document_count,

            (
                SELECT COUNT(*)
                FROM enrollment_documents ed
                INNER JOIN enrollments e
                    ON e.enrollment_id = ed.enrollment_id
                WHERE e.student_id = sp.student_id
                AND {$statusExpression} = 'pending'
            ) AS pending_document_count

        FROM student_profiles sp
        INNER JOIN users u ON u.user_id = sp.user_id
        LEFT JOIN (
            SELECT e1.student_id, MAX(e1.enrollment_id) AS latest_enrollment_id
            FROM enrollments e1
            GROUP BY e1.student_id
        ) latest_enrollment
            ON latest_enrollment.student_id = sp.student_id
        LEFT JOIN enrollments current_enrollment
            ON current_enrollment.enrollment_id = latest_enrollment.latest_enrollment_id
        LEFT JOIN courses current_course
            ON current_course.course_id = current_enrollment.course_id
        LEFT JOIN sections current_section
            ON current_section.section_id = current_enrollment.section_id
        LEFT JOIN academic_years current_academic_year
            ON current_academic_year.academic_year_id = current_enrollment.academic_year_id
        LEFT JOIN semesters current_semester
            ON current_semester.semester_id = current_enrollment.semester_id
        ORDER BY sp.last_name ASC, sp.first_name ASC, sp.student_number ASC
    ";

    $students = $pdo->query($listSql)->fetchAll(PDO::FETCH_ASSOC);

    foreach ($students as &$student) {

        $nameParts = [];

        foreach (['first_name', 'middle_name', 'last_name'] as $field) {
            $value = trim((string) ($student[$field] ?? ''));

            if ($value !== '') {
                $nameParts[] = $value;
            }
        }

        $name = trim(implode(' ', $nameParts));

        $student['display_name'] = $name !== ''
            ? $name
            : ($student['full_name'] ?? 'Student');

        $student['application_count'] = (int) $student['application_count'];
        $student['document_count'] = (int) $student['document_count'];
        $student['pending_document_count'] = (int) $student['pending_document_count'];
    }

    unset($student);

    registrarStudentsResponse(200, [
        'success' => true,
        'students' => $students
    ]);

} catch (PDOException $error) {

    error_log('Registrar students route: ' . $error->getMessage());

    registrarStudentsResponse(500, [
        'success' => false,
        'message' => 'Unable to load student records. Check the database tables.'
    ]);

} catch (Throwable $error) {

    error_log('Registrar students route: ' . $error->getMessage());

    registrarStudentsResponse(500, [
        'success' => false,
        'message' => 'An unexpected error occurred.'
    ]);
}