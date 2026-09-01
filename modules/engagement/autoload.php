<?php

spl_autoload_register(function ($class) {
    $prefix = 'App\\';
    $base_dir = __DIR__ . '/';

    if (strncmp($prefix, $class, strlen($prefix)) !== 0) {
        return;
    }

    $relative_class = substr($class, strlen($prefix));
    $parts = explode('\\', $relative_class);
    $root = array_shift($parts);

    $map = [
        'Controllers' => 'controllers/',
        'Models' => 'models/',
        'Api' => 'api/',
    ];

    if (!isset($map[$root])) {
        return;
    }

    $file = __DIR__ . '/' . $map[$root] . implode('/', $parts) . '.php';

    if (file_exists($file)) {
        require_once $file;
    }
});
