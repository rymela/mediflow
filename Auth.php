<?php

declare(strict_types=1);

namespace App\Core;

use App\Models\User;

final class Auth
{
    public static function id(): ?int
    {
        $id = $_SESSION['user_id'] ?? null;
        if (is_int($id)) {
            return $id;
        }
        if (is_string($id) && ctype_digit($id)) {
            return (int)$id;
        }
        return null;
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function user(): ?array
    {
        $id = self::id();
        if ($id === null) {
            return null;
        }

        return User::findWithRole($id);
    }

    public static function check(): bool
    {
        return self::id() !== null;
    }

    /**
     * @param string[] $roles
     */
    public static function requireAnyRole(array $roles): void
    {
        $user = self::user();
        if ($user === null) {
            $method = strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET'));
            if ($method === 'GET') {
                $next = (string)($_SERVER['REQUEST_URI'] ?? 'backOffice.php');
                Response::redirect('login.php?next=' . urlencode($next));
            }

            Response::text('Unauthorized', 401);
        }

        $role = (string)($user['role_libelle'] ?? '');
        foreach ($roles as $allowed) {
            if (strcasecmp($role, $allowed) === 0) {
                return;
            }
        }

        Response::text('Forbidden', 403);
    }
}
