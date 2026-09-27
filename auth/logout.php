<?php
session_start();
session_destroy();
header('Location: /hrms-capstone/index.php');
exit();
