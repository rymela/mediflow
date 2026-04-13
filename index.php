<?php

declare(strict_types=1);

require __DIR__ . '/bootstrap.php';

use App\Core\Response;

$controllerKey = isset($_GET['controller']) ? (string)$_GET['controller'] : 'front';
$actionKey = isset($_GET['action']) ? (string)$_GET['action'] : 'index';

$controllerMap = [
    'front' => App\Controllers\FrontController::class,
    'admin' => App\Controllers\AdminController::class,
];

$controllerClass = $controllerMap[$controllerKey] ?? null;
if ($controllerClass === null || !class_exists($controllerClass)) {
    Response::text('Not found', 404);
}

$controller = new $controllerClass();
$method = $actionKey . 'Action';

if (!method_exists($controller, $method)) {
    Response::text('Not found', 404);
}

try {
    $controller->{$method}();
} catch (Throwable $e) {
    // Keep it simple for a university project; in production log the exception.
    Response::text('Server error: ' . $e->getMessage(), 500);
}
