<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/ActivityLogController.php';

class AcademicYearController
{
    private PDO $pdo;
    private ActivityLogController $activityLogger;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
        $this->activityLogger = new ActivityLogController($pdo);
    }

    private function logActivity(
        string $activity,
        string $details
    ): void {
        $userId = (int) ($_SESSION['user_id'] ?? 0);

        if ($userId < 1) {
            return;
        }

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

    public function getAllAcademicYears(): array
    {
        $stmt = $this->pdo->query("
            SELECT *
            FROM academic_years
            ORDER BY start_date DESC
        ");

        return $stmt->fetchAll();
    }

    public function addAcademicYear(array $data): array
    {
        $startDate = is_string($data['start_date'] ?? null)
            ? trim($data['start_date'])
            : '';

        $endDate = is_string($data['end_date'] ?? null)
            ? trim($data['end_date'])
            : '';

        $status = $data['status'] ?? 'upcoming';

        if (!$this->isValidDate($startDate) || !$this->isValidDate($endDate)) {
            return [
                'success' => false,
                'message' => 'Valid start and end dates are required.'
            ];
        }

        if ($endDate <= $startDate) {
            return [
                'success' => false,
                'message' => 'End date must be later than start date.'
            ];
        }

        if (!in_array($status, ['upcoming', 'open', 'closed'], true)) {
            return [
                'success' => false,
                'message' => 'Invalid academic year status.'
            ];
        }

        $startYear = date('Y', strtotime($startDate));
        $endYear = date('Y', strtotime($endDate));
        $yearName = $startYear . '-' . $endYear;

        if ($this->hasDateOverlap($startDate, $endDate)) {
            return [
                'success' => false,
                'message' => 'The selected dates overlap an existing academic year.'
            ];
        }

        if ($status === 'open' && $this->hasOpenAcademicYear()) {
            return [
                'success' => false,
                'message' => 'Another academic year is already open.'
            ];
        }

        $stmt = $this->pdo->prepare("
            INSERT INTO academic_years
            (year_name, start_date, end_date, status)
            VALUES (?, ?, ?, ?)
        ");

        $stmt->execute([
            $yearName,
            $startDate,
            $endDate,
            $status
        ]);

        $academicYearId = (int) $this->pdo->lastInsertId();

        $this->logActivity(
            'Academic Year Created',
            'Academic year ' . $yearName .
            ' was created. Academic Year ID: ' . $academicYearId .
            ', start date: ' . $startDate .
            ', end date: ' . $endDate .
            ', status: ' . $status . '.'
        );

        return [
            'success' => true,
            'message' => 'Academic year created successfully.'
        ];
    }

    public function updateAcademicYear(array $data): array
    {
        $academicYearId = (int) ($data['academic_year_id'] ?? 0);

        $startDate = is_string($data['start_date'] ?? null)
            ? trim($data['start_date'])
            : '';

        $endDate = is_string($data['end_date'] ?? null)
            ? trim($data['end_date'])
            : '';

        $status = $data['status'] ?? '';

        if ($academicYearId <= 0) {
            return [
                'success' => false,
                'message' => 'Invalid academic year ID.'
            ];
        }

        if (!$this->isValidDate($startDate) || !$this->isValidDate($endDate)) {
            return [
                'success' => false,
                'message' => 'Valid start and end dates are required.'
            ];
        }

        if ($endDate <= $startDate) {
            return [
                'success' => false,
                'message' => 'End date must be later than start date.'
            ];
        }

        if (!in_array($status, ['upcoming', 'open', 'closed'], true)) {
            return [
                'success' => false,
                'message' => 'Invalid academic year status.'
            ];
        }

        $stmt = $this->pdo->prepare("
            SELECT *
            FROM academic_years
            WHERE academic_year_id = ?
        ");

        $stmt->execute([$academicYearId]);
        $oldAcademicYear = $stmt->fetch();

        if (!$oldAcademicYear) {
            return [
                'success' => false,
                'message' => 'Academic year not found.'
            ];
        }

        if ($this->hasDateOverlap($startDate, $endDate, $academicYearId)) {
            return [
                'success' => false,
                'message' => 'The selected dates overlap another academic year.'
            ];
        }

        if ($status === 'open' && $this->hasOpenAcademicYear($academicYearId)) {
            return [
                'success' => false,
                'message' => 'Another academic year is already open.'
            ];
        }

        $startYear = date('Y', strtotime($startDate));
        $endYear = date('Y', strtotime($endDate));
        $yearName = $startYear . '-' . $endYear;

        $stmt = $this->pdo->prepare("
            UPDATE academic_years
            SET year_name = ?,
                start_date = ?,
                end_date = ?,
                status = ?
            WHERE academic_year_id = ?
        ");

        $stmt->execute([
            $yearName,
            $startDate,
            $endDate,
            $status,
            $academicYearId
        ]);

        if ($stmt->rowCount() > 0) {
            $this->logActivity(
                'Academic Year Updated',
                'Academic year ID ' . $academicYearId .
                ' was updated from ' . $oldAcademicYear['year_name'] .
                ' to ' . $yearName .
                '. New dates: ' . $startDate . ' to ' . $endDate .
                '. New status: ' . $status . '.'
            );
        }

        return [
            'success' => true,
            'message' => 'Academic year updated successfully.'
        ];
    }

    public function updateAcademicYearStatus(array $data): array
    {
        $academicYearId = (int) ($data['academic_year_id'] ?? 0);
        $status = $data['status'] ?? '';

        if ($academicYearId <= 0) {
            return [
                'success' => false,
                'message' => 'Invalid academic year ID.'
            ];
        }

        if (!in_array($status, ['upcoming', 'open', 'closed'], true)) {
            return [
                'success' => false,
                'message' => 'Invalid academic year status.'
            ];
        }

        $stmt = $this->pdo->prepare("
            SELECT year_name, status
            FROM academic_years
            WHERE academic_year_id = ?
        ");

        $stmt->execute([$academicYearId]);
        $academicYear = $stmt->fetch();

        if (!$academicYear) {
            return [
                'success' => false,
                'message' => 'Academic year not found.'
            ];
        }

        if ($status === 'open' && $this->hasOpenAcademicYear($academicYearId)) {
            return [
                'success' => false,
                'message' => 'Another academic year is already open.'
            ];
        }

        if ($academicYear['status'] === $status) {
            return [
                'success' => true,
                'message' => 'Academic year already has this status.'
            ];
        }

        $stmt = $this->pdo->prepare("
            UPDATE academic_years
            SET status = ?
            WHERE academic_year_id = ?
        ");

        $stmt->execute([
            $status,
            $academicYearId
        ]);

        if ($stmt->rowCount() > 0) {
            $this->logActivity(
                'Academic Year Status Updated',
                'Academic year ' . $academicYear['year_name'] .
                ' (ID: ' . $academicYearId .
                ') status was changed from ' . $academicYear['status'] .
                ' to ' . $status . '.'
            );
        }

        return [
            'success' => true,
            'message' => 'Academic year status updated successfully.'
        ];
    }

    private function isValidDate(string $date): bool
    {
        $parsedDate = DateTime::createFromFormat('!Y-m-d', $date);

        return $parsedDate && $parsedDate->format('Y-m-d') === $date;
    }

    private function hasDateOverlap(
        string $startDate,
        string $endDate,
        int $excludeId = 0
    ): bool {
        $stmt = $this->pdo->prepare("
            SELECT COUNT(*)
            FROM academic_years
            WHERE start_date <= ?
            AND end_date >= ?
            AND academic_year_id != ?
        ");

        $stmt->execute([
            $endDate,
            $startDate,
            $excludeId
        ]);

        return (int) $stmt->fetchColumn() > 0;
    }

    private function hasOpenAcademicYear(int $excludeId = 0): bool
    {
        $stmt = $this->pdo->prepare("
            SELECT COUNT(*)
            FROM academic_years
            WHERE status = 'open'
            AND academic_year_id != ?
        ");

        $stmt->execute([$excludeId]);

        return (int) $stmt->fetchColumn() > 0;
    }
}

$academicYearController = new AcademicYearController($pdo);
