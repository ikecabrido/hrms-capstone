<?php
namespace App\Api;

class ApiRouter
{
    private string $resource;

    public function __construct(string $resource)
    {
        $this->resource = trim($resource);
    }

    public function dispatch(): void
    {
        if ($this->resource === '') {
            ApiResponse::error('API resource is required', 400);
        }

        if (!preg_match('/^[a-zA-Z0-9_]+$/', $this->resource)) {
            ApiResponse::error('Invalid API resource', 400);
        }

        $resourceFile = __DIR__ . '/' . $this->resource . '.php';
        if (!file_exists($resourceFile)) {
            ApiResponse::error('API resource not found', 404);
        }

        require_once $resourceFile;
    }
}

