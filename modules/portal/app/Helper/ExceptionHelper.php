<?php

namespace App\Helper;

class ExceptionHelper
{
    public static function handle(
        \Throwable $e,
        ?string $cleanupFile = null
    ): void {

        error_log(
            "APPLICATION ERROR\n" .
            "Exception: " . get_class($e) . "\n" .
            "Message: " . $e->getMessage() . "\n" .
            "File: " . $e->getFile() . "\n" .
            "Line: " . $e->getLine() . "\n" .
            "Trace:\n" . $e->getTraceAsString() . "\n" .
            "POST: " . print_r($_POST, true) . "\n" .
            "FILES: " . print_r($_FILES, true) . "\n" .
            "----------------------------------------\n"
        );

        if (
            $cleanupFile !== null &&
            is_file($cleanupFile)
        ) {
            unlink($cleanupFile);
        }

        $_SESSION['error'] = 'Something went wrong. Please try again.';
    }
}
