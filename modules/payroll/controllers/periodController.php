<?php
require_once __DIR__ . '/../../auth/database.php';
require_once __DIR__ . '/../models/payrollPeriodModel.php';

class PeriodController
{
    private PDO $db;
    private PayrollPeriodModel $periodModel;

    public function __construct()
    {
        $this->db = (new Database())->getConnection();
        $this->periodModel = new PayrollPeriodModel($this->db);
    }

    public function index()
    {
        return $this->periodModel->getAll();
    }

    public function create(array $data): bool
    {
        return $this->periodModel->create($data);
    }

    public function update(int $id, array $data): bool
    {
        return $this->periodModel->update($id, $data);
    }

    public function delete(int $id): bool
    {
        return $this->periodModel->delete($id);
    }

    public function getById(int $id): ?array
    {
        return $this->periodModel->getById($id);
    }

    public function getNextPeriod(): array
    {
        return $this->periodModel->generateNextPeriod();
    }
    public function updateStatus(int $id, string $status): bool
    {
        return $this->periodModel->updateStatus($id, $status);
    }
}
