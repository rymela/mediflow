<?php

/** @var array<string, mixed>|null $user */
/** @var string $mode */
/** @var array<string, mixed>|null $post */
/** @var array<int, array<string, mixed>> $categories */
/** @var string $csrfToken */

$isEdit      = $mode === 'edit';
$action      = $isEdit && $post
    ? 'index.php?controller=admin&action=update&id=' . (int)$post['id_post']
    : 'index.php?controller=admin&action=store';

$title       = (string)($post['title']        ?? '');
$excerpt     = (string)($post['excerpt']      ?? '');
$content     = (string)($post['content']      ?? '');
$imageUrl    = (string)($post['image_url']    ?? '');
$categoryId  = (int)($post['category_id']     ?? 0);
$status      = (string)($post['status']       ?? 'published');
$publishedAt = (string)($post['published_at'] ?? '');

$adminName = $user ? trim((string)($user['prenom'] . ' ' . $user['nom'])) : 'Admin';
$adminRole = $user ? (string)($user['role_libelle'] ?? '') : '';
?>
<!DOCTYPE html>
<html lang="en" class="light">
<head>
<?php include __DIR__ . '/../shared/head.php'; ?>
<style>
  .form-label { font-size: 11px; font-weight: 700; letter-spacing: .04em; text-transform: uppercase; color: #424752; }
  .form-input, .form-select, .form-textarea {
    width: 100%; border-radius: .6rem; border: 1.5px solid #e0e3e5;
    padding: .6rem .875rem; font-size: .875rem; outline: none;
    transition: border-color .15s, box-shadow .15s; background: #fff;
  }
  .form-input:focus, .form-select:focus, .form-textarea:focus {
    border-color: #004d99; box-shadow: 0 0 0 3px rgba(0,77,153,.12);
  }
  .form-textarea { resize: vertical; font-family: 'Inter', sans-serif; line-height: 1.6; }
</style>
</head>
<body class="bg-surface text-on-surface">

<!-- ═══════════ SIDEBAR ═══════════ -->
<aside class="h-screen w-64 fixed left-0 top-0 bg-white flex flex-col border-r border-slate-100 z-40 shadow-sm">
  <div class="px-6 py-6 border-b border-slate-100">
    <div class="text-lg font-extrabold text-blue-900 font-headline">MediFlow <span class="text-tertiary">Mag</span></div>
    <div class="text-xs text-slate-400 mt-0.5">Admin Portal</div>
  </div>

  <nav class="flex-1 px-3 py-4 space-y-1">
    <a href="index.php?controller=admin&action=index"
       class="flex items-center gap-3 text-blue-700 font-bold border-r-4 border-teal-500 bg-blue-50 px-4 py-2.5 rounded-l-xl text-sm">
      <span class="material-symbols-outlined text-[20px]">dashboard</span>
      <span>Dashboard</span>
    </a>
    <a href="index.php?controller=admin&action=moderation"
       class="flex items-center gap-3 text-slate-500 px-4 py-2.5 hover:bg-slate-50 rounded-l-xl text-sm transition-colors">
      <span class="material-symbols-outlined text-[20px]">gavel</span>
      <span>Moderation</span>
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
    <a href="index.php?controller=admin&action=index"
       class="w-full bg-surface-container text-on-surface-variant py-3 px-4 rounded-xl font-semibold flex items-center justify-center gap-2 hover:bg-surface-container-high transition text-sm">
      <span class="material-symbols-outlined text-[18px]">arrow_back</span>
      Back to Posts
    </a>
  </div>
</aside>

<!-- ═══════════ MAIN ═══════════ -->
<main class="ml-64 min-h-screen bg-surface">

  <!-- Header -->
  <header class="sticky top-0 z-30 bg-white/90 backdrop-blur border-b border-slate-100 px-8 py-4 flex items-center justify-between gap-4">
    <div>
      <h1 class="text-2xl font-extrabold tracking-tight text-on-surface font-headline">
        <?= $isEdit ? 'Edit Post' : 'Create New Post' ?>
      </h1>
      <p class="text-xs text-on-surface-variant mt-0.5">
        <?= $isEdit ? 'Update an existing publication' : 'Write and publish a new article' ?>
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
    <div class="max-w-4xl mx-auto">
      <form method="post" action="<?= e($action) ?>" id="post-form" novalidate>
        <?= csrf_field() ?>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

          <!-- ── LEFT: Main content ── -->
          <div class="lg:col-span-2 space-y-5">

            <!-- Title -->
            <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-6 space-y-2">
              <label class="form-label block" for="post-title">Title <span class="text-error">*</span></label>
              <input id="post-title" class="form-input" name="title" type="text" required
                     placeholder="Enter a compelling article title…"
                     value="<?= e($title) ?>"/>
            </div>

            <!-- Excerpt -->
            <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-6 space-y-2">
              <div class="flex items-center justify-between">
                <label class="form-label block" for="post-excerpt">Excerpt</label>
                <span id="excerpt-counter" class="text-[11px] text-slate-400 tabular-nums">0 / 300</span>
              </div>
              <textarea id="post-excerpt" class="form-textarea" name="excerpt" rows="3"
                        placeholder="A short summary shown in article cards…"
                        maxlength="300"><?= e($excerpt) ?></textarea>
              <p class="text-[11px] text-slate-400">Appears on article cards and in search results. Keep it under 160 chars for best SEO.</p>
            </div>

            <!-- Content -->
            <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-6 space-y-2">
              <div class="flex items-center justify-between">
                <label class="form-label block" for="post-content">Content <span class="text-error">*</span></label>
                <span id="content-counter" class="text-[11px] text-slate-400">0 words</span>
              </div>
              <textarea id="post-content" class="form-textarea" name="content" rows="16" required
                        placeholder="Write your full article here…"><?= e($content) ?></textarea>
              <p class="text-[11px] text-slate-400">Plain text. Line breaks are preserved in the reader view.</p>
            </div>

          </div>

          <!-- ── RIGHT: Meta options ── -->
          <div class="space-y-5">

            <!-- Image URL + Preview -->
            <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-5 space-y-3">
              <label class="form-label block">Cover Image URL</label>
              <input class="form-input" name="image_url" type="url"
                     placeholder="https://example.com/image.jpg"
                     value="<?= e($imageUrl) ?>"/>
              <!-- Live preview -->
              <div id="img-preview-container" class="<?= $imageUrl ? '' : 'hidden' ?>">
                <img id="img-preview" src="<?= e($imageUrl) ?>" alt="Image preview"
                     class="w-full h-36 object-cover rounded-xl border border-slate-100"/>
                <p class="text-[10px] text-slate-400 mt-1 text-center">Preview (paste URL and click away)</p>
              </div>
              <?php if (!$imageUrl): ?>
              <div class="w-full h-24 rounded-xl border-2 border-dashed border-slate-200 flex items-center justify-center" id="img-placeholder">
                <span class="material-symbols-outlined text-3xl text-slate-300">image</span>
              </div>
              <?php endif; ?>
            </div>

            <!-- Category -->
            <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-5 space-y-2">
              <label class="form-label block" for="post-category">Category <span class="text-error">*</span></label>
              <select id="post-category" class="form-select" name="category_id" required>
                <option value="">Select a category…</option>
                <?php foreach ($categories as $c): ?>
                <option value="<?= (int)$c['id_category'] ?>"
                  <?= ((int)$c['id_category'] === $categoryId) ? 'selected' : '' ?>>
                  <?= e((string)$c['name']) ?>
                </option>
                <?php endforeach; ?>
              </select>
            </div>

            <!-- Status -->
            <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-5 space-y-3">
              <label class="form-label block">Status</label>
              <div class="space-y-2">
                <?php foreach (['published' => ['Published', 'text-tertiary', 'check_circle'], 'draft' => ['Draft', 'text-amber-600', 'edit_note'], 'archived' => ['Archived', 'text-slate-400', 'archive']] as $val => [$label, $colour, $icon]): ?>
                <label class="flex items-center gap-3 p-3 rounded-xl border border-slate-100 cursor-pointer hover:bg-slate-50 transition has-[:checked]:border-primary has-[:checked]:bg-blue-50/50">
                  <input type="radio" name="status" value="<?= $val ?>" class="sr-only" <?= $status === $val ? 'checked' : '' ?>>
                  <span class="material-symbols-outlined <?= $colour ?> text-[20px]"><?= $icon ?></span>
                  <div>
                    <p class="text-sm font-semibold text-on-surface"><?= $label ?></p>
                    <p class="text-[10px] text-slate-400">
                      <?= match($val) {
                        'published' => 'Visible to all readers',
                        'draft'     => 'Saved, not yet public',
                        'archived'  => 'Hidden from readers',
                      } ?>
                    </p>
                  </div>
                </label>
                <?php endforeach; ?>
              </div>
            </div>

            <!-- Publish date -->
            <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-5 space-y-2">
              <label class="form-label block" for="post-published-at">Published At <span class="text-slate-400 font-normal normal-case">(optional)</span></label>
              <input id="post-published-at" class="form-input" name="published_at" type="datetime-local"
                     value="<?= e($publishedAt ? date('Y-m-d\TH:i', strtotime($publishedAt)) : '') ?>"/>
              <p class="text-[11px] text-slate-400">Leave empty to use the current time.</p>
            </div>

            <!-- Submit row -->
            <div class="flex flex-col gap-3">
              <button type="submit"
                      class="w-full bg-gradient-to-r from-primary to-primary-container text-on-primary py-3.5 px-5 rounded-xl font-bold text-sm shadow-sm hover:opacity-90 transition flex items-center justify-center gap-2">
                <span class="material-symbols-outlined text-[18px]"><?= $isEdit ? 'save' : 'publish' ?></span>
                <?= $isEdit ? 'Save Changes' : 'Publish Post' ?>
              </button>
              <a href="index.php?controller=admin&action=index"
                 class="w-full bg-surface-container text-on-surface-variant py-3 px-5 rounded-xl font-semibold text-sm hover:bg-surface-container-high transition flex items-center justify-center gap-2">
                <span class="material-symbols-outlined text-[18px]">close</span>
                Cancel
              </a>
            </div>

          </div>
        </div>
      </form>
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
