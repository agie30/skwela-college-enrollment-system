
<?php

require_once __DIR__ . '/../config/database.php';

class AcademicYearController
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
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
        $startDate = $data['start_date'] ?? '';
        $endDate = $data['end_date'] ?? '';
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

        return [
            'success' => true,
            'message' => 'Academic year created successfully.'
        ];
    }

    public function updateAcademicYear(array $data): array
    {
        $academicYearId = (int) ($data['academic_year_id'] ?? 0);
        $startDate = $data['start_date'] ?? '';
        $endDate = $data['end_date'] ?? '';
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
            SELECT academic_year_id
            FROM academic_years
            WHERE academic_year_id = ?
        ");

        $stmt->execute([$academicYearId]);

        if (!$stmt->fetch()) {
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

        if ($status === 'open' && $this->hasOpenAcademicYear($academicYearId)) {
            return [
                'success' => false,
                'message' => 'Another academic year is already open.'
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

        if ($stmt->rowCount() === 0) {
            $check = $this->pdo->prepare("
                SELECT academic_year_id
                FROM academic_years
                WHERE academic_year_id = ?
            ");

            $check->execute([$academicYearId]);

            if (!$check->fetch()) {
                return [
                    'success' => false,
                    'message' => 'Academic year not found.'
                ];
            }
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
