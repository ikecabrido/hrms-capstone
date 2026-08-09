<?php
session_start();
session_destroy();
header('Location: /hrms-capstone-master/index.php');
exit();