<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/functions.php';

date_default_timezone_set('Asia/Kolkata');

if (!is_dir(__DIR__ . '/../logs')) {
    mkdir(__DIR__ . '/../logs', 0755, true);
}

ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../logs/error.log');
?>
