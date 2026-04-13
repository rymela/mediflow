/* MediFlow Magazine — Admin Office interactions
 * Handles: delete confirmations, image URL preview, char/word counters, toast notifications
 */
'use strict';

// ══════════════════════════════════════════════════════════════════════
//  Toast notification system (mirrors front office)
// ══════════════════════════════════════════════════════════════════════
function showToast(msg, icon, isError) {
  icon    = icon    || 'check_circle';
  isError = isError || false;

  var toast  = document.getElementById('mf-toast');
  var msgEl  = document.getElementById('mf-toast-msg');
  var iconEl = document.getElementById('mf-toast-icon');
  if (!toast || !msgEl) return;

  msgEl.textContent = msg;
  if (iconEl) iconEl.textContent = icon;

  var inner = toast.querySelector('div');
  if (inner) {
    inner.classList.toggle('bg-red-900',   isError);
    inner.classList.toggle('bg-slate-900', !isError);
  }
  toast.classList.add('show');
  clearTimeout(toast._toastTimer);
  toast._toastTimer = setTimeout(function () {
    toast.classList.remove('show');
  }, 3000);
}
window.showToast = showToast;

// ══════════════════════════════════════════════════════════════════════
//  DELETE CONFIRMATION  (forms with data-confirm="…")
// ══════════════════════════════════════════════════════════════════════
document.querySelectorAll('form[data-confirm]').forEach(function (form) {
  form.addEventListener('submit', function (e) {
    var msg = form.getAttribute('data-confirm') || 'Are you sure? This cannot be undone.';
    if (!confirm(msg)) {
      e.preventDefault();
    }
  });
});

// ══════════════════════════════════════════════════════════════════════
//  IMAGE URL LIVE PREVIEW
// ══════════════════════════════════════════════════════════════════════
(function () {
  var imageInput        = document.querySelector('input[name="image_url"]');
  var previewContainer  = document.getElementById('img-preview-container');
  var previewImg        = document.getElementById('img-preview');
  var placeholder       = document.getElementById('img-placeholder');

  function updatePreview() {
    if (!previewContainer || !previewImg) return;
    var url = imageInput ? imageInput.value.trim() : '';
    if (url) {
      previewImg.src = url;
      previewContainer.classList.remove('hidden');
      if (placeholder) placeholder.classList.add('hidden');
    } else {
      previewContainer.classList.add('hidden');
      if (placeholder) placeholder.classList.remove('hidden');
    }
  }

  if (imageInput) {
    imageInput.addEventListener('blur',  updatePreview);
    imageInput.addEventListener('paste', function () { setTimeout(updatePreview, 120); });
    // If editing an existing post that already has an image — show preview immediately
    if (imageInput.value.trim()) updatePreview();
  }

  if (previewImg) {
    previewImg.addEventListener('error', function () {
      // Invalid URL — hide preview, show placeholder
      previewContainer && previewContainer.classList.add('hidden');
      if (placeholder) placeholder.classList.remove('hidden');
      showToast('Could not load image from that URL.', 'broken_image', true);
    });
  }
})();

// ══════════════════════════════════════════════════════════════════════
//  EXCERPT CHARACTER COUNTER
// ══════════════════════════════════════════════════════════════════════
(function () {
  var excerptArea    = document.querySelector('textarea[name="excerpt"]');
  var excerptCounter = document.getElementById('excerpt-counter');
  if (!excerptArea || !excerptCounter) return;

  var max = parseInt(excerptArea.getAttribute('maxlength') || '300', 10);

  function update() {
    var len = excerptArea.value.length;
    excerptCounter.textContent = len + ' / ' + max;
    excerptCounter.classList.toggle('text-error',       len >= max);
    excerptCounter.classList.toggle('text-amber-500',   len >= max * 0.85 && len < max);
    excerptCounter.classList.toggle('text-slate-400',   len <  max * 0.85);
  }

  excerptArea.addEventListener('input', update);
  update(); // run on load (edit mode)
})();

// ══════════════════════════════════════════════════════════════════════
//  CONTENT WORD COUNTER
// ══════════════════════════════════════════════════════════════════════
(function () {
  var contentArea    = document.querySelector('textarea[name="content"]');
  var contentCounter = document.getElementById('content-counter');
  if (!contentArea || !contentCounter) return;

  function update() {
    var trimmed = contentArea.value.trim();
    var words   = trimmed === '' ? 0 : trimmed.split(/\s+/).length;
    var mins    = Math.max(1, Math.ceil(words / 200));
    contentCounter.textContent = words + ' words · ~' + mins + ' min read';
  }

  contentArea.addEventListener('input', update);
  update();
})();

// ══════════════════════════════════════════════════════════════════════
//  TITLE LIVE SLUG PREVIEW  (optional, shows estimated URL slug)
// ══════════════════════════════════════════════════════════════════════
(function () {
  var titleInput   = document.getElementById('post-title');
  var slugPreview  = document.getElementById('slug-preview');
  if (!titleInput || !slugPreview) return;

  function toSlug(str) {
    return str
      .toLowerCase()
      .normalize('NFD').replace(/[\u0300-\u036f]/g, '')  // remove accents
      .replace(/[^a-z0-9\s-]/g, '')
      .replace(/\s+/g, '-')
      .replace(/-+/g, '-')
      .replace(/^-|-$/g, '')
      .slice(0, 60);
  }

  function update() {
    var slug = toSlug(titleInput.value);
    slugPreview.textContent = slug ? '/mag/' + slug : '';
  }

  titleInput.addEventListener('input', update);
  update();
})();

// ══════════════════════════════════════════════════════════════════════
//  STATUS RADIO VISUAL SYNC
//  (card border highlights whichever status radio is checked)
// ══════════════════════════════════════════════════════════════════════
(function () {
  var radios = document.querySelectorAll('input[name="status"]');
  if (!radios.length) return;

  function syncHighlight() {
    radios.forEach(function (r) {
      var card = r.closest('label');
      if (!card) return;
      if (r.checked) {
        card.classList.add('border-primary', 'bg-blue-50/50');
        card.classList.remove('border-slate-100');
      } else {
        card.classList.remove('border-primary', 'bg-blue-50/50');
        card.classList.add('border-slate-100');
      }
    });
  }

  radios.forEach(function (r) { r.addEventListener('change', syncHighlight); });
  syncHighlight(); // run on page load for edit form
})();

// ══════════════════════════════════════════════════════════════════════
//  SEARCH — submit on Enter (already works natively; add clear on Escape)
// ══════════════════════════════════════════════════════════════════════
(function () {
  var searchInput = document.getElementById('admin-search');
  if (!searchInput) return;

  searchInput.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') {
      searchInput.value = '';
      searchInput.form && searchInput.form.submit();
    }
  });
})();

// ══════════════════════════════════════════════════════════════════════
//  POST ROW HOVER REVEAL  (subtle action feedback)
// ══════════════════════════════════════════════════════════════════════
(function () {
  document.querySelectorAll('.post-row').forEach(function (row) {
    var actions = row.querySelectorAll('a[title], button[title]');
    row.addEventListener('mouseenter', function () {
      actions.forEach(function (a) { a.style.opacity = '1'; });
    });
    row.addEventListener('mouseleave', function () {
      actions.forEach(function (a) { a.style.opacity = ''; });
    });
  });
})();
