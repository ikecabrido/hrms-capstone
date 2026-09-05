<?php

function dg_get_signature_image(int $height = 90): string {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $src = $protocol . $host . '/hrms-capstone/assets/img/signature.png';
    return '<img src="' . $src . '" alt="Signature" style="height:' . $height . 'px; vertical-align:middle; display:inline-block;">';
}

if (!function_exists('lc_get_signature_image')) {
    function lc_get_signature_image(int $height = 90): string {
        return dg_get_signature_image($height);
    }
}
