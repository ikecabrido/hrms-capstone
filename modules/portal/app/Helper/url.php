<?php

function asset(string $path = ''): string
{
    $serverName = $_SERVER['SERVER_NAME'] ?? '';

    $baseUrl = (
        $serverName === 'localhost' ||
        $serverName === '127.0.0.1'
    )
        ? '/hrms-capstone/modules/portal/public'
        : '/modules/portal/public';

    return $baseUrl . ($path !== '' ? '/' . ltrim($path, '/') : '');
}   