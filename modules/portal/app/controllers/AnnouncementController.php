<?php

namespace App\Controllers;

use App\Models\Announcement;
use App\Helper\Helper;

class AnnouncementController
{
    private $announcementModel;

    public function __construct()
    {
        $this->announcementModel = new Announcement();
    }

    public function index()
    {
        $announcements = $this->announcementModel->all();

        $title = "Employee Announcements";
        $content = __DIR__ . '/../views/employee-portal/announcement/content.php';
        require __DIR__ . '/../views/employee-portal/index.php';
    }
    public function adminIndex()
    {
        $title = "Admin Announcements";
        $content = __DIR__ . '/../views/admin-portal/announcement/content.php';
        require __DIR__ . '/../views/admin-portal/index.php';
    }
    public function view()
    {
        $id = (int) ($_GET['id'] ?? 0);

        if ($id <= 0) {
            $_SESSION['error'] = 'Invalid announcement.';
            Helper::redirect('index.php?url=announcement');
        }

        $announcement = $this->announcementModel->find($id);

        if (!$announcement) {
            $_SESSION['error'] = 'Announcement not found.';
            Helper::redirect('index.php?url=announcement');
        }

        $title = "Announcement";
        $content = __DIR__ . '/../views/employee-portal/announcement/view.php';

        require __DIR__ . '/../views/employee-portal/index.php';
    }
}