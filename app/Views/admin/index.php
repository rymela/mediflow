<?php

/** @var array<string, mixed>|null $user */
/** @var array<int, array<string, mixed>> $posts */
/** @var int $postsTotal */
/** @var int $currentPage */
/** @var int $totalPages */
/** @var string $searchQuery */
/** @var array<int, array<string, mixed>> $queue */
/** @var int $pendingModCount */
/** @var array{total_published:int,total_views:int,total_likes:int,weekly_posts:int} $stats */
/** @var string $csrfToken */

$adminName = $user ? trim((string)($user['prenom'] . ' ' . $user['nom'])) : 'Admin';
$adminRole = $user ? (string)($user['role_libelle'] ?? '')                : '';

// Build pagination base URL preserving search
$paginationBase = 'index.php?controller=admin&action=index';
if ($searchQuery !== '') $paginationBase .= '&q=' . urlencode($searchQuery);
?>
<!DOCTYPE html>
<html lang="en" class="light">
<head>
<?php include __DIR__ . '/../shared/head.php'; ?>
<style>
  .stat-card { transition: transform .15s ease, box-shadow .15s ease; }
  .stat-card:hover { transform: translateY(-2px); box-shadow: 0 12px 32px rgba(0,77,153,.1); }
  .nav-item { transition: background .2s, color .2s; }
  .post-row { transition: background .15s; }
  .status-badge { font-size: 10px; font-weight: 700; padding: 2px 8px; border-radius: 9999px; text-transform: uppercase; letter-spacing: .05em; }
</style>
</head>
<body class="bg-surface text-on-surface">

<!-- ═══════════ SIDEBAR ═══════════ -->
<aside class="h-screen w-64 fixed left-0 top-0 bg-white flex flex-col border-r border-slate-100 z-40 shadow-sm">
  <!-- Logo -->
  <div class="px-6 py-6 border-b border-slate-100">
    <div class="text-lg font-extrabold text-blue-900 font-headline">MediFlow <span class="text-tertiary">Mag</span></div>
    <div class="text-xs text-slate-400 mt-0.5">Admin Portal</div>
  </div>

  <!-- Nav -->
  <nav class="flex-1 px-3 py-4 space-y-1 overflow-y-auto">
    <a href="index.php?controller=admin&action=index"
       class="nav-item flex items-center gap-3 text-blue-700 font-bold border-r-4 border-teal-500 bg-blue-50 px-4 py-2.5 rounded-l-xl text-sm">
      <span class="material-symbols-outlined text-[20px]">dashboard</span>
      <span>Dashboard</span>
    </a>
    <a href="index.php?controller=admin&action=moderation"
       class="nav-item flex items-center gap-3 text-slate-500 px-4 py-2.5 hover:bg-slate-50 rounded-l-xl text-sm">
      <span class="material-symbols-outlined text-[20px]">gavel</span>
      <span class="flex-1">Moderation</span>
      <?php if ($pendingModCount > 0): ?>
      <span class="bg-error text-on-error text-[10px] font-bold px-2 py-0.5 rounded-full"><?= min(99, $pendingModCount) ?></span>
      <?php endif; ?>
    </a>
    <div class="h-px bg-slate-100 my-2"></div>
    <a href="index.php?controller=front&action=index" target="_blank"
       class="nav-item flex items-center gap-3 text-slate-500 px-4 py-2.5 hover:bg-slate-50 rounded-l-xl text-sm">
      <span class="material-symbols-outlined text-[20px]">open_in_new</span>
      <span>View Portal</span>
    </a>
    <a href="logout.php?next=index.php"
       class="nav-item flex items-center gap-3 text-slate-500 px-4 py-2.5 hover:bg-red-50 hover:text-error rounded-l-xl text-sm">
      <span class="material-symbols-outlined text-[20px]">logout</span>
      <span>Logout</span>
    </a>
  </nav>

  <!-- Create Post CTA -->
  <div class="p-4 border-t border-slate-100">
    <a href="index.php?controller=admin&action=create"
       class="w-full bg-gradient-to-r from-primary to-primary-container text-on-primary py-3 px-4 rounded-xl font-semibold flex items-center justify-center gap-2 shadow-sm hover:opacity-90 transition-opacity text-sm">
      <span class="material-symbols-outlined text-[18px]">add</span>
      Create Post
    </a>
  </div>
</aside>

<!-- ═══════════ MAIN ═══════════ -->
<main class="ml-64 min-h-screen bg-surface">

  <!-- Header bar -->
  <header class="sticky top-0 z-30 bg-white/90 backdrop-blur border-b border-slate-100 px-8 py-4 flex items-center justify-between gap-4">
    <div>
      <h1 class="text-2xl font-extrabold tracking-tight text-on-surface font-headline">Magazine Management</h1>
      <p class="text-xs text-on-surface-variant mt-0.5">Control centre for articles and community interactions</p>
    </div>
    <div class="flex items-center gap-3">
      <!-- Search form -->
      <form method="get" action="index.php" class="flex items-center gap-2">
        <input type="hidden" name="controller" value="admin"/>
        <input type="hidden" name="action"     value="index"/>
        <div class="relative">
          <span class="material-symbols-outlined absolute left-2.5 top-1/2 -translate-y-1/2 text-slate-400 text-[18px] pointer-events-none">search</span>
          <input id="admin-search" name="q" type="search" value="<?= e($searchQuery) ?>"
                 placeholder="Search posts…"
                 class="pl-9 pr-3 py-1.5 text-sm rounded-lg border border-slate-200 bg-white focus:ring-2 focus:ring-primary/30 focus:border-primary outline-none w-44 transition-all"/>
        </div>
        <?php if ($searchQuery !== ''): ?>
        <a href="index.php?controller=admin&action=index" class="text-xs text-slate-400 hover:text-error transition-colors" title="Clear search">
          <span class="material-symbols-outlined text-[18px]">close</span>
        </a>
        <?php endif; ?>
      </form>
      <!-- User avatar -->
      <div class="flex items-center gap-2 pl-3 border-l border-slate-200">
        <?= avatar_html($adminName, '9', 'xs') ?>
        <div class="hidden md:block text-right">
          <p class="text-xs font-bold text-on-surface truncate max-w-[100px]"><?= e($adminName) ?></p>
          <p class="text-[10px] text-on-surface-variant"><?= e($adminRole) ?></p>
        </div>
      </div>
    </div>
  </header>

  <div class="p-8 space-y-8 page-fade">

    <!-- ── STATS ROW ── -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-5">
      <div class="stat-card bg-white rounded-2xl p-5 border border-slate-100 shadow-sm">
        <div class="flex items-start justify-between mb-3">
          <p class="text-xs font-bold text-on-surface-variant uppercase tracking-wide">Published</p>
          <span class="material-symbols-outlined text-primary text-[22px]">article</span>
        </div>
        <p class="text-3xl font-extrabold text-primary"><?= e(format_count($stats['total_published'])) ?></p>
        <p class="text-[11px] text-tertiary mt-1.5 flex items-center gap-1">
          <span class="material-symbols-outlined text-[13px]">trending_up</span>
          +<?= (int)$stats['weekly_posts'] ?> this week
        </p>
      </div>
      <div class="stat-card bg-white rounded-2xl p-5 border border-slate-100 shadow-sm">
        <div class="flex items-start justify-between mb-3">
          <p class="text-xs font-bold text-on-surface-variant uppercase tracking-wide">Total Views</p>
          <span class="material-symbols-outlined text-tertiary text-[22px]">visibility</span>
        </div>
        <p class="text-3xl font-extrabold text-primary"><?= e(format_count($stats['total_views'])) ?></p>
        <p class="text-[11px] text-slate-400 mt-1.5">across all articles</p>
      </div>
      <div class="stat-card bg-white rounded-2xl p-5 border border-slate-100 shadow-sm">
        <div class="flex items-start justify-between mb-3">
          <p class="text-xs font-bold text-on-surface-variant uppercase tracking-wide">Total Likes</p>
          <span class="material-symbols-outlined text-red-400 text-[22px]">favorite</span>
        </div>
        <p class="text-3xl font-extrabold text-primary"><?= e(format_count($stats['total_likes'])) ?></p>
        <p class="text-[11px] text-slate-400 mt-1.5">community engagement</p>
      </div>
      <div class="stat-card bg-white rounded-2xl p-5 border border-slate-100 shadow-sm">
        <div class="flex items-start justify-between mb-3">
          <p class="text-xs font-bold text-on-surface-variant uppercase tracking-wide">Moderation</p>
          <span class="material-symbols-outlined <?= $pendingModCount > 0 ? 'text-error' : 'text-tertiary' ?> text-[22px]">gavel</span>
        </div>
        <p class="text-3xl font-extrabold <?= $pendingModCount > 0 ? 'text-error' : 'text-primary' ?>"><?= (int)$pendingModCount ?></p>
        <p class="text-[11px] text-slate-400 mt-1.5">
          <?php if ($pendingModCount > 0): ?>
          <a href="index.php?controller=admin&action=moderation" class="text-error font-semibold hover:underline">Review now →</a>
          <?php else: ?>
          queue is clear ✓
          <?php endif; ?>
        </p>
      </div>
    </div>

    <!-- ── POSTS + MODERATION GRID ── -->
    <div class="grid grid-cols-12 gap-6">

      <!-- Post list (8 cols) -->
      <div class="col-span-12 xl:col-span-8 space-y-4">
        <div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
          <!-- List header -->
          <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100">
            <h2 class="text-base font-bold flex items-center gap-2 font-headline">
              <span class="w-1 h-5 bg-tertiary rounded-full inline-block"></span>
              Active Publications
            </h2>
            <div class="flex items-center gap-2">
              <span class="text-xs font-bold text-primary bg-primary-fixed px-3 py-1 rounded-full"><?= (int)$postsTotal ?> TOTAL</span>
              <?php if ($searchQuery !== ''): ?>
              <span class="text-xs text-on-surface-variant">filtered</span>
              <?php endif; ?>
            </div>
          </div>

          <!-- Post rows -->
          <div class="divide-y divide-slate-50">
            <?php foreach ($posts as $p): ?>
            <?php
              $statusColour = match((string)($p['status'] ?? 'draft')) {
                  'published' => 'bg-teal-100 text-teal-700',
                  'draft'     => 'bg-amber-100 text-amber-700',
                  'archived'  => 'bg-slate-100 text-slate-500',
                  default     => 'bg-slate-100 text-slate-500',
              };
            ?>
            <div class="post-row flex items-center gap-4 px-6 py-4 hover:bg-slate-50/80">
              <!-- Thumbnail -->
              <?php if (!empty($p['image_url'])): ?>
              <img class="w-14 h-14 rounded-xl object-cover shrink-0" src="<?= e((string)$p['image_url']) ?>" alt="<?= e((string)$p['title']) ?>"/>
              <?php else: ?>
              <div class="w-14 h-14 rounded-xl bg-surface-container-high flex items-center justify-center shrink-0">
                <span class="material-symbols-outlined text-outline/40 text-2xl">image</span>
              </div>
              <?php endif; ?>

              <!-- Info -->
              <div class="flex-1 min-w-0">
                <h3 class="font-bold text-on-surface text-sm truncate"><?= e((string)$p['title']) ?></h3>
                <p class="text-xs text-on-surface-variant mt-0.5 flex items-center gap-2 flex-wrap">
                  <span class="font-semibold text-tertiary"><?= e((string)($p['category_name'] ?? '')) ?></span>
                  <span class="text-slate-300">·</span>
                  <?= e(time_ago((string)($p['published_at'] ?? $p['created_at'] ?? ''))) ?>
                  <span class="text-slate-300">·</span>
                  By <?= e((string)($p['author_name'] ?? '')) ?>
                </p>
                <div class="flex items-center gap-3 mt-1.5 text-[11px] text-slate-400">
                  <span class="flex items-center gap-1"><span class="material-symbols-outlined text-[13px]">visibility</span><?= e(format_count((int)($p['views_count'] ?? 0))) ?></span>
                </div>
              </div>

              <!-- Status badge + actions -->
              <div class="flex items-center gap-2 shrink-0">
                <span class="status-badge <?= $statusColour ?>"><?= e((string)($p['status'] ?? 'draft')) ?></span>
                <a class="p-2 text-on-surface-variant hover:text-primary hover:bg-surface-container-high rounded-lg transition-all"
                   href="index.php?controller=admin&action=edit&id=<?= (int)$p['id_post'] ?>" title="Edit">
                  <span class="material-symbols-outlined text-[18px]">edit</span>
                </a>
                <form method="post" action="index.php?controller=admin&action=delete&id=<?= (int)$p['id_post'] ?>"
                      data-confirm="Delete '<?= e((string)$p['title']) ?>'?" class="inline">
                  <?= csrf_field() ?>
                  <button class="p-2 text-on-surface-variant hover:text-error hover:bg-error-container/20 rounded-lg transition-all" title="Delete" type="submit">
                    <span class="material-symbols-outlined text-[18px]">delete</span>
                  </button>
                </form>
              </div>
            </div>
            <?php endforeach; ?>

            <?php if (empty($posts)): ?>
            <div class="px-6 py-12 text-center">
              <span class="material-symbols-outlined text-5xl text-outline/40 block mb-3">article</span>
              <p class="text-sm text-on-surface-variant">
                <?= $searchQuery !== '' ? 'No posts match your search.' : 'No posts yet. Create your first one!' ?>
              </p>
              <?php if ($searchQuery !== ''): ?>
              <a href="index.php?controller=admin&action=index" class="mt-3 inline-block text-xs text-primary font-semibold hover:underline">Clear search</a>
              <?php endif; ?>
            </div>
            <?php endif; ?>
          </div>

          <!-- Pagination footer -->
          <?php if ($totalPages > 1): ?>
          <div class="px-6 py-4 border-t border-slate-100 bg-surface-container-low/40">
            <?= pagination_links($currentPage, $totalPages, $paginationBase) ?>
          </div>
          <?php endif; ?>
        </div>
      </div>

      <!-- Moderation mini-queue (4 cols) -->
      <div class="col-span-12 xl:col-span-4">
        <div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
          <div class="flex items-center justify-between px-5 py-4 border-b border-slate-100">
            <h2 class="text-base font-bold flex items-center gap-2 font-headline">
              <span class="material-symbols-outlined text-tertiary text-[20px]">gavel</span>
              Moderation
            </h2>
            <a href="index.php?controller=admin&action=moderation"
               class="text-xs text-primary font-semibold hover:underline">View all</a>
          </div>

          <div class="divide-y divide-slate-50">
            <?php foreach ($queue as $cm): ?>
            <?php
              $flagged  = ((string)$cm['status']) === 'flagged';
              $cmName   = trim((string)($cm['user_name'] ?? 'Anonymous'));
            ?>
            <div class="p-4 <?= $flagged ? 'bg-red-50/50' : '' ?>">
              <div class="flex items-start gap-3 mb-3">
                <?= avatar_html($cmName, '7', 'xs') ?>
                <div class="flex-1 min-w-0">
                  <div class="flex items-center justify-between gap-1 flex-wrap">
                    <span class="text-xs font-bold <?= $flagged ? 'text-error' : 'text-on-surface' ?> truncate"><?= e($cmName) ?></span>
                    <?php if ($flagged): ?>
                    <span class="status-badge bg-red-100 text-error">flagged</span>
                    <?php endif; ?>
                  </div>
                  <?php if (!empty($cm['post_title'])): ?>
                  <p class="text-[10px] text-slate-400 truncate">on: <?= e(truncate((string)$cm['post_title'], 35)) ?></p>
                  <?php endif; ?>
                </div>
                <span class="text-[10px] text-on-surface-variant shrink-0 mt-0.5"><?= e(time_ago((string)$cm['created_at'])) ?></span>
              </div>
              <p class="text-xs text-on-surface-variant leading-relaxed mb-3 <?= $flagged ? 'italic' : '' ?> line-clamp-2">
                <?= e((string)$cm['content']) ?>
              </p>
              <div class="flex gap-2">
                <form class="flex-1" method="post" action="index.php?controller=admin&action=commentWarn&id=<?= (int)$cm['id_comment'] ?>">
                  <?= csrf_field() ?>
                  <button class="w-full py-1.5 text-[11px] font-bold rounded-lg bg-surface-container text-on-surface hover:bg-surface-container-high transition" type="submit">
                    <?= $flagged ? 'Un-flag' : 'Flag' ?>
                  </button>
                </form>
                <form class="flex-1" method="post" action="index.php?controller=admin&action=commentDelete&id=<?= (int)$cm['id_comment'] ?>"
                      data-confirm="Delete this comment?">
                  <?= csrf_field() ?>
                  <button class="w-full py-1.5 text-[11px] font-bold rounded-lg <?= $flagged ? 'bg-error text-on-error hover:opacity-90' : 'bg-error-container/30 text-error hover:bg-error-container/50' ?> transition" type="submit">
                    Delete
                  </button>
                </form>
              </div>
            </div>
            <?php endforeach; ?>

            <?php if (empty($queue)): ?>
            <div class="px-5 py-10 text-center">
              <span class="material-symbols-outlined text-4xl text-tertiary/40 block mb-2">check_circle</span>
              <p class="text-sm text-on-surface-variant">Queue is clear!</p>
            </div>
            <?php endif; ?>
          </div>
        </div>
      </div>

    </div>
  </div>
</main>

<!-- Toast -->
<div id="mf-toast" aria-live="polite">
  <div class="bg-slate-900 text-white text-sm font-semibold px-5 py-3 rounded-2xl shadow-xl flex items-center gap-3">
    <span class="material-symbols-outlined text-[18px]" id="mf-toast-icon">check_circle</span>
    <span id="mf-toast-msg">Done!</span>
  </div>
</div>

<script src="assets/js/adminOffice.js"></script>
</body>
</html>
