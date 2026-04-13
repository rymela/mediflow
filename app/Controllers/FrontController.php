<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Csrf;
use App\Models\Comment;
use App\Models\Like;
use App\Models\Post;

final class FrontController extends Controller
{
    private const PER_PAGE = 9; // 3-column grid

    // ── Public: article index ─────────────────────────────────────────

    public function indexAction(): void
    {
        $user = Auth::user();

        // Category filter
        $categories        = Post::categories();
        $requestedSlug     = isset($_GET['cat']) ? trim((string)$_GET['cat']) : '';
        $activeCategorySlug = null;
        if ($requestedSlug !== '') {
            foreach ($categories as $c) {
                if (isset($c['slug']) && (string)$c['slug'] === $requestedSlug) {
                    $activeCategorySlug = $requestedSlug;
                    break;
                }
            }
        }

        // Search query
        $q = isset($_GET['q']) ? trim((string)$_GET['q']) : '';

        // Pagination
        $page       = max(1, (int)($_GET['page'] ?? 1));
        $totalCount = Post::countPublished($q, $activeCategorySlug);
        $totalPages = max(1, (int)ceil($totalCount / self::PER_PAGE));
        $page       = min($page, $totalPages);

        // Fetch all published posts for the current page
        $published = Post::publishedPaged($q, $activeCategorySlug, $page, self::PER_PAGE);

        // For page 1 only: split into featured / sidebar / more
        $featured   = ($page === 1 && $q === '') ? ($published[0] ?? null) : null;
        $featuredId = $featured ? (int)$featured['id_post'] : null;
        $sidebar    = ($page === 1 && $q === '') ? array_slice($published, 1, 2) : [];
        $morePosts  = ($page === 1 && $q === '') ? array_slice($published, 3)   : $published;

        // Recent community discussions — shown at bottom of the page.
        // For logged-in users: prioritise their own recent comments (deduplicated by post),
        // then pad with the latest activity from other posts up to 6 cards.
        // For guests: show the 6 latest active comments across all published posts.
        if ($user !== null) {
            $mine           = Comment::recentDiscussionsForUser((int)$user['id_PK'], 6);
            $seenPostIds    = array_map(fn($c) => (int)$c['post_id'], $mine);
            $others         = Comment::recentAcrossAllPosts(12, $seenPostIds);
            $recentComments = array_slice(array_merge($mine, $others), 0, 6);
        } else {
            $recentComments = Comment::recentAcrossAllPosts(6);
        }

        // Has the current user liked the featured article?
        $likedFeatured = false;
        if ($featuredId !== null && $user !== null) {
            $likedFeatured = Like::hasLiked($featuredId, (int)$user['id_PK']);
        }

        $this->render('front/index', [
            'pageTitle'          => 'MediFlow Mag | Healthcare Knowledge Base',
            'metaDesc'           => 'Browse verified medical articles, health news and research from MediFlow Clinic.',
            'user'               => $user,
            'categories'         => $categories,
            'activeCategorySlug' => $activeCategorySlug,
            'searchQuery'        => $q,
            'featured'           => $featured,
            'sidebarPosts'       => $sidebar,
            'morePosts'          => $morePosts,
            'recentComments'     => $recentComments,
            'likedFeatured'      => $likedFeatured,
            'currentPage'        => $page,
            'totalPages'         => $totalPages,
            'totalCount'         => $totalCount,
            'csrfToken'          => csrf_token(),
        ]);
    }

    // ── Public: single article ────────────────────────────────────────

    public function showAction(): void
    {
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        if ($id <= 0) {
            $this->redirect('index.php');
        }

        $user       = Auth::user();
        $categories = Post::categories();
        $post       = Post::findPublishedWithCounts($id);
        if ($post === null) {
            $this->redirect('index.php');
        }

        // Record a view (fire-and-forget; ignore errors)
        try {
            Post::incrementViews($id);
        } catch (\Throwable) {
            // non-critical
        }

        $comments = Comment::forPost($id, 200);

        $liked = false;
        if ($user !== null) {
            $liked = Like::hasLiked($id, (int)$user['id_PK']);
        }

        $this->render('front/show', [
            'pageTitle'  => 'MediFlow Mag | ' . (string)($post['title'] ?? ''),
            'metaDesc'   => truncate((string)($post['excerpt'] ?? $post['title'] ?? ''), 160),
            'user'       => $user,
            'categories' => $categories,
            'post'       => $post,
            'comments'   => $comments,
            'liked'      => $liked,
            'csrfToken'  => csrf_token(),
        ]);
    }

    // ── AJAX: toggle like ─────────────────────────────────────────────

    public function toggleLikeAction(): void
    {
        Csrf::requireValid($_POST['_token'] ?? null);

        $user = Auth::user();
        if ($user === null) {
            $this->json(['ok' => false, 'error' => 'LOGIN_REQUIRED'], 401);
        }

        $postId = isset($_POST['post_id']) ? (int)$_POST['post_id'] : 0;
        if ($postId <= 0) {
            $this->json(['ok' => false, 'error' => 'INVALID_POST'], 400);
        }

        $result = Like::toggle($postId, (int)$user['id_PK']);
        $count  = Like::countForPost($postId);

        $this->json([
            'ok'         => true,
            'liked'      => $result['liked'],
            'count'      => $count,
            'countLabel' => format_count($count),
        ]);
    }

    // ── AJAX: add comment ─────────────────────────────────────────────

    public function commentStoreAction(): void
    {
        Csrf::requireValid($_POST['_token'] ?? null);

        $user = Auth::user();
        if ($user === null) {
            $this->json(['ok' => false, 'error' => 'LOGIN_REQUIRED'], 401);
        }

        $postId  = isset($_POST['post_id']) ? (int)$_POST['post_id'] : 0;
        $content = trim((string)($_POST['content'] ?? ''));

        if ($postId <= 0 || $content === '') {
            $this->json(['ok' => false, 'error' => 'INVALID_INPUT'], 400);
        }
        if (mb_strlen($content) > 2000) {
            $this->json(['ok' => false, 'error' => 'TOO_LONG'], 400);
        }

        $comment = Comment::create($postId, (int)$user['id_PK'], $content);
        $this->json(['ok' => true, 'comment' => $comment]);
    }

    // ── AJAX: report a comment ────────────────────────────────────────

    public function commentReportAction(): void
    {
        Csrf::requireValid($_POST['_token'] ?? null);

        $user = Auth::user();
        if ($user === null) {
            $this->json(['ok' => false, 'error' => 'LOGIN_REQUIRED'], 401);
        }

        $commentId = isset($_POST['comment_id']) ? (int)$_POST['comment_id'] : 0;
        if ($commentId <= 0) {
            $this->json(['ok' => false, 'error' => 'INVALID_INPUT'], 400);
        }

        // Cannot report your own comment
        $comment = Comment::find($commentId);
        if ($comment === null) {
            $this->json(['ok' => false, 'error' => 'NOT_FOUND'], 404);
        }
        if ((int)($comment['user_id'] ?? 0) === (int)$user['id_PK']) {
            $this->json(['ok' => false, 'error' => 'CANNOT_REPORT_OWN'], 400);
        }

        Comment::report($commentId);
        $this->json(['ok' => true]);
    }

    // ── AJAX: edit comment ────────────────────────────────────────────

    public function commentUpdateAction(): void
    {
        Csrf::requireValid($_POST['_token'] ?? null);

        $user = Auth::user();
        if ($user === null) {
            $this->json(['ok' => false, 'error' => 'LOGIN_REQUIRED'], 401);
        }

        $commentId = isset($_POST['comment_id']) ? (int)$_POST['comment_id'] : 0;
        $content   = trim((string)($_POST['content'] ?? ''));

        if ($commentId <= 0 || $content === '') {
            $this->json(['ok' => false, 'error' => 'INVALID_INPUT'], 400);
        }
        if (mb_strlen($content) > 2000) {
            $this->json(['ok' => false, 'error' => 'TOO_LONG'], 400);
        }

        $updated = Comment::updateOwned($commentId, (int)$user['id_PK'], $content);
        if (!$updated) {
            $this->json(['ok' => false, 'error' => 'FORBIDDEN'], 403);
        }

        $this->json(['ok' => true]);
    }

    // ── AJAX: delete comment ──────────────────────────────────────────

    public function commentDeleteAction(): void
    {
        Csrf::requireValid($_POST['_token'] ?? null);

        $user = Auth::user();
        if ($user === null) {
            $this->json(['ok' => false, 'error' => 'LOGIN_REQUIRED'], 401);
        }

        $commentId = isset($_POST['comment_id']) ? (int)$_POST['comment_id'] : 0;
        if ($commentId <= 0) {
            $this->json(['ok' => false, 'error' => 'INVALID_INPUT'], 400);
        }

        $deleted = Comment::deleteOwned($commentId, (int)$user['id_PK']);
        if (!$deleted) {
            $this->json(['ok' => false, 'error' => 'FORBIDDEN'], 403);
        }

        $this->json(['ok' => true]);
    }
}
