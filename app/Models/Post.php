<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class Post
{
    private const PER_PAGE_ADMIN = 10;
    private const PER_PAGE_FRONT = 9;

    // ══════════════════════════════════════════════════════════════════
    //  READ — front office
    // ══════════════════════════════════════════════════════════════════

    /**
     * Full paginated + searchable list for the front office.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function publishedListWithCounts(
        ?string $categorySlug = null,
        array   $excludeIds   = [],
        int     $limit        = 0
    ): array {
        $pdo = Database::pdo();

        $sql =
            "SELECT p.*, c.name AS category_name, c.slug AS category_slug,
                    CONCAT(COALESCE(u.prenom,''), ' ', COALESCE(u.nom,'')) AS author_name,
                    COALESCE(p.likes_count,  0) AS likes_count,
                    COALESCE(p.views_count,  0) AS views_count,
                    (SELECT COUNT(*) FROM mag_comments cm
                       WHERE cm.post_id = p.id_post AND cm.status = 'active') AS comments_count
             FROM mag_posts p
             JOIN mag_categories c ON c.id_category = p.category_id
             LEFT JOIN utilisateurs u ON u.id_PK = p.author_id
             WHERE p.status = 'published'";

        $params = [];
        if ($categorySlug !== null && trim($categorySlug) !== '') {
            $sql .= ' AND c.slug = :slug';
            $params[':slug'] = $categorySlug;
        }

        $excludeIds = array_values(array_unique(array_map('intval', $excludeIds)));
        if (!empty($excludeIds)) {
            $placeholders = [];
            foreach ($excludeIds as $i => $id) {
                $ph              = ':ex' . $i;
                $placeholders[]  = $ph;
                $params[$ph]     = $id;
            }
            $sql .= ' AND p.id_post NOT IN (' . implode(',', $placeholders) . ')';
        }

        $sql .= ' ORDER BY COALESCE(p.published_at, p.created_at) DESC';
        if ($limit > 0) {
            $sql .= ' LIMIT ' . (int)$limit;
        }

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Paginated + searchable front-office list.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function publishedPaged(
        string  $q,
        ?string $catSlug,
        int     $page,
        int     $perPage = self::PER_PAGE_FRONT
    ): array {
        $pdo    = Database::pdo();
        $offset = max(0, ($page - 1) * $perPage);

        $sql =
            "SELECT p.*, c.name AS category_name, c.slug AS category_slug,
                    CONCAT(COALESCE(u.prenom,''), ' ', COALESCE(u.nom,'')) AS author_name,
                    COALESCE(p.likes_count, 0)  AS likes_count,
                    COALESCE(p.views_count, 0)  AS views_count,
                    (SELECT COUNT(*) FROM mag_comments cm
                       WHERE cm.post_id = p.id_post AND cm.status = 'active') AS comments_count
             FROM mag_posts p
             JOIN mag_categories c ON c.id_category = p.category_id
             LEFT JOIN utilisateurs u ON u.id_PK = p.author_id
             WHERE p.status = 'published'";

        $params = [];
        if ($catSlug !== null && trim($catSlug) !== '') {
            $sql .= ' AND c.slug = :slug';
            $params[':slug'] = $catSlug;
        }
        if ($q !== '') {
            $sql .= ' AND (p.title LIKE :q1 OR p.excerpt LIKE :q2 OR p.content LIKE :q3)';
            $like            = '%' . $q . '%';
            $params[':q1']   = $like;
            $params[':q2']   = $like;
            $params[':q3']   = $like;
        }

        $sql .= ' ORDER BY COALESCE(p.published_at, p.created_at) DESC';
        $sql .= ' LIMIT ' . (int)$perPage . ' OFFSET ' . (int)$offset;

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /** Count published posts for pagination. */
    public static function countPublished(string $q = '', ?string $catSlug = null): int
    {
        $pdo = Database::pdo();
        $sql =
            'SELECT COUNT(*) FROM mag_posts p
             JOIN mag_categories c ON c.id_category = p.category_id
             WHERE p.status = \'published\'';

        $params = [];
        if ($catSlug !== null && trim($catSlug) !== '') {
            $sql .= ' AND c.slug = :slug';
            $params[':slug'] = $catSlug;
        }
        if ($q !== '') {
            $sql .= ' AND (p.title LIKE :q1 OR p.excerpt LIKE :q2 OR p.content LIKE :q3)';
            $like          = '%' . $q . '%';
            $params[':q1'] = $like;
            $params[':q2'] = $like;
            $params[':q3'] = $like;
        }
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return (int)$stmt->fetchColumn();
    }

    /** @return array<string, mixed>|null */
    public static function findPublishedWithCounts(int $id): ?array
    {
        $pdo  = Database::pdo();
        $stmt = $pdo->prepare(
            "SELECT p.*, c.name AS category_name, c.slug AS category_slug,
                    CONCAT(COALESCE(u.prenom,''), ' ', COALESCE(u.nom,'')) AS author_name,
                    COALESCE(p.likes_count, 0) AS likes_count,
                    COALESCE(p.views_count, 0) AS views_count,
                    (SELECT COUNT(*) FROM mag_comments cm
                       WHERE cm.post_id = p.id_post AND cm.status = 'active') AS comments_count
             FROM mag_posts p
             JOIN mag_categories c ON c.id_category = p.category_id
             LEFT JOIN utilisateurs u ON u.id_PK = p.author_id
             WHERE p.id_post = :id AND p.status = 'published'
             LIMIT 1"
        );
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        return $row !== false ? $row : null;
    }

    // ══════════════════════════════════════════════════════════════════
    //  READ — admin
    // ══════════════════════════════════════════════════════════════════

    /**
     * Paginated post list for the admin, with optional keyword search.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function allForAdminPaged(
        string $q       = '',
        int    $page    = 1,
        int    $perPage = self::PER_PAGE_ADMIN
    ): array {
        $pdo    = Database::pdo();
        $offset = max(0, ($page - 1) * $perPage);

        $sql =
            "SELECT p.*, c.name AS category_name, c.slug AS category_slug,
                    CONCAT(COALESCE(u.prenom,''), ' ', COALESCE(u.nom,'')) AS author_name,
                    COALESCE(p.views_count, 0) AS views_count
             FROM mag_posts p
             JOIN mag_categories c ON c.id_category = p.category_id
             LEFT JOIN utilisateurs u ON u.id_PK = p.author_id";

        $params = [];
        if ($q !== '') {
            $sql .= " WHERE (p.title LIKE :q1 OR p.excerpt LIKE :q2)";
            $like          = '%' . $q . '%';
            $params[':q1'] = $like;
            $params[':q2'] = $like;
        }

        $sql .= ' ORDER BY COALESCE(p.published_at, p.created_at) DESC';
        $sql .= ' LIMIT ' . (int)$perPage . ' OFFSET ' . (int)$offset;

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /** Count all posts (with optional search) for admin pagination. */
    public static function countAllAdmin(string $q = ''): int
    {
        $pdo    = Database::pdo();
        $sql    = 'SELECT COUNT(*) FROM mag_posts p';
        $params = [];
        if ($q !== '') {
            $sql .= " WHERE (p.title LIKE :q1 OR p.excerpt LIKE :q2)";
            $like          = '%' . $q . '%';
            $params[':q1'] = $like;
            $params[':q2'] = $like;
        }
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return (int)$stmt->fetchColumn();
    }

    /** Keep legacy method for backward compat (used by old admin view). */
    public static function allForAdmin(int $limit): array
    {
        return self::allForAdminPaged('', 1, $limit);
    }

    public static function countAll(): int
    {
        $pdo = Database::pdo();
        return (int)$pdo->query('SELECT COUNT(*) FROM mag_posts')->fetchColumn();
    }

    /** @return array<string, mixed>|null */
    public static function find(int $id): ?array
    {
        $pdo  = Database::pdo();
        $stmt = $pdo->prepare('SELECT * FROM mag_posts WHERE id_post = :id LIMIT 1');
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        return $row !== false ? $row : null;
    }

    // ══════════════════════════════════════════════════════════════════
    //  STATS (aggregated from existing tables — no new table needed)
    // ══════════════════════════════════════════════════════════════════

    /**
     * Aggregate stats for the admin dashboard.
     *
     * @return array{total_published: int, total_views: int, total_likes: int, weekly_posts: int}
     */
    public static function getStats(): array
    {
        $pdo = Database::pdo();
        return [
            'total_published' => (int)$pdo->query(
                "SELECT COUNT(*) FROM mag_posts WHERE status = 'published'"
            )->fetchColumn(),
            'total_views'     => (int)$pdo->query(
                'SELECT COALESCE(SUM(views_count), 0) FROM mag_posts'
            )->fetchColumn(),
            'total_likes'     => (int)$pdo->query(
                'SELECT COALESCE(SUM(likes_count), 0) FROM mag_posts'
            )->fetchColumn(),
            'weekly_posts'    => (int)$pdo->query(
                "SELECT COUNT(*) FROM mag_posts
                 WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)"
            )->fetchColumn(),
        ];
    }

    // ══════════════════════════════════════════════════════════════════
    //  WRITE
    // ══════════════════════════════════════════════════════════════════

    /** Increment the view counter by 1. */
    public static function incrementViews(int $id): void
    {
        $pdo  = Database::pdo();
        $stmt = $pdo->prepare(
            'UPDATE mag_posts SET views_count = views_count + 1 WHERE id_post = :id'
        );
        $stmt->execute([':id' => $id]);
    }

    /** @param array<string, mixed> $data */
    public static function create(array $data): int
    {
        $pdo  = Database::pdo();
        $stmt = $pdo->prepare(
            'INSERT INTO mag_posts
               (title, excerpt, content, image_url, category_id, author_id, status, published_at)
             VALUES
               (:title, :excerpt, :content, :image_url, :category_id, :author_id, :status, :published_at)'
        );

        $publishedAt = trim((string)($data['published_at'] ?? ''));
        $publishedAt = $publishedAt === '' ? null : $publishedAt;

        $stmt->execute([
            ':title'        => (string)$data['title'],
            ':excerpt'      => ($data['excerpt']   ?? '') !== '' ? (string)$data['excerpt']   : null,
            ':content'      => (string)$data['content'],
            ':image_url'    => ($data['image_url'] ?? '') !== '' ? (string)$data['image_url'] : null,
            ':category_id'  => (int)$data['category_id'],
            ':author_id'    => (int)$data['author_id'],
            ':status'       => (string)$data['status'],
            ':published_at' => $publishedAt,
        ]);

        return (int)$pdo->lastInsertId();
    }

    /** @param array<string, mixed> $data */
    public static function update(int $id, array $data): void
    {
        $pdo         = Database::pdo();
        $publishedAt = trim((string)($data['published_at'] ?? ''));
        $publishedAt = $publishedAt === '' ? null : $publishedAt;

        $stmt = $pdo->prepare(
            'UPDATE mag_posts
             SET title        = :title,
                 excerpt      = :excerpt,
                 content      = :content,
                 image_url    = :image_url,
                 category_id  = :category_id,
                 status       = :status,
                 published_at = :published_at
             WHERE id_post = :id'
        );

        $stmt->execute([
            ':id'           => $id,
            ':title'        => (string)$data['title'],
            ':excerpt'      => ($data['excerpt']   ?? '') !== '' ? (string)$data['excerpt']   : null,
            ':content'      => (string)$data['content'],
            ':image_url'    => ($data['image_url'] ?? '') !== '' ? (string)$data['image_url'] : null,
            ':category_id'  => (int)$data['category_id'],
            ':status'       => (string)$data['status'],
            ':published_at' => $publishedAt,
        ]);
    }

    public static function delete(int $id): void
    {
        $pdo  = Database::pdo();
        $stmt = $pdo->prepare('DELETE FROM mag_posts WHERE id_post = :id');
        $stmt->execute([':id' => $id]);
    }

    // ══════════════════════════════════════════════════════════════════
    //  CATEGORIES
    // ══════════════════════════════════════════════════════════════════

    /** @return array<int, array<string, mixed>> */
    public static function categories(): array
    {
        $pdo = Database::pdo();
        return $pdo
            ->query('SELECT id_category, name, slug FROM mag_categories ORDER BY id_category ASC')
            ->fetchAll();
    }
}
