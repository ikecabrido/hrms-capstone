// ==========================================================================
// HRMS Capstone — Notification Compose Page (JS)
// Outlook/Gmail-style compose with recipient chips, employee search,
// attachment drop zone, live branded email preview, and submission.
// ==========================================================================

document.addEventListener('DOMContentLoaded', function() {
  'use strict';

  var cfg = window.__ncConfig || {};
  var NC = {
    sendToMany:           !!cfg.sendToMany,
    webBase:              cfg.webBase || '',
    composeMode:          cfg.composeMode || '',
    replySenderEmail:     cfg.replySenderEmail || '',
    origSenderName:       cfg.origSenderName || '',
    originalSubject:      cfg.originalSubject || '',
    originalMessage:      cfg.originalMessage || '',
    attachmentContractId: parseInt(cfg.attachmentContractId || '0', 10),
    attachmentName:       cfg.attachmentName || '',
    attachmentUrl:        cfg.attachmentUrl || '',
    composeBody:          cfg.composeBody || '',
    notificationId:       parseInt(cfg.notificationId || '0', 10),
    notificationKey:      cfg.notificationKey || '',
    templateCode:         cfg.templateCode || '',
    documentType:         cfg.documentType || '',
    composeEmployeeId:    cfg.composeEmployeeId || '',
    contractSalaryInput:  cfg.contractSalaryInput || '',
    companyEmail:         cfg.companyEmail || 'hr@bestlink.edu.ph',
    companyWebsite:       cfg.companyWebsite || 'www.bestlinkcollege.edu.ph',
    companyAddress:       cfg.companyAddress || 'Quirino Highway, Brgy. Minuyan Proper, City of San Jose del Monte, Bulacan'
  };

  // Centered paper-plane animation shown after a confirmed successful send.
  function ncShowSentPlane(message, onComplete) {
    ncHideSendingPlane();
    var existing = document.getElementById('ncSentPlaneOverlay');
    if (existing) existing.remove();

    if (!document.getElementById('ncSentPlaneStyles')) {
      var style = document.createElement('style');
      style.id = 'ncSentPlaneStyles';
      style.textContent = `
        #ncSentPlaneOverlay {
          position: fixed; inset: 0; width: 100%; height: 100%;
          display: flex; align-items: center; justify-content: center;
          padding: 20px; box-sizing: border-box;
          background: rgba(15, 23, 42, .58);
          backdrop-filter: blur(5px); -webkit-backdrop-filter: blur(5px);
          z-index: 2147483000; opacity: 0; visibility: hidden;
          transition: opacity .25s ease, visibility .25s ease;
        }
        #ncSentPlaneOverlay.nc-plane-visible {
          opacity: 1; visibility: visible;
        }
        #ncSentPlaneOverlay .nc-plane-card {
          width: min(410px, 100%); box-sizing: border-box;
          padding: 32px 26px 28px; border-radius: 24px;
          background: #fff; color: #172033; text-align: center;
          font-family: inherit;
          box-shadow: 0 24px 80px rgba(0,0,0,.25);
          transform: translateY(18px) scale(.96);
          transition: transform .35s cubic-bezier(.2,.8,.2,1);
        }
        #ncSentPlaneOverlay.nc-plane-visible .nc-plane-card {
          transform: translateY(0) scale(1);
        }
        #ncSentPlaneOverlay .nc-plane-scene {
          position: relative; width: 190px; height: 125px;
          margin: 0 auto 12px; overflow: hidden;
        }
        #ncSentPlaneOverlay .nc-plane-trail {
          position: absolute; left: 12px; top: 76px;
          width: 145px; height: 35px;
          border-top: 2px dashed #b8c8e8; border-radius: 50%;
          transform: rotate(-18deg);
        }
        #ncSentPlaneOverlay .nc-plane-icon {
          position: absolute; top: 27px; left: 54px;
          width: 82px; height: 72px; color: #4263eb;
          animation: ncPlaneFly 1.25s cubic-bezier(.2,.7,.2,1) both;
        }
        #ncSentPlaneOverlay .nc-plane-spark {
          position: absolute; color: #f5b942; font-size: 21px;
          animation: ncPlaneSpark .9s ease-out both;
        }
        #ncSentPlaneOverlay .nc-plane-spark.one { top: 15px; right: 26px; }
        #ncSentPlaneOverlay .nc-plane-spark.two { top: 70px; right: 8px; animation-delay: .18s; }
        #ncSentPlaneOverlay .nc-plane-check {
          display: flex; align-items: center; justify-content: center;
          width: 48px; height: 48px; margin: 0 auto 15px;
          border-radius: 50%; background: #e8f8ee; color: #16834a;
          font-size: 27px; font-weight: 700;
          animation: ncPlaneCheck .45s .65s both;
        }
        #ncSentPlaneOverlay .nc-plane-title {
          margin: 0 0 9px; font-size: 23px; line-height: 1.3;
          font-weight: 750; letter-spacing: -.4px;
        }
        #ncSentPlaneOverlay .nc-plane-message {
          margin: 0 auto; max-width: 320px; color: #647087;
          font-size: 14px; line-height: 1.6; overflow-wrap: anywhere;
        }
        #ncSentPlaneOverlay .nc-plane-progress {
          height: 4px; margin-top: 24px; overflow: hidden;
          border-radius: 10px; background: #edf0f5;
        }
        #ncSentPlaneOverlay .nc-plane-progress span {
          display: block; width: 100%; height: 100%;
          background: #4263eb; transform-origin: left;
          animation: ncPlaneProgress 2.7s linear both;
        }
        @keyframes ncPlaneFly {
          0% { transform: translate(-55px, 32px) rotate(-24deg); opacity: 0; }
          20% { opacity: 1; }
          75% { transform: translate(20px, -8px) rotate(-8deg); opacity: 1; }
          100% { transform: translate(43px, -18px) rotate(0); opacity: 0; }
        }
        @keyframes ncPlaneSpark {
          0% { transform: scale(.2); opacity: 0; }
          55% { transform: scale(1.15); opacity: 1; }
          100% { transform: scale(.8); opacity: .75; }
        }
        @keyframes ncPlaneCheck {
          from { transform: scale(.45); opacity: 0; }
          to { transform: scale(1); opacity: 1; }
        }
        @keyframes ncPlaneProgress {
          from { transform: scaleX(1); }
          to { transform: scaleX(0); }
        }
        @media (prefers-reduced-motion: reduce) {
          #ncSentPlaneOverlay *, #ncSentPlaneOverlay *::before,
          #ncSentPlaneOverlay *::after {
            animation-duration: .01ms !important;
            animation-delay: 0ms !important;
            transition-duration: .01ms !important;
          }
        }
      `;
      document.head.appendChild(style);
    }

    var overlay = document.createElement('div');
    overlay.id = 'ncSentPlaneOverlay';
    overlay.setAttribute('role', 'status');
    overlay.setAttribute('aria-live', 'polite');
    overlay.setAttribute('aria-hidden', 'true');
    overlay.innerHTML =
      '<div class="nc-plane-card">' +
        '<div class="nc-plane-scene" aria-hidden="true">' +
          '<div class="nc-plane-trail"></div>' +
          '<span class="nc-plane-spark one">✦</span>' +
          '<span class="nc-plane-spark two">✧</span>' +
          '<svg class="nc-plane-icon" viewBox="0 0 100 90" fill="none" xmlns="http://www.w3.org/2000/svg">' +
            '<path d="M8 39L88 7L62 80L45 52L8 39Z" fill="#4263eb"/>' +
            '<path d="M45 52L88 7L37 43" stroke="white" stroke-width="3" stroke-linejoin="round"/>' +
            '<path d="M45 52L48 69L62 80" stroke="#c7d2fe" stroke-width="2" stroke-linejoin="round"/>' +
          '</svg>' +
        '</div>' +
        '<div class="nc-plane-check" aria-hidden="true">✓</div>' +
        '<h2 class="nc-plane-title">Message sent successfully!</h2>' +
        '<p class="nc-plane-message"></p>' +
        '<div class="nc-plane-progress" aria-hidden="true"><span></span></div>' +
      '</div>';

    overlay.querySelector('.nc-plane-message').textContent =
      message || 'Your email has been sent successfully.';

    document.body.appendChild(overlay);
    document.body.style.overflow = 'hidden';

    requestAnimationFrame(function() {
      overlay.classList.add('nc-plane-visible');
      overlay.setAttribute('aria-hidden', 'false');
    });

    // Automatically close the success overlay after the animation.
    setTimeout(function() {
      if (overlay.isConnected) {
        overlay.classList.remove('nc-plane-visible');
        overlay.setAttribute('aria-hidden', 'true');

        setTimeout(function() {
          if (overlay.isConnected) overlay.remove();
          document.body.style.overflow = '';

          if (typeof onComplete === 'function') onComplete();
        }, 300);
      } else if (typeof onComplete === 'function') {
        onComplete();
      }
    }, 2800);
  }

  // Loading overlay displayed while the email API request is running.
  function ncHideSendingPlane() {
    var overlay = document.getElementById('ncSendingPlaneOverlay');
    if (!overlay) return;
    overlay.remove();
    document.body.style.overflow = overlay.dataset.previousOverflow || '';
  }

  function ncShowSendingPlane() {
    ncHideSendingPlane();

    if (!document.getElementById('ncSendingPlaneStyles')) {
      var style = document.createElement('style');
      style.id = 'ncSendingPlaneStyles';
      style.textContent = `
        #ncSendingPlaneOverlay {
          position: fixed; inset: 0; z-index: 2147483001;
          display: flex; align-items: center; justify-content: center;
          padding: 20px; box-sizing: border-box;
          background: rgba(15,23,42,.58);
          backdrop-filter: blur(5px); -webkit-backdrop-filter: blur(5px);
          opacity: 0; transition: opacity .2s ease;
        }
        #ncSendingPlaneOverlay.nc-sending-visible { opacity: 1; }
        #ncSendingPlaneOverlay .nc-sending-card {
          width: min(390px,100%); box-sizing: border-box;
          padding: 34px 26px; border-radius: 24px; background: #fff;
          color: #172033; text-align: center; font-family: inherit;
          box-shadow: 0 24px 80px rgba(0,0,0,.25);
        }
        #ncSendingPlaneOverlay .nc-sending-orbit {
          width: 100px; height: 100px; margin: 0 auto 22px;
          position: relative; border: 3px solid #e6ebff;
          border-radius: 50%; display: flex;
          align-items: center; justify-content: center;
        }
        #ncSendingPlaneOverlay .nc-sending-orbit:after {
          content: ''; position: absolute; inset: -3px;
          border: 3px solid transparent; border-top-color: #4263eb;
          border-right-color: #4263eb; border-radius: 50%;
          animation: ncSendingSpin 1.1s linear infinite;
        }
        #ncSendingPlaneOverlay .nc-sending-plane {
          color: #4263eb; font-size: 39px;
          animation: ncSendingFloat 1.1s ease-in-out infinite;
        }
        #ncSendingPlaneOverlay .nc-sending-title {
          font-size: 22px; line-height: 1.35; margin: 0 0 10px;
          font-weight: 750;
        }
        #ncSendingPlaneOverlay .nc-sending-copy {
          margin: 0; color: #647087; font-size: 14px; line-height: 1.6;
        }
        #ncSendingPlaneOverlay .nc-sending-dots:after {
          content: ''; animation: ncSendingDots 1.4s steps(4,end) infinite;
        }
        @keyframes ncSendingSpin { to { transform: rotate(360deg); } }
        @keyframes ncSendingFloat {
          0%,100% { transform: translate(0,2px) rotate(-8deg); }
          50% { transform: translate(5px,-5px) rotate(5deg); }
        }
        @keyframes ncSendingDots {
          0% { content: ''; } 25% { content: '.'; }
          50% { content: '..'; } 75%,100% { content: '...'; }
        }
        @media (prefers-reduced-motion: reduce) {
          #ncSendingPlaneOverlay * { animation-duration: .01ms !important; }
        }
      `;
      document.head.appendChild(style);
    }

    var overlay = document.createElement('div');
    overlay.id = 'ncSendingPlaneOverlay';
    overlay.setAttribute('role', 'status');
    overlay.setAttribute('aria-live', 'polite');
    overlay.dataset.previousOverflow = document.body.style.overflow;
    overlay.innerHTML =
      '<div class="nc-sending-card">' +
        '<div class="nc-sending-orbit" aria-hidden="true">' +
          '<span class="nc-sending-plane">➤</span>' +
        '</div>' +
        '<h2 class="nc-sending-title">Sending your message</h2>' +
        '<p class="nc-sending-copy">Please wait while we deliver your email<span class="nc-sending-dots"></span></p>' +
      '</div>';

    document.body.appendChild(overlay);
    document.body.style.overflow = 'hidden';
    requestAnimationFrame(function() {
      overlay.classList.add('nc-sending-visible');
    });
  }

  function ncIsValidEmail(email) {
    return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(String(email || '').trim());
  }

  function ncReplyHasImplicit() {
    return NC.composeMode === 'reply' && ncIsValidEmail(NC.replySenderEmail);
  }

  function ncApplySubjectPrefix(subject, mode) {
    var s = (subject || '').trim();
    if (s === '') return mode === 'reply' ? 'Re: ' : 'Fwd: ';
    var prefix = mode === 'reply' ? 'Re: ' : 'Fwd: ';
    var regex = mode === 'reply' ? /^re:\s*/i : /^fwd:\s*/i;
    if (regex.test(s)) return s;
    return prefix + s;
  }

  function ncBuildQuotedBody(mode, originalMessage, originalSubject, senderEmail, senderName) {
    var lines = [];
    if (mode === 'reply') {
      lines.push('On ' + new Date().toLocaleString() + ', ' + (senderName || senderEmail || 'sender') + ' wrote:');
      lines.push('');
      var quoted = (originalMessage || '').trim();
      if (quoted !== '') {
        lines.push('> ' + quoted.replace(/\n/g, '\n> '));
      }
    } else if (mode === 'forward') {
      lines.push('---------- Forwarded message ---------');
      lines.push('From: ' + (senderEmail || 'Unknown'));
      lines.push('Subject: ' + (originalSubject || 'No subject'));
      lines.push('');
      var fwdQuoted = (originalMessage || '').trim();
      if (fwdQuoted !== '') {
        lines.push(fwdQuoted);
      }
    }
    return lines.join('\n');
  }

  function ncEscape(str) {
    return String(str).replace(/[&<>"']/g, function(c) {
      return ({'&':'&amp;','<':'<','>':'>','"':'"','\'':'&#39;'})[c];
    });
  }

  function ncInitials(name) {
    var parts = String(name || '').trim().split(/\s+/).filter(Boolean);
    if (parts.length >= 2) return (parts[0][0] + parts[parts.length - 1][0]).toUpperCase();
    return (parts[0] ? parts[0].slice(0, 2) : '??').toUpperCase();
  }

  var chipsEl      = document.getElementById('ncRecipientChips');
  var searchEl     = document.getElementById('ncRecipientSearch');
  var searchWrap   = document.getElementById('ncSearchWrap');
  var resultsEl    = document.getElementById('recipientResults');
  var validationEl = document.getElementById('recipientValidation');
  var hiddenToEl   = document.getElementById('toRecipients');
  var addChipBtn   = document.getElementById('ncAddChipBtn');
  var dropzone     = document.getElementById('ncAttachmentDropzone');
  var fileInput    = document.getElementById('lcAttachmentInput');
  var previewBody  = document.getElementById('ncPreviewBody');
  var statusEl     = document.getElementById('ncDraftStatus');

  var recipientChips = [];

  function ncSyncRecipients() {
    var emails = recipientChips.map(function(c) { return c.email; });
    var unique = Array.from(new Set(emails));
    hiddenToEl.value = unique.join(',');
    if (ncReplyHasImplicit()) {
      validationEl.style.display = 'none';
    } else {
      validationEl.style.display = unique.length === 0 ? 'block' : 'none';
    }
  }

  function ncRenderChips() {
    while (chipsEl.firstChild && chipsEl.firstChild !== addChipBtn) {
      chipsEl.removeChild(chipsEl.firstChild);
    }
    for (var i = 0; i < recipientChips.length; i++) {
      var chip = document.createElement('span');
      chip.className = 'nc-recipient-chip';
      chip.innerHTML = '<i class="bi bi-person"></i> ' + ncEscape(recipientChips[i].name || recipientChips[i].email)
        + '<button type="button" class="nc-chip-remove" onclick="ncRemoveChip(' + i + ')" title="Remove"><i class="bi bi-x"></i></button>';
      chipsEl.insertBefore(chip, addChipBtn);
    }
  }

  function ncHideSearch() {
    if (searchWrap) searchWrap.style.display = 'none';
    if (resultsEl) { resultsEl.innerHTML = ''; resultsEl.style.display = 'none'; }
    if (addChipBtn) addChipBtn.style.display = 'inline-flex';
  }

  window.ncRemoveChip = function(index) {
    if (index < 0 || index >= recipientChips.length) return;
    recipientChips.splice(index, 1);
    ncRenderChips();
    ncSyncRecipients();
    if (recipientChips.length === 0 && !ncReplyHasImplicit()) {
      validationEl.style.display = 'block';
    }
    ncRenderPreview();
  };

  if (resultsEl) {
    resultsEl.addEventListener('mousedown', function(e) {
      var item = e.target.closest('.sr-item');
      if (!item) return;
      e.preventDefault();
      var customEmail = item.getAttribute('data-custom-email');
      if (customEmail) {
        ncAddChip(customEmail, customEmail);
        searchEl.value = '';
        resultsEl.innerHTML = '';
        resultsEl.style.display = 'none';
        if (searchWrap) searchWrap.style.display = 'none';
        if (addChipBtn) addChipBtn.style.display = 'inline-flex';
        searchEl.focus();
        return;
      }
      var idx = parseInt(item.getAttribute('data-emp-index'), 10);
      var store = resultsEl._empItems;
      if (!Array.isArray(store) || isNaN(idx)) return;
      var selected = store[idx];
      if (!selected) return;
      var email = String(selected.email || '').trim();
      if (!email) {
        alert('"' + (selected.full_name || 'this employee') + '" does not have an email address on file.');
        return;
      }
      ncAddChip(selected.full_name || email, email);
      searchEl.value = '';
      resultsEl.innerHTML = '';
      resultsEl.style.display = 'none';
      if (searchWrap) searchWrap.style.display = 'none';
      if (addChipBtn) addChipBtn.style.display = 'inline-flex';
      searchEl.focus();
    });
  }

  function ncAddChip(name, email) {
    if (!email) return;
    for (var i = 0; i < recipientChips.length; i++) {
      if (recipientChips[i].email === email) return;
    }
    if (!NC.sendToMany && recipientChips.length > 0) {
      chipsEl.innerHTML = '';
      recipientChips = [];
    }
    var chip = document.createElement('span');
    chip.className = 'nc-recipient-chip';
    chip.innerHTML = '<i class="bi bi-person"></i> ' + ncEscape(name || email)
      + '<button type="button" class="nc-chip-remove" onclick="ncRemoveChip(' + recipientChips.length + ')" title="Remove"><i class="bi bi-x"></i></button>';
    chipsEl.insertBefore(chip, addChipBtn);
    recipientChips.push({ name: name, email: email });
    ncSyncRecipients();
    ncHideSearch();
    ncRenderPreview();
  }

  if (addChipBtn) {
    addChipBtn.addEventListener('click', function() {
      if (searchWrap) {
        searchWrap.style.display = 'block';
        addChipBtn.style.display = 'none';
        if (searchEl) { searchEl.value = ''; searchEl.focus(); }
      }
    });
  }

  var debounceT = null;

  if (searchEl) {
    searchEl.addEventListener('input', function() {
      var q = searchEl.value.trim();
      if (q.length < 2) {
        if (resultsEl) { resultsEl.innerHTML = ''; resultsEl.style.display = 'none'; }
        return;
      }
      clearTimeout(debounceT);
      debounceT = setTimeout(function() {
        var url = NC.webBase + 'lib/api/search-employees.php?q=' + encodeURIComponent(q);
        fetch(url, { credentials: 'same-origin' })
          .then(function(r) { return r.json(); })
          .then(function(data) {
            if (!resultsEl) return;
            if (!data || !data.success) {
              resultsEl.innerHTML = '';
              resultsEl.style.display = 'none';
              return;
            }
            var items = Array.isArray(data.data) ? data.data : [];
            if (!items.length) {
              var sq = searchEl.value.trim();
              if (ncIsValidEmail(sq)) {
                resultsEl.innerHTML = '<div class="sr-item sr-custom-email" role="option" tabindex="0" data-custom-email="' + ncEscape(sq) + '">'
                  + '<div class="sr-av"><i class="bi bi-envelope"></i></div>'
                  + '<div class="sr-text"><div><b>Add custom email</b></div>'
                  + '<div class="sr-sub">' + ncEscape(sq) + '</div>'
                  + '</div></div>';
              } else {
                resultsEl.innerHTML = '<div class="sr-empty">No results</div>';
              }
              resultsEl.style.display = 'block';
              return;
            }
            resultsEl.innerHTML = items.map(function(emp, idx) {
              var name = emp.full_name || 'Employee';
              var initials = ncInitials(name);
              var dept = emp.department || '';
              var email = emp.email || '';
              return '<div class="sr-item" role="option" tabindex="0" data-emp-index="' + idx + '">'
                + '<div class="sr-av">' + ncEscape(initials) + '</div>'
                + '<div class="sr-text">'
                + '<div><b>' + ncEscape(name) + '</b></div>'
                + '<div class="sr-sub">' + ncEscape(dept) + (email ? ' &middot; ' + ncEscape(email) : '') + '</div>'
                + '</div>'
                + '</div>';
            }).join('');
            resultsEl._empItems = items;
            resultsEl.style.display = 'block';
          });
      }, 220);
    });

    searchEl.addEventListener('blur', function() {
      setTimeout(function() {
        if (resultsEl) { resultsEl.innerHTML = ''; resultsEl.style.display = 'none'; }
      }, 200);
    });

    searchEl.addEventListener('keydown', function(e) {
      if (e.key === 'Escape') ncHideSearch();
      if (e.key === 'Enter') {
        e.preventDefault();
        var q = searchEl.value.trim();
        if (ncIsValidEmail(q)) {
          ncAddChip(q, q);
          searchEl.value = '';
          resultsEl.innerHTML = '';
          resultsEl.style.display = 'none';
          searchEl.focus();
        }
      }
    });
  }

  document.addEventListener('click', function(e) {
    if (searchWrap && resultsEl && !searchWrap.contains(e.target) && !resultsEl.contains(e.target)) {
      if (!e.target.closest('.nc-recipient-chip') && !e.target.closest('#ncAddChipBtn')) {
        ncHideSearch();
      }
    }
  });

  if (NC.composeMode === 'reply') {
    var replyEmail = NC.replySenderEmail || NC.preselectedEmail;
    var replyName = NC.replySenderEmail ? NC.replySenderEmail : (NC.preselectedName || NC.preselectedEmail);
    if (ncIsValidEmail(replyEmail)) {
      var replyChip = document.createElement('span');
      replyChip.className = 'nc-recipient-chip nc-reply-chip';
      replyChip.innerHTML = '<i class="bi bi-reply-all"></i> ' + ncEscape(replyName)
        + '<button type="button" class="nc-chip-remove" onclick="ncRemoveChip(' + recipientChips.length + ')" title="Remove"><i class="bi bi-x"></i></button>';
      recipientChips.push({ name: replyName, email: replyEmail });
      chipsEl.insertBefore(replyChip, addChipBtn);
      ncSyncRecipients();
    }
  }

  if (NC.composeMode === 'new' && ncIsValidEmail(NC.preselectedEmail)) {
    var preChip = document.createElement('span');
    preChip.className = 'nc-recipient-chip';
    preChip.innerHTML = '<i class="bi bi-person"></i> ' + ncEscape(NC.preselectedName || NC.preselectedEmail)
      + '<button type="button" class="nc-chip-remove" onclick="ncRemoveChip(' + recipientChips.length + ')" title="Remove"><i class="bi bi-x"></i></button>';
    recipientChips.push({ name: NC.preselectedName || NC.preselectedEmail, email: NC.preselectedEmail });
    chipsEl.insertBefore(preChip, addChipBtn);
    ncSyncRecipients();
    ncRenderPreview();
  }

  (function() {
    var toggle = document.getElementById('ncOriginalToggle');
    var content = document.getElementById('ncOriginalContent');
    if (toggle && content) {
      toggle.addEventListener('click', function() {
        var isOpen = content.classList.contains('open');
        content.classList.toggle('open');
        toggle.classList.toggle('open');
        toggle.innerHTML = isOpen
          ? '<i class="bi bi-chevron-right"></i> Show original notification details'
          : '<i class="bi bi-chevron-right"></i> Hide original notification details';
      });
    }
  })();

  var MAX_ATTACH_SIZE = 5 * 1024 * 1024;
  var ALLOWED_EXTS = ['pdf','doc','docx','xls','xlsx','ppt','pptx','txt','jpg','jpeg','png','gif','html'];
  var attachments = [];

  if (NC.attachmentUrl !== '' && NC.attachmentName !== '') {
    var attExt = NC.attachmentName.split('.').pop().toLowerCase();
    var attType = 'application/octet-stream';
    if (attExt === 'pdf') attType = 'application/pdf';
    else if (attExt === 'html' || attExt === 'htm') attType = 'text/html';
    else if (attExt === 'doc') attType = 'application/msword';
    else if (attExt === 'docx') attType = 'application/vnd.openxmlformats-officedocument.wordprocessingml.document';
    else if (attExt === 'txt') attType = 'text/plain';
    else if (attExt === 'jpg' || attExt === 'jpeg') attType = 'image/jpeg';
    else if (attExt === 'png') attType = 'image/png';
    else if (attExt === 'gif') attType = 'image/gif';
    attachments.push({ name: NC.attachmentName, size: 0, type: attType, data: '' });
    ncRenderAtts();
  }

  function ncExt(name) {
    var parts = String(name).split('.');
    return parts.length > 1 ? parts.pop().toLowerCase() : '';
  }

  function ncFmtSize(bytes) {
    if (bytes < 1024) return bytes + ' B';
    if (bytes < 1048576) return (bytes / 1024).toFixed(1) + ' KB';
    return (bytes / 1048576).toFixed(1) + ' MB';
  }

  function ncReadFile(file) {
    return new Promise(function(resolve, reject) {
      var reader = new FileReader();
      reader.onload = function(e) { resolve(e.target.result); };
      reader.onerror = reject;
      reader.readAsDataURL(file);
    });
  }

  window.ncRemoveAttachment = function(index) {
    if (index >= 0 && index < attachments.length) {
      attachments.splice(index, 1);
      ncRenderAtts();
    }
  };

  function ncRenderAtts() {
    var list = document.getElementById('lcAttachmentList');
    if (!list) return;
    list.innerHTML = attachments.map(function(att, i) {
      return '<div class="nc-attachment-item" data-index="' + i + '">'
        + '<div class="nc-attachment-icon"><i class="bi bi-file-earmark"></i></div>'
        + '<div class="nc-attachment-info">'
        + '<div class="nc-attachment-name">' + ncEscape(att.name) + '</div>'
        + '<div class="nc-attachment-size">' + ncFmtSize(att.size) + '</div>'
        + '</div>'
        + '<button type="button" class="nc-attachment-remove" onclick="ncRemoveAttachment(' + i + ')" title="Remove"><i class="bi bi-x"></i></button>'
        + '</div>';
    }).join('');
    ncRenderPreview();
  }

  async function ncHandleFiles(files) {
    var maxFiles = 3;
    for (var i = 0; i < files.length; i++) {
      var file = files[i];
      if (attachments.length >= maxFiles) {
        alert('Maximum ' + maxFiles + ' attachments allowed.');
        break;
      }
      var ext = ncExt(file.name);
      if (ALLOWED_EXTS.indexOf(ext) === -1) {
        alert('File type not allowed: ' + file.name);
        continue;
      }
      if (file.size > MAX_ATTACH_SIZE) {
        alert('File too large (max 5MB): ' + file.name);
        continue;
      }
      try {
        var data = await ncReadFile(file);
        attachments.push({ name: file.name, size: file.size, type: file.type, data: data });
        ncRenderAtts();
      } catch (e) {
        alert('Failed to read file: ' + file.name);
      }
    }
  }

  if (dropzone && fileInput) {
    dropzone.addEventListener('click', function() { fileInput.click(); });
    fileInput.addEventListener('change', function(e) {
      if (e.target.files && e.target.files.length > 0) {
        ncHandleFiles(e.target.files);
        fileInput.value = '';
      }
    });
    dropzone.addEventListener('dragover', function(e) {
      e.preventDefault(); e.stopPropagation();
      dropzone.style.borderColor = 'var(--color7)';
      dropzone.style.background = 'rgba(81, 70, 183, 0.10)';
    });
    dropzone.addEventListener('dragleave', function(e) {
      e.preventDefault(); e.stopPropagation();
      dropzone.style.borderColor = '';
      dropzone.style.background = '';
    });
    dropzone.addEventListener('drop', function(e) {
      e.preventDefault(); e.stopPropagation();
      dropzone.style.borderColor = '';
      dropzone.style.background = '';
      if (e.dataTransfer.files && e.dataTransfer.files.length > 0) {
        ncHandleFiles(e.dataTransfer.files);
      }
    });
  }

  var previewTimer = null;

  function ncRenderPreview() {
    if (!previewBody) return;
    if (typeof attachments === 'undefined') { return; }

    var subject = (document.getElementById('subject')?.value || '').trim();
    var body = (document.getElementById('body')?.value || '').trim();
    var recipientName = 'Recipient';
    if (recipientChips.length > 0 && recipientChips[0].name) {
      recipientName = recipientChips[0].name;
    } else {
      recipientName = NC.preselectedName || NC.replySenderEmail || 'Recipient';
    }
    var senderName = NC.origSenderName || 'Human Resources & Legal Compliance Office';

    if (!subject && !body) {
      previewBody.innerHTML = '';
      return;
    }

    var safeBody = ncEscape(body).replace(/\n/g, '<br>');
    var safeSubject = ncEscape(subject || '(No subject)');
    var safeRecipient = ncEscape(recipientName);
    var safeSender = ncEscape(senderName);
    var safeCC = 'Department Head (Optional)';
    var safePriority = 'Normal';
    var today = new Date().toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' });
    var timeStr = new Date().toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit', hour12: true });
    var formattedDate = today + ' &bull; ' + timeStr;

    var attsSummaryHtml = '';
    if (attachments.length > 0) {
      var attNames = [];
      for (var i = 0; i < attachments.length; i++) {
        attNames.push(attachments[i].name);
      }
      attsSummaryHtml = '<div class="nc-preview-info-row">'
        + '<span class="nc-preview-info-label">&#128206;</span>'
        + '<span class="nc-preview-info-value">' + ncEscape(attNames.join(', ')) + '</span>'
        + '</div>';
    }

    var attsHtml = '';
    if (attachments.length > 0) {
      var attItems = [];
      for (var i = 0; i < attachments.length; i++) {
        var icon = 'bi-file-earmark';
        var ext = attachments[i].name.split('.').pop().toLowerCase();
        if (ext === 'pdf') icon = 'bi-filetype-pdf';
        else if (ext === 'html' || ext === 'htm') icon = 'bi-filetype-html';
        else if (ext === 'doc' || ext === 'docx') icon = 'bi-filetype-docx';
        else if (ext === 'png' || ext === 'jpg' || ext === 'jpeg' || ext === 'gif') icon = 'bi-filetype-img';
        attItems.push('<div class="nc-preview-att-item"><i class="bi ' + icon + '"></i> ' + ncEscape(attachments[i].name) + '</div>');
      }
      attsHtml = '<div class="nc-preview-divider"></div>'
        + '<div class="nc-preview-section-label"><i class="bi bi-paperclip"></i> Attachments</div>'
        + '<div class="nc-preview-attachments">' + attItems.join('') + '</div>';
    }

    var logoSrc = 'https://s3.ap-southeast-1.amazonaws.com/buckets.epicareer.com/employer/logo/20240919150720-2590899-bestlink-college-of-the-philippines.png';

    previewBody.innerHTML = '<div class="nc-preview-email">'
      + '<div class="nc-preview-header-block">'
      + '<img src="' + logoSrc + '" alt="BCP Logo" class="nc-preview-logo" onerror="this.style.display=\'none\'">'
      + '<div class="nc-preview-institution">'
      + '<span class="nc-preview-institution-name">BESTLINK COLLEGE OF THE PHILIPPINES</span>'
      + '<span class="nc-preview-campus">Bulacan Campus</span>'
      + '<span class="nc-preview-office">Human Resources &amp; Legal Compliance Office</span>'
      + '</div>'
      + '</div>'
      + '<div class="nc-preview-info-block">'
      + '<div class="nc-preview-info-title"><i class="bi bi-envelope-fill"></i> Email Information</div>'
      + '<div class="nc-preview-info-row"><span class="nc-preview-info-label">From</span><span class="nc-preview-info-value">' + safeSender + '</span></div>'
      + '<div class="nc-preview-info-row"><span class="nc-preview-info-label">To</span><span class="nc-preview-info-value">' + safeRecipient + '</span></div>'
      + '<div class="nc-preview-info-row"><span class="nc-preview-info-label">CC</span><span class="nc-preview-info-value">' + safeCC + '</span></div>'
      + '<div class="nc-preview-info-row"><span class="nc-preview-info-label">Subject</span><span class="nc-preview-info-value"><b>' + safeSubject + '</b></span></div>'
      + '<div class="nc-preview-info-row"><span class="nc-preview-info-label">Date</span><span class="nc-preview-info-value">' + formattedDate + '</span></div>'
      + '<div class="nc-preview-info-row"><span class="nc-preview-info-label">Priority</span><span class="nc-preview-info-value"><span class="nc-priority-normal">' + safePriority + '</span></span></div>'
      + attsSummaryHtml
      + '</div>'
      + '<div class="nc-preview-divider"></div>'
      + '<div style="padding:24px 20px 18px;font-size:14px;line-height:1.7;color:#1b2430;font-family:inherit;">'
      + '<p style="margin:0 0 16px;">Dear <strong>' + safeRecipient + '</strong>,</p>'
      + '<div style="margin:0 0 20px;">' + (safeBody || '<span style="color:#8b93a1;">(No message content)</span>') + '</div>'
      + '<div style="margin-top:24px;padding-top:16px;border-top:1px solid #e4e8ee;">'
      + '<p style="margin:0 0 2px;line-height:1.4;">Sincerely,</p>'
      + '<img src="' + NC.webBase + 'assets/images.png" alt="Signatory" style="display:block;max-width:140px;max-height:56px;width:auto;height:auto;margin-top:6px;margin-bottom:4px;" onerror="this.style.display=\'none\'">'
      + '<p style="margin:0;font-weight:600;color:#0e1c33;line-height:1.4;">Blythe Lewis</p>'
      + '<p style="margin:0;font-size:13px;color:#5b6472;line-height:1.4;">HR Director</p>'
      + '<p style="margin:0;font-size:12px;color:#8b93a1;line-height:1.4;">Human Resources &amp; Legal Compliance Office</p>'
      + '<p style="margin:0;font-size:12px;color:#8b93a1;line-height:1.4;">Bestlink College of the Philippines</p>'
      + '</div>'
      + '</div>'
      + attsHtml
      + '<div class="nc-preview-divider"></div>'
      + '<div class="nc-preview-footer-block">'
      + '<div class="nc-preview-footer-name">BESTLINK COLLEGE OF THE PHILIPPINES</div>'
      + '<div class="nc-preview-footer-office">Human Resources &amp; Legal Compliance Office</div>'
      + '<div class="nc-preview-footer-address">' + NC.companyAddress + '</div>'
      + '<div class="nc-preview-footer-contact">'
      + '<span class="nc-preview-contact-item"><i class="bi bi-envelope-fill"></i> ' + NC.companyEmail + '</span>'
      + '<span class="nc-preview-contact-item"><i class="bi bi-globe"></i> ' + NC.companyWebsite + '</span>'
      + '<span class="nc-preview-contact-item"><i class="bi bi-telephone-fill"></i> 09077915906</span>'
      + '</div>'
      + '</div>'
      + '<div class="nc-preview-notice">'
      + '<div class="nc-preview-notice-title"><i class="bi bi-shield-lock-fill"></i> Confidentiality Notice</div>'
      + '<div class="nc-preview-notice-text">This email and any attachments may contain confidential and privileged information intended only for the designated recipient(s). Unauthorized access, disclosure, copying, distribution, or use of this information is prohibited.</div>'
      + '</div>'
      + '<div class="nc-preview-disclaimer">'
      + '<div class="nc-preview-disclaimer-title"><i class="bi bi-journal-text"></i> Academic / Thesis Disclaimer</div>'
      + '<div class="nc-preview-disclaimer-text">This email preview is generated solely for academic and research purposes as part of the development of a Human Resource Management System (HRMS) thesis project.</div>'
      + '<div class="nc-preview-disclaimer-text">The Bestlink College logo, branding, employee names, email addresses, documents, and other information displayed are used only for demonstration and system testing. They do not represent actual institutional communications or official records of Bestlink College of the Philippines.</div>'
      + '<div class="nc-preview-disclaimer-copy">&copy; 2026 Human Resource Management System (HRMS) Thesis Project &mdash; Bestlink College of the Philippines</div>'
      + '</div>'
      + '</div>';
  }

  window.ncSubmitCompose = function() {
    var explicitRecipients = (hiddenToEl?.value || '').trim();
    var toRecipients = (ncReplyHasImplicit() && explicitRecipients === '')
      ? NC.replySenderEmail
      : explicitRecipients;
    var subject = (document.getElementById('subject').value || '').trim();
    if (NC.composeMode === 'reply' || NC.composeMode === 'forward') {
      subject = ncApplySubjectPrefix(subject, NC.composeMode);
    }
    var body = document.getElementById('body').value.trim();

    if (NC.composeMode === 'reply' || NC.composeMode === 'forward') {
      var quoted = ncBuildQuotedBody(
        NC.composeMode,
        NC.originalMessage,
        NC.originalSubject,
        NC.replySenderEmail,
        NC.origSenderName
      );
      if (quoted !== '') {
        body = (body ? body + '\n\n' : '') + quoted;
      }
    }

    if (!toRecipients) { alert('At least one recipient is required.'); return; }
    if (!subject) { alert('Subject is required'); return; }
    if (!body) { alert('Message body is required'); return; }

    var allEmails = toRecipients.split(',').map(function(s) { return s.trim(); }).filter(Boolean);
    if (!NC.sendToMany && allEmails.length > 1) {
      alert('This notification does not allow sending to multiple recipients.');
      return;
    }

    var payload = {
      mode: NC.composeMode,
      notification_id: NC.notificationId,
      notification_key: NC.notificationKey,
      recipients: toRecipients,
       subject: subject,
       body: body,
       template_code: NC.templateCode,
       document_type: NC.documentType,
       employee_id: NC.composeEmployeeId,
       complaint_id: NC.complaintId || 0,
       contract_salary_input: NC.contractSalaryInput,
       contract_id: NC.contractId,
       department: NC.recipientDept || ''
     };

    if (NC.attachmentContractId > 0) {
      payload.attachment_contract_id = NC.attachmentContractId;
      payload.attachment_name = NC.attachmentName;
    } else if (NC.attachmentUrl !== '') {
      payload.attachment_url = NC.attachmentUrl;
      payload.attachment_name = NC.attachmentName;
    }

    if (attachments.length > 0) {
      payload.attachments = attachments.map(function(a) {
        return { name: a.name, type: a.type, size: a.size, data: a.data };
      });
    }

    ncShowSendingPlane();

    fetch(NC.webBase + 'lib/api/notification-send.php', {
      method: 'POST',
      credentials: 'same-origin',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload)
    })
    .then(function(r) {
      var status = r.status;
      return r.text().then(function(text) {
        var data = {};
        var jsonError = null;
        try { data = JSON.parse(text); } catch (e) { jsonError = e; }
        if (!r.ok) {
          throw new Error('HTTP ' + status + (data.message ? ': ' + data.message : ': ' + (text || 'No response')));
        }
        if (jsonError) {
          console.error('ncSubmitCompose JSON parse error:', jsonError);
          console.error('ncSubmitCompose raw response (first 500 chars):', text.substring(0, 500));
        }
        return data;
      });
    })
    .then(function(data) {
      if (!data.success) {
        var errorMessage = data.message || data.error_info ||
          'The server did not confirm that the email was sent.';
        ncHideSendingPlane();
        console.error('Email send failed:', data);
        console.error('Email send failure message:', errorMessage);
        return;
      }

      var msg = data.message || 'Your email has been sent successfully.';
      ncShowSentPlane(msg, function() {
        console.log('[Plane animation] Finished; redirect paused for testing.');
      });
    })
    .catch(function(err) {
      var msg = 'Network error';
      if (err && err.message) msg = err.message;
      ncHideSendingPlane();
      console.error('ncSubmitCompose error', err);
      console.error('Email send network/error message:', msg);
    });
  };

  if (NC.composeBody && NC.composeBody !== '') {
    var bodyEl = document.getElementById('body');
    if (bodyEl && bodyEl.value.trim() === '') {
      bodyEl.value = NC.composeBody;
    }
  }

  ['subject', 'body'].forEach(function(id) {
    var el = document.getElementById(id);
    if (el) {
      el.addEventListener('input', function() {
        if (previewTimer) clearTimeout(previewTimer);
        previewTimer = setTimeout(ncRenderPreview, 200);
      });
    }
  });

  setTimeout(ncRenderPreview, 300);

  var subjectEl = document.getElementById('subject');
  var bodyEl2 = document.getElementById('body');
  if (subjectEl && bodyEl2 && statusEl) {
    function updateStatus() {
      var s = subjectEl.value.trim();
      var b = bodyEl2.value.trim();
      if (s && b) {
        statusEl.textContent = 'Ready to send';
      } else if (s || b) {
        statusEl.textContent = 'Draft in progress';
      } else {
        statusEl.textContent = 'Ready to send';
      }
    }
    subjectEl.addEventListener('input', updateStatus);
    bodyEl2.addEventListener('input', updateStatus);
  }
});

/* Auto-close the notification success card */
window.ncAutoClosePlaneCard = function(delay = 1800) {
  const card = document.querySelector('.nc-plane-card');
  if (!card) return;

  setTimeout(function() {
    if (!card.isConnected) return;

    card.classList.add('nc-plane-card--closing');

    setTimeout(function() {
      if (card.isConnected) card.remove();
    }, 250);
  }, delay);
};
