<?php

require_once __DIR__ . '/../../../database/db.php';
require_once __DIR__ . '/../model/FeedbackDashboardModel.php';

class FeedbackDashboardController
{
    private FeedbackDashboardModel $model;

    /**
     * Initialize controller with PDO connection.
     */
    public function __construct($pdo = null)
    {
        if (!($pdo instanceof PDO)) {
            $database = new Database();
            $pdo = $database->getConnection();
        }
        $this->model = new FeedbackDashboardModel($pdo);
    }

    /**
     * Get all dashboard data.
     */
    public function getDashboardData(array $filters = []): array
    {
        // Get and sanitize filters
        $cycle = isset($filters['cycle'])
            ? trim((string) $filters['cycle'])
            : '';

        $raterType = isset($filters['rater_type'])
            ? trim((string) $filters['rater_type'])
            : 'all';

        // Allowed rater types
        $allowedRaterTypes = [
            'all',
            'self',
            'peer',
            'manager',
            'subordinate'
        ];

        if (!in_array($raterType, $allowedRaterTypes, true)) {
            $raterType = 'all';
        }

        $modelFilters = [
            'cycle' => $cycle,
            'rater_type' => $raterType
        ];

        // Dashboard statistics
        $stats = $this->model->getDashboardStats($modelFilters);

        // Radar / competency overview
        $overview = $this->model->getRadarChartData($modelFilters);

        // Rater breakdown
        $raterBreakdown = $this->model->getRaterBreakdown([
            'cycle' => $cycle
        ]);

        // Progress summary
        $progress = $this->model->getProgressSummary([
            'cycle' => $cycle
        ]);

        // Overall score
        $overall = $this->model->getOverallScore($modelFilters);

        // Competency summary
        $competencySummary = $this->model->getCompetencySummary($modelFilters);

        // Recent submissions
        $recentSubmissions = $this->model->getRecentSubmissions(
            $modelFilters,
            8
        );

        // Available review cycles
        $cycles = $this->model->getReviewCycles();

        return [
            'stats' => $stats ?? [],
            'overview' => $overview ?? [],
            'raterBreakdown' => $raterBreakdown ?? [],
            'progress' => $progress ?? [],
            'overall' => $overall ?? [],
            'competencySummary' => $competencySummary ?? [],
            'recentSubmissions' => $recentSubmissions ?? [],
            'cycles' => $cycles ?? [],

            'filters' => [
                'cycle' => $cycle,
                'rater_type' => $raterType
            ]
        ];
    }
}