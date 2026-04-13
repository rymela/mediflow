<?php

/** @var array<string, mixed>|null $user */
/** @var array<int, array<string, mixed>> $categories */
/** @var array<string, mixed> $post */
/** @var array<int, array<string, mixed>> $comments */
/** @var bool $liked */
/** @var string $csrfToken */

$postId        = (int)$post['id_post'];
$likesCount    = (int)($post['likes_count']    ?? 0);
$commentsCount = (int)($post['comments_count'] ?? 0);
$viewsCount    = (int)($post['views_count']    ?? 0);
$nextParam     = urlencode((string)($_SERVER['REQUEST_URI'] ?? 'index.php'));
$authHref      = $user ? ('logout.php?next=' . $nextParam) : ('login.php?next=' . $nextParam);
$authLabel     = $user ? 'Logout' : 'Login';
$displayName   = $user ? trim((string)($user['prenom'] . ' ' . $user['nom'])) : '';
$activeCategorySlug = isset($post['category_slug']) ? (string)$post['category_slug'] : null;
$navActive     = 'text-blue-700 font-semibold border-b-2 border-teal-500 pb-1';
$navInactive   = 'text-slate-500 hover:text-blue-600 transition-colors';
?>
<!DOCTYPE html>
<html lang="en" class="light">
<head>
<?php include __DIR__ . '/../shared/head.php'; ?>
</head>
<body class="text-on-surface bg-surface antialiased overflow-x-hidden">

<!-- TOP NAV -->
<nav class="fixed top-0 w-full z-50 bg-white/80 backdrop-blur-xl shadow-[0_4px_30px_rgba(0,77,153,0.07)] border-b border-slate-100/60">
  <div class="flex items-center gap-3 px-4 md:px-8 py-3">
    <a class="text-xl font-extrabold tracking-tight text-blue-900 shrink-0 font-headline"
       href="index.php?controller=front&action=index">
      MediFlow <span class="text-tertiary">Mag</span>
    </a>

    <div class="flex-1 overflow-x-auto hidden sm:block">
      <div class="flex items-center gap-5 md:gap-6 whitespace-nowrap">
        <a class="<?= $activeCategorySlug === null ? $navActive : $navInactive ?> text-sm"
           href="index.php?controller=front&action=index">All</a>
        <?php foreach ($categories as $cat): ?>
        <a class="<?= $activeCategorySlug === (string)($cat['slug'] ?? '') ? $navActive : $navInactive ?> text-sm"
           href="index.php?controller=front&action=index&cat=<?= urlencode((string)($cat['slug'] ?? '')) ?>">
          <?= e((string)($cat['name'] ?? '')) ?>
        </a>
        <?php endforeach; ?>
      </div>
    </div>

    <?php if ($user !== null): ?>
    <div class="flex items-center gap-2 shrink-0">
      <?= avatar_html($displayName, '8', 'xs') ?>
      <a class="text-xs text-slate-500 hover:text-blue-600 transition-colors hidden md:block" href="<?= e($authHref) ?>"><?= e($authLabel) ?></a>
    </div>
    <?php else: ?>
    <a class="shrink-0 text-sm font-semibold text-primary hover:text-blue-700 transition-colors" href="<?= e($authHref) ?>"><?= e($authLabel) ?></a>
    <?php endif; ?>
  </div>
</nav>

<div class="flex min-h-screen pt-[60px]">

  <!-- Sidebar -->
  <aside class="hidden lg:flex flex-col w-64 xl:w-72 h-[calc(100vh-60px)] sticky top-[60px] bg-surface-container-low border-r border-outline-variant/20 p-6 space-y-6 shrink-0">
    <div class="space-y-2">
      <h3 class="text-[10px] font-bold uppercase tracking-widest text-outline">Navigation</h3>
      <nav class="flex flex-col gap-1">
        <a class="flex items-center gap-3 px-4 py-2.5 rounded-xl bg-primary-container text-on-primary font-semibold text-sm shadow-sm"
           href="index.php?controller=front&action=index">
          <span class="material-symbols-outlined text-[20px]">article</span>
          <span>Magazine</span>
        </a>
      </nav>
    </div>

    <!-- Article meta card -->
    <div class="bg-surface-container rounded-2xl p-4 space-y-3">
      <h4 class="text-xs font-bold text-outline uppercase tracking-wider">About this article</h4>
      <div class="space-y-2 text-xs text-on-surface-variant">
        <div class="flex items-center gap-2">
          <?= avatar_html((string)($post['author_name'] ?? 'Author'), '7', 'xs') ?>
          <span><?= e((string)($post['author_name'] ?? '')) ?></span>
        </div>
        <div class="flex items-center gap-2">
          <span class="material-symbols-outlined text-[16px]">schedule</span>
          <span><?= e(time_ago((string)($post['published_at'] ?? $post['created_at'] ?? ''))) ?></span>
        </div>
        <div class="flex items-center gap-2">
          <span class="material-symbols-outlined text-[16px]">timer</span>
          <span><?= e(read_time((string)($post['content'] ?? ''))) ?></span>
        </div>
        <div class="flex items-center gap-2">
          <span class="material-symbols-outlined text-[16px]">visibility</span>
          <span><?= e(format_count($viewsCount)) ?> views</span>
        </div>
        <div class="flex items-center gap-2">
          <span class="material-symbols-outlined text-[16px]">label</span>
          <span class="font-semibold text-tertiary"><?= e((string)($post['category_name'] ?? '')) ?></span>
        </div>
      </div>
    </div>
  </aside>

  <!-- Main content -->
  <main class="flex-1 min-w-0 px-4 md:px-8 xl:px-12 py-8 page-fade">
    <div class="mx-auto w-full max-w-4xl">

      <!-- Back link -->
      <a class="inline-flex items-center gap-1.5 text-sm font-semibold text-primary hover:opacity-80 transition-opacity mb-6"
         href="index.php?controller=front&action=index">
        <span class="material-symbols-outlined text-[18px]">arrow_back</span> Back to Magazine
      </a>

      <!-- Article card -->
      <article class="bg-surface-container-lowest rounded-3xl overflow-hidden shadow-[0_20px_50px_rgba(0,77,153,0.07)] border-t-4 border-tertiary-fixed mb-8">
        <?php if (!empty($post['image_url'])): ?>
        <img class="w-full h-64 md:h-80 object-cover" src="<?= e((string)$post['image_url']) ?>" alt="<?= e((string)$post['title']) ?>"/>
        <?php else: ?>
        <div class="w-full h-48 bg-gradient-to-br from-primary-fixed to-secondary-container flex items-center justify-center">
          <span class="material-symbols-outlined text-6xl text-primary/30">article</span>
        </div>
        <?php endif; ?>

        <div class="p-6 md:p-10">
          <!-- Category pill -->
          <span class="text-xs font-bold text-tertiary px-2.5 py-1 bg-tertiary-fixed/40 rounded-full">
            <?= e((string)($post['category_name'] ?? '')) ?>
          </span>

          <!-- Title -->
          <h1 class="text-2xl md:text-4xl font-extrabold text-blue-900 tracking-tight leading-tight mt-4 mb-4 font-headline">
            <?= e((string)$post['title']) ?>
          </h1>

          <!-- Author + meta row -->
          <div class="flex items-center gap-3 mb-6 flex-wrap">
            <?= avatar_html((string)($post['author_name'] ?? 'Author'), '9', 'xs') ?>
            <div>
              <p class="text-sm font-bold text-on-surface"><?= e((string)($post['author_name'] ?? '')) ?></p>
              <p class="text-xs text-slate-400">
                <?= e(time_ago((string)($post['published_at'] ?? $post['created_at'] ?? ''))) ?>
                &middot; <?= e(read_time((string)($post['content'] ?? ''))) ?>
                &middot; <span class="material-symbols-outlined text-[12px] align-middle">visibility</span> <?= e(format_count($viewsCount)) ?>
              </p>
            </div>
          </div>

          <!-- Excerpt -->
          <?php if (!empty($post['excerpt'])): ?>
          <p class="text-base text-slate-600 mb-6 leading-relaxed border-l-4 border-tertiary-fixed pl-4 italic">
            <?= e((string)$post['excerpt']) ?>
          </p>
          <?php endif; ?>

          <!-- Body content -->
          <div class="prose prose-slate max-w-none text-slate-700 leading-relaxed whitespace-pre-line text-[15px]">
            <?= e((string)$post['content']) ?>
          </div>

          <!-- Interactions bar -->
          <div class="flex items-center justify-between pt-6 mt-8 border-t border-slate-100 flex-wrap gap-4">
            <div class="flex items-center gap-5">
              <!-- Like -->
              <button class="flex items-center gap-2 group/like transition-all" data-like-button data-post-id="<?= $postId ?>" data-csrf="<?= e($csrfToken) ?>">
                <span class="material-symbols-outlined text-2xl transition-all duration-200 <?= $liked ? 'text-red-500' : 'text-slate-400 group-hover/like:text-red-400' ?>"
                      data-like-icon style="font-variation-settings:'FILL' <?= $liked ? 1 : 0 ?>">favorite</span>
                <span class="text-sm font-bold text-slate-500 tabular-nums" data-like-count><?= e(format_count($likesCount)) ?></span>
              </button>
              <!-- Comments count -->
              <div class="flex items-center gap-2 text-slate-400">
                <span class="material-symbols-outlined text-2xl">forum</span>
                <span class="text-sm font-bold tabular-nums"><?= e(format_count($commentsCount)) ?> Comments</span>
              </div>
            </div>
            <!-- Share / copy link -->
            <button id="copy-link-btn" class="flex items-center gap-1.5 text-sm text-on-surface-variant hover:text-primary transition-colors font-semibold"
                    data-url="<?= e($_SERVER['REQUEST_URI'] ?? '') ?>">
              <span class="material-symbols-outlined text-[18px]">share</span> Share
            </button>
          </div>
        </div>
      </article>

      <!-- Comments section -->
      <section class="bg-surface-container-high rounded-3xl p-6 md:p-8" id="comments">
        <h2 class="text-lg font-bold text-blue-900 mb-6 flex items-center gap-2 font-headline">
          <span class="material-symbols-outlined text-xl text-tertiary">forum</span>
          Discussion <span class="text-sm font-normal text-on-surface-variant ml-1">(<?= count($comments) ?>)</span>
        </h2>

        <!-- Comment list -->
        <div class="space-y-4 mb-6" data-comments-list>
          <?php foreach ($comments as $cm): ?>
          <?php $isMine = $user && (int)$cm['user_id'] === (int)$user['id_PK']; ?>
          <div class="flex gap-3" data-comment-row="<?= (int)$cm['id_comment'] ?>">
            <?= avatar_html((string)($cm['user_name'] ?? 'Anonymous'), '8', 'xs') ?>
            <div class="flex-1 min-w-0">
              <div class="<?= $isMine ? 'bg-white border-l-4 border-teal-500' : 'bg-white/70' ?> rounded-2xl p-4 shadow-sm">
                <div class="flex justify-between items-center mb-2 flex-wrap gap-2">
                  <div>
                    <span class="text-sm font-bold <?= $isMine ? 'text-primary' : 'text-on-surface' ?>">
                      <?= $isMine ? 'You' : e((string)($cm['user_name'] ?? 'Anonymous')) ?>
                    </span>
                    <span class="text-xs text-slate-400 ml-2"><?= e(time_ago((string)$cm['created_at'])) ?></span>
                  </div>
                  <div class="flex gap-1 items-center">
                    <?php if ($isMine): ?>
                    <!-- Own comment: edit + delete -->
                    <button class="text-slate-400 hover:text-blue-600 transition-colors p-1.5 rounded-lg hover:bg-slate-100" title="Edit"
                            data-comment-edit data-comment-id="<?= (int)$cm['id_comment'] ?>" data-csrf="<?= e($csrfToken) ?>">
                      <span class="material-symbols-outlined text-[16px]">edit</span>
                    </button>
                    <button class="text-slate-400 hover:text-red-500 transition-colors p-1.5 rounded-lg hover:bg-red-50" title="Delete"
                            data-comment-delete data-comment-id="<?= (int)$cm['id_comment'] ?>" data-csrf="<?= e($csrfToken) ?>">
                      <span class="material-symbols-outlined text-[16px]">delete</span>
                    </button>
                    <?php elseif ($user !== null): ?>
                    <!-- Other user's comment (logged-in): report button -->
                    <button class="text-slate-300 hover:text-amber-500 transition-colors p-1.5 rounded-lg hover:bg-amber-50 group/rep" title="Report this comment"
                            data-comment-report data-comment-id="<?= (int)$cm['id_comment'] ?>" data-csrf="<?= e($csrfToken) ?>">
                      <span class="material-symbols-outlined text-[16px]">flag</span>
                    </button>
                    <?php endif; ?>
                  </div>
                </div>
                <p class="text-sm text-slate-700 leading-relaxed" data-comment-body="<?= (int)$cm['id_comment'] ?>"><?= e((string)$cm['content']) ?></p>
              </div>
            </div>
          </div>
          <?php endforeach; ?>

          <?php if (empty($comments)): ?>
          <div class="text-center py-10">
            <span class="material-symbols-outlined text-5xl text-outline/40 block mb-3">chat_bubble_outline</span>
            <p class="text-sm text-on-surface-variant">No comments yet. Be the first to share your thoughts!</p>
          </div>
          <?php endif; ?>
        </div>

        <!-- Comment input area -->
        <?php if ($user !== null): ?>
        <div class="flex gap-3 items-start pt-4 border-t border-outline-variant/20">
          <?= avatar_html($displayName, '9', 'xs') ?>
          <div class="flex-1 relative">
            <textarea id="comment-input"
                      class="w-full text-sm bg-white rounded-2xl border border-slate-200 focus:ring-2 focus:ring-tertiary/30 focus:border-tertiary p-4 pr-12 outline-none resize-none transition leading-relaxed"
                      placeholder="Share your thoughts…" rows="3" maxlength="2000"
                      data-comment-input></textarea>
            <button class="absolute right-3 bottom-3 text-primary hover:text-blue-700 transition-colors disabled:opacity-40"
                    data-comment-send data-post-id="<?= $postId ?>" data-csrf="<?= e($csrfToken) ?>" title="Post comment">
              <span class="material-symbols-outlined text-xl">send</span>
            </button>
          </div>
        </div>
        <p class="text-xs text-slate-400 mt-2 ml-12">Max 2000 characters. Be respectful.</p>
        <?php else: ?>
        <div class="pt-4 border-t border-outline-variant/20">
          <div class="bg-blue-50 border border-blue-100 rounded-2xl p-5 text-center">
            <span class="material-symbols-outlined text-3xl text-primary/60 block mb-2">lock</span>
            <p class="text-sm text-slate-600 mb-3">
              <a href="login.php?next=<?= urlencode('index.php?controller=front&action=show&id=' . $postId . '#comments') ?>"
                 class="text-primary font-bold hover:underline">Login</a>
              to join the discussion and like this article.
            </p>
          </div>
        </div>
        <?php endif; ?>
      </section>

    </div>
  </main>
</div>

<!-- Toast -->
<div id="mf-toast" aria-live="polite">
  <div class="bg-slate-900 text-white text-sm font-semibold px-5 py-3 rounded-2xl shadow-xl flex items-center gap-3">
    <span class="material-symbols-outlined text-[18px]" id="mf-toast-icon">check_circle</span>
    <span id="mf-toast-msg">Done!</span>
  </div>
</div>

<script src="assets/js/frontOffice.js"></script>
<script>
// Copy share link
document.getElementById('copy-link-btn')?.addEventListener('click', function () {
  navigator.clipboard.writeText(window.location.href).then(() => {
    window.showToast('Link copied to clipboard!', 'link');
  }).catch(() => {});
});
</script>
</body>
</html>
