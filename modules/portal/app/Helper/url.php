<?php

function asset(string $path = ''): string
{
    $host = strtolower($_SERVER['HTTP_HOST'] ?? '');

    if (
        str_starts_with($host, 'localhost') ||
        str_starts_with($host, '127.0.0.1')
    ) {
        $baseUrl = '/hrms-capstone/modules/portal/public';
    } else {
        $baseUrl = '/modules/portal/public';
    }

    return $baseUrl . ($path !== '' ? '/' . ltrim($path, '/') : '');
}

function appUrl(string $path = ''): string
{
    $host = strtolower($_SERVER['HTTP_HOST'] ?? '');

    if (
        str_starts_with($host, 'localhost') ||
        str_starts_with($host, '127.0.0.1')
    ) {
        $baseUrl = '/hrms-capstone/modules/portal';
    } else {
        $baseUrl = '/modules/portal';
    }

    return $baseUrl . ($path !== '' ? '/' . ltrim($path, '/') : '');
}