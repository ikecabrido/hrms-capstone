<?php
// Diagnostic helper for shifts page. Writes to time_attendance/debug_shifts.log
function shiftDebug($message) {
    file_put_contents(
        __DIR__ . '/debug_shifts.log',
        date('Y-m-d H:i:s') . ' ' . $message . PHP_EOL,
        FILE_APPEND
    );
}
