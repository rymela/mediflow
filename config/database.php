<?php

declare(strict_types=1);

return [
    // Change these to match your XAMPP/MySQL setup
    'host' => getenv('MEDIFLOW_DB_HOST') ?: '127.0.0.1',
    'dbname' => getenv('MEDIFLOW_DB_NAME') ?: 'mediflow',
    'user' => getenv('MEDIFLOW_DB_USER') ?: 'root',
    'pass' => getenv('MEDIFLOW_DB_PASS') ?: '',
    'charset' => 'utf8mb4',
];
