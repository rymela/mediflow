<?php

declare(strict_types=1);

// Convenience entrypoint: dynamic BackOffice (MVC)
$_GET['controller'] = 'admin';
$_GET['action'] = $_GET['action'] ?? 'index';
require __DIR__ . '/index.php';
