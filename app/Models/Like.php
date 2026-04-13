<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class Like
{
    public static function countForPost(int $postId): int
    {
        $pdo = Database::pdo();
        $stmt = $pdo->prepare('SELECT likes_count FROM mag_posts WHERE id_post = :id LIMIT 1');
        $stmt->execute([':id' => $postId]);
        $value = $stmt->fetchColumn();
        return $value !== false ? (int)$value : 0;
    }

    public static function hasLiked(int $postId, int $userId): bool
    {
        $pdo = Database::pdo();
        $stmt = $pdo->prepare('SELECT liked_user_ids FROM mag_posts WHERE id_post = :p LIMIT 1');
        $stmt->execute([':p' => $postId]);
        $json = $stmt->fetchColumn();

        if ($json === false || $json === null || trim((string)$json) === '') {
            return false;
        }

        $decoded = json_decode((string)$json, true);
        if (!is_array($decoded)) {
            return false;
        }

        foreach ($decoded as $id) {
            if ((int)$id === $userId) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array{liked: bool}
     */
    public static function toggle(int $postId, int $userId): array
    {
        $pdo = Database::pdo();

        $pdo->beginTransaction();

        try {
            $stmt = $pdo->prepare('SELECT liked_user_ids FROM mag_posts WHERE id_post = :p LIMIT 1 FOR UPDATE');
            $stmt->execute([':p' => $postId]);
            $row = $stmt->fetch();

            if ($row === false) {
                $pdo->rollBack();
                return ['liked' => false];
            }

            $currentJson = (string)($row['liked_user_ids'] ?? '');
            $decoded = $currentJson !== '' ? json_decode($currentJson, true) : [];
            $decoded = is_array($decoded) ? $decoded : [];

            $set = [];
            foreach ($decoded as $id) {
                $id = (int)$id;
                if ($id > 0) {
                    $set[$id] = true;
                }
            }

            $likedNow = true;
            if (isset($set[$userId])) {
                unset($set[$userId]);
                $likedNow = false;
            } else {
                $set[$userId] = true;
            }

            $ids = array_map('intval', array_keys($set));
            sort($ids);
            $newJson = json_encode($ids, JSON_UNESCAPED_SLASHES);
            if ($newJson === false) {
                $newJson = '[]';
            }

            $count = count($ids);

            $update = $pdo->prepare('UPDATE mag_posts SET likes_count = :c, liked_user_ids = :j WHERE id_post = :p');
            $update->execute([':c' => $count, ':j' => $newJson, ':p' => $postId]);

            $pdo->commit();
            return ['liked' => $likedNow];
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }
}
