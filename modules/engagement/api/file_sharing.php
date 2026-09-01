<?php
// Proxy legacy file-sharing requests to the centralized communication API.

$_GET['action'] = 'share_file';
require __DIR__ . '/communication.php';

