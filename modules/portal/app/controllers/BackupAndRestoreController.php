<?php

namespace App\Controllers;


class BackupAndRestoreController
{

    public function __construct()
    {

    }
    public function adminIndex()
    {
        $title = "Admin Announcements";
        $content = __DIR__ . '/../views/admin-portal/backup-and-restore/content.php';
        require __DIR__ . '/../views/admin-portal/index.php';
    }
}