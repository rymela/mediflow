/* MediFlow Magazine — Front Office interactions
 * Handles: likes, inline comment edit, comment delete, comment send, toast notifications
 */
'use strict';

// ══════════════════════════════════════════════════════════════════════
//  Toast notification system
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
  }, 3200);
}
// Expose globally so show.php inline scripts can call it too
window.showToast = showToast;

// ══════════════════════════════════════════════════════════════════════
//  HTTP helper — sends application/x-www-form-urlencoded POST
// ══════════════════════════════════════════════════════════════════════
function postJson(url, payload) {
  return fetch(url, {
    method:  'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8' },
    body:    new URLSearchParams(payload).toString(),
  }).then(function (res) {
    return res.json().catch(function () { return null; }).then(function (data) {
      if (!res.ok) {
        var code = (data && data.error) ? data.error : 'REQUEST_FAILED';
        throw new Error(code);
      }
      return data;
    });
  });
}

// ══════════════════════════════════════════════════════════════════════
//  Login-redirect helper (used when server responds LOGIN_REQUIRED)
// ══════════════════════════════════════════════════════════════════════
function redirectToLogin(delay) {
  delay = delay || 1500;
  showToast('Please login to continue.', 'lock', true);
  setTimeout(function () {
    window.location.href = 'login.php?next=' + encodeURIComponent(window.location.pathname + window.location.search);
  }, delay);
}

// ══════════════════════════════════════════════════════════════════════
//  LIKE BUTTON
// ══════════════════════════════════════════════════════════════════════
document.addEventListener('click', function (e) {
  var likeBtn = e.target.closest('[data-like-button]');
  if (!likeBtn) return;
  e.preventDefault();
  if (likeBtn.disabled) return;

  var postId = likeBtn.getAttribute('data-post-id');
  var token  = likeBtn.getAttribute('data-csrf');
  likeBtn.disabled = true;

  postJson('index.php?controller=front&action=toggleLike', {
    post_id: postId,
    _token:  token,
  }).then(function (data) {
    var icon  = likeBtn.querySelector('[data-like-icon]');
    var label = likeBtn.querySelector('[data-like-count]');

    if (icon) {
      icon.style.fontVariationSettings = data.liked ? "'FILL' 1" : "'FILL' 0";
      icon.classList.toggle('text-red-500',   data.liked);
      icon.classList.toggle('text-slate-400', !data.liked);
      // Micro-animation: brief scale pulse
      icon.style.transform = 'scale(1.3)';
      setTimeout(function () { icon.style.transform = ''; }, 200);
    }
    if (label) label.textContent = data.countLabel;
    showToast(
      data.liked ? 'Added to your likes!' : 'Removed from likes',
      data.liked ? 'favorite' : 'heart_broken'
    );
  }).catch(function (err) {
    if (err.message === 'LOGIN_REQUIRED') {
      redirectToLogin();
    } else {
      showToast('Could not update like. Try again.', 'error', true);
    }
  }).finally(function () {
    likeBtn.disabled = false;
  });
});

// ══════════════════════════════════════════════════════════════════════
//  COMMENT — INLINE EDIT (replaces prompt())
// ══════════════════════════════════════════════════════════════════════
document.addEventListener('click', function (e) {
  var editBtn = e.target.closest('[data-comment-edit]');
  if (!editBtn) return;
  e.preventDefault();

  var commentId = editBtn.getAttribute('data-comment-id');
  var token     = editBtn.getAttribute('data-csrf');
  var bodyEl    = document.querySelector('[data-comment-body="' + commentId + '"]');
  if (!bodyEl) return;

  // Prevent double-opening
  var row = document.querySelector('[data-comment-row="' + commentId + '"]');
  if (row && row.querySelector('textarea[data-edit-area]')) return;

  var original = bodyEl.textContent;
  bodyEl.classList.add('hidden');

  // Build inline textarea
  var ta        = document.createElement('textarea');
  ta.setAttribute('data-edit-area', commentId);
  ta.value      = original;
  ta.rows       = 3;
  ta.maxLength  = 2000;
  ta.className  = 'w-full text-sm bg-white rounded-xl border-2 border-primary/30 focus:border-primary p-3 outline-none resize-none mb-2 transition leading-relaxed';

  // Save / Cancel buttons
  var btnRow   = document.createElement('div');
  btnRow.className = 'flex gap-2 mb-2';

  var saveBtn  = document.createElement('button');
  saveBtn.type      = 'button';
  saveBtn.textContent = 'Save';
  saveBtn.className = 'text-[11px] font-bold px-4 py-1.5 bg-primary text-white rounded-lg hover:opacity-90 transition';

  var cancelBtn = document.createElement('button');
  cancelBtn.type      = 'button';
  cancelBtn.textContent = 'Cancel';
  cancelBtn.className = 'text-[11px] font-bold px-4 py-1.5 bg-slate-100 text-slate-600 rounded-lg hover:bg-slate-200 transition';

  btnRow.appendChild(saveBtn);
  btnRow.appendChild(cancelBtn);

  bodyEl.parentNode.insertBefore(ta, bodyEl.nextSibling);
  bodyEl.parentNode.insertBefore(btnRow, ta.nextSibling);
  ta.focus();
  ta.setSelectionRange(ta.value.length, ta.value.length);

  function restore() {
    ta.remove();
    btnRow.remove();
    bodyEl.classList.remove('hidden');
  }

  cancelBtn.addEventListener('click', restore);

  saveBtn.addEventListener('click', function () {
    var next = ta.value.trim();
    if (!next) {
      showToast('Comment cannot be empty.', 'warning', true);
      return;
    }
    saveBtn.disabled     = true;
    saveBtn.textContent  = 'Saving…';

    postJson('index.php?controller=front&action=commentUpdate', {
      comment_id: commentId,
      content:    next,
      _token:     token,
    }).then(function () {
      bodyEl.textContent = next;
      restore();
      showToast('Comment updated!', 'edit');
    }).catch(function () {
      showToast('Could not save. Try again.', 'error', true);
      saveBtn.disabled    = false;
      saveBtn.textContent = 'Save';
    });
  });
});

// ══════════════════════════════════════════════════════════════════════
//  COMMENT — DELETE
// ══════════════════════════════════════════════════════════════════════
document.addEventListener('click', function (e) {
  var delBtn = e.target.closest('[data-comment-delete]');
  if (!delBtn) return;
  e.preventDefault();

  if (!confirm('Delete this comment? This cannot be undone.')) return;

  var commentId = delBtn.getAttribute('data-comment-id');
  var token     = delBtn.getAttribute('data-csrf');

  postJson('index.php?controller=front&action=commentDelete', {
    comment_id: commentId,
    _token:     token,
  }).then(function () {
    var rowEl = document.querySelector('[data-comment-row="' + commentId + '"]');
    if (rowEl) {
      rowEl.style.transition = 'opacity .25s ease, transform .25s ease';
      rowEl.style.opacity    = '0';
      rowEl.style.transform  = 'translateX(-12px)';
      setTimeout(function () { rowEl.remove(); }, 260);
    }
    showToast('Comment deleted', 'delete');
  }).catch(function (err) {
    if (err.message === 'LOGIN_REQUIRED') {
      redirectToLogin();
    } else {
      showToast('Could not delete comment.', 'error', true);
    }
  });
});

// ══════════════════════════════════════════════════════════════════════
//  COMMENT — SEND (new comment)
// ══════════════════════════════════════════════════════════════════════
document.addEventListener('click', function (e) {
  var sendBtn = e.target.closest('[data-comment-send]');
  if (!sendBtn) return;
  e.preventDefault();

  var postId  = sendBtn.getAttribute('data-post-id');
  var token   = sendBtn.getAttribute('data-csrf');
  var input   = document.querySelector('[data-comment-input]');
  var content = input ? input.value.trim() : '';

  if (!content) {
    showToast('Please write something first.', 'warning', true);
    input && input.focus();
    return;
  }
  if (content.length > 2000) {
    showToast('Comment too long (max 2000 characters).', 'warning', true);
    return;
  }
  if (sendBtn.disabled) return;
  sendBtn.disabled = true;

  postJson('index.php?controller=front&action=commentStore', {
    post_id: postId,
    content: content,
    _token:  token,
  }).then(function (data) {
    if (input) input.value = '';

    var list = document.querySelector('[data-comments-list]');
    if (list && data.comment) {
      // Remove "no comments yet" empty-state if present
      var placeholder = list.querySelector('.text-center');
      if (placeholder) placeholder.remove();

      var cid = data.comment.id_comment;

      var wrapper = document.createElement('div');
      wrapper.setAttribute('data-comment-row', cid);
      wrapper.className = 'flex gap-3';
      wrapper.style.opacity   = '0';
      wrapper.style.transform = 'translateY(10px)';
      wrapper.style.transition = 'opacity .3s ease, transform .3s ease';

      wrapper.innerHTML =
        '<div class="w-8 h-8 rounded-full bg-blue-100 text-blue-700 flex items-center justify-center font-bold text-xs shrink-0">You</div>' +
        '<div class="flex-1 min-w-0">' +
          '<div class="bg-white border-l-4 border-teal-500 rounded-2xl p-4 shadow-sm">' +
            '<div class="flex justify-between items-center mb-2 flex-wrap gap-2">' +
              '<div>' +
                '<span class="text-sm font-bold text-primary">You</span>' +
                '<span class="text-xs text-slate-400 ml-2">just now</span>' +
              '</div>' +
              '<div class="flex gap-2">' +
                '<button class="text-slate-400 hover:text-blue-600 transition-colors p-1" data-comment-edit data-comment-id="' + cid + '" data-csrf="' + token + '" title="Edit">' +
                  '<span class="material-symbols-outlined text-[16px]">edit</span>' +
                '</button>' +
                '<button class="text-slate-400 hover:text-red-500 transition-colors p-1" data-comment-delete data-comment-id="' + cid + '" data-csrf="' + token + '" title="Delete">' +
                  '<span class="material-symbols-outlined text-[16px]">delete</span>' +
                '</button>' +
              '</div>' +
            '</div>' +
            '<p class="text-sm text-slate-700 leading-relaxed" data-comment-body="' + cid + '"></p>' +
          '</div>' +
        '</div>';

      // Set content safely via textContent to avoid XSS
      wrapper.querySelector('[data-comment-body="' + cid + '"]').textContent = data.comment.content;

      list.appendChild(wrapper);

      // Trigger fade-in
      requestAnimationFrame(function () {
        wrapper.style.opacity   = '1';
        wrapper.style.transform = 'none';
      });

      // Smooth scroll to new comment
      setTimeout(function () {
        wrapper.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
      }, 100);
    }
    showToast('Comment posted!', 'check_circle');

  }).catch(function (err) {
    if (err.message === 'LOGIN_REQUIRED') {
      redirectToLogin();
    } else if (err.message === 'TOO_LONG') {
      showToast('Comment too long (max 2000 chars).', 'warning', true);
    } else {
      showToast('Could not post comment. Try again.', 'error', true);
    }
  }).finally(function () {
    sendBtn.disabled = false;
  });
});

// ══════════════════════════════════════════════════════════════════════
//  COMMENT — REPORT
//  Triggered by [data-comment-report] buttons on other users' comments.
//  Sends to FrontController::commentReportAction().
// ══════════════════════════════════════════════════════════════════════
document.addEventListener('click', function (e) {
  var reportBtn = e.target.closest('[data-comment-report]');
  if (!reportBtn) return;
  e.preventDefault();

  if (!confirm('Report this comment to the moderation team?')) return;
  if (reportBtn.disabled) return;

  var commentId = reportBtn.getAttribute('data-comment-id');
  var token     = reportBtn.getAttribute('data-csrf');
  reportBtn.disabled = true;

  postJson('index.php?controller=front&action=commentReport', {
    comment_id: commentId,
    _token:     token,
  }).then(function () {
    // Replace the flag icon with a "reported" indicator
    var icon = reportBtn.querySelector('.material-symbols-outlined');
    if (icon) {
      icon.textContent = 'flag_circle';
      icon.style.color = '#d97706'; // amber
    }
    reportBtn.title = 'Comment reported — thank you';
    reportBtn.style.pointerEvents = 'none';
    showToast('Comment reported. Our team will review it shortly.', 'flag_circle');
  }).catch(function (err) {
    reportBtn.disabled = false;
    if (err.message === 'LOGIN_REQUIRED') {
      redirectToLogin();
    } else if (err.message === 'CANNOT_REPORT_OWN') {
      showToast("You can't report your own comment.", 'info', true);
    } else {
      showToast('Could not submit report. Please try again.', 'error', true);
    }
  });
});

// ══════════════════════════════════════════════════════════════════════
//  CATEGORY CHIPS — mobile syncing
// ══════════════════════════════════════════════════════════════════════
// Highlight active category in mobile row on load (already handled by PHP class).
// Keep for any future client-side filtering enhancements.
