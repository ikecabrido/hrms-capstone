<?php

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../../../database/db.php';
require_once __DIR__ . '/../classes/Job.php';

function respond($data, $status = 200)
{
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function requestData()
{
    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true);

    return is_array($data) ? $data : $_POST;
}

try {

    $database = new Database();
    $db = $database->getConnection();

    $jobModel = new Job();

    $method = $_SERVER['REQUEST_METHOD'];
    $action = $_GET['action'] ?? 'jobs';


    /*
    |--------------------------------------------------------------------------
    | GET JOBS
    |--------------------------------------------------------------------------
    */
    if ($action === 'jobs' && $method === 'GET') {

        $jobs = array_map(function ($job) use ($db) {

            // Count applicants for this job
            $stmt = $db->prepare("
                SELECT COUNT(*)
                FROM rao_applications
                WHERE job_id = :job_id
                  AND is_archived = 0
            ");

            $stmt->execute([
                ':job_id' => $job['id']
            ]);

            $totalApplicants = (int) $stmt->fetchColumn();

            $maxApplicants = (int) ($job['max_applicants'] ?? 0);

            // 0 = unlimited
            $isFull =
                $maxApplicants > 0 &&
                $totalApplicants >= $maxApplicants;

            return [
                'id' => (string) $job['id'],
                'title' => $job['title'],
                'position' => $job['position'],
                'department' => $job['department'],
                'type' => 'Full-time',
                'location' => $job['location'],
                'salary' => '',
                'desc' => $job['description'],
                'qual' => $job['qualifications'],
                'posted' => (int) $job['id'],

                // Applicant information
                'total_applicants' => $totalApplicants,
                'max_applicants' => $maxApplicants,
                'is_full' => $isFull
            ];
        }, $jobModel->all());

        respond($jobs);
    }


    /*
    |--------------------------------------------------------------------------
    | CREATE JOB
    |--------------------------------------------------------------------------
    */
    if ($action === 'jobs' && $method === 'POST') {

        $data = requestData();

        foreach (
            ['title', 'position', 'desc', 'qual', 'department', 'location']
            as $field
        ) {
            if (empty(trim((string) ($data[$field] ?? '')))) {
                respond([
                    'error' => "Missing field: $field"
                ], 422);
            }
        }

        $maxApplicants = (int) ($data['max_applicants'] ?? 0);

        if ($maxApplicants < 0) {
            respond([
                'error' => 'Number of applicants cannot be negative.'
            ], 422);
        }

        $jobModel->create(
            trim($data['title']),
            trim($data['position']),
            trim($data['desc']),
            trim($data['qual']),
            trim($data['department']),
            trim($data['location']),
            $maxApplicants
        );

        respond([
            'success' => true
        ], 201);
    }


    /*
    |--------------------------------------------------------------------------
    | DELETE JOB
    |--------------------------------------------------------------------------
    */
    if ($action === 'jobs' && $method === 'DELETE') {

        $id = (int) ($_GET['id'] ?? 0);

        $jobModel->delete($id);

        respond([
            'success' => true
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | UNKNOWN REQUEST
    |--------------------------------------------------------------------------
    */
    respond([
        'error' => 'Unknown API request'
    ], 404);
} catch (Throwable $e) {

    respond([
        'error' => $e->getMessage()
    ], 500);
}
