<?php

require_once __DIR__ . '/config.php';

// Should be set to 0 in production
error_reporting(E_ALL);

// Should be set to '0' in production
ini_set('display_errors', API_DISPLAY_ERROR);

// Settings
$settings = [];

$settings['pdo'] = [
    'db_host' => DB_HOST,
    'db_name' => DB_NAME,
    'db_user' => DB_USER,
    'db_pass' => DB_PASS
];

return $settings;
