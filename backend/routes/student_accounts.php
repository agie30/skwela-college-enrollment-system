
<?php

header('Content-Type: application/json');

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../middleware/AuthMiddleware.php';
require_once __DIR__ . '/../controllers/ActivityLogController.php';

AuthMiddleware::requireRole('admin');

$adminId = (int) ($_SESSION['user_id'] ?? 0);
$method = $_SERVER['REQUEST_METHOD'];

$input = json_decode(file_get_contents('php://input'), true);

if (!is_array($input)) {
    $input = $_POST;
}

function studentAccountResponse(int $statusCode, array $data): void
{
    http_response_code($statusCode);
    echo json_encode($data);
    exit;
}

function studentAccountLog(
    PDO $pdo,
    int $adminId,
    string $activity,
    string $details
): void {
    try {
        $logger = new ActivityLogController($pdo);
        $logger->recordActivity($adminId, $activity, $details);
    } catch (Throwable $e) {
        error_log($e->getMessage());
    }
}

function validStudentBirthDate(string $date): bool
{
    $parsed = DateTimeImmutable::createFromFormat('!Y-m-d', $date);

    return $parsed !== false && $parsed->format('Y-m-d') === $date;
}

try {

    if ($method === 'GET') {

        $stmt = $pdo->prepare("
            SELECT
                u.user_id,
                u.full_name,
                u.email,
                u.account_status,
                u.created_at,
                sp.student_id,
                sp.student_number,
                sp.first_name,
                sp.middle_name,
                sp.last_name,
                sp.birth_date,
                sp.gender,
                sp.phone,
                sp.address,
                sp.emergency_contact_name,
                sp.emergency_contact_number,
                latest_enrollment.enrollment_id AS latest_enrollment_id,
                latest_enrollment.status AS enrollment_status,
                latest_course.course_code,
                latest_course.course_name,
                latest_section.section_id,
                latest_section.section_name,
                latest_section.year_level AS enrollment_year_level,
                latest_year.year_name,
                latest_semester.semester_name
            FROM users u
            INNER JOIN student_profiles sp
                ON sp.user_id = u.user_id
            LEFT JOIN enrollments latest_enrollment
                ON latest_enrollment.enrollment_id = (
                    SELECT e2.enrollment_id
                    FROM enrollments e2
                    WHERE e2.student_id = sp.student_id
                    ORDER BY e2.enrollment_id DESC
                    LIMIT 1
                )
            LEFT JOIN courses latest_course
                ON latest_course.course_id = latest_enrollment.course_id
            LEFT JOIN sections latest_section
                ON latest_section.section_id = latest_enrollment.section_id
            LEFT JOIN academic_years latest_year
                ON latest_year.academic_year_id = latest_enrollment.academic_year_id
            LEFT JOIN semesters latest_semester
                ON latest_semester.semester_id = latest_enrollment.semester_id
            WHERE u.role_id = 3
            ORDER BY sp.last_name ASC, sp.first_name ASC, sp.middle_name ASC, u.created_at ASC
        ");

        $stmt->execute();

        studentAccountResponse(200, [
            'success' => true,
            'students' => $stmt->fetchAll(PDO::FETCH_ASSOC)
        ]);
    }

    if ($method === 'POST') {

        $action = $input['action'] ?? '';

        if ($action === 'add') {

            $firstName = trim($input['first_name'] ?? '');
            $middleName = trim($input['middle_name'] ?? '');
            $lastName = trim($input['last_name'] ?? '');
            $email = strtolower(trim($input['email'] ?? ''));
            $password = $input['password'] ?? '';

            $birthDate = trim($input['birth_date'] ?? '');
            $gender = trim($input['gender'] ?? '');
            $phone = trim($input['phone'] ?? '');
            $address = trim($input['address'] ?? '');
            $emergencyName = trim($input['emergency_contact_name'] ?? '');
            $emergencyNumber = trim($input['emergency_contact_number'] ?? '');

            if (
                $firstName === '' ||
                strlen($firstName) > 100 ||
                $lastName === '' ||
                strlen($lastName) > 100 ||
                strlen($middleName) > 100 ||
                $email === '' ||
                strlen($email) > 150 ||
                !filter_var($email, FILTER_VALIDATE_EMAIL) ||
                !is_string($password) ||
                strlen($password) < 10
            ) {
                studentAccountResponse(422, [
                    'success' => false,
                    'message' => 'Please provide valid first name, last name, email, and a password containing at least 10 characters.'
                ]);
            }

            if ($birthDate !== '' && !validStudentBirthDate($birthDate)) {
                studentAccountResponse(422, [
                    'success' => false,
                    'message' => 'Please enter a valid birth date.'
                ]);
            }

            if (
                strlen($gender) > 30 ||
                strlen($phone) > 30 ||
                strlen($emergencyName) > 150 ||
                strlen($emergencyNumber) > 30
            ) {
                studentAccountResponse(422, [
                    'success' => false,
                    'message' => 'One or more profile fields exceed the allowed length.'
                ]);
            }

            $fullName = trim(
                $firstName . ' ' .
                ($middleName !== '' ? $middleName . ' ' : '') .
                $lastName
            );

            if (strlen($fullName) > 150) {
                studentAccountResponse(422, [
                    'success' => false,
                    'message' => 'The complete name is too long.'
                ]);
            }

            $pdo->beginTransaction();

            $checkEmail = $pdo->prepare("
                SELECT user_id
                FROM users
                WHERE email = ?
                LIMIT 1
            ");

            $checkEmail->execute([$email]);

            if ($checkEmail->fetch()) {
                $pdo->rollBack();

                studentAccountResponse(409, [
                    'success' => false,
                    'message' => 'This email address is already registered.'
                ]);
            }

            $year = date('Y');

            $numberQuery = $pdo->prepare("
                SELECT COALESCE(
                    MAX(CAST(SUBSTRING_INDEX(student_number, '-', -1) AS UNSIGNED)),
                    0
                ) AS last_number
                FROM student_profiles
                WHERE student_number LIKE :prefix
            ");

            $numberQuery->execute([
                'prefix' => $year . '-%'
            ]);

            $lastNumber = (int) $numberQuery->fetchColumn();
            $studentNumber = $year . '-' . str_pad(
                (string) ($lastNumber + 1),
                4,
                '0',
                STR_PAD_LEFT
            );

            $passwordHash = password_hash($password, PASSWORD_DEFAULT);

            $createUser = $pdo->prepare("
                INSERT INTO users (
                    role_id,
                    full_name,
                    email,
                    password,
                    account_status
                )
                VALUES (
                    3,
                    :full_name,
                    :email,
                    :password,
                    'active'
                )
            ");

            $createUser->execute([
                'full_name' => $fullName,
                'email' => $email,
                'password' => $passwordHash
            ]);

            $userId = (int) $pdo->lastInsertId();

            $createProfile = $pdo->prepare("
                INSERT INTO student_profiles (
                    user_id,
                    student_number,
                    first_name,
                    middle_name,
                    last_name,
                    birth_date,
                    gender,
                    phone,
                    address,
                    emergency_contact_name,
                    emergency_contact_number
                )
                VALUES (
                    :user_id,
                    :student_number,
                    :first_name,
                    :middle_name,
                    :last_name,
                    :birth_date,
                    :gender,
                    :phone,
                    :address,
                    :emergency_contact_name,
                    :emergency_contact_number
                )
            ");

            $createProfile->execute([
                'user_id' => $userId,
                'student_number' => $studentNumber,
                'first_name' => $firstName,
                'middle_name' => $middleName !== '' ? $middleName : null,
                'last_name' => $lastName,
                'birth_date' => $birthDate !== '' ? $birthDate : null,
                'gender' => $gender !== '' ? $gender : null,
                'phone' => $phone !== '' ? $phone : null,
                'address' => $address !== '' ? $address : null,
                'emergency_contact_name' => $emergencyName !== '' ? $emergencyName : null,
                'emergency_contact_number' => $emergencyNumber !== '' ? $emergencyNumber : null
            ]);

            $studentId = (int) $pdo->lastInsertId();

            $pdo->commit();

            studentAccountLog(
                $pdo,
                $adminId,
                'Student Account Created',
                'Student account for ' . $fullName .
                ' was created. Student Number: ' .
                $studentNumber . '. User ID: ' . $userId . '.'
            );

            studentAccountResponse(201, [
                'success' => true,
                'message' => 'Student account created successfully.',
                'user_id' => $userId,
                'student_id' => $studentId,
                'student_number' => $studentNumber
            ]);
        }

        if ($action === 'update_status') {

            $studentUserId = (int) ($input['user_id'] ?? 0);
            $status = $input['account_status'] ?? '';

            if (
                $studentUserId < 1 ||
                !in_array($status, ['active', 'inactive', 'suspended'], true)
            ) {
                studentAccountResponse(422, [
                    'success' => false,
                    'message' => 'Invalid student account details.'
                ]);
            }

            $check = $pdo->prepare("
                SELECT
                    u.full_name,
                    u.account_status,
                    sp.student_number
                FROM users u
                INNER JOIN student_profiles sp
                    ON sp.user_id = u.user_id
                WHERE u.user_id = ?
                AND u.role_id = 3
                LIMIT 1
            ");

            $check->execute([$studentUserId]);
            $student = $check->fetch(PDO::FETCH_ASSOC);

            if (!$student) {
                studentAccountResponse(404, [
                    'success' => false,
                    'message' => 'Student account not found.'
                ]);
            }

            if ($student['account_status'] === $status) {
                studentAccountResponse(200, [
                    'success' => true,
                    'message' => 'The account already has this status.'
                ]);
            }

            $update = $pdo->prepare("
                UPDATE users
                SET account_status = :status
                WHERE user_id = :user_id
                AND role_id = 3
            ");

            $update->execute([
                'status' => $status,
                'user_id' => $studentUserId
            ]);

            studentAccountLog(
                $pdo,
                $adminId,
                'Student Account Status Updated',
                'Student ' . $student['full_name'] .
                ' (' . $student['student_number'] .
                ') status changed from ' .
                $student['account_status'] . ' to ' . $status . '.'
            );

            studentAccountResponse(200, [
                'success' => true,
                'message' => 'Student account status updated successfully.'
            ]);
        }

        studentAccountResponse(400, [
            'success' => false,
            'message' => 'Invalid action.'
        ]);
    }

    studentAccountResponse(405, [
        'success' => false,
        'message' => 'Method not allowed.'
    ]);

} catch (PDOException $e) {

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log($e->getMessage());

    if ($e->getCode() === '23000') {
        studentAccountResponse(409, [
            'success' => false,
            'message' => 'A duplicate email or student number already exists. Please refresh and try again.'
        ]);
    }

    studentAccountResponse(500, [
        'success' => false,
        'message' => 'A database error occurred while processing the student account.'
    ]);

} catch (Throwable $e) {

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log($e->getMessage());

    studentAccountResponse(500, [
        'success' => false,
        'message' => 'An unexpected server error occurred.'
    ]);
}
