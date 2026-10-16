<?php
/**
 * Template for the database connection used by database/db.php.
 *
 * Copy this file to db_config.php at the project root (same folder as this file)
 * and fill in the real credentials for the target environment.
 * db_config.php is git-ignored, so it stays out of version control.
 *
 * Never commit real credentials to the repository.
 */

return [
    'host'     => 'localhost',   // or the external DB host, e.g. 'sql123.example.com'
    'port'     => '3306',
    'database' => 'lear_hrms',
    'username' => 'learning',
    'password' => 'CHANGE_ME',
];
