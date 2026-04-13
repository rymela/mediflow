<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Csrf;
use App\Models\Comment;
use App\Models\Post;

final class AdminController extends Controller
{
    private const PER_PAGE_POSTS  = 10;
    private const PER_PAGE_MOD    = 15;

    // ── Dashboard + post list ─────────────────────────────────────────

    public function indexAction(): void
    {
        Auth::requireAnyRole(['Admin', 'Magazine']);
        $user = Auth::user();

        // Search & pagination
        $q    = isset($_GET['q']) ? trim((string)$_GET['q']) : '';
        $page = max(1, (int)($_GET['page'] ?? 1));

        $totalPosts = Post::countAllAdmin($q);
        $totalPages = max(1, (int)ceil($totalPosts / self::PER_PAGE_POSTS));
        $page       = min($page, $totalPages);

        $posts = Post::allForAdminPaged($q, $page, self::PER_PAGE_POSTS);

        // Mini moderation queue (sidebar)
        $queue            = Comment::moderationQueue(3);
        $pendingModCount  = Comment::countPending();

        // Real aggregate stats
        $stats = Post::getStats();

        $this->render('admin/index', [
            'pageTitle'       => 'MediFlow Mag | CMS Portal',
            'user'            => $user,
            'posts'           => $posts,
            'postsTotal'      => $totalPosts,
            'currentPage'     => $page,
            'totalPages'      => $totalPages,
            'searchQuery'     => $q,
            'queue'           => $queue,
            'pendingModCount' => $pendingModCount,
            'stats'           => $stats,
            'csrfToken'       => csrf_token(),
        ]);
    }

    // ── Dedicated moderation page ─────────────────────────────────────

    public function moderationAction(): void
    {
        Auth::requireAnyRole(['Admin', 'Magazine']);
        $user = Auth::user();

        // Search / filter params
        $q      = isset($_GET['q'])       ? trim((string)$_GET['q'])    : '';
        $postId = isset($_GET['post_id']) ? (int)$_GET['post_id']       : null;
        if ($postId !== null && $postId <= 0) {
            $postId = null;
        }

        // Pagination
        $page  = max(1, (int)($_GET['page'] ?? 1));
        $total = Comment::countForModeration($q, $postId);
        $pages = max(1, (int)ceil($total / self::PER_PAGE_MOD));
        $page  = min($page, $pages);

        $comments        = Comment::moderationQueuePaged($page, self::PER_PAGE_MOD, $q, $postId);
        $pendingModCount = Comment::countPending(); // badge count = reported only

        // All posts for the post-filter dropdown
        $allPosts = Post::allForAdminPaged('', 1, 200);

        // Build pagination base URL
        $paginationBase = 'index.php?controller=admin&action=moderation';
        if ($q !== '')          $paginationBase .= '&q='       . urlencode($q);
        if ($postId !== null)   $paginationBase .= '&post_id=' . $postId;

        $this->render('admin/moderation', [
            'pageTitle'       => 'MediFlow Mag | Comment Moderation',
            'user'            => $user,
            'comments'        => $comments,
            'pendingModCount' => $pendingModCount,
            'currentPage'     => $page,
            'totalPages'      => $pages,
            'totalCount'      => $total,
            'searchQuery'     => $q,
            'filterPostId'    => $postId,
            'allPosts'        => $allPosts,
            'hasFilter'       => $q !== '' || $postId !== null,
            'paginationBase'  => $paginationBase,
            'csrfToken'       => csrf_token(),
        ]);
    }

    // ── Create post form ──────────────────────────────────────────────

    public function createAction(): void
    {
        Auth::requireAnyRole(['Admin', 'Magazine']);
        $user = Auth::user();

        $this->render('admin/post_form', [
            'pageTitle'  => 'MediFlow Mag | Create Post',
            'user'       => $user,
            'mode'       => 'create',
            'post'       => null,
            'categories' => Post::categories(),
            'csrfToken'  => csrf_token(),
        ]);
    }

    // ── Store new post ────────────────────────────────────────────────

    public function storeAction(): void
    {
        Auth::requireAnyRole(['Admin', 'Magazine']);
        Csrf::requireValid($_POST['_token'] ?? null);

        $user = Auth::user();

        $data = [
            'title'        => trim((string)($_POST['title']        ?? '')),
            'excerpt'      => trim((string)($_POST['excerpt']      ?? '')),
            'content'      => trim((string)($_POST['content']      ?? '')),
            'image_url'    => trim((string)($_POST['image_url']    ?? '')),
            'category_id'  => (int)($_POST['category_id']         ?? 0),
            'status'       => (string)($_POST['status']            ?? 'published'),
            'published_at' => (string)($_POST['published_at']      ?? ''),
            'author_id'    => (int)($user['id_PK']                 ?? 0),
        ];

        Post::create($data);
        $this->redirect('index.php?controller=admin&action=index');
    }

    // ── Edit post form ────────────────────────────────────────────────

    public function editAction(): void
    {
        Auth::requireAnyRole(['Admin', 'Magazine']);
        $user = Auth::user();

        $id   = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        $post = $id > 0 ? Post::find($id) : null;
        if ($post === null) {
            $this->redirect('index.php?controller=admin&action=index');
        }

        $this->render('admin/post_form', [
            'pageTitle'  => 'MediFlow Mag | Edit Post',
            'user'       => $user,
            'mode'       => 'edit',
            'post'       => $post,
            'categories' => Post::categories(),
            'csrfToken'  => csrf_token(),
        ]);
    }

    // ── Update existing post ──────────────────────────────────────────

    public function updateAction(): void
    {
        Auth::requireAnyRole(['Admin', 'Magazine']);
        Csrf::requireValid($_POST['_token'] ?? null);

        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        if ($id <= 0) {
            $this->redirect('index.php?controller=admin&action=index');
        }

        $data = [
            'title'        => trim((string)($_POST['title']        ?? '')),
            'excerpt'      => trim((string)($_POST['excerpt']      ?? '')),
            'content'      => trim((string)($_POST['content']      ?? '')),
            'image_url'    => trim((string)($_POST['image_url']    ?? '')),
            'category_id'  => (int)($_POST['category_id']         ?? 0),
            'status'       => (string)($_POST['status']            ?? 'published'),
            'published_at' => (string)($_POST['published_at']      ?? ''),
        ];

        Post::update($id, $data);
        $this->redirect('index.php?controller=admin&action=index');
    }

    // ── Delete post ───────────────────────────────────────────────────

    public function deleteAction(): void
    {
        Auth::requireAnyRole(['Admin', 'Magazine']);
        Csrf::requireValid($_POST['_token'] ?? null);

        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        if ($id > 0) {
            Post::delete($id);
        }

        $this->redirect('index.php?controller=admin&action=index');
    }

    // ── Restore comment (Keep / dismiss report) ───────────────────────

    public function commentRestoreAction(): void
    {
        Auth::requireAnyRole(['Admin', 'Magazine']);
        Csrf::requireValid($_POST['_token'] ?? null);

        $id   = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        $back = $_SERVER['HTTP_REFERER'] ?? 'index.php?controller=admin&action=moderation';

        if ($id > 0) {
            Comment::restore($id);
        }

        $this->redirect($back);
    }

    // ── Warn comment (report / flag) ──────────────────────────────────

    public function commentWarnAction(): void
    {
        Auth::requireAnyRole(['Admin', 'Magazine']);
        Csrf::requireValid($_POST['_token'] ?? null);

        $id   = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        $back = $_SERVER['HTTP_REFERER'] ?? 'index.php?controller=admin&action=index';

        if ($id > 0) {
            Comment::flag($id);
        }

        $this->redirect($back);
    }

    // ── Delete comment (soft) ─────────────────────────────────────────

    public function commentDeleteAction(): void
    {
        Auth::requireAnyRole(['Admin', 'Magazine']);
        Csrf::requireValid($_POST['_token'] ?? null);

        $id   = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        $back = $_SERVER['HTTP_REFERER'] ?? 'index.php?controller=admin&action=index';

        if ($id > 0) {
            Comment::adminDelete($id);
        }

        $this->redirect($back);
    }
}
