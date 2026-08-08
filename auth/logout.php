<?php
session_start();
session_destroy();
header('Location: /hrms/index.php');
exit();