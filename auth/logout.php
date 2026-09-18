<?php
session_start();
session_destroy();
require_once __DIR__ . '/../includes/app-base.php';
header('Location: ' . AppBase::pathFor('index.php'));
exit();
