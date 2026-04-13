<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class User
{
    /**
     * @return array<string, mixed>|null
     */
    public static function findWithRole(int $id): ?array
    {
        $pdo = Database::pdo();
        $stmt = $pdo->prepare(
            'SELECT u.id_PK, u.nom, u.prenom, u.mail, u.id_role, r.libelle AS role_libelle
             FROM utilisateurs u
             LEFT JOIN roles r ON r.id_role = u.id_role
             WHERE u.id_PK = :id
             LIMIT 1'
        );
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();

        return $row !== false ? $row : null;
    }
}
