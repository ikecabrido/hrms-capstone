<?php
require_once __DIR__ . '/Goal.php';

class GoalService
{
    private Goal $goalModel;

    public function __construct($pdo = null)
    {
        $this->goalModel = new Goal($pdo);
    }

    public function getOverview(): array
    {
        $stats = $this->goalModel->getDashboardStats();
        $goals = $this->goalModel->getGoals();

        return [
            'stats' => $stats,
            'goals' => $goals,
            'categories' => $this->goalModel->getGoalCategories(),
            'statuses' => $this->goalModel->getGoalStatuses(),
            'priorities' => $this->goalModel->getPriorities(),
            'types' => $this->goalModel->getGoalTypes(),
        ];
    }

    public function getEmployees(): array
    {
        return $this->goalModel->getEmployees();
    }

    public function getGoals(array $filters = []): array
    {
        return $this->goalModel->getGoals($filters);
    }

    public function getGoalById(int $goalId): ?array
    {
        return $this->goalModel->getGoalById($goalId);
    }

    public function getGoalHistory(int $goalId): array
    {
        return $this->goalModel->getGoalHistory($goalId);
    }

    public function getGoalProgressEntries(int $goalId): array
    {
        return $this->goalModel->getGoalProgressEntries($goalId);
    }
}
