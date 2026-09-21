
<?php
// Centralized Database Configuration
// All testbed files require this file instead of duplicating connection parameters.

$db_host = getenv('DB_HOST') ?: 'db';
$db_user = getenv('DB_USER') ?: 'root';
$db_pass = getenv('DB_PASS') ?: 'password';
$db_name = getenv('DB_NAME') ?: 'db';
