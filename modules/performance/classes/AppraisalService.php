<?php
require_once __DIR__ . '/Appraisal.php';

class AppraisalService
{
    private Appraisal $appraisalModel;

    public function __construct($pdo = null)
    {
        $this->appraisalModel = new Appraisal($pdo);
    }

    public function getDashboardStats(): array
    {
        return $this->appraisalModel->getDashboardStats();
    }

    public function getStatusSummary(): array
    {
        return $this->appraisalModel->getStatusSummary();
    }

    public function getReviewCycles(): array
    {
        return $this->appraisalModel->getReviewCycles();
    }

    public function getAppraisals(array $filters = []): array
    {
        return $this->appraisalModel->getAppraisals($filters);
    }

    public function getAppraisalById(int $appraisalId): ?array
    {
        return $this->appraisalModel->getAppraisalById($appraisalId);
    }

    public function getAppraisalItems(int $appraisalId): array
    {
        return $this->appraisalModel->getAppraisalItems($appraisalId);
    }

    public function getAppraisalHistory(int $appraisalId): array
    {
        return $this->appraisalModel->getAppraisalHistory($appraisalId);
    }

    public function getEmployees(): array
    {
        return $this->appraisalModel->getEmployees();
    }

    public function getDefaultCriteria(): array
    {
        return $this->appraisalModel->getDefaultCriteria();
    }

    public function createReviewCycle(array $data): bool
    {
        return $this->appraisalModel->createReviewCycle($data);
    }

    public function createAppraisal(array $data): bool
    {
        return $this->appraisalModel->createAppraisal($data);
    }

    public function updateAppraisal(array $data): bool
    {
        return $this->appraisalModel->updateAppraisal($data);
    }

    public function updateStatus(int $appraisalId, string $status, string $updatedBy = 'System', string $details = ''): bool
    {
        return $this->appraisalModel->updateStatus($appraisalId, $status, $updatedBy, $details);
    }

    public function saveAppraisalItems(int $appraisalId, array $items, string $updatedBy = 'System'): bool
    {
        return $this->appraisalModel->saveAppraisalItems($appraisalId, $items, $updatedBy);
    }
}
