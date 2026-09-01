<?php
function jsonResponse($data, $status = 200)
{
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

function inputData()
{
    $body = file_get_contents('php://input');
    $json = json_decode($body, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        return $_POST;
    }
    return array_merge($_GET, (array)$json, $_POST);
}

