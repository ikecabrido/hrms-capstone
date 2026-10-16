<?php
require_once __DIR__ . '/../classes/Job.php';

class JobController
{

    public function index()
    {
        $job = new Job();
        $jobs = $job->all();
        // Updated path to use Capital 'Jobs'
        require "../pages/dashboard.php";
    }

    public function showJobList()
    {
        $job = new Job();
        $jobs = $job->all();

        // Ensure this path matches your folder exactly: app/views/Jobs/job_list.php
        require "../app/views/Jobs/job_list.php";
    }

    public function create()
    {
        if (isset($_POST['save'])) {
            (new Job())->create(
                $_POST['title'],
                $_POST['description'],
                $_POST['qualifications'],
                $_POST['category'],
                $_POST['location'],
                $_POST['max_applicants'] ?? 0
            );
            // Redirect to the new Job List page after saving
            header("Location: index.php?page=job-list");
            exit();
        }
        require "../app/views/Jobs/add.php";
    }

    public function edit()
    {
        $job = new Job();
        $data = $job->find($_GET['id'])->fetch_assoc();

        if (isset($_POST['update'])) {
            $job->update(
                $_GET['id'],
                $_POST['title'],
                $_POST['description'],
                $_POST['qualifications'],
                $_POST['category'],
                $_POST['location'],
                $_POST['max_applicants'] ?? 0
            );
            header("Location: index.php?page=job-list");
            exit();
        }
        require "../app/views/Jobs/edit.php";
    }

    public function delete()
    {
        (new Job())->delete($_GET['id']);
        header("Location: index.php?page=job-list");
        exit();
    }

    public function publicJobs()
    {
        $job = new Job();
        $jobs = $job->all();
        require "../app/views/Jobs/public.php";
    }
}
