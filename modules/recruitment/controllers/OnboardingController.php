<?php

require_once __DIR__ . '/../classes/Onboarding.php';
require_once __DIR__ . '/../classes/Candidate.php';

class OnboardingController
{
    private $model;
    private $candidateModel;

    public function __construct()
    {
        $this->model = new Onboarding();
        $this->candidateModel = new Candidate();
    }


    /**
     * Show onboarding form
     */
    public function create()
    {
        $applicants = $this->model->getAcceptedApplicants();

        require __DIR__ . '/../pages/onboarding-selection.php';
    }


    /**
     * Store new onboarding record
     */
    public function store()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: index.php?page=onboarding");
            exit;
        }

        try {

            $this->model->insert($_POST);

            // Success message
            $_SESSION['success'] = "Onboarding finalized successfully!";

            // Redirect to tracking
            header("Location: index.php?page=onboarding-tracking");
            exit;
        } catch (Exception $e) {

            $_SESSION['error'] = $e->getMessage();

            header("Location: index.php?page=onboarding");
            exit;
        }
    }


    /**
     * Show onboarding tracking
     */
    public function tracking()
    {
        $records = $this->model->getAll();

        require __DIR__ . '/../pages/onboarding-tracking.php';
    }


    /**
     * Manage onboarding
     */
    public function manage()
    {
        if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
            $_SESSION['error'] = "Invalid onboarding ID.";
            header("Location: index.php?page=onboarding-tracking");
            exit;
        }

        $id = (int) $_GET['id'];

        $record = $this->model->getById($id);

        if (!$record) {
            $_SESSION['error'] = "Onboarding record not found.";
            header("Location: index.php?page=onboarding-tracking");
            exit;
        }

        // Decode checklist
        if (!empty($record['checklist'])) {

            $decoded = json_decode(
                $record['checklist'],
                true
            );

            $record['checklist'] = is_array($decoded)
                ? $decoded
                : [];
        } else {

            $record['checklist'] = [];
        }

        require __DIR__ . '/../pages/onboarding-manage.php';
    }


    /**
     * Save onboarding checklist progress
     */
    public function saveProgress()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: index.php?page=onboarding-tracking");
            exit;
        }

        if (
            !isset($_POST['id']) ||
            !is_numeric($_POST['id'])
        ) {
            $_SESSION['error'] = "Invalid onboarding ID.";
            header("Location: index.php?page=onboarding-tracking");
            exit;
        }

        $id = (int) $_POST['id'];

        $texts = $_POST['text'] ?? [];
        $checks = $_POST['check'] ?? [];

        $checklist = [];

        foreach ($texts as $i => $text) {

            $checklist[] = [
                'text' => trim($text),
                'checked' => isset($checks[$i]) ? 1 : 0
            ];
        }


        // Calculate progress
        $total = count($checklist);
        $completed = 0;

        foreach ($checklist as $item) {

            if ((int) $item['checked'] === 1) {
                $completed++;
            }
        }


        // Prevent division by zero
        $progress = $total > 0
            ? round(($completed / $total) * 100, 2)
            : 0;


        try {

            $this->model->updateProgress(
                $id,
                $checklist,
                $progress
            );

            $_SESSION['success'] = "Onboarding progress updated.";
        } catch (Exception $e) {

            $_SESSION['error'] = $e->getMessage();
        }


        header("Location: index.php?page=onboarding-tracking");
        exit;
    }
}
