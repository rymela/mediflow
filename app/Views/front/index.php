<?php

/** @var array<string, mixed>|null $user */
/** @var array<int, array<string, mixed>> $categories */
/** @var string|null $activeCategorySlug */
/** @var string $searchQuery */
/** @var array<string, mixed>|null $featured */
/** @var array<int, array<string, mixed>> $sidebarPosts */
/** @var array<int, array<string, mixed>> $morePosts */
/** @var array<int, array<string, mixed>> $recentComments */
/** @var bool $likedFeatured */
/** @var int $currentPage */
/** @var int $totalPages */
/** @var int $totalCount */
/** @var string $csrfToken */

$featuredId    = $featured ? (int)$featured['id_post']       : 0;
$likesCount    = $featured ? (int)$featured['likes_count']   : 0;
$commentsCount = $featured ? (int)$featured['comments_count']: 0;
$displayName   = $user ? trim((string)($user['prenom'] . ' ' . $user['nom'])) : '';
$nextParam     = urlencode((string)($_SERVER['REQUEST_URI'] ?? 'index.php'));
$authHref      = $user ? ('logout.php?next=' . $nextParam) : ('login.php?next=' . $nextParam);
$authLabel     = $user ? 'Logout' : 'Login';
$navActive     = 'text-blue-700 font-semibold border-b-2 border-teal-500 pb-1';
$navInactive   = 'text-slate-500 hover:text-blue-600 transition-colors';

// Build base URL for pagination (preserve q and cat params)
$paginationBase = 'index.php?controller=front&action=index';
if ($activeCategorySlug !== null) $paginationBase .= '&cat=' . urlencode($activeCategorySlug);
if ($searchQuery !== '')          $paginationBase .= '&q='   . urlencode($searchQuery);
?>
<!DOCTYPE html>
<html lang="en" class="light">
<head>
<?php include __DIR__ . '/../shared/head.php'; ?>
</head>
<body class="text-on-surface bg-surface antialiased overflow-x-hidden">

<!-- ═══════════════════════ TOP NAVIGATION ═══════════════════════ -->
<nav class="fixed top-0 w-full z-50 bg-white/80 backdrop-blur-xl shadow-[0_4px_30px_rgba(0,77,153,0.07)] border-b border-slate-100/60">
  <div class="flex items-center gap-3 px-4 md:px-8 py-3">
    <!-- Logo -->
    <a class="text-xl font-extrabold tracking-tight text-blue-900 shrink-0 font-headline" href="index.php?controller=front&action=index">
      MediFlow <span class="text-tertiary">Mag</span>
    </a>

    <!-- Category tabs -->
    <div class="flex-1 overflow-x-auto hidden sm:block">
      <div class="flex items-center gap-5 md:gap-6 whitespace-nowrap">
        <a class="<?= $activeCategorySlug === null && $searchQuery === '' ? $navActive : $navInactive ?> text-sm"
           href="index.php?controller=front&action=index">All</a>
        <?php foreach ($categories as $cat): ?>
        <a class="<?= $activeCategorySlug === (string)($cat['slug'] ?? '') ? $navActive : $navInactive ?> text-sm"
           href="index.php?controller=front&action=index&cat=<?= urlencode((string)($cat['slug'] ?? '')) ?>">
          <?= e((string)($cat['name'] ?? '')) ?>
        </a>
        <?php endforeach; ?>
      </div>
    </div>

    <!-- Search form -->
    <form method="get" action="index.php" class="flex items-center gap-2 shrink-0">
      <input type="hidden" name="controller" value="front"/>
      <input type="hidden" name="action"     value="index"/>
      <?php if ($activeCategorySlug !== null): ?>
      <input type="hidden" name="cat" value="<?= e($activeCategorySlug) ?>"/>
      <?php endif; ?>
      <div class="relative">
        <span class="material-symbols-outlined absolute left-2.5 top-1/2 -translate-y-1/2 text-slate-400 text-[18px] pointer-events-none">search</span>
        <input id="nav-search" name="q" type="search" value="<?= e($searchQuery) ?>"
               placeholder="Search articles…"
               class="pl-8 pr-3 py-1.5 text-sm rounded-lg border border-slate-200 bg-white focus:ring-2 focus:ring-primary/30 focus:border-primary outline-none w-40 md:w-52 transition-all"/>
      </div>
    </form>

    <!-- Auth link -->
    <?php if ($user !== null): ?>
    <div class="flex items-center gap-2 shrink-0">
      <?= avatar_html($displayName, '8', 'xs') ?>
      <a class="text-xs text-slate-500 hover:text-blue-600 transition-colors hidden md:block" href="<?= e($authHref) ?>"><?= e($authLabel) ?></a>
    </div>
    <?php else: ?>
    <a class="shrink-0 text-sm font-semibold text-primary hover:text-blue-700 transition-colors" href="<?= e($authHref) ?>"><?= e($authLabel) ?></a>
    <?php endif; ?>
  </div>

  <!-- Mobile category row -->
  <div class="sm:hidden overflow-x-auto border-t border-slate-100 px-4 py-2">
    <div class="flex items-center gap-4 whitespace-nowrap">
      <a class="<?= $activeCategorySlug === null && $searchQuery === '' ? $navActive : $navInactive ?> text-xs" href="index.php?controller=front&action=index">All</a>
      <?php foreach ($categories as $cat): ?>
      <a class="<?= $activeCategorySlug === (string)($cat['slug'] ?? '') ? $navActive : $navInactive ?> text-xs"
         href="index.php?controller=front&action=index&cat=<?= urlencode((string)($cat['slug'] ?? '')) ?>">
        <?= e((string)($cat['name'] ?? '')) ?>
      </a>
      <?php endforeach; ?>
    </div>
  </div>
</nav>

<div class="flex min-h-screen pt-[88px] sm:pt-[60px]">

  <!-- ═══════════════════════ LEFT SIDEBAR ═══════════════════════ -->
<aside class="hidden lg:flex flex-col w-60 xl:w-64 h-[calc(100vh-60px)] sticky top-[60px] bg-surface-container-low border-r border-outline-variant/20 p-5 shrink-0">

  <!-- Navigation -->
  <div class="space-y-1.5">
    <h3 class="text-[10px] font-bold uppercase tracking-widest text-outline px-1 mb-2">Navigation</h3>
    <a class="flex items-center gap-3 px-4 py-2.5 rounded-xl bg-primary-container text-on-primary font-semibold text-sm shadow-sm"
       href="index.php?controller=front&action=index">
      <span class="material-symbols-outlined text-[20px]">article</span>
      <span>Magazine</span>
    </a>
  </div>

</aside>

  <!-- ═══════════════════════ MAIN CONTENT ═══════════════════════ -->
  <main class="flex-1 min-w-0 px-4 md:px-8 xl:px-12 py-8 page-fade">
    <div class="mx-auto w-full max-w-5xl">

      <!-- Search result banner -->
      <?php if ($searchQuery !== ''): ?>
      <div class="mb-8 flex items-center justify-between flex-wrap gap-3">
        <div>
          <p class="text-sm text-on-surface-variant">
            Showing <strong class="text-on-surface"><?= (int)$totalCount ?></strong> result<?= $totalCount !== 1 ? 's' : '' ?> for
            "<strong class="text-primary"><?= e($searchQuery) ?></strong>"
          </p>
        </div>
        <a href="index.php?controller=front&action=index<?= $activeCategorySlug ? '&cat=' . urlencode($activeCategorySlug) : '' ?>"
           class="inline-flex items-center gap-1 text-xs font-semibold text-on-surface-variant hover:text-error transition-colors bg-surface-container px-3 py-1.5 rounded-lg">
          <span class="material-symbols-outlined text-[14px]">close</span> Clear search
        </a>
      </div>
      <?php endif; ?>

      <!-- Hero Header (page 1 only, no search) -->
      <?php if ($currentPage === 1 && $searchQuery === ''): ?>
      <header class="mb-10">
        <div class="inline-flex items-center gap-2 px-3 py-1 bg-tertiary-fixed text-on-tertiary-fixed rounded-full text-[10px] font-bold uppercase tracking-widest mb-3">
          <span class="w-1.5 h-1.5 bg-tertiary rounded-full animate-pulse"></span>
          Trending Today
        </div>
        <h1 class="text-3xl md:text-4xl font-extrabold text-blue-900 tracking-tight leading-tight mb-3">
          Curated Healthcare <br/><span class="text-tertiary">Knowledge Base</span>
        </h1>
        <p class="text-base text-slate-500 max-w-xl leading-relaxed">
          Verified medical insights and news from our clinical staff to help you navigate your wellness journey.
        </p>
      </header>
      <?php endif; ?>

      <!-- Guest CTA banner -->
      <?php if ($user === null): ?>
      <div class="mb-8 bg-gradient-to-r from-blue-50 to-teal-50 border border-blue-100 rounded-2xl p-5 flex items-center justify-between flex-wrap gap-4">
        <div>
          <p class="font-bold text-blue-900 text-sm font-headline">Join the MediFlow community</p>
          <p class="text-xs text-slate-500 mt-0.5">Login to like articles, leave comments, and save your progress.</p>
        </div>
        <a href="login.php?next=<?= urlencode('index.php?controller=front&action=index') ?>"
           class="shrink-0 bg-primary text-on-primary text-sm font-semibold px-5 py-2.5 rounded-xl hover:opacity-90 transition-opacity shadow-sm">
          Login / Sign up
        </a>
      </div>
      <?php endif; ?>

      <!-- ── Featured + Sidebar layout (page 1, no search) ── -->
      <?php if ($featured && $currentPage === 1 && $searchQuery === ''): ?>
      <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 mb-12">
        <!-- Featured article -->
        <article class="lg:col-span-8 bg-surface-container-lowest rounded-3xl overflow-hidden shadow-[0_20px_50px_rgba(0,77,153,0.07)] border-t-4 border-tertiary-fixed group">
          <?php if (!empty($featured['image_url'])): ?>
          <a href="index.php?controller=front&action=show&id=<?= $featuredId ?>">
            <img class="w-full h-64 md:h-80 object-cover group-hover:scale-[1.01] transition-transform duration-500" src="<?= e((string)$featured['image_url']) ?>" alt="<?= e((string)$featured['title']) ?>"/>
          </a>
          <?php else: ?>
          <div class="w-full h-64 md:h-80 bg-gradient-to-br from-primary-fixed to-secondary-container flex items-center justify-center">
            <span class="material-symbols-outlined text-6xl text-primary/30">article</span>
          </div>
          <?php endif; ?>

          <div class="p-6 md:p-8">
            <!-- Category + read time -->
            <div class="flex items-center gap-3 mb-4">
              <span class="text-xs font-bold text-tertiary px-2.5 py-1 bg-tertiary-fixed/40 rounded-full">
                <?= e((string)($featured['category_name'] ?? '')) ?>
              </span>
              <span class="text-xs text-slate-400"><?= e(read_time((string)($featured['content'] ?? ''))) ?></span>
            </div>

            <!-- Author row -->
            <div class="flex items-center gap-3 mb-4">
              <?= avatar_html((string)($featured['author_name'] ?? 'Author'), '9', 'xs') ?>
              <div>
                <p class="text-sm font-bold text-on-surface"><?= e((string)($featured['author_name'] ?? '')) ?></p>
                <p class="text-xs text-slate-400"><?= e(time_ago((string)($featured['published_at'] ?? $featured['created_at'] ?? ''))) ?></p>
              </div>
            </div>

            <h2 class="text-2xl md:text-3xl font-extrabold text-blue-900 mb-3 leading-tight font-headline">
              <a href="index.php?controller=front&action=show&id=<?= $featuredId ?>" class="hover:text-primary transition-colors">
                <?= e((string)$featured['title']) ?>
              </a>
            </h2>
            <p class="text-slate-600 mb-6 leading-relaxed line-clamp-3">
              <?= e((string)($featured['excerpt'] ?? '')) ?>
            </p>

            <!-- Interactions bar -->
            <div class="flex items-center justify-between pt-5 border-t border-slate-100">
              <div class="flex items-center gap-5">
                <!-- Like button -->
                <button class="flex items-center gap-2 group/like" data-like-button data-post-id="<?= $featuredId ?>" data-csrf="<?= e($csrfToken) ?>" <?= $featuredId ? '' : 'disabled' ?>>
                  <span class="material-symbols-outlined text-xl transition-all duration-200 <?= $likedFeatured ? 'text-red-500' : 'text-slate-400 group-hover/like:text-red-400' ?>"
                        data-like-icon style="font-variation-settings:'FILL' <?= $likedFeatured ? 1 : 0 ?>">favorite</span>
                  <span class="text-sm font-bold text-slate-500 tabular-nums" data-like-count><?= e(format_count($likesCount)) ?></span>
                </button>
                <!-- Comments link -->
                <a class="flex items-center gap-2 group/cm text-slate-500 hover:text-blue-600 transition-colors"
                   href="index.php?controller=front&action=show&id=<?= $featuredId ?>#comments">
                  <span class="material-symbols-outlined text-xl">forum</span>
                  <span class="text-sm font-bold tabular-nums"><?= e(format_count($commentsCount)) ?></span>
                </a>
                <!-- Views -->
                <span class="hidden sm:flex items-center gap-1.5 text-xs text-slate-400">
                  <span class="material-symbols-outlined text-[16px]">visibility</span>
                  <?= e(format_count((int)($featured['views_count'] ?? 0))) ?>
                </span>
              </div>
              <a class="flex items-center gap-1.5 text-primary font-bold text-sm hover:gap-2.5 transition-all"
                 href="index.php?controller=front&action=show&id=<?= $featuredId ?>">
                Read article <span class="material-symbols-outlined text-[18px]">arrow_forward</span>
              </a>
            </div>
          </div>
        </article>

        <!-- Sidebar cards -->
        <div class="lg:col-span-4 flex flex-col gap-5">
          <?php foreach ($sidebarPosts as $p): ?>
          <?php $pid = (int)($p['id_post'] ?? 0); ?>
          <article class="bg-surface-container-lowest rounded-2xl p-5 shadow-[0_10px_30px_rgba(0,77,153,0.04)] border border-outline-variant/10 hover:shadow-[0_14px_40px_rgba(0,77,153,0.08)] transition-shadow flex flex-col gap-3">
            <?php if (!empty($p['image_url'])): ?>
            <a href="index.php?controller=front&action=show&id=<?= $pid ?>">
              <img class="w-full h-32 object-cover rounded-xl" src="<?= e((string)$p['image_url']) ?>" alt="<?= e((string)$p['title']) ?>"/>
            </a>
            <?php endif; ?>
            <div class="flex items-center gap-2">
              <span class="text-[10px] font-bold text-tertiary px-2 py-0.5 bg-tertiary-fixed/30 rounded-full">
                <?= e((string)($p['category_name'] ?? '')) ?>
              </span>
              <span class="text-[10px] text-slate-400"><?= e(time_ago((string)($p['published_at'] ?? $p['created_at'] ?? ''))) ?></span>
            </div>
            <h3 class="text-base font-bold text-blue-900 leading-snug font-headline">
              <a href="index.php?controller=front&action=show&id=<?= $pid ?>" class="hover:text-primary transition-colors">
                <?= e((string)$p['title']) ?>
              </a>
            </h3>
            <p class="text-xs text-slate-500 leading-relaxed line-clamp-2"><?= e((string)($p['excerpt'] ?? '')) ?></p>
            <div class="flex items-center gap-3 text-xs text-slate-400 mt-auto">
              <span class="flex items-center gap-1"><span class="material-symbols-outlined text-[14px]" style="font-variation-settings:'FILL' 1">favorite</span><?= e(format_count((int)($p['likes_count'] ?? 0))) ?></span>
              <span class="flex items-center gap-1"><span class="material-symbols-outlined text-[14px]">forum</span><?= e(format_count((int)($p['comments_count'] ?? 0))) ?></span>
            </div>
          </article>
          <?php endforeach; ?>

          <?php if (empty($sidebarPosts)): ?>
          <div class="bg-surface-container-lowest rounded-2xl p-5 text-sm text-on-surface-variant">More articles coming soon.</div>
          <?php endif; ?>
        </div>
      </div>
      <?php endif; ?>

      <!-- ── More / All articles grid ── -->
      <?php if (!empty($morePosts)): ?>
      <section>
        <?php if ($currentPage === 1 && $searchQuery === ''): ?>
        <h2 class="text-xl font-bold text-blue-900 mb-6 font-headline">More Articles</h2>
        <?php endif; ?>

        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
          <?php foreach ($morePosts as $p): ?>
          <?php
            $pid    = (int)($p['id_post']       ?? 0);
            $likes  = (int)($p['likes_count']   ?? 0);
            $cmCnt  = (int)($p['comments_count']?? 0);
            $views  = (int)($p['views_count']   ?? 0);
          ?>
          <article class="bg-surface-container-lowest rounded-2xl overflow-hidden shadow-[0_8px_24px_rgba(0,77,153,0.04)] border border-outline-variant/10 hover:shadow-[0_12px_32px_rgba(0,77,153,0.09)] hover:-translate-y-0.5 transition-all">
            <?php if (!empty($p['image_url'])): ?>
            <a href="index.php?controller=front&action=show&id=<?= $pid ?>">
              <img class="w-full h-40 object-cover" src="<?= e((string)$p['image_url']) ?>" alt="<?= e((string)($p['title'] ?? '')) ?>"/>
            </a>
            <?php else: ?>
            <div class="w-full h-40 bg-gradient-to-br from-surface-container-low to-surface-container flex items-center justify-center">
              <span class="material-symbols-outlined text-4xl text-outline/40">article</span>
            </div>
            <?php endif; ?>

            <div class="p-5">
              <div class="flex items-center justify-between mb-2">
                <span class="text-[10px] font-bold text-tertiary px-2 py-0.5 bg-tertiary-fixed/30 rounded-full">
                  <?= e((string)($p['category_name'] ?? '')) ?>
                </span>
                <span class="text-[10px] text-slate-400"><?= e(time_ago((string)($p['published_at'] ?? $p['created_at'] ?? ''))) ?></span>
              </div>
              <h3 class="text-base font-bold text-blue-900 mb-2 leading-snug font-headline">
                <a href="index.php?controller=front&action=show&id=<?= $pid ?>" class="hover:text-primary transition-colors">
                  <?= e((string)($p['title'] ?? '')) ?>
                </a>
              </h3>
              <p class="text-xs text-slate-500 mb-4 leading-relaxed line-clamp-2">
                <?= e((string)($p['excerpt'] ?? '')) ?>
              </p>
              <div class="flex items-center justify-between">
                <div class="flex items-center gap-3 text-xs text-slate-400">
                  <span class="flex items-center gap-1"><span class="material-symbols-outlined text-[14px]" style="font-variation-settings:'FILL' 1">favorite</span><?= e(format_count($likes)) ?></span>
                  <span class="flex items-center gap-1"><span class="material-symbols-outlined text-[14px]">forum</span><?= e(format_count($cmCnt)) ?></span>
                  <span class="flex items-center gap-1"><span class="material-symbols-outlined text-[14px]">visibility</span><?= e(format_count($views)) ?></span>
                </div>
                <?php if ($pid > 0): ?>
                <a class="flex items-center gap-1 text-xs text-primary font-bold hover:gap-2 transition-all"
                   href="index.php?controller=front&action=show&id=<?= $pid ?>">
                  Read <span class="material-symbols-outlined text-[14px]">arrow_forward</span>
                </a>
                <?php endif; ?>
              </div>
            </div>
          </article>
          <?php endforeach; ?>
        </div>
      </section>
      <?php elseif ($searchQuery !== ''): ?>
      <div class="text-center py-20">
        <span class="material-symbols-outlined text-6xl text-outline/40 mb-4 block">search_off</span>
        <h2 class="text-xl font-bold text-on-surface mb-2">No results found</h2>
        <p class="text-sm text-on-surface-variant mb-6">Try different keywords or browse by category.</p>
        <a href="index.php?controller=front&action=index" class="inline-flex items-center gap-2 bg-primary text-on-primary text-sm font-semibold px-5 py-2.5 rounded-xl hover:opacity-90 transition-opacity">
          <span class="material-symbols-outlined text-[18px]">home</span> Back to Home
        </a>
      </div>
      <?php elseif (!$featured): ?>
      <div class="text-center py-20">
        <span class="material-symbols-outlined text-6xl text-outline/40 mb-4 block">article</span>
        <h2 class="text-xl font-bold text-on-surface mb-2">No articles yet</h2>
        <p class="text-sm text-on-surface-variant">The editorial team is working on new content. Check back soon!</p>
      </div>
      <?php endif; ?>

      <!-- Pagination -->
      <?php if ($totalPages > 1): ?>
      <div class="mt-10">
        <?= pagination_links($currentPage, $totalPages, $paginationBase) ?>
      </div>
      <?php endif; ?>

      <!-- ── COMMUNITY DISCUSSIONS ── -->
      <?php if (!empty($recentComments)): ?>
      <section class="mt-14">
        <div class="flex items-center justify-between mb-5">
          <h2 class="text-lg font-bold text-blue-900 font-headline flex items-center gap-2">
            <span class="material-symbols-outlined text-tertiary text-[22px]">forum</span>
            Recent Discussions
          </h2>
          <?php if ($user === null): ?>
          <a href="login.php?next=<?= urlencode('index.php?controller=front&action=index') ?>"
             class="text-xs font-semibold text-primary hover:underline flex items-center gap-1">
            <span class="material-symbols-outlined text-[14px]">login</span> Login to join
          </a>
          <?php endif; ?>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4" data-comments-list>
          <?php foreach ($recentComments as $cm): ?>
          <?php $isMine = $user && (int)$cm['user_id'] === (int)$user['id_PK']; ?>
          <div class="bg-white rounded-2xl p-4 border <?= $isMine ? 'border-teal-200' : 'border-slate-100' ?> shadow-sm hover:shadow-md transition-shadow"
               data-comment-row="<?= (int)$cm['id_comment'] ?>">
            <div class="flex items-start gap-3 mb-3">
              <?= avatar_html($isMine ? $displayName : (string)($cm['user_name'] ?? 'Anonymous'), '8', 'xs') ?>
              <div class="flex-1 min-w-0">
                <p class="text-xs font-bold <?= $isMine ? 'text-primary' : 'text-on-surface' ?> truncate">
                  <?= $isMine ? 'You' : e((string)($cm['user_name'] ?? 'Anonymous')) ?>
                </p>
                <?php if (!empty($cm['post_title'])): ?>
                <a class="text-[10px] text-slate-400 hover:text-primary truncate block transition-colors"
                   href="index.php?controller=front&action=show&id=<?= (int)$cm['post_id'] ?>#comments">
                  <span class="material-symbols-outlined text-[11px] align-middle">article</span>
                  <?= e(truncate((string)$cm['post_title'], 38)) ?>
                </a>
                <?php endif; ?>
              </div>
              <span class="text-[10px] text-slate-400 shrink-0"><?= e(time_ago((string)$cm['created_at'])) ?></span>
            </div>
            <p class="text-xs text-slate-600 leading-relaxed line-clamp-2 mb-3"
               data-comment-body="<?= (int)$cm['id_comment'] ?>">
              <?= e((string)$cm['content']) ?>
            </p>
            <?php if ($isMine): ?>
            <div class="flex gap-2 border-t border-slate-100 pt-2">
              <button class="flex items-center gap-1 text-[10px] text-slate-400 hover:text-primary transition-colors"
                      data-comment-edit data-comment-id="<?= (int)$cm['id_comment'] ?>" data-csrf="<?= e($csrfToken) ?>">
                <span class="material-symbols-outlined text-[13px]">edit</span> Edit
              </button>
              <button class="flex items-center gap-1 text-[10px] text-slate-400 hover:text-error transition-colors"
                      data-comment-delete data-comment-id="<?= (int)$cm['id_comment'] ?>" data-csrf="<?= e($csrfToken) ?>">
                <span class="material-symbols-outlined text-[13px]">delete</span> Delete
              </button>
              <a class="ml-auto flex items-center gap-1 text-[10px] text-primary font-semibold hover:underline"
                 href="index.php?controller=front&action=show&id=<?= (int)$cm['post_id'] ?>#comments">
                View <span class="material-symbols-outlined text-[12px]">arrow_forward</span>
              </a>
            </div>
            <?php else: ?>
            <a class="flex items-center justify-end gap-1 text-[10px] text-primary font-semibold hover:underline border-t border-slate-100 pt-2"
               href="index.php?controller=front&action=show&id=<?= (int)$cm['post_id'] ?>#comments">
              View discussion <span class="material-symbols-outlined text-[12px]">arrow_forward</span>
            </a>
            <?php endif; ?>
          </div>
          <?php endforeach; ?>
        </div>

        <?php if ($user !== null && $featuredId > 0): ?>
        <div class="mt-6 flex items-center gap-3">
          <?= avatar_html($displayName, '9', 'sm') ?>
          <div class="relative flex-1">
            <input class="w-full text-sm bg-white rounded-xl border border-slate-200 focus:ring-2 focus:ring-tertiary/30 focus:border-tertiary py-3 pl-4 pr-12 outline-none transition"
                   placeholder="Share a thought on today's featured article…"
                   type="text" data-comment-input maxlength="2000"/>
            <button class="absolute right-3 top-1/2 -translate-y-1/2 text-primary hover:text-blue-700 transition-colors"
                    data-comment-send data-post-id="<?= (int)$featuredId ?>" data-csrf="<?= e($csrfToken) ?>" title="Post">
              <span class="material-symbols-outlined text-xl">send</span>
            </button>
          </div>
        </div>
        <?php endif; ?>
      </section>
      <?php endif; ?>

    </div>
  </main>
</div>

<!-- Toast notification -->
<div id="mf-toast" aria-live="polite">
  <div class="bg-slate-900 text-white text-sm font-semibold px-5 py-3 rounded-2xl shadow-xl flex items-center gap-3">
    <span class="material-symbols-outlined text-[18px]" id="mf-toast-icon">check_circle</span>
    <span id="mf-toast-msg">Done!</span>
  </div>
</div>

<script src="assets/js/frontOffice.js"></script>
</body>
</html>
