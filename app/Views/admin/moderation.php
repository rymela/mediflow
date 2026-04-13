<?php

/** @var array<string, mixed>|null $user */
/** @var array<int, array<string, mixed>> $comments */
/** @var int $pendingModCount */
/** @var int $currentPage */
/** @var int $totalPages */
/** @var int $totalCount */
/** @var string $searchQuery */
/** @var int|null $filterPostId */
/** @var array<int, array<string, mixed>> $allPosts */
/** @var bool $hasFilter */
/** @var string $paginationBase */
/** @var string $csrfToken */

$adminName = $user ? trim((string)($user['prenom'] . ' ' . $user['nom'])) : 'Admin';
$adminRole = $user ? (string)($user['role_libelle'] ?? '') : '';

// Build filter URL preserving current search/post params
$filterBase = 'index.php?controller=admin&action=moderation';
?>
<!DOCTYPE html>
<html lang="en" class="light">
<head>
<?php include __DIR__ . '/../shared/head.php'; ?>
<style>
  .cm-row { transition: background .15s; }
  .cm-reported { background: #fff5f5; border-left: 4px solid #ba1a1a; }
  .cm-active   { background: #ffffff; border-left: 4px solid #84f5e8; }
  .status-pill { font-size: 10px; font-weight: 700; padding: 2px 9px; border-radius: 9999px; text-transform: uppercase; letter-spacing: .04em; }
</style>
</head>
<body class="bg-surface text-on-surface">

<!-- SIDEBAR -->
<aside class="h-screen w-64 fixed left-0 top-0 bg-white flex flex-col border-r border-slate-100 z-40 shadow-sm">
  <div class="px-6 py-6 border-b border-slate-100">
    <div class="text-lg font-extrabold text-blue-900 font-headline">MediFlow <span class="text-tertiary">Mag</span></div>
    <div class="text-xs text-slate-400 mt-0.5">Admin Portal</div>
  </div>
  <nav class="flex-1 px-3 py-4 space-y-1">
    <a href="index.php?controller=admin&action=index"
       class="flex items-center gap-3 text-slate-500 px-4 py-2.5 hover:bg-slate-50 rounded-l-xl text-sm transition-colors">
      <span class="material-symbols-outlined text-[20px]">dashboard</span>
      <span>Dashboard</span>
    </a>
    <a href="index.php?controller=admin&action=moderation"
       class="flex items-center gap-3 text-blue-700 font-bold border-r-4 border-teal-500 bg-blue-50 px-4 py-2.5 rounded-l-xl text-sm">
      <span class="material-symbols-outlined text-[20px]">report</span>
      <span class="flex-1">Moderation</span>
      <?php if ($pendingModCount > 0): ?>
      <span class="bg-error text-on-error text-[10px] font-bold px-2 py-0.5 rounded-full"><?= min(99, $pendingModCount) ?></span>
      <?php endif; ?>
    </a>
    <div class="h-px bg-slate-100 my-2"></div>
    <a href="index.php?controller=front&action=index" target="_blank"
       class="flex items-center gap-3 text-slate-500 px-4 py-2.5 hover:bg-slate-50 rounded-l-xl text-sm transition-colors">
      <span class="material-symbols-outlined text-[20px]">open_in_new</span>
      <span>View Portal</span>
    </a>
    <a href="logout.php?next=index.php"
       class="flex items-center gap-3 text-slate-500 px-4 py-2.5 hover:bg-red-50 hover:text-error rounded-l-xl text-sm transition-colors">
      <span class="material-symbols-outlined text-[20px]">logout</span>
      <span>Logout</span>
    </a>
  </nav>
  <div class="p-4 border-t border-slate-100">
    <a href="index.php?controller=admin&action=create"
       class="w-full bg-gradient-to-r from-primary to-primary-container text-on-primary py-3 px-4 rounded-xl font-semibold flex items-center justify-center gap-2 hover:opacity-90 transition text-sm">
      <span class="material-symbols-outlined text-[18px]">add</span> Create Post
    </a>
  </div>
</aside>

<!-- MAIN -->
<main class="ml-64 min-h-screen bg-surface">

  <!-- Header -->
  <header class="sticky top-0 z-30 bg-white/90 backdrop-blur border-b border-slate-100 px-8 py-4 flex items-center justify-between gap-4">
    <div>
      <h1 class="text-2xl font-extrabold tracking-tight text-on-surface font-headline flex items-center gap-2">
        <span class="material-symbols-outlined text-error text-[26px]">report</span>
        Comment Moderation
      </h1>
      <p class="text-xs text-on-surface-variant mt-0.5">
        <?php if ($hasFilter): ?>
          Showing <strong><?= (int)$totalCount ?></strong> comment<?= $totalCount !== 1 ? 's' : '' ?> matching your filter
        <?php else: ?>
          <strong class="<?= $pendingModCount > 0 ? 'text-error' : 'text-tertiary' ?>"><?= (int)$pendingModCount ?></strong>
          reported comment<?= $pendingModCount !== 1 ? 's' : '' ?> awaiting review
        <?php endif; ?>
        <?php if ($totalPages > 1): ?> · Page <?= $currentPage ?> of <?= $totalPages ?><?php endif; ?>
      </p>
    </div>
    <div class="flex items-center gap-3">
      <?= avatar_html($adminName, '9', 'xs') ?>
      <div class="hidden md:block text-right">
        <p class="text-xs font-bold text-on-surface truncate max-w-[100px]"><?= e($adminName) ?></p>
        <p class="text-[10px] text-on-surface-variant"><?= e($adminRole) ?></p>
      </div>
    </div>
  </header>

  <div class="p-8 page-fade">
    <div class="max-w-5xl mx-auto space-y-5">

      <!-- ── FILTER BAR ── -->
      <form method="get" action="index.php" class="bg-white rounded-2xl border border-slate-100 shadow-sm px-5 py-4">
        <input type="hidden" name="controller" value="admin"/>
        <input type="hidden" name="action"     value="moderation"/>
        <div class="flex flex-wrap gap-3 items-end">

          <!-- Keyword search -->
          <div class="flex-1 min-w-[180px] space-y-1">
            <label class="text-[10px] font-bold uppercase tracking-wide text-on-surface-variant block">Search keywords</label>
            <div class="relative">
              <span class="material-symbols-outlined absolute left-2.5 top-1/2 -translate-y-1/2 text-slate-400 text-[17px] pointer-events-none">search</span>
              <input type="search" name="q" value="<?= e($searchQuery) ?>"
                     placeholder="Search comment text…"
                     class="w-full pl-8 pr-3 py-2 text-sm rounded-lg border border-slate-200 bg-white focus:ring-2 focus:ring-primary/30 focus:border-primary outline-none transition"/>
            </div>
          </div>

          <!-- Post filter -->
          <div class="flex-1 min-w-[200px] space-y-1">
            <label class="text-[10px] font-bold uppercase tracking-wide text-on-surface-variant block">Filter by post</label>
            <select name="post_id" class="w-full py-2 px-3 text-sm rounded-lg border border-slate-200 bg-white focus:ring-2 focus:ring-primary/30 focus:border-primary outline-none transition">
              <option value="">— All posts —</option>
              <?php foreach ($allPosts as $p): ?>
              <option value="<?= (int)$p['id_post'] ?>" <?= $filterPostId === (int)$p['id_post'] ? 'selected' : '' ?>>
                <?= e(truncate((string)($p['title'] ?? ''), 55)) ?>
              </option>
              <?php endforeach; ?>
            </select>
          </div>

          <!-- Buttons -->
          <div class="flex gap-2">
            <button type="submit"
                    class="inline-flex items-center gap-1.5 bg-primary text-on-primary text-sm font-semibold px-4 py-2 rounded-lg hover:opacity-90 transition">
              <span class="material-symbols-outlined text-[16px]">filter_alt</span> Apply
            </button>
            <?php if ($hasFilter): ?>
            <a href="<?= e($filterBase) ?>"
               class="inline-flex items-center gap-1.5 bg-surface-container text-on-surface-variant text-sm font-semibold px-4 py-2 rounded-lg hover:bg-surface-container-high transition">
              <span class="material-symbols-outlined text-[16px]">close</span> Clear
            </a>
            <?php endif; ?>
          </div>
        </div>
      </form>

      <!-- ── MODE LEGEND ── -->
      <div class="flex items-center gap-6 text-xs text-on-surface-variant">
        <?php if (!$hasFilter): ?>
        <span class="flex items-center gap-2">
          <span class="w-2.5 h-2.5 rounded-full bg-error/60"></span>
          Reported by users — review and act
        </span>
        <span class="text-slate-300">·</span>
        <span class="text-slate-400 italic">Use the filter above to browse all comments of a specific post</span>
        <?php else: ?>
        <span class="flex items-center gap-2">
          <span class="w-2.5 h-2.5 rounded-full bg-error/60"></span> Reported
        </span>
        <span class="flex items-center gap-2">
          <span class="w-2.5 h-2.5 rounded-full bg-tertiary-fixed border border-tertiary-fixed-dim"></span> Active
        </span>
        <?php endif; ?>
      </div>

      <!-- ── COMMENT LIST ── -->
      <?php if (empty($comments)): ?>
      <div class="bg-white rounded-2xl border border-slate-100 shadow-sm py-24 text-center">
        <span class="material-symbols-outlined text-6xl text-tertiary/40 block mb-4">check_circle</span>
        <h2 class="text-xl font-bold text-on-surface font-headline mb-2">
          <?= $hasFilter ? 'No comments match your filter' : 'No reported comments!' ?>
        </h2>
        <p class="text-sm text-on-surface-variant mb-6">
          <?= $hasFilter ? 'Try different keywords or select another post.' : 'The queue is clear — all comments are clean.' ?>
        </p>
        <?php if ($hasFilter): ?>
        <a href="<?= e($filterBase) ?>" class="inline-flex items-center gap-2 bg-surface-container text-on-surface-variant text-sm font-semibold px-5 py-2.5 rounded-xl hover:bg-surface-container-high transition">
          <span class="material-symbols-outlined text-[18px]">close</span> Clear filter
        </a>
        <?php else: ?>
        <a href="index.php?controller=admin&action=index" class="inline-flex items-center gap-2 bg-primary text-on-primary text-sm font-semibold px-5 py-2.5 rounded-xl hover:opacity-90 transition">
          <span class="material-symbols-outlined text-[18px]">arrow_back</span> Back to Dashboard
        </a>
        <?php endif; ?>
      </div>

      <?php else: ?>
      <div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden divide-y divide-slate-50">
        <?php foreach ($comments as $cm): ?>
        <?php
          $reported = ((string)$cm['status']) === 'flagged';
          $cmName   = trim((string)($cm['user_name'] ?? 'Anonymous'));
        ?>
        <div class="cm-row <?= $reported ? 'cm-reported' : 'cm-active' ?> px-6 py-5">
          <div class="flex gap-4">

            <!-- Avatar -->
            <div class="shrink-0">
              <?= avatar_html($cmName, '10', 'xs') ?>
            </div>

            <!-- Body -->
            <div class="flex-1 min-w-0">

              <!-- Top row: name + status + meta -->
              <div class="flex flex-wrap items-start justify-between gap-2 mb-2">
                <div class="flex items-center gap-2 flex-wrap">
                  <span class="text-sm font-bold <?= $reported ? 'text-error' : 'text-on-surface' ?>">
                    <?= e($cmName) ?>
                  </span>
                  <?php if ($reported): ?>
                  <span class="status-pill bg-red-100 text-error flex items-center gap-1">
                    <span class="material-symbols-outlined text-[12px]">flag</span> Reported
                  </span>
                  <?php else: ?>
                  <span class="status-pill bg-teal-100 text-teal-700">Active</span>
                  <?php endif; ?>
                </div>
                <div class="flex items-center gap-3 text-xs text-on-surface-variant shrink-0">
                  <span><?= e(time_ago((string)$cm['created_at'])) ?></span>
                  <?php if (!empty($cm['post_title'])): ?>
                  <a href="index.php?controller=front&action=show&id=<?= (int)$cm['post_id'] ?>#comments"
                     target="_blank"
                     class="flex items-center gap-1 text-primary hover:underline max-w-[200px] truncate">
                    <span class="material-symbols-outlined text-[13px]">open_in_new</span>
                    <?= e(truncate((string)$cm['post_title'], 32)) ?>
                  </a>
                  <!-- Quick filter by this post -->
                  <a href="<?= e($filterBase . '&post_id=' . (int)$cm['post_id']) ?>"
                     class="text-[10px] text-slate-400 hover:text-primary transition"
                     title="See all comments of this post">
                    <span class="material-symbols-outlined text-[13px]">filter_list</span>
                  </a>
                  <?php endif; ?>
                </div>
              </div>

              <!-- Comment text -->
              <div class="bg-surface-container-low rounded-xl px-4 py-3 mb-3">
                <?php
                  // Highlight search keyword in the comment text
                  $rawContent = (string)$cm['content'];
                  $display    = e($rawContent);
                  if ($searchQuery !== '') {
                    $safe = preg_quote(e($searchQuery), '/');
                    $display = preg_replace(
                        '/(' . $safe . ')/i',
                        '<mark class="bg-yellow-200 rounded px-0.5">$1</mark>',
                        $display
                    );
                  }
                ?>
                <p class="text-sm text-on-surface leading-relaxed <?= $reported ? 'italic' : '' ?>">
                  <?= $display ?>
                </p>
              </div>

              <!-- Action buttons -->
              <div class="flex gap-2 flex-wrap">
                <?php if ($reported): ?>
                <!-- Keep (dismiss report — restore to active) -->
                <form method="post" action="index.php?controller=admin&action=commentRestore&id=<?= (int)$cm['id_comment'] ?>">
                  <?= csrf_field() ?>
                  <button type="submit"
                          class="inline-flex items-center gap-1.5 text-xs font-bold px-4 py-2 rounded-lg bg-teal-100 text-teal-700 hover:bg-teal-200 transition">
                    <span class="material-symbols-outlined text-[15px]">check_circle</span>
                    Keep (Dismiss Report)
                  </button>
                </form>
                <?php endif; ?>

                <!-- Delete -->
                <form method="post"
                      action="index.php?controller=admin&action=commentDelete&id=<?= (int)$cm['id_comment'] ?>"
                      data-confirm="Permanently delete this comment?">
                  <?= csrf_field() ?>
                  <button type="submit"
                          class="inline-flex items-center gap-1.5 text-xs font-bold px-4 py-2 rounded-lg
                    <?= $reported ? 'bg-error text-on-error hover:opacity-90' : 'bg-error-container/30 text-error hover:bg-error-container/60' ?>
                    transition">
                    <span class="material-symbols-outlined text-[15px]">delete_forever</span>
                    Delete Comment
                  </button>
                </form>

                <!-- View in article -->
                <?php if (!empty($cm['post_id'])): ?>
                <a href="index.php?controller=front&action=show&id=<?= (int)$cm['post_id'] ?>#comments"
                   target="_blank"
                   class="inline-flex items-center gap-1.5 text-xs font-semibold px-4 py-2 rounded-lg text-on-surface-variant hover:bg-surface-container transition">
                  <span class="material-symbols-outlined text-[15px]">open_in_new</span>
                  View in article
                </a>
                <?php endif; ?>
              </div>
            </div>
          </div>
        </div>
        <?php endforeach; ?>
      </div>

      <!-- Pagination -->
      <?php if ($totalPages > 1): ?>
      <div class="bg-white rounded-2xl border border-slate-100 shadow-sm px-6 py-4">
        <?= pagination_links($currentPage, $totalPages, $paginationBase) ?>
      </div>
      <?php endif; ?>

      <?php endif; ?>
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
