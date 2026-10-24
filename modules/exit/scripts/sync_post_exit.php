<?php
require_once __DIR__ . '/../models/SurveyModel.php';

$surveyModel = new SurveyModel();
$surveyModel->syncPendingSurveys();
$surveyModel->finalizeEmployeeSeparation();

echo "Post-exit sync completed." . PHP_EOL;
