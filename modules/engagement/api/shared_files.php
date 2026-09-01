<?php
// Proxy legacy shared-file list and delete requests to the centralized communication API.

$action = $_GET['action'] ?? 'list';
if ($action === 'list') {
    $_GET['action'] = 'shared_files';
} elseif ($action === 'delete') {
    $_GET['action'] = 'delete_shared_file';
}

require __DIR__ . '/communication.php';

