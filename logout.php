<?php

declare(strict_types=1);

require __DIR__ . '/bootstrap.php';

use App\Core\Response;

function sanitize_next(string $next): string
{
    $next = trim($next);
    if ($next === '') {
        return 'frontOffice.php';
    }

    if (str_contains($next, "\r") || str_contains($next, "\n")) {
        return 'frontOffice.php';
    }

    if (preg_match('/^[a-z][a-z0-9+.-]*:/i', $next) === 1) {
        return 'frontOffice.php';
    }

    if (str_starts_with($next, '//')) {
        return 'frontOffice.php';
    }

    return $next;
}

$next = isset($_GET['next']) && is_string($_GET['next']) ? sanitize_next($_GET['next']) : 'frontOffice.php';

$_SESSION = [];

if ((bool)ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
}

session_destroy();

Response::redirect($next);
