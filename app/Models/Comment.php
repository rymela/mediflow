<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class Comment
{
    // ══════════════════════════════════════════════════════════════════
    //  READ — front office
    // ══════════════════════════════════════════════════════════════════

    /**
     * Most-recent comments per post the user discussed in (deduplicated by post).
     *
     * @return array<int, array<string, mixed>>
     */
    public static function recentDiscussionsForUser(int $userId, int $limit): array
    {
        $limit = max(0, $limit);
        if ($limit === 0) {
            return [];
        }

        $pdo    = Database::pdo();
        $window = max(50, $limit * 10);

        $stmt = $pdo->prepare(
            'SELECT cm.id_comment, cm.post_id, cm.user_id, cm.content, cm.status, cm.created_at,
                    CONCAT(COALESCE(u.prenom,\'\'), \' \', COALESCE(u.nom,\'\')) AS user_name,
                    p.title AS post_title
             FROM mag_comments cm
             JOIN mag_posts p ON p.id_post = cm.post_id
             LEFT JOIN utilisateurs u ON u.id_PK = cm.user_id
             WHERE cm.user_id = :u AND cm.status = \'active\' AND p.status = \'published\'
             ORDER BY cm.created_at DESC, cm.id_comment DESC
             LIMIT ' . (int)$window
        );
        $stmt->execute([':u' => $userId]);

        $out  = [];
        $seen = [];
        foreach ($stmt->fetchAll() as $row) {
            $postId = (int)($row['post_id'] ?? 0);
            if ($postId <= 0 || isset($seen[$postId])) {
                continue;
            }
            $seen[$postId] = true;
            $out[]         = $row;
            if (count($out) >= $limit) {
                break;
            }
        }

        return $out;
    }

    /**
     * Recent comments for a specific post (for guest sidebar preview).
     *
     * @return array<int, array<string, mixed>>
     */
    public static function recentForPost(int $postId, int $limit): array
    {
        $pdo  = Database::pdo();
        $stmt = $pdo->prepare(
            'SELECT cm.id_comment, cm.post_id, cm.user_id, cm.content, cm.status, cm.created_at,
                    CONCAT(COALESCE(u.prenom,\'\'), \' \', COALESCE(u.nom,\'\')) AS user_name,
                    p.title AS post_title
             FROM mag_comments cm
             JOIN mag_posts p ON p.id_post = cm.post_id
             LEFT JOIN utilisateurs u ON u.id_PK = cm.user_id
             WHERE cm.post_id = :p AND cm.status = \'active\'
             ORDER BY cm.created_at DESC
             LIMIT ' . (int)$limit
        );
        $stmt->execute([':p' => $postId]);
        return $stmt->fetchAll();
    }

    /**
     * Most recent active comments from across ALL published posts.
     * Used to populate the "Recent Discussions" grid on the front index page.
     *
     * @param int[]  $excludePostIds  Post IDs whose comments should be skipped
     * @return array<int, array<string, mixed>>
     */
    public static function recentAcrossAllPosts(int $limit = 6, array $excludePostIds = []): array
    {
        $pdo   = Database::pdo();
        $limit = max(1, $limit);

        $excludeSql = '';
        if (!empty($excludePostIds)) {
            $placeholders = implode(',', array_fill(0, count($excludePostIds), '?'));
            $excludeSql   = ' AND cm.post_id NOT IN (' . $placeholders . ')';
        }

        $stmt = $pdo->prepare(
            'SELECT cm.id_comment, cm.post_id, cm.user_id, cm.content, cm.status, cm.created_at,
                    CONCAT(COALESCE(u.prenom,\'\'), \' \', COALESCE(u.nom,\'\')) AS user_name,
                    p.title AS post_title
             FROM mag_comments cm
             JOIN mag_posts p ON p.id_post = cm.post_id AND p.status = \'published\'
             LEFT JOIN utilisateurs u ON u.id_PK = cm.user_id
             WHERE cm.status = \'active\'' . $excludeSql . '
             ORDER BY cm.created_at DESC
             LIMIT ' . (int)$limit
        );

        $stmt->execute($excludePostIds ? array_values($excludePostIds) : []);
        return $stmt->fetchAll();
    }

    /**
     * All active comments for a full article view.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function forPost(int $postId, int $limit): array
    {
        $pdo  = Database::pdo();
        $stmt = $pdo->prepare(
            'SELECT cm.id_comment, cm.post_id, cm.user_id, cm.content, cm.status, cm.created_at,
                    CONCAT(COALESCE(u.prenom,\'\'), \' \', COALESCE(u.nom,\'\')) AS user_name
             FROM mag_comments cm
             LEFT JOIN utilisateurs u ON u.id_PK = cm.user_id
             WHERE cm.post_id = :p AND cm.status = \'active\'
             ORDER BY cm.created_at ASC
             LIMIT ' . (int)$limit
        );
        $stmt->execute([':p' => $postId]);
        return $stmt->fetchAll();
    }

    // ══════════════════════════════════════════════════════════════════
    //  READ — admin moderation
    // ══════════════════════════════════════════════════════════════════

    /**
     * Paginated moderation queue.
     *
     * Default (no filters): shows only REPORTED (flagged) comments.
     * With $postId or $q filter: shows all non-deleted comments matching
     * those criteria so the admin can review a full post's thread.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function moderationQueuePaged(
        int     $page    = 1,
        int     $perPage = 15,
        string  $q       = '',
        ?int    $postId  = null
    ): array {
        $pdo    = Database::pdo();
        $offset = max(0, ($page - 1) * $perPage);

        $hasFilter = $q !== '' || $postId !== null;

        $sql =
            'SELECT cm.id_comment, cm.post_id, cm.user_id, cm.content, cm.status, cm.created_at,
                    CONCAT(COALESCE(u.prenom,\'\'), \' \', COALESCE(u.nom,\'\')) AS user_name,
                    p.title AS post_title
             FROM mag_comments cm
             LEFT JOIN utilisateurs u ON u.id_PK = cm.user_id
             LEFT JOIN mag_posts   p ON p.id_post = cm.post_id
             WHERE ';

        $params = [];
        if ($hasFilter) {
            // When filtering: show all non-deleted comments matching the criteria
            $sql .= 'cm.status <> \'deleted\'';
            if ($postId !== null) {
                $sql            .= ' AND cm.post_id = :pid';
                $params[':pid']  = $postId;
            }
            if ($q !== '') {
                $sql           .= ' AND cm.content LIKE :q';
                $params[':q']   = '%' . $q . '%';
            }
            $sql .= ' ORDER BY (cm.status = \'flagged\') DESC, cm.created_at DESC';
        } else {
            // Default: only reported (flagged) comments
            $sql .= 'cm.status = \'flagged\'';
            $sql .= ' ORDER BY cm.created_at DESC';
        }

        $sql .= ' LIMIT ' . (int)$perPage . ' OFFSET ' . (int)$offset;

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /** Mini-queue for admin dashboard sidebar — reported only, most recent first. */
    public static function moderationQueue(int $limit): array
    {
        $pdo  = Database::pdo();
        $stmt = $pdo->query(
            'SELECT cm.id_comment, cm.post_id, cm.user_id, cm.content, cm.status, cm.created_at,
                    CONCAT(COALESCE(u.prenom,\'\'), \' \', COALESCE(u.nom,\'\')) AS user_name,
                    p.title AS post_title
             FROM mag_comments cm
             LEFT JOIN utilisateurs u ON u.id_PK = cm.user_id
             LEFT JOIN mag_posts   p ON p.id_post = cm.post_id
             WHERE cm.status = \'flagged\'
             ORDER BY cm.created_at DESC
             LIMIT ' . (int)$limit
        );
        return $stmt->fetchAll();
    }

    /** Count REPORTED (flagged) comments for the nav badge. */
    public static function countPending(): int
    {
        $pdo = Database::pdo();
        return (int)$pdo
            ->query("SELECT COUNT(*) FROM mag_comments WHERE status = 'flagged'")
            ->fetchColumn();
    }

    /**
     * Count comments in the moderation view (depends on active filters).
     * No filters → count only flagged; with filters → count all non-deleted matching.
     */
    public static function countForModeration(string $q = '', ?int $postId = null): int
    {
        $pdo       = Database::pdo();
        $hasFilter = $q !== '' || $postId !== null;

        if ($hasFilter) {
            $sql    = "SELECT COUNT(*) FROM mag_comments cm WHERE cm.status <> 'deleted'";
            $params = [];
            if ($postId !== null) {
                $sql            .= ' AND cm.post_id = :pid';
                $params[':pid']  = $postId;
            }
            if ($q !== '') {
                $sql          .= ' AND cm.content LIKE :q';
                $params[':q']  = '%' . $q . '%';
            }
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            return (int)$stmt->fetchColumn();
        }

        return (int)$pdo
            ->query("SELECT COUNT(*) FROM mag_comments WHERE status = 'flagged'")
            ->fetchColumn();
    }

    // ══════════════════════════════════════════════════════════════════
    //  WRITE
    // ══════════════════════════════════════════════════════════════════

    /** @return array<string, mixed> */
    public static function create(int $postId, int $userId, string $content): array
    {
        $pdo  = Database::pdo();
        $stmt = $pdo->prepare(
            "INSERT INTO mag_comments (post_id, user_id, content, status)
             VALUES (:p, :u, :c, 'active')"
        );
        $stmt->execute([':p' => $postId, ':u' => $userId, ':c' => $content]);

        $id  = (int)$pdo->lastInsertId();
        $row = self::find($id);
        return $row ?? [
            'id_comment' => $id,
            'post_id'    => $postId,
            'user_id'    => $userId,
            'content'    => $content,
            'status'     => 'active',
        ];
    }

    /** @return array<string, mixed>|null */
    public static function find(int $commentId): ?array
    {
        $pdo  = Database::pdo();
        $stmt = $pdo->prepare(
            'SELECT cm.id_comment, cm.post_id, cm.user_id, cm.content, cm.status, cm.created_at,
                    CONCAT(COALESCE(u.prenom,\'\'), \' \', COALESCE(u.nom,\'\')) AS user_name
             FROM mag_comments cm
             LEFT JOIN utilisateurs u ON u.id_PK = cm.user_id
             WHERE cm.id_comment = :id
             LIMIT 1'
        );
        $stmt->execute([':id' => $commentId]);
        $row = $stmt->fetch();
        return $row !== false ? $row : null;
    }

    public static function updateOwned(int $commentId, int $userId, string $content): bool
    {
        $pdo  = Database::pdo();
        $stmt = $pdo->prepare(
            "UPDATE mag_comments
             SET content = :c
             WHERE id_comment = :id AND user_id = :u AND status <> 'deleted'"
        );
        $stmt->execute([':c' => $content, ':id' => $commentId, ':u' => $userId]);
        return $stmt->rowCount() > 0;
    }

    public static function deleteOwned(int $commentId, int $userId): bool
    {
        $pdo  = Database::pdo();
        $stmt = $pdo->prepare(
            "UPDATE mag_comments
             SET status = 'deleted'
             WHERE id_comment = :id AND user_id = :u AND status <> 'deleted'"
        );
        $stmt->execute([':id' => $commentId, ':u' => $userId]);
        return $stmt->rowCount() > 0;
    }

    /**
     * Report a comment (sets status to 'flagged').
     * Called from the front office when a user clicks Report.
     */
    public static function report(int $commentId): void
    {
        $pdo  = Database::pdo();
        $stmt = $pdo->prepare(
            "UPDATE mag_comments SET status = 'flagged' WHERE id_comment = :id AND status = 'active'"
        );
        $stmt->execute([':id' => $commentId]);
    }

    /** Keep alias for admin backward-compat (warn = flag). */
    public static function flag(int $commentId): void
    {
        self::report($commentId);
    }

    /**
     * Restore a reported comment — dismiss the report, set back to active.
     * Called from admin "Keep" action.
     */
    public static function restore(int $commentId): void
    {
        $pdo  = Database::pdo();
        $stmt = $pdo->prepare(
            "UPDATE mag_comments SET status = 'active' WHERE id_comment = :id AND status = 'flagged'"
        );
        $stmt->execute([':id' => $commentId]);
    }

    public static function adminDelete(int $commentId): void
    {
        $pdo  = Database::pdo();
        $stmt = $pdo->prepare("UPDATE mag_comments SET status = 'deleted' WHERE id_comment = :id");
        $stmt->execute([':id' => $commentId]);
    }
}
