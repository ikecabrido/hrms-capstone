function escapeHtml(text) {
  var span = document.createElement('span');
  span.textContent = text == null ? '' : String(text);
  return span.innerHTML;
}

// Persist active tab state using localStorage with smooth transitions
  const STORAGE_KEY = 'engagement:social:active-tab';
  const LEGACY_STORAGE_KEY = 'socialPageActiveTab';
  const LEGACY_STORAGE_KEY_2 = 'social-active-tab';
  const engagementScript = Array.from(document.scripts).find(function(script) {
    return script.src.indexOf('/modules/engagement/js/script.js') !== -1;
  });
  const SOCIAL_API_BASE = engagementScript
    ? new URL('../api/index.php', engagementScript.src).href
    : 'api/index.php';
  const socialTabState = {
    initialized: false,
    clickHandlersBound: false,
    activeTabObserver: null
  };

  function closeSocialModal(modalId) {
    const modal = document.getElementById(modalId);
    if (!modal) return;

    if (window.bootstrap && window.bootstrap.Modal) {
      const instance = window.bootstrap.Modal.getInstance(modal) || new window.bootstrap.Modal(modal);
      instance.hide();
      return;
    }

    modal.classList.remove('show');
    modal.setAttribute('aria-hidden', 'true');
    modal.style.display = 'none';
    document.body.classList.remove('modal-open');
    document.querySelectorAll('.modal-backdrop').forEach(function(backdrop) {
      backdrop.remove();
    });
  }

  window.closeSocialModal = closeSocialModal;

  document.addEventListener('click', function(event) {
    var modal = document.getElementById('groupMembersModal');
    if (modal && modal.classList.contains('show') && event.target === modal) {
      window.closeSocialModal('groupMembersModal');
    }
  });

  document.addEventListener('keydown', function(event) {
    if (event.key !== 'Escape') return;
    var modal = document.getElementById('groupMembersModal');
    if (modal && modal.classList.contains('show')) {
      window.closeSocialModal('groupMembersModal');
    }
  });

  window.openGroupMembersModal = function(groupItem) {
    var modal = document.getElementById('groupMembersModal');
    var modalTitle = document.getElementById('groupMembersModalLabel');
    var groupIdField = document.getElementById('groupMembersModalGroupId');
    var membersList = document.getElementById('groupMembersModalList');
    if (!modal || !groupItem) return;

    var groupName = groupItem.dataset.groupName || 'Group';
    var members = [];
    try {
      members = JSON.parse(groupItem.dataset.groupMembers || '[]');
    } catch (error) {
      members = [];
    }

    if (modalTitle) modalTitle.innerHTML = '<i class="fas fa-users mr-2"></i>' + escapeHtml(groupName) + ' Members';
    if (groupIdField) groupIdField.value = groupItem.dataset.groupId || '';
    if (membersList) {
      membersList.innerHTML = members.length
        ? members.map(function(member) {
            var name = member.full_name || ('Employee ID: ' + (member.employee_id || 'N/A'));
            return '<div class="group-member-row"><i class="fas fa-user mr-2"></i>' + escapeHtml(name) + '</div>';
          }).join('')
        : '<div class="group-member-empty">No members yet.</div>';
    }

    if (window.bootstrap && window.bootstrap.Modal) {
      (window.bootstrap.Modal.getInstance(modal) || new window.bootstrap.Modal(modal)).show();
    } else if (window.jQuery && typeof window.jQuery.fn.modal === 'function') {
      window.jQuery(modal).modal('show');
    } else {
      modal.classList.add('show');
      modal.setAttribute('aria-hidden', 'false');
      modal.style.display = 'flex';
      document.body.classList.add('modal-open');
    }
  };

  // Add smooth transition styles
  const style = document.createElement('style');
  style.textContent = `
    .social-area .tab-pane {
      opacity: 0;
      transition: opacity 0.3s ease-in-out;
      pointer-events: none;
    }
    .social-area .tab-pane.show.active {
      opacity: 1;
      pointer-events: auto;
    }
    .social-area .nav-link {
      transition: all 0.3s ease-in-out;
    }
    .social-area .nav-link.active {
      transition: all 0.3s ease-in-out;
    }
  `;
  document.head.appendChild(style);

  function getSavedSocialTabId() {
    const validTabIds = ['feed', 'forums', 'groups', 'projects'];

    const cookieMatch = document.cookie.match(/(?:^|;\s*)engagement_social_tab=([^;]*)/);
    if (cookieMatch) {
      const cookieTab = decodeURIComponent(cookieMatch[1]);
      if (validTabIds.includes(cookieTab)) {
        console.log('[Social Tab] Using cookie:', cookieTab);
        return cookieTab;
      }
    }

    const candidateKeys = [STORAGE_KEY, LEGACY_STORAGE_KEY, LEGACY_STORAGE_KEY_2];

    // Priority 1: Check sessionStorage / localStorage. Storage is checked
    // before the URL hash on purpose - if anything else on the page resets
    // or overwrites window.location.hash (e.g. an outer page router), the
    // saved tab should still win rather than silently falling back to feed.
    for (const key of candidateKeys) {
      try {
        const savedTab = sessionStorage.getItem(key);
        if (savedTab && validTabIds.includes(savedTab)) {
          console.log('[Social Tab] Using sessionStorage:', savedTab);
          return savedTab;
        }
      } catch (error) {
        // Ignore storage access errors
      }
    }

    for (const key of candidateKeys) {
      try {
        const savedTab = localStorage.getItem(key);
        if (savedTab && validTabIds.includes(savedTab)) {
          console.log('[Social Tab] Using localStorage:', savedTab);
          return savedTab;
        }
      } catch (error) {
        // Ignore storage access errors
      }
    }

    // Priority 2: Fall back to the URL hash (e.g. a bookmarked #forums link)
    // only when nothing has been saved yet.
    const urlHash = window.location.hash.replace('#', '');
    if (urlHash && validTabIds.includes(urlHash)) {
      console.log('[Social Tab] Using URL hash:', urlHash);
      return urlHash;
    }

    console.log('[Social Tab] No saved tab found, using default: feed');
    return 'feed';
  }

  function getValidSocialTabId(tabId) {
    if (!tabId) return 'feed';
    return document.getElementById(tabId) ? tabId : 'feed';
  }

  function persistSocialTab(tabId) {
    const validTabIds = ['feed', 'forums', 'groups', 'projects'];
    const validTabId = validTabIds.includes(tabId) ? tabId : 'feed';

    try {
      document.cookie = 'engagement_social_tab=' + encodeURIComponent(validTabId) + '; max-age=604800; path=/hrms-capstone/modules/engagement/';
      sessionStorage.setItem(STORAGE_KEY, validTabId);
      sessionStorage.setItem(LEGACY_STORAGE_KEY, validTabId);
      sessionStorage.setItem(LEGACY_STORAGE_KEY_2, validTabId);
      localStorage.setItem(STORAGE_KEY, validTabId);
      localStorage.setItem(LEGACY_STORAGE_KEY, validTabId);
      localStorage.setItem(LEGACY_STORAGE_KEY_2, validTabId);
      console.log('[Social Tab] Persisted:', validTabId);
      
      // Also update URL hash
      const currentHash = window.location.hash.replace('#', '');
      if (currentHash !== validTabId) {
        window.history.replaceState({}, '', window.location.pathname + window.location.search + '#' + validTabId);
        console.log('[Social Tab] Updated URL hash to:', validTabId);
      }
    } catch (error) {
      console.warn('Unable to save active social tab.', error);
    }
  }

  // The collaboration tabs card starts hidden (inline style in the HTML)
  // so nothing flashes on screen before the correct tab is selected.
  function revealCollaborationTabsCard() {
    const card = document.getElementById('collaboration-tabs-card');
    if (card) {
      card.style.visibility = '';
    }
  }

  // Absolute safety net: never leave the card hidden for more than a moment,
  // no matter what happens during restore.
  setTimeout(revealCollaborationTabsCard, 2000);

  function restoreSavedTab() {
    const validTabIds = ['feed', 'forums', 'groups', 'projects'];
    const savedTab = getSavedSocialTabId();
    
    console.log('[Social Tab] Attempting to restore tab:', savedTab);

    const targetPane = document.getElementById(savedTab);
    const targetLink = document.querySelector('#collaboration-tabs a[aria-controls="' + savedTab + '"]');

    if (targetPane && targetLink) {
        console.log('[Social Tab] DOM ready, restoring tab:', savedTab);
        
        document.querySelectorAll('#collaboration-tabs .nav-link').forEach(function(link) {
          link.classList.remove('active');
          link.setAttribute('aria-selected', 'false');
        });

        document.querySelectorAll('.social-area .tab-pane').forEach(function(pane) {
          pane.classList.remove('show', 'active');
        });

        targetPane.classList.add('show', 'active');
        targetLink.classList.add('active');
        targetLink.setAttribute('aria-selected', 'true');
        
        // Persist the restored tab
        persistSocialTab(savedTab);
        revealCollaborationTabsCard();
        protectSavedSocialTab();
    } else {
      revealCollaborationTabsCard();
    }
  }

  function protectSavedSocialTab() {
    const tabs = document.getElementById('collaboration-tabs');
    if (!tabs || socialTabState.activeTabObserver) return;

    socialTabState.activeTabObserver = new MutationObserver(function() {
      const savedTab = getSavedSocialTabId();
      const activeTab = tabs.querySelector('.nav-link.active');
      const activeTabId = activeTab ? activeTab.getAttribute('aria-controls') : '';
      const activePane = document.getElementById(savedTab);

      if (savedTab !== activeTabId || !activePane || !activePane.classList.contains('active')) {
        restoreSavedTab();
      }
    });

    socialTabState.activeTabObserver.observe(tabs, {
      subtree: true,
      attributes: true,
      attributeFilter: ['class', 'aria-selected']
    });
  }

  function resetSocialTabObserver() {
    if (socialTabState.activeTabObserver) {
      socialTabState.activeTabObserver.disconnect();
      socialTabState.activeTabObserver = null;
    }
  }

  function switchSocialTab(tabLink, isInitialLoad) {
    if (!tabLink) return;

    const href = tabLink.getAttribute('href');
    if (!href || !href.startsWith('#')) return;

    const tabId = getValidSocialTabId(href.replace('#', ''));
    const targetPane = document.getElementById(tabId);
    if (!targetPane) return;

    const currentActiveTab = document.querySelector('#collaboration-tabs .nav-link.active');
    if (currentActiveTab && currentActiveTab.getAttribute('aria-controls') === tabId && targetPane.classList.contains('active')) {
      return;
    }

    persistSocialTab(tabId);

    if (!isInitialLoad) {
      const nextHash = '#' + tabId;
      if (window.location.hash !== nextHash) {
        window.history.replaceState({}, '', window.location.pathname + window.location.search + nextHash);
      }
    }

    const allTabs = document.querySelectorAll('#collaboration-tabs .nav-link');
    const allPanes = document.querySelectorAll('.social-area .tab-pane');

    allTabs.forEach(function(tab) {
      tab.classList.remove('active');
      tab.setAttribute('aria-selected', 'false');
    });

    allPanes.forEach(function(pane) {
      pane.classList.remove('show', 'active');
    });

    tabLink.classList.add('active');
    tabLink.setAttribute('aria-selected', 'true');
    targetPane.classList.add('show', 'active');

  }

  function activateSocialTabFromHash(isInitialLoad = false) {
    const urlHash = window.location.hash ? window.location.hash.replace('#', '') : '';
    const savedHash = getSavedSocialTabId();

    // Saved state takes priority over the URL hash for the same reason as
    // getSavedSocialTabId(): the hash can be reset by something else on the
    // page, but the saved tab reflects what the person actually chose.
    let hash = 'feed';
    if (savedHash && document.getElementById(savedHash)) {
      hash = savedHash;
    } else if (urlHash && document.getElementById(urlHash)) {
      hash = urlHash;
    }

    const targetTab = document.querySelector('#collaboration-tabs a[aria-controls="' + hash + '"]');
    if (!targetTab) return;

    const targetPane = document.getElementById(hash);
    if (!targetPane) return;

    const currentActiveTab = document.querySelector('#collaboration-tabs .nav-link.active');
    const currentHash = currentActiveTab ? currentActiveTab.getAttribute('aria-controls') : null;

    if (currentHash === hash && targetPane.classList.contains('active')) {
      return;
    }

    if (!isInitialLoad && window.location.hash !== '#' + hash) {
      const baseUrl = window.location.pathname + window.location.search;
      window.history.replaceState({}, '', baseUrl + '#' + hash);
    }

    switchSocialTab(targetTab, isInitialLoad);
  }

  function bindSocialTabEvents() {
    if (socialTabState.clickHandlersBound) return;
    socialTabState.clickHandlersBound = true;

    document.addEventListener('click', function(event) {
      const tabLink = event.target.closest('#collaboration-tabs a[data-toggle="tab"]');
      if (!tabLink) return;

      event.preventDefault();
      event.stopPropagation();

      const tabId = tabLink.getAttribute('aria-controls');
      if (!tabId) return;

      persistSocialTab(tabId);
      switchSocialTab(tabLink, false);
    }, true);

    if (typeof window.jQuery === 'function') {
      window.jQuery(document).on('shown.bs.tab', '#collaboration-tabs a[data-toggle="tab"]', function() {
        const tabId = this.getAttribute('aria-controls');
        if (tabId) {
          persistSocialTab(tabId);
        }
      });
    }

    window.addEventListener('hashchange', function() {
      if (document.getElementById('collaboration-tabs')) {
        activateSocialTabFromHash(false);
      }
    });

    window.addEventListener('pageshow', function() {
      if (document.getElementById('collaboration-tabs')) {
        restoreSavedTab();
        activateSocialTabFromHash(true);
      }
    });

    window.addEventListener('page:loaded', function(event) {
      if (event && event.detail && event.detail.page === 'social') {
        initializeForumModal();
        initializeProjectModal();
        resetSocialTabObserver();
        restoreSavedTab();
        activateSocialTabFromHash(true);
        setTimeout(loadSocialPageData, 50);
      }
    });
  }

  function initializeSocialTabs() {
    if (socialTabState.initialized) return;
    socialTabState.initialized = true;

    bindSocialTabEvents();
    restoreSavedTab();
    activateSocialTabFromHash(true);
  }

  function initializeGroupModal() {
    var modal = document.getElementById('createGroupModal');
    if (!modal || modal.dataset.groupModalBound === '1') return;
    modal.dataset.groupModalBound = '1';

    function openGroupModal() {
      if (window.bootstrap && window.bootstrap.Modal) {
        var instance = window.bootstrap.Modal.getInstance(modal) || new window.bootstrap.Modal(modal);
        instance.show();
        return;
      }

      modal.classList.add('show');
      modal.setAttribute('aria-hidden', 'false');
      document.body.classList.add('modal-open');
      var nameInput = document.getElementById('groupName');
      if (nameInput) nameInput.focus();
    }

    function closeGroupModal() {
      if (window.bootstrap && window.bootstrap.Modal) {
        var instance = window.bootstrap.Modal.getInstance(modal);
        if (instance) {
          instance.hide();
          return;
        }
      }

      modal.classList.remove('show');
      modal.setAttribute('aria-hidden', 'true');
      document.body.classList.remove('modal-open');
    }

    document.addEventListener('click', function(event) {
      var trigger = event.target.closest('[data-target="#createGroupModal"]');
      var toggleTrigger = event.target.closest('[data-toggle="modal"][data-target="#createGroupModal"]');
      if (trigger || toggleTrigger) {
        event.preventDefault();
        event.stopPropagation();
        openGroupModal();
        return;
      }

      if (event.target.closest('#createGroupModal [data-dismiss="modal"], #createGroupModal .close') || event.target === modal) {
        event.preventDefault();
        closeGroupModal();
      }
    }, true);

    document.addEventListener('keydown', function(event) {
      if (event.key === 'Escape' && modal.classList.contains('show')) closeGroupModal();
    });
  }

  function initializeForumModal() {
    var modal = document.getElementById('createForumModal');
    if (!modal || modal.dataset.forumModalBound === '1') return;
    modal.dataset.forumModalBound = '1';

    function closeModal() {
      if (window.bootstrap && window.bootstrap.Modal) {
        var instance = window.bootstrap.Modal.getInstance(modal);
        if (instance) {
          instance.hide();
          return;
        }
      }

      modal.classList.remove('show');
      modal.setAttribute('aria-hidden', 'true');
      document.body.classList.remove('modal-open');
    }

    document.addEventListener('click', function(event) {
      var openButton = event.target.closest('[data-target="#createForumModal"]');
      var toggleTrigger = event.target.closest('[data-toggle="modal"][data-target="#createForumModal"]');
      if (openButton || toggleTrigger) {
        event.preventDefault();
        event.stopPropagation();
        if (window.bootstrap && window.bootstrap.Modal) {
          var instance = window.bootstrap.Modal.getInstance(modal) || new window.bootstrap.Modal(modal);
          instance.show();
        } else {
          modal.classList.add('show');
          modal.setAttribute('aria-hidden', 'false');
          document.body.classList.add('modal-open');
        }
        var titleInput = document.getElementById('forumTitle');
        if (titleInput) titleInput.focus();
        return;
      }

      if (event.target.closest('#createForumModal [data-dismiss="modal"], #createForumModal .close')) {
        event.preventDefault();
        closeModal();
        return;
      }

      if (event.target === modal) closeModal();
    }, true);

    document.addEventListener('keydown', function(event) {
      if (event.key === 'Escape' && modal.classList.contains('show')) closeModal();
    });
  }

  function initializePostModal() {
    if (document.documentElement.dataset.postModalBound === '1') return;
    document.documentElement.dataset.postModalBound = '1';

    function closeModal() {
      closeSocialModal('createPostModal');
      var form = document.getElementById('createPostForm');
      if (form) form.reset();
      var statusBox = document.getElementById('postFormStatus');
      if (statusBox) statusBox.textContent = '';
    }

    document.addEventListener('click', function(event) {
      var modal = document.getElementById('createPostModal');
      var openButton = event.target.closest('[data-target="#createPostModal"]');
      var toggleTrigger = event.target.closest('[data-toggle="modal"][data-target="#createPostModal"]');
      if ((openButton || toggleTrigger) && modal) {
        event.preventDefault();
        event.stopPropagation();
        if (window.bootstrap && window.bootstrap.Modal) {
          var instance = window.bootstrap.Modal.getInstance(modal) || new window.bootstrap.Modal(modal);
          instance.show();
        } else {
          modal.classList.add('show');
          modal.setAttribute('aria-hidden', 'false');
          document.body.classList.add('modal-open');
        }
        var contentInput = document.getElementById('postContent');
        var statusBox = document.getElementById('postFormStatus');
        if (statusBox) statusBox.textContent = '';
        if (contentInput) contentInput.focus();
        return;
      }

      if (modal && (event.target.closest('#createPostModal [data-dismiss="modal"], #createPostModal .close') || event.target === modal)) {
        event.preventDefault();
        closeModal();
      }
    }, true);

    document.addEventListener('keydown', function(event) {
      var modal = document.getElementById('createPostModal');
      if (modal && event.key === 'Escape' && modal.classList.contains('show')) closeModal();
    });

    document.addEventListener('submit', function(event) {
      var form = event.target.closest('#createPostForm');
      if (!form) return;
      event.preventDefault();
      var contentInput = document.getElementById('postContent');
      var descriptionInput = document.getElementById('postDescription');
      var attachmentInput = document.getElementById('postAttachment');
      var statusBox = document.getElementById('postFormStatus');
      var submitButton = form.querySelector('button[type="submit"]');
      var content = contentInput ? contentInput.value.trim() : '';
      var description = descriptionInput ? descriptionInput.value.trim() : '';
      var attachment = attachmentInput && attachmentInput.files.length > 0 ? attachmentInput.files[0] : null;
      if (!content && !attachment) {
        if (statusBox) {
          statusBox.className = 'small mt-3 text-danger';
          statusBox.textContent = 'Write a post or choose an attachment first.';
        }
        return;
      }

      submitButton.disabled = true;
      submitButton.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i>Publishing...';
      if (statusBox) {
        statusBox.className = 'small mt-3 text-muted';
        statusBox.textContent = 'Saving post...';
      }
      var requestOptions;
      var requestUrl = SOCIAL_API_BASE + '?resource=social&action=post';
      if (attachment) {
        var formData = new FormData();
        formData.append('shared_file', attachment);
        formData.append('content', content);
        formData.append('description', description);
        requestUrl = SOCIAL_API_BASE + '?resource=file_sharing';
        requestOptions = { method: 'POST', body: formData };
      } else {
        requestOptions = {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ content: content, description: description })
        };
      }

      requestOptions.credentials = 'same-origin';
      fetch(requestUrl, requestOptions)
        .then(function(response) {
          return response.text().then(function(responseText) {
            var data;
            try {
              data = JSON.parse(responseText);
            } catch (parseError) {
              throw new Error('The server returned an invalid response.');
            }
            if (!response.ok || data.success === false) {
              throw new Error(data.message || data.error || 'Unable to publish post.');
            }
            return data;
          });
        })
        .then(function() {
          if (statusBox) {
            statusBox.className = 'small mt-3 text-success';
            statusBox.textContent = 'Post published successfully.';
          }
          closeModal();
          if (typeof window.fetchSocialFeed === 'function') {
            window.fetchSocialFeed();
          } else {
            window.location.reload();
          }
        })
        .catch(function(error) {
          if (statusBox) {
            statusBox.className = 'small mt-3 text-danger';
            statusBox.textContent = error.message || 'Unable to publish post.';
          }
        })
        .finally(function() {
          submitButton.disabled = false;
          submitButton.innerHTML = '<i class="fas fa-paper-plane mr-1"></i>Publish Post';
        });
    });
  }

  function initializeProjectModal() {
    var modal = document.getElementById('createProjectModal');
    if (!modal || modal.dataset.projectModalBound === '1') return;
    modal.dataset.projectModalBound = '1';
    function closeProjectModal() {
      if (window.bootstrap && window.bootstrap.Modal) {
        var instance = window.bootstrap.Modal.getInstance(modal);
        if (instance) {
          instance.hide();
          return;
        }
      }

      modal.classList.remove('show');
      modal.setAttribute('aria-hidden', 'true');
      document.body.classList.remove('modal-open');
    }
    document.addEventListener('click', function(event) {
      var openButton = event.target.closest('[data-target="#createProjectModal"]');
      var toggleTrigger = event.target.closest('[data-toggle="modal"][data-target="#createProjectModal"]');
      if (openButton || toggleTrigger) {
        event.preventDefault();
        event.stopPropagation();
        if (window.bootstrap && window.bootstrap.Modal) {
          var instance = window.bootstrap.Modal.getInstance(modal) || new window.bootstrap.Modal(modal);
          instance.show();
        } else {
          modal.classList.add('show');
          modal.setAttribute('aria-hidden', 'false');
          document.body.classList.add('modal-open');
        }
        var nameInput = document.getElementById('projectName');
        if (nameInput) nameInput.focus();
      } else if (event.target.closest('#createProjectModal [data-dismiss="modal"], #createProjectModal .close') || event.target === modal) {
        event.preventDefault();
        closeProjectModal();
      }
    }, true);
    document.addEventListener('keydown', function(event) { if (event.key === 'Escape' && modal.classList.contains('show')) closeProjectModal(); });
  }

  function initializeSocialFilters() {
    const searchInput = document.getElementById('social-global-search');
    const filterButtons = document.querySelectorAll('.social-filter-btn');
    const filterableItems = document.querySelectorAll('[data-social-item]');

    if (!searchInput || !filterButtons.length || !filterableItems.length) {
      return;
    }

    function applyFilters() {
      const query = (searchInput.value || '').trim().toLowerCase();
      const activeFilter = document.querySelector('.social-filter-btn.active')?.dataset.filter || 'all';

      filterableItems.forEach(function(item) {
        const itemType = item.dataset.socialItem || 'all';
        const itemText = (item.textContent || '').toLowerCase();
        const matchesQuery = !query || itemText.includes(query);
        const matchesType = activeFilter === 'all' || itemType === activeFilter;
        item.style.display = matchesQuery && matchesType ? '' : 'none';
      });
    }

    searchInput.addEventListener('input', applyFilters);
    filterButtons.forEach(function(button) {
      button.addEventListener('click', function() {
        filterButtons.forEach(function(btn) { btn.classList.toggle('active', btn === button); });
        applyFilters();
      });
    });

    applyFilters();
  }

  function initializeModerationBreakdownSort() {
    const table = document.querySelector('.moderation-breakdown-table');
    if (!table || table.dataset.sortInitialized === 'true') return;

    const body = table.querySelector('tbody');
    const buttons = table.querySelectorAll('.moderation-sort-btn');
    if (!body || !buttons.length) return;

    table.dataset.sortInitialized = 'true';
    buttons.forEach(function(button) {
      button.addEventListener('click', function() {
        const sortKey = button.dataset.sortKey || 'employee';
        const rows = Array.from(body.querySelectorAll('[data-employee-row]'));
        const numericSort = sortKey !== 'employee';
        const currentDirection = button.dataset.direction === 'asc' ? 'desc' : 'asc';

        buttons.forEach(function(otherButton) {
          otherButton.dataset.direction = '';
          otherButton.classList.remove('is-sorted');
          otherButton.removeAttribute('aria-sort');
        });
        button.dataset.direction = currentDirection;
        button.classList.add('is-sorted');
        button.setAttribute('aria-sort', currentDirection === 'asc' ? 'ascending' : 'descending');

        rows.sort(function(firstRow, secondRow) {
          const firstValue = firstRow.dataset[sortKey] || '';
          const secondValue = secondRow.dataset[sortKey] || '';
          const comparison = numericSort
            ? Number(firstValue) - Number(secondValue)
            : firstValue.localeCompare(secondValue);
          return currentDirection === 'asc' ? comparison : -comparison;
        });

        rows.forEach(function(row) { body.appendChild(row); });
      });
    });
  }

  function initializeModerationInsightsTriggers() {
    if (document.documentElement.dataset.moderationTriggersBound === '1') return;
    document.documentElement.dataset.moderationTriggersBound = '1';

    function toggleInsights(trigger) {
      if (!trigger || trigger.dataset.disabled === 'true') return;
      const details = document.getElementById(trigger.dataset.insightsTarget || '');
      if (!details) return;
      const insightsFilter = trigger.dataset.insightsFilter || 'all';
      const sameFilter = details.dataset.activeFilter === insightsFilter;
      const willOpen = details.hidden || !sameFilter;
      details.querySelectorAll('[data-insights-section]').forEach(function(section) {
        section.hidden = insightsFilter !== 'all' && section.dataset.insightsSection !== insightsFilter;
      });
      details.hidden = !willOpen;
      details.dataset.activeFilter = insightsFilter;
      document.querySelectorAll('.moderation-insights-trigger').forEach(function(otherTrigger) {
        otherTrigger.setAttribute('aria-expanded', otherTrigger === trigger && willOpen ? 'true' : 'false');
      });
    }

    document.addEventListener('click', function(event) {
      var trigger = event.target.closest('.moderation-insights-trigger');
      if (trigger) toggleInsights(trigger);
    });

    document.addEventListener('keydown', function(event) {
      var trigger = event.target.closest('.moderation-insights-trigger');
      if (!trigger || (event.key !== 'Enter' && event.key !== ' ')) return;
      event.preventDefault();
      toggleInsights(trigger);
    });
  }

  function initializeResolvePostActions() {
    if (document.documentElement.dataset.resolvePostBound === '1') return;
    document.documentElement.dataset.resolvePostBound = '1';
    document.addEventListener('click', function(event) {
      var button = event.target.closest('.resolve-post-btn');
      if (!button || button.disabled) return;
      var postId = button.dataset.postId;
      if (!postId) return;

      button.disabled = true;
      button.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i>Saving...';
      fetch(SOCIAL_API_BASE + '?resource=social&action=resolve', {
        method: 'POST',
        credentials: 'same-origin',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ post_id: postId })
      })
        .then(function(response) {
          return response.json().then(function(data) {
            if (!response.ok || data.success === false) {
              throw new Error(data.message || data.error || 'Unable to resolve post.');
            }
            return data;
          });
        })
        .then(function() {
          window.location.reload();
        })
        .catch(function(error) {
          button.disabled = false;
          button.innerHTML = '<i class="fas fa-check mr-1"></i>Mark resolved';
          window.alert(error.message || 'Unable to resolve post.');
        });
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', function() {
      initializeSocialTabs();
      initializeGroupModal();
      initializeForumModal();
      initializePostModal();
      initializeProjectModal();
      initializeSocialFilters();
      initializeModerationBreakdownSort();
      initializeModerationInsightsTriggers();
      initializeResolvePostActions();
    }, { once: true });
  } else {
    initializeSocialTabs();
    initializeGroupModal();
    initializeForumModal();
    initializePostModal();
    initializeProjectModal();
    initializeSocialFilters();
    initializeModerationBreakdownSort();
    initializeModerationInsightsTriggers();
    initializeResolvePostActions();
  }

  function initializeSocialFeed() {
    // Save tab when clicked
    document.addEventListener('click', function(e) {
      const tabLink = e.target.closest('#collaboration-tabs a[data-toggle="tab"]');
      if (!tabLink) return;
      
      const tabId = tabLink.getAttribute('aria-controls');
      if (tabId) {
        persistSocialTab(tabId);
      }
    }, true);
    
    // Also listen for Bootstrap tab events if available
    if (typeof window.jQuery === 'function') {
      window.jQuery(document).on('shown.bs.tab', '#collaboration-tabs a[data-toggle="tab"]', function() {
        const tabId = this.getAttribute('aria-controls');
        if (tabId) {
          persistSocialTab(tabId);
        }
      });
    }

    const socialFeed = document.getElementById('social-feed');
    if (!socialFeed) return;
    if (socialFeed.dataset.socialInlineBound === '1' || socialFeed.dataset.socialScriptBound === '1') return;
    socialFeed.dataset.socialScriptBound = '1';

    const canReply = socialFeed.dataset.canReply === 'true';
    const employeeId = socialFeed.dataset.employeeId || null;
    var socialPosts = [];
    var sharedFilesData = [];

    function bindCommentForms() {
      const commentForms = document.querySelectorAll('.comment-form');
      commentForms.forEach(function(form) {
        if (form.dataset.bound === 'true') return;
        form.dataset.bound = 'true';
        form.addEventListener('submit', function(event) {
          event.preventDefault();
          event.stopImmediatePropagation();

          const button = form.querySelector('button[type=submit]');
          const textarea = form.querySelector('textarea[name="comment"], textarea[name="content"]');
          const postIdInput = form.querySelector('input[name="post_id"]');
          const commentIdInput = form.querySelector('input[name="comment_id"]');
          const parentReplyIdInput = form.querySelector('input[name="parent_reply_id"]');

          if (!textarea || !postIdInput) {
            return;
          }

          const postId = postIdInput.value;
          const payload = {
            post_id: postId,
            content: textarea.value.trim()
          };
          let url = SOCIAL_API_BASE + '?resource=social&action=comment';

          if (commentIdInput) {
            const commentId = commentIdInput.value || form.dataset.commentId || '';
            if (!commentId) {
              if (button) button.disabled = false;
              return;
            }
            commentIdInput.value = commentId;
            payload.comment_id = commentId;
            payload.content = textarea.value.trim();
            if (parentReplyIdInput && parentReplyIdInput.value) {
              payload.parent_reply_id = parentReplyIdInput.value;
            }
            url = SOCIAL_API_BASE + '?resource=reply&action=add';
          } else {
            payload.comment = textarea.value.trim();
          }

          if (!textarea.value.trim()) {
            alert('Please enter a message.');
            return;
          }

          if (button) {
            button.textContent = 'Posting...';
            button.disabled = true;
          }

          fetch(url, {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json'
            },
            body: JSON.stringify(payload)
          })
            .then(function(response) {
              return response.json();
            })
            .then(function(data) {
              if (data.success || data.id) {
                textarea.value = '';
                fetchSocialFeed();
              } else {
                alert(data.error || data.message || 'Failed to post.');
              }
            })
            .catch(function() {
              alert('Failed to post.');
            })
            .finally(function() {
              if (button) {
                var resetLabel = form.querySelector('input[name="comment_id"]') ? 'Post Reply' : 'Comment';
                button.textContent = resetLabel;
                button.disabled = false;
              }
            });
        });
      });
    }

    function setFeedHtml(html) {
      socialFeed.innerHTML = html;
      bindCommentForms();
    }

    function renderCommentReactionButtons(targetType, targetId, counts) {
      counts = counts || {};
      return '<div class="comment-reaction-buttons mt-1" data-target-type="' + targetType + '" data-target-id="' + targetId + '">' +
        '<button type="button" class="btn btn-sm btn-link p-0 mr-2 comment-react-btn" data-target-type="' + targetType + '" data-target-id="' + targetId + '" data-reaction="like" title="Like" aria-label="Like"><i class="fas fa-thumbs-up"></i> <span>' + (parseInt(counts.like, 10) || 0) + '</span></button>' +
        '<button type="button" class="btn btn-sm btn-link p-0 mr-2 comment-react-btn text-danger" data-target-type="' + targetType + '" data-target-id="' + targetId + '" data-reaction="heart" title="Heart" aria-label="Heart"><i class="fas fa-heart"></i> <span>' + (parseInt(counts.heart, 10) || 0) + '</span></button>' +
        '<button type="button" class="btn btn-sm btn-link p-0 mr-2 comment-react-btn text-warning" data-target-type="' + targetType + '" data-target-id="' + targetId + '" data-reaction="wow" title="Wow" aria-label="Wow"><i class="fas fa-star"></i> <span>' + (parseInt(counts.wow, 10) || 0) + '</span></button>' +
        '<button type="button" class="btn btn-sm btn-link p-0 comment-react-btn text-danger" data-target-type="' + targetType + '" data-target-id="' + targetId + '" data-reaction="angry" title="Angry" aria-label="Angry"><i class="fas fa-angry"></i> <span>' + (parseInt(counts.angry, 10) || 0) + '</span></button>' +
        '</div>';
    }

    function createPostCard(post) {
      var postId = post.eer_social_post_id || post.id || post.post_id || '';
      postId = postId ? escapeHtml(postId) : '';
      var employeeName = post.author_name ? escapeHtml(post.author_name) : 'Unknown';
      var content = post.content ? escapeHtml(post.content) : '';
      var description = post.description ? escapeHtml(post.description) : '';
      var createdAt = post.created_at ? escapeHtml(post.created_at) : '';
      var commentsHtml = '<p class="text-muted font-italic small mb-0">No comments yet.</p>';

      function renderFileAttachment(attachment) {
        var fileIcon = getFileIcon(attachment.file_type);
        var fileSize = formatFileSize(attachment.file_size);
        var uploaderName = attachment.uploader_name || attachment.author_name || 'Unknown';
        uploaderName = escapeHtml(uploaderName);
        var descriptionText = attachment.description ? escapeHtml(attachment.description) : 'No description provided.';
        var fileId = attachment.eer_social_post_id || attachment.id || '';
        var downloadUrl = fileId ? 'download.php?id=' + fileId : '#';

        return '<div class="shared-file-attachment mb-3 p-3 bg-light rounded-lg border">' +
          '<div class="shared-file-attachment-header mb-2">' +
            '<div class="shared-file-name-wrap">' +
              '<h6 class="shared-file-name mb-1"><span class="shared-file-type mr-2">FILE</span>' + escapeHtml(attachment.file_name) + '</h6>' +
              '<span class="badge badge-pill badge-info shared-file-badge">Shared File</span>' +
            '</div>' +
            '<a href="' + downloadUrl + '" class="btn btn-sm btn-outline-primary shared-file-download" download>Download</a>' +
          '</div>' +
          '<p class="mb-2 small text-muted">' + descriptionText + '</p>' +
          '<div class="d-flex justify-content-between small text-muted">' +
            '<span>Uploaded by ' + uploaderName + '</span>' +
            '<span>' + escapeHtml(attachment.created_at || '') + '</span>' +
          '</div>' +
        '</div>';
      }

      var fileSection = '';
      if (Array.isArray(post.file_attachments) && post.file_attachments.length > 0) {
        fileSection = post.file_attachments.map(renderFileAttachment).join('');
      } else if (post.file_attachment) {
        fileSection = renderFileAttachment(post.file_attachment);
      } else if (post.file_name && post.file_path) {
        fileSection = renderFileAttachment(post);
      }

      var likeCount = post.like_count ? parseInt(post.like_count, 10) : 0;
      var heartCount = post.heart_count ? parseInt(post.heart_count, 10) : 0;
      var wowCount = post.wow_count ? parseInt(post.wow_count, 10) : 0;
      var angryCount = post.angry_count ? parseInt(post.angry_count, 10) : 0;

      var reactionHtml = (likeCount > 0 ? '<i class="fas fa-thumbs-up text-primary mr-1"></i>' + likeCount + ' ' : '') +
            (heartCount > 0 ? '<i class="fas fa-heart text-danger mr-1"></i>' + heartCount + ' ' : '') +
            (wowCount > 0 ? '<i class="fas fa-star text-warning mr-1"></i>' + wowCount + ' ' : '') +
            (angryCount > 0 ? '<i class="fas fa-angry text-danger mr-1"></i>' + angryCount + ' ' : '');
      
      if (!reactionHtml.trim()) {
        reactionHtml = '<span class="text-muted">No reactions yet</span>';
      }

      if (Array.isArray(post.comments) && post.comments.length > 0) {
        commentsHtml = '<div class="comments-list">' +
          post.comments.map(function(comment) {
            var commenter = comment.author_name ? escapeHtml(comment.author_name) : 'Unknown';
            var commentText = comment.comment ? escapeHtml(comment.comment) : '';
            var commentTime = comment.created_at ? escapeHtml(comment.created_at) : '';
            var commentId = comment.eer_comment_id || comment.comment_id || comment.id || '';
            var repliesHtml = '';
            var commentCounts = comment.reaction_counts || {};

            if (Array.isArray(comment.replies) && comment.replies.length > 0) {
              function renderReplyTree(reply) {
                var replier = reply.author_name ? escapeHtml(reply.author_name) : 'Unknown';
                var replyText = reply.content ? escapeHtml(reply.content) : '';
                var replyTime = reply.created_at ? escapeHtml(reply.created_at) : '';
                var replyId = reply.eer_reply_id || reply.reply_id || reply.id || '';
                var nestedReplies = comment.replies.filter(function(childReply) {
                  var parentId = childReply.parent_reply_id;
                  return replyId && parentId !== null && parentId !== undefined && String(parentId) === String(replyId);
                });
                var nestedHtml = nestedReplies.length
                  ? '<div class="nested-replies mt-2 ml-3 pl-3 border-left">' + nestedReplies.map(renderReplyTree).join('') + '</div>'
                  : '';
                var nestedReplyControls = replyId ?
                  '<button type="button" class="btn btn-sm reply-action" data-comment-id="' + commentId + '" data-post-id="' + postId + '" data-parent-reply-id="' + replyId + '">Reply</button>' +
                  '<form method="POST" class="comment-form reply-form nested-reply-form mt-2 p-2 bg-light rounded d-none" data-skip="true" style="display: none;" data-comment-id="' + commentId + '" data-post-id="' + postId + '" data-parent-reply-id="' + replyId + '">' +
                    '<input type="hidden" name="comment_id" value="' + commentId + '">' +
                    '<input type="hidden" name="post_id" value="' + postId + '">' +
                    '<input type="hidden" name="parent_reply_id" value="' + replyId + '">' +
                    '<textarea name="content" class="form-control form-control-sm mt-2" rows="2" placeholder="Write a reply to this reply..." required></textarea>' +
                    '<button type="submit" class="btn btn-sm btn-primary mt-2">Post Reply</button>' +
                    '<button type="button" class="btn btn-sm btn-secondary mt-2 cancel-reply" style="margin-left: 0.5rem;">Cancel</button>' +
                  '</form>' : '';
                return '<div class="reply-item mb-2"><strong class="small">' + replier + ':</strong> <span class="small">' + replyText + '</span> <small class="text-muted d-block">' + replyTime + '</small>' + renderCommentReactionButtons('reply', replyId, reply.reaction_counts || {}) + '<div class="reply-actions mt-1">' + nestedReplyControls + '</div>' + nestedHtml + '</div>';
              }

              var rootReplies = comment.replies.filter(function(reply) {
                return reply.parent_reply_id === null || reply.parent_reply_id === undefined || reply.parent_reply_id === '' || String(reply.parent_reply_id) === '0';
              });
              repliesHtml = '<div class="replies mt-2 ml-4 border-left pl-2">' + rootReplies.map(renderReplyTree).join('') + '</div>';
            }

            var replyControls = commentId ?
              '<div class="reply-actions mt-2">' +
                '<button type="button" class="btn btn-sm reply-action" data-comment-id="' + commentId + '" data-post-id="' + postId + '" data-parent-reply-id="">Reply</button>' +
              '</div>' +
              '<form method="POST" class="comment-form reply-form mt-2 p-2 bg-light rounded d-none" data-skip="true" style="display: none;" data-comment-id="' + commentId + '" data-post-id="' + postId + '" data-parent-reply-id="">' +
                '<input type="hidden" name="comment_id" value="' + commentId + '">' +
                '<input type="hidden" name="post_id" value="' + postId + '">' +
                '<div class="form-group mb-2">' +
                  '<textarea name="content" class="form-control form-control-sm" rows="2" placeholder="Write your reply..." required></textarea>' +
                '</div>' +
                '<button type="submit" class="btn btn-sm btn-primary">Post Reply</button>' +
                '<button type="button" class="btn btn-sm btn-secondary ms-2 cancel-reply" style="margin-left: 0.5rem;">Cancel</button>' +
              '</form>' : '';

            return '<div class="comment-item mb-3 pb-2 border-bottom">' +
              '<div><strong class="small">' + commenter + ':</strong> <span class="small">' + commentText + '</span> </div>' +
              '<small class="text-muted d-block mb-2">' + commentTime + '</small>' +
              renderCommentReactionButtons('comment', commentId, commentCounts) +
              repliesHtml +
              replyControls +
              '</div>';
          }).join('') +
          '</div>';
      }

      var replySection = '<form method="POST" class="comment-form mt-3" data-skip="true">' +
        '<input type="hidden" name="post_id" value="' + postId + '">' +
        '<div class="form-group mb-2">' +
          '<textarea name="comment" class="form-control form-control-sm" rows="2" placeholder="Write a comment..." required></textarea>' +
        '</div>' +
        '<button type="submit" class="btn btn-sm btn-primary">Comment</button>' +
        '</form>';

      var reactionButtons = '<div class="reaction-buttons mt-3 d-flex gap-2">' +
        '<button type="button" class="btn btn-sm btn-outline-primary react-btn" data-post-id="' + postId + '" data-reaction="like" title="Like" aria-label="Like"><i class="fas fa-thumbs-up"></i> <span class="reaction-count" data-reaction="like">' + likeCount + '</span></button>' +
        '<button type="button" class="btn btn-sm btn-outline-danger react-btn" data-post-id="' + postId + '" data-reaction="heart" title="Heart" aria-label="Heart"><i class="fas fa-heart"></i> <span class="reaction-count" data-reaction="heart">' + heartCount + '</span></button>' +
        '<button type="button" class="btn btn-sm btn-outline-warning react-btn" data-post-id="' + postId + '" data-reaction="wow" title="Wow" aria-label="Wow"><i class="fas fa-star"></i> <span class="reaction-count" data-reaction="wow">' + wowCount + '</span></button>' +
        '<button type="button" class="btn btn-sm btn-outline-danger react-btn" data-post-id="' + postId + '" data-reaction="angry" title="Angry" aria-label="Angry"><i class="fas fa-angry"></i> <span class="reaction-count" data-reaction="angry">' + angryCount + '</span></button>' +
        '</div>';

      return '<div class="card mb-3 social-post-card" style="border-left: 4px solid #007bff;">' +
        '<div class="card-body">' +
          '<div class="post-header d-flex justify-content-between align-items-start mb-3">' +
            '<div>' +
              '<h6 class="card-title mb-1" style="font-weight: 600;">' + employeeName + '</h6>' +
              '<small class="text-muted">' + createdAt + '</small>' +
            '</div>' +
          '</div>' +
          '<p class="card-text mb-3 lh-relaxed">' + content + '</p>' +
          (description ? '<p class="card-text text-muted small mb-3"><strong>Description:</strong> ' + description + '</p>' : '') +
          fileSection +
          '<div class="reaction-summary border-top border-bottom py-2 px-0 mb-3">' +
            '<small class="text-muted">' + reactionHtml + '</small>' +
          '</div>' +
          reactionButtons +
          '<div class="mt-3">' +
            '<div class="comments-section">' + commentsHtml + '</div>' +
            replySection +
          '</div>' +
        '</div>' +
        '</div>';
    }

    function createSharedFileCard(file) {
      var fileId = file.eer_social_post_id || file.eer_shared_file_id || file.id || file.file_id || '';
      fileId = fileId ? fileId : '';  // Don't escape - we need the raw ID for download link
      var fileIcon = getFileIcon(file.file_type);
      var fileSize = formatFileSize(file.file_size);
      var uploaderName = file.uploader_name || 'Unknown';
      var description = file.description || '';
      var content = file.content ? String(file.content).trim() : '';
      var createdAt = file.created_at || '';

      var contentHtml = content ? '<p class="card-text mb-3">' + escapeHtml(content) + '</p>' : '';
      var descriptionHtml = '';
      if (description && description !== content) {
        descriptionHtml = '<p class="card-text text-clamp-3">' + escapeHtml(description) + '</p>';
      } else if (!content) {
        descriptionHtml = '<p class="card-text text-clamp-3">No description provided.</p>';
      }

      var likeCount = file.like_count ? parseInt(file.like_count, 10) : 0;
      var heartCount = file.heart_count ? parseInt(file.heart_count, 10) : 0;
      var wowCount = file.wow_count ? parseInt(file.wow_count, 10) : 0;
      var angryCount = file.angry_count ? parseInt(file.angry_count, 10) : 0;

      var reactionHtml = (likeCount > 0 ? '<i class="fas fa-thumbs-up text-primary mr-1"></i>' + likeCount + ' ' : '') +
            (heartCount > 0 ? '<i class="fas fa-heart text-danger mr-1"></i>' + heartCount + ' ' : '') +
            (wowCount > 0 ? '<i class="fas fa-star text-warning mr-1"></i>' + wowCount + ' ' : '');
      
      if (!reactionHtml.trim()) {
        reactionHtml = '<span class="text-muted">No reactions yet</span>';
      }

      var reactionButtons = '<div class="reaction-buttons mt-2 d-flex gap-2">' +
        '<button type="button" class="btn btn-sm btn-outline-primary react-btn" data-post-id="' + fileId + '" data-reaction="like" title="Like" aria-label="Like"><i class="fas fa-thumbs-up"></i> <span class="reaction-count" data-reaction="like">' + likeCount + '</span></button>' +
        '<button type="button" class="btn btn-sm btn-outline-danger react-btn" data-post-id="' + fileId + '" data-reaction="heart" title="Heart" aria-label="Heart"><i class="fas fa-heart"></i> <span class="reaction-count" data-reaction="heart">' + heartCount + '</span></button>' +
        '<button type="button" class="btn btn-sm btn-outline-warning react-btn" data-post-id="' + fileId + '" data-reaction="wow" title="Wow" aria-label="Wow"><i class="fas fa-star"></i> <span class="reaction-count" data-reaction="wow">' + wowCount + '</span></button>' +
        '<button type="button" class="btn btn-sm btn-outline-danger react-btn" data-post-id="' + fileId + '" data-reaction="angry" title="Angry" aria-label="Angry"><i class="fas fa-angry"></i> <span class="reaction-count" data-reaction="angry">' + angryCount + '</span></button>' +
        '</div>';

      var commentHtml = '<form method="POST" class="comment-form mt-2">' +
        '<input type="hidden" name="post_id" value="' + fileId + '">' +
        '<div class="form-group mb-2">' +
          '<textarea name="comment" class="form-control form-control-sm" rows="2" placeholder="Write a comment..." required></textarea>' +
        '</div>' +
        '<button type="submit" class="btn btn-sm btn-primary">Comment</button>' +
        '</form>';

      return '<div class="card mb-3 shared-file-card" style="border-left: 4px solid #17a2b8;"><div class="card-body">' +
        '<div class="post-header mb-2 align-items-start">' +
          '<div>' +
            '<h5 class="card-title mb-1"><i class="' + fileIcon + ' mr-2 text-primary"></i>' + escapeHtml(file.file_name) + '</h5>' +
            '<span class="badge badge-pill badge-info shared-file-badge">Shared File</span>' +
          '</div>' +
          '<div class="post-timestamp text-muted small">' + escapeHtml(createdAt) + '</div>' +
        '</div>' +
        contentHtml +
        descriptionHtml +
        '<div class="d-flex flex-wrap justify-content-between align-items-center mt-3 gap-2">' +
          '<div class="text-muted small">Uploaded by ' + escapeHtml(uploaderName) + ' • ' + fileSize + '</div>' +
          '<a href="download.php?id=' + fileId + '" class="btn btn-sm btn-outline-primary" download><i class="fas fa-download"></i> Download</a>' +
        '</div>' +
        '<div class="reaction-summary border-top border-bottom py-2 px-0 mt-3 mb-3">' +
          '<small class="text-muted">' + reactionHtml + '</small>' +
        '</div>' +
        reactionButtons +
        commentHtml +
        '</div></div>';
    }

    function renderCombinedFeed() {
      var items = [];
      var mergedPosts = Array.isArray(socialPosts) ? socialPosts.slice() : [];
      mergedPosts = mergedPosts.filter(function(post) {
        return !post.item_type || post.item_type === 'post';
      });

      if (Array.isArray(sharedFilesData) && sharedFilesData.length > 0) {
        sharedFilesData.forEach(function(file) {
          var matchedPost = null;
          
          // Try to find a matching post
          for (var i = 0; i < mergedPosts.length; i++) {
            var post = mergedPosts[i];
            if (!post.created_at || !file.created_at) continue;

            // Check author match
            var postUserId = String(post.user_id || post.employee_id || '').trim();
            var fileUserId = String(file.user_id || file.employee_id || '').trim();
            var postUserType = String(post.user_type || 'user').trim().toLowerCase();
            var fileUserType = String(file.user_type || 'user').trim().toLowerCase();
            
            var sameAuthorId = postUserId && fileUserId && postUserId === fileUserId;
            var sameAuthorName = false;
            if (!sameAuthorId) {
              var postAuthorName = String(post.author_name || post.uploader_name || '').trim().toLowerCase();
              var fileAuthorName = String(file.uploader_name || file.author_name || '').trim().toLowerCase();
              sameAuthorName = postAuthorName && fileAuthorName && postAuthorName === fileAuthorName;
            }
            
            if (!sameAuthorId && !sameAuthorName) continue;

            var sameContent = String(post.content || '').trim() !== ''
              && String(post.content || '').trim() === String(file.content || '').trim();

            if (sameContent) {
              matchedPost = post;
              break;
            }

            // Check time window for file-only descriptions without matching content.
            var postTime = new Date(post.created_at).getTime();
            var fileTime = new Date(file.created_at).getTime();
            var timeDiff = Math.abs(postTime - fileTime);
            if (timeDiff > 90000) continue;

            matchedPost = post;
            break;
          }

          if (matchedPost) {
            matchedPost.file_attachments = matchedPost.file_attachments || [];
            matchedPost.file_attachments.push(file);
            file.__merged = true;
          } else {
            file.__feed_type = 'file';
            items.push(file);
          }
        });
      }

      if (mergedPosts.length > 0) {
        mergedPosts.forEach(function(post) {
          post.__feed_type = 'post';
          items.push(post);
        });
      }

      if (items.length === 0) {
        setFeedHtml('<p class="text-muted">No posts or shared files yet.</p>');
        return;
      }

      items.sort(function(a, b) {
        var dateA = new Date(a.created_at || a.uploaded_at || 0).getTime();
        var dateB = new Date(b.created_at || b.uploaded_at || 0).getTime();
        if (dateA !== dateB) {
          return dateB - dateA;
        }
        if (a.__feed_type === b.__feed_type) {
          return 0;
        }
        return a.__feed_type === 'file' ? -1 : 1;
      });

      var html = items.map(function(item) {
        return item.__feed_type === 'file' ? createSharedFileCard(item) : createPostCard(item);
      }).join('');

      setFeedHtml(html);
      updateAnalytics(socialPosts);
    }

    function updateReactionCount(postId, reactionType, increment) {
      var reactionButton = document.querySelector('.react-btn[data-post-id="' + postId + '"][data-reaction="' + reactionType + '"]');
      if (!reactionButton) {
        fetchSocialFeed();
        return;
      }
      var reactionCountSpan = reactionButton.querySelector('.reaction-count[data-reaction="' + reactionType + '"]');
      if (!reactionCountSpan) {
        fetchSocialFeed();
        return;
      }
      var currentCount = parseInt(reactionCountSpan.textContent, 10) || 0;
      reactionCountSpan.textContent = Math.max(0, currentCount + (increment ? 1 : -1));
    }

    socialFeed.addEventListener('click', function(event) {
      // Handle Reply button click
      var replyToggle = event.target.closest('.reply-action');
      if (replyToggle) {
        event.preventDefault();
        var commentId = replyToggle.getAttribute('data-comment-id');
        var postId = replyToggle.getAttribute('data-post-id');
        var parentReplyId = replyToggle.getAttribute('data-parent-reply-id') || '';
        var form = Array.from(socialFeed.querySelectorAll('.reply-form[data-comment-id="' + commentId + '"][data-post-id="' + postId + '"]')).find(function(candidate) {
          return (candidate.getAttribute('data-parent-reply-id') || '') === parentReplyId;
        });
        if (form) {
          var shouldShow = form.style.display === 'none';
          form.style.display = shouldShow ? '' : 'none';
          form.classList.toggle('d-none', !shouldShow);
          replyToggle.classList.toggle('is-open', shouldShow);
          replyToggle.innerHTML = shouldShow ? '<i class="fas fa-arrow-left" aria-hidden="true"></i>' : 'Reply';
          replyToggle.setAttribute('aria-label', shouldShow ? 'Back' : 'Reply');
          replyToggle.setAttribute('title', shouldShow ? 'Back' : 'Reply');
        }
        return;
      }

      // Handle Cancel button click
      var cancelBtn = event.target.closest('.cancel-reply');
      if (cancelBtn) {
        event.preventDefault();
        var form = cancelBtn.closest('.reply-form');
        if (form) {
          form.classList.add('d-none');
          form.style.display = 'none';
          var cancelCommentId = form.getAttribute('data-comment-id') || '';
          var cancelPostId = form.getAttribute('data-post-id') || '';
          var cancelParentReplyId = form.getAttribute('data-parent-reply-id') || '';
          var matchingReplyButton = Array.from(socialFeed.querySelectorAll('.reply-action[data-comment-id="' + cancelCommentId + '"][data-post-id="' + cancelPostId + '"]')).find(function(button) {
            return (button.getAttribute('data-parent-reply-id') || '') === cancelParentReplyId;
          });
          if (matchingReplyButton) {
            matchingReplyButton.classList.remove('is-open');
            matchingReplyButton.innerHTML = 'Reply';
            matchingReplyButton.setAttribute('aria-label', 'Reply');
            matchingReplyButton.setAttribute('title', 'Reply');
          }
        }
        return;
      }

      var commentReactionButton = event.target.closest('.comment-react-btn');
      if (commentReactionButton) {
        event.preventDefault();
        var commentReactionPayload = {
          post_id: '0',
          target_type: commentReactionButton.getAttribute('data-target-type'),
          target_id: commentReactionButton.getAttribute('data-target-id'),
          type: commentReactionButton.getAttribute('data-reaction')
        };
        if (employeeId) commentReactionPayload.employee_id = employeeId;

        fetch(SOCIAL_API_BASE + '?resource=reaction', {
          method: 'POST',
          headers: {'Content-Type': 'application/json'},
          body: JSON.stringify(commentReactionPayload)
        })
          .then(function(response) { return response.json(); })
          .then(function(data) {
            if (!data.success) throw new Error(data.message || 'Failed to react.');
            fetchSocialFeed();
          })
          .catch(function(error) { alert(error.message || 'Failed to send reaction.'); });
        return;
      }

      var button = event.target.closest('.react-btn');
      if (!button) return;

      var postId = button.getAttribute('data-post-id');
      var reactionType = button.getAttribute('data-reaction');

      var payload = { post_id: postId, type: reactionType };
      if (employeeId) {
        payload.employee_id = employeeId;
      }

      fetch(SOCIAL_API_BASE + '?resource=reaction', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json'
        },
        body: JSON.stringify(payload)
      })
        .then(function(response) {
          if (!response.ok) {
            throw new Error('Network response was not ok');
          }
          return response.text().then(function(text) {
            try {
              return JSON.parse(text);
            } catch (error) {
              throw new Error('Reaction server returned an invalid response.');
            }
          });
        })
        .then(function(data) {
          if (data.success) {
            fetchSocialFeed();
          } else {
            alert(data.message || 'Failed to react.');
          }
        })
        .catch(function(error) {
          alert(error.message || 'Failed to send reaction.');
        });
    });

    function fetchSocialFeed() {
      var feedPromise = fetch(SOCIAL_API_BASE + '?resource=social&action=feed', {
        method: 'GET',
        headers: {
          'Accept': 'application/json'
        }
      })
        .then(function(response) {
          if (!response.ok) {
            throw new Error('Failed to load social posts.');
          }
          return response.json();
        })
        .then(function(data) {
          if (data && data.success && Array.isArray(data.data)) {
            socialPosts = data.data;
          } else {
            throw new Error('Invalid social feed response.');
          }
        })
        .catch(function(error) {
          console.warn('Unable to refresh social feed:', error.message);
        });

      var filePromise = fetch(SOCIAL_API_BASE + '?resource=shared_files&action=list', {
        method: 'GET',
        headers: {
          'Accept': 'application/json'
        }
      })
        .then(function(response) {
          if (!response.ok) {
            throw new Error('Failed to load shared files.');
          }
          return response.json();
        })
        .then(function(data) {
          if (data && data.success && Array.isArray(data.data)) {
            sharedFilesData = data.data;
          } else {
            throw new Error('Invalid shared files response.');
          }
        })
        .catch(function(error) {
          console.warn('Unable to refresh shared files:', error.message);
        });

      Promise.all([feedPromise, filePromise])
        .then(function() {
          renderCombinedFeed();
        })
        .catch(function() {
          setFeedHtml('<p class="text-danger">Failed to load social feed.</p>');
          updateAnalytics([]);
        });
    }

    window.fetchSocialFeed = fetchSocialFeed;

    function computeSentimentSummary(posts) {
      if (!Array.isArray(posts) || posts.length === 0) {
        return { positive: 0, neutral: 0, negative: 0 };
      }

      var positiveWords = ['good','great','love','excellent','awesome','happy','nice','amazing'];
      var negativeWords = ['bad','sad','angry','terrible','hate','poor','worst','problem','putang','gago','tanga','bwisit','pangit','galit','inis','problema','ayaw'];

      var counts = { positive: 0, neutral: 0, negative: 0 };

      posts.forEach(function(post) {
        if (post.moderation_status === 'resolved') {
          counts.neutral++;
          return;
        }
        var content = (post.content || '') + ' ' + (Array.isArray(post.comments) ? post.comments.map(function(c){ return c.comment || ''; }).join(' ') : '');
        var text = content.toLowerCase();

        var foundPositive = positiveWords.some(function(word){ return text.indexOf(word) !== -1; });
        var foundNegative = negativeWords.some(function(word){ return text.indexOf(word) !== -1; });

        if (foundPositive && !foundNegative) {
          counts.positive++;
        } else if (foundNegative && !foundPositive) {
          counts.negative++;
        } else {
          counts.neutral++;
        }
      });

      return counts;
    }

    function updateAnalytics(posts) {
      var totalPosts = Array.isArray(posts) ? posts.length : 0;
      var totalComments = 0;
      var totalReactions = 0;

      if (Array.isArray(posts)) {
        posts.forEach(function(post) {
          totalComments += Array.isArray(post.comments) ? post.comments.length : 0;
          totalReactions += (parseInt(post.like_count,10)||0) + (parseInt(post.heart_count,10)||0) + (parseInt(post.wow_count,10)||0) + (parseInt(post.angry_count,10)||0);
        });
      }

      var sentiment = computeSentimentSummary(posts);
      var moderationCounts = {
        negative: sentiment.negative,
        positive: sentiment.positive,
        resolved: 0
      };

      moderationCounts.negative = 0;
      if (Array.isArray(posts)) {
        posts.forEach(function(post) {
          var moderationText = ((post.content || '') + ' ' + (Array.isArray(post.comments) ? post.comments.map(function(comment) { return comment.comment || ''; }).join(' ') : '')).toLowerCase();
          var hasNegative = ['bad','sad','angry','terrible','hate','poor','worst','problem','putang','gago','tanga','bwisit','pangit','galit','inis','problema','ayaw'].some(function(word) {
            return moderationText.indexOf(word) !== -1;
          });
          if (hasNegative) {
            if (post.moderation_status === 'resolved') {
              moderationCounts.resolved++;
            } else {
              moderationCounts.negative++;
            }
          }
        });
      }

      document.querySelectorAll('[data-moderation-count]').forEach(function(countElement) {
        var countType = countElement.dataset.moderationCount;
        countElement.textContent = moderationCounts[countType] || 0;
      });

      document.querySelectorAll('.moderation-insights-trigger').forEach(function(trigger) {
        var moderationType = trigger.dataset.moderationType || '';
        var moderationCount = moderationCounts[moderationType] || 0;
        var hasItems = moderationCount > 0;
        trigger.dataset.disabled = hasItems ? 'false' : 'true';
        trigger.setAttribute('aria-disabled', hasItems ? 'false' : 'true');
        trigger.setAttribute('tabindex', hasItems ? '0' : '-1');
        trigger.classList.toggle('is-disabled', !hasItems);
        if (!hasItems) {
          trigger.setAttribute('aria-expanded', 'false');
          var details = document.getElementById(trigger.dataset.insightsTarget || '');
          if (details && details.dataset.activeFilter === trigger.dataset.insightsFilter) {
            details.hidden = true;
          }
        }
      });

      var engagementHtml = '<div class="analytics-stat-grid">'
        + '<div class="analytics-stat"><strong>' + totalPosts + '</strong><span>Posts</span></div>'
        + '<div class="analytics-stat"><strong>' + totalComments + '</strong><span>Comments</span></div>'
        + '<div class="analytics-stat"><strong>' + totalReactions + '</strong><span>Reactions</span></div>'
        + '</div>';

      document.getElementById('engagement-analytics').innerHTML = engagementHtml;

      var sentimentHtml = '<div class="analytics-stat-grid">'
        + '<div class="analytics-stat analytics-stat-positive"><strong>' + sentiment.positive + '</strong><span>Positive</span></div>'
        + '<div class="analytics-stat analytics-stat-neutral"><strong>' + sentiment.neutral + '</strong><span>Neutral</span></div>'
        + '<div class="analytics-stat analytics-stat-negative"><strong>' + sentiment.negative + '</strong><span>Negative</span></div>'
        + '</div>';

      document.getElementById('sentiment-analysis').innerHTML = sentimentHtml;
    }

    function escapeHtml(text) {
      var span = document.createElement('span');
      span.textContent = text;
      return span.innerHTML;
    }

    function renderGroupMembers(groupId, members) {
      var wrapper = document.getElementById('group-members-' + groupId);
      var groupItem = wrapper ? wrapper.closest('.social-group-item') : document.querySelector('.social-group-item[data-group-id="' + groupId + '"]');
      var countLabel = groupItem ? groupItem.querySelector('.group-member-count') : null;
      if (countLabel) {
        countLabel.textContent = (Array.isArray(members) ? members.length : 0) + ' members';
      }

      if (groupItem) {
        groupItem.dataset.groupMembers = JSON.stringify(Array.isArray(members) ? members : []);
      }

      if (!wrapper) return;

      if (!Array.isArray(members) || members.length === 0) {
        wrapper.innerHTML = '<div class="group-member-empty">No members yet.</div>';
        return;
      }

      var items = members.map(function(member) {
        var text = 'Employee ID: ' + escapeHtml(member.employee_id || 'N/A');
        if (member.full_name) {
          text += ' - ' + escapeHtml(member.full_name);
        }
        return '<li class="list-group-item py-1">' + text + '</li>';
      }).join('');

      wrapper.innerHTML = items;
    }

    function renderGroupMembersModalList(members) {
      var membersList = document.getElementById('groupMembersModalList');
      if (!membersList) return;

      if (!Array.isArray(members) || members.length === 0) {
        membersList.innerHTML = '<div class="group-member-empty">No members yet.</div>';
        return;
      }

      membersList.innerHTML = members.map(function(member) {
        var memberName = member.full_name || ('Employee ID: ' + (member.employee_id || 'N/A'));
        return '<div class="group-member-row"><i class="fas fa-user mr-2"></i>' + escapeHtml(memberName) + '</div>';
      }).join('');
    }

    function refreshGroupMembers(groupId) {
      fetch(SOCIAL_API_BASE + '?resource=group_member&action=list&group_id=' + encodeURIComponent(groupId), {
        method: 'GET',
        headers: {
          'Accept': 'application/json'
        }
      })
        .then(function(response) {
          if (response.ok) {
            return response.json();
          }
          if (response.status === 404) {
            return { success: false, noMembers: true };
          }
          throw new Error('Failed to refresh group members.');
        })
        .then(function(data) {
          if (data.success && Array.isArray(data.data)) {
            renderGroupMembers(groupId, data.data);
          } else if (data.noMembers) {
            renderGroupMembers(groupId, []);
          }
        })
        .catch(function() {
          console.warn('Unable to refresh group members for group', groupId);
        });
    }

    var shareStatus = document.getElementById('share-status');
    var shareForm = document.querySelector('.share-form');

    function showShareStatus(message, type) {
      if (!shareStatus) return;
      shareStatus.innerHTML = '<div class="alert alert-' + type + '">' + message + '</div>';
    }

    function readApiResponse(response) {
      return response.text().then(function(text) {
        if (!text || !text.trim()) {
          throw new Error('Server returned an empty response.');
        }

        var data;
        try {
          data = JSON.parse(text);
        } catch (error) {
          var normalizedText = (text || '').toLowerCase();
          if (normalizedText.indexOf('<html') !== -1 || normalizedText.indexOf('<!doctype') !== -1) {
            throw new Error('Your session has expired or the request was blocked. Please log in again.');
          }
          throw new Error('Server returned an invalid response.');
        }

        if (!response.ok || data.success === false) {
          throw new Error((data && (data.error || data.message)) || 'Request failed.');
        }

        return data;
      });
    }

    if (shareForm) {
      shareForm.addEventListener('submit', function(event) {
        event.preventDefault();
        event.stopImmediatePropagation();
        if (!shareStatus) return;

        var contentInput = document.getElementById('content');
        var fileInput = document.getElementById('file-upload');
        var descriptionInput = document.getElementById('file-description');
        var content = contentInput ? contentInput.value.trim() : '';
        var description = descriptionInput ? descriptionInput.value.trim() : '';
        var file = fileInput && fileInput.files.length > 0 ? fileInput.files[0] : null;

        if (!content && !file) {
          showShareStatus('Please add a message or attach a file before sharing.', 'danger');
          return;
        }

        shareForm.querySelector('button[type="submit"]').textContent = 'Sharing...';
        shareForm.querySelector('button[type="submit"]').disabled = true;
        showShareStatus('Sending your update...', 'info');

        var postPromise = Promise.resolve({ success: true });
        var filePromise = Promise.resolve({ success: true });

        if (file) {
          if (!description && content) {
            description = content;
          }
          var formData = new FormData();
          formData.append('shared_file', file);
          if (description) {
            formData.append('description', description);
          }
          if (content) {
            formData.append('content', content);
          }
          filePromise = fetch(SOCIAL_API_BASE + '?resource=file_sharing', {
            method: 'POST',
            body: formData
          }).then(readApiResponse);
        } else if (content) {
          postPromise = fetch(SOCIAL_API_BASE + '?resource=social&action=post', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ content: content, description: description })
          }).then(readApiResponse);
        }

        Promise.all([postPromise, filePromise])
          .then(function(results) {
            var postResult = results[0];
            var fileResult = results[1];
            if ((postResult && !postResult.success) || (fileResult && !fileResult.success)) {
              var message = (postResult && postResult.message) || (fileResult && fileResult.message) || 'Failed to share update.';
              showShareStatus(message, 'danger');
              return;
            }

            showShareStatus('Your update has been shared.', 'success');
            if (contentInput) contentInput.value = '';
            if (fileInput) fileInput.value = '';
            if (descriptionInput) descriptionInput.value = '';
            fetchSocialFeed();
          })
          .catch(function(error) {
            showShareStatus(error.message || 'Unable to share your update.');
          })
          .finally(function() {
            if (shareForm) {
              var btn = shareForm.querySelector('button[type="submit"]');
              if (btn) {
                btn.textContent = 'Share';
                btn.disabled = false;
              }
            }
          });
      });
    }

    var groupMemberForm = document.getElementById('group-member-form');
    if (groupMemberForm) {
      groupMemberForm.addEventListener('submit', function(event) {
        event.preventDefault();

        var groupId = document.getElementById('group-id').value;
        var employeeId = document.getElementById('employee-id').value;

        if (!groupId || !employeeId) {
          alert('Please select a group and an employee.');
          return;
        }

        fetch(SOCIAL_API_BASE + '?resource=group_member', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json'
          },
          body: JSON.stringify({
            group_id: groupId,
            employee_id: employeeId
          })
        })
          .then(function(response) {
            return response.json().then(function(data) {
              if (!response.ok) {
                throw new Error(data.message || 'Failed to add member.');
              }
              return data;
            });
          })
          .then(function(data) {
            if (data.success) {
              refreshGroupMembers(groupId);
            } else {
              alert(data.message || 'Failed to add member.');
            }
          })
          .catch(function(error) {
            alert(error.message || 'Failed to add member.');
          });
      });
    }

    var createForumForm = document.getElementById('createForumForm');
    if (createForumForm) {
      createForumForm.addEventListener('submit', function(event) {
        event.preventDefault();
        var titleInput = document.getElementById('forumTitle');
        var descriptionInput = document.getElementById('forumDescription');
        var categoryInput = document.getElementById('forumCategory');
        var title = titleInput ? titleInput.value.trim() : '';
        var description = descriptionInput ? descriptionInput.value.trim() : '';
        var category = categoryInput ? categoryInput.value : '';

        if (!title || !description || !category) {
          alert('Please fill out all forum fields.');
          return;
        }

        var submitButton = createForumForm.querySelector('button[type="submit"]');
        if (submitButton) {
          submitButton.textContent = 'Creating...';
          submitButton.disabled = true;
        }

        fetch(new URL('../api/forum.php?action=create', engagementScript.src).href, {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ title: title, description: description, category: category }),
          credentials: 'same-origin'
        })
          .then(function(response) {
            return response.text().then(function(text) {
              var data = {};
              try { data = text ? JSON.parse(text) : {}; } catch (error) {
                throw new Error('Forum API returned an invalid response.');
              }
              if (!response.ok) throw new Error(data.message || data.error || 'Failed to create forum.');
              return data;
            });
          })
          .then(function(data) {
            if (data.success) {
              if (titleInput) titleInput.value = '';
              if (descriptionInput) descriptionInput.value = '';
              if (categoryInput) categoryInput.value = '';
              closeSocialModal('createForumModal');
              var forum = data.data || {title: title, description: description, category: category};
              var forumsList = document.getElementById('forums-list');
              if (forumsList) {
                var emptyState = forumsList.querySelector('.alert-info');
                if (emptyState) emptyState.remove();
                forumsList.insertAdjacentHTML('afterbegin', '<div class="card mb-3 forum-card"><div class="card-body"><div class="d-flex justify-content-between align-items-start mb-2"><div><h5 class="mb-1" style="font-weight:600;">' + escapeHtml(forum.title) + '</h5><p class="mb-1 text-muted">' + escapeHtml(forum.description) + '</p><small class="text-muted">Category: ' + escapeHtml(forum.category) + '</small></div></div><div class="d-flex justify-content-between text-muted small"><span>Created by: You</span><span>' + escapeHtml(forum.created_at || 'Just now') + '</span></div></div></div>');
              }
            } else {
              alert(data.message || 'Failed to create forum.');
            }
          })
          .catch(function(error) {
            alert(error.message || 'Unable to create forum.');
          })
          .finally(function() {
            if (submitButton) {
              submitButton.textContent = 'Create Forum';
              submitButton.disabled = false;
            }
          });
      });
    }

    var createProjectForm = document.getElementById('createProjectForm');
    if (createProjectForm) {
      var projectDeadlineInput = document.getElementById('projectDeadline');
      if (projectDeadlineInput) {
        var today = new Date();
        var maximumDeadlineDate = new Date(today.getTime() + (3 * 24 * 60 * 60 * 1000));
        var formatDateInputValue = function(date) {
          return date.getFullYear() + '-' + String(date.getMonth() + 1).padStart(2, '0') + '-' + String(date.getDate()).padStart(2, '0');
        };
        projectDeadlineInput.min = formatDateInputValue(today);
        projectDeadlineInput.max = formatDateInputValue(maximumDeadlineDate);
      }

      createProjectForm.addEventListener('submit', function(event) {
        event.preventDefault();
        var nameInput = document.getElementById('projectName');
        var descriptionInput = document.getElementById('projectDescription');
        var deadlineInput = document.getElementById('projectDeadline');
        var deadlineTimeInput = document.getElementById('projectDeadlineTime');
        var statusInput = document.getElementById('projectStatus');
        var name = nameInput ? nameInput.value.trim() : '';
        var description = descriptionInput ? descriptionInput.value.trim() : '';
        var deadline = deadlineInput ? deadlineInput.value : '';
        var deadlineTime = deadlineTimeInput ? deadlineTimeInput.value : '';
        var status = statusInput ? statusInput.value : '';

        if (!name || !description) {
          alert('Please provide a project name and description.');
          return;
        }

        if (!deadline || !deadlineTime) {
          alert('Please provide both a deadline date and time.');
          return;
        }

        var deadlineDate = new Date(deadline + 'T' + deadlineTime);
        var now = new Date();
        var todayDate = new Date(now.getFullYear(), now.getMonth(), now.getDate());
        var maximumDeadlineDate = new Date(todayDate.getTime() + (3 * 24 * 60 * 60 * 1000));
        var selectedDate = new Date(deadline + 'T00:00:00');
        if (Number.isNaN(deadlineDate.getTime()) || Number.isNaN(selectedDate.getTime())
          || deadlineDate < now || selectedDate < todayDate || selectedDate > maximumDeadlineDate) {
          alert('The deadline must be within the next 3 days.');
          return;
        }

        var submitButton = createProjectForm.querySelector('button[type="submit"]');
        if (submitButton) {
          submitButton.textContent = 'Creating...';
          submitButton.disabled = true;
        }

        fetch(SOCIAL_API_BASE + '?resource=project&action=create', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          credentials: 'same-origin',
          body: JSON.stringify({
            name: name,
            description: description,
            deadline: deadline,
            deadline_time: deadlineTime,
            status: status
          })
        })
          .then(function(response) {
            return response.text().then(function(text) {
              var data;
              try {
                data = text ? JSON.parse(text) : {};
              } catch (error) {
                throw new Error('The server returned an invalid response.');
              }
              if (!response.ok) {
                throw new Error(data.message || data.error || 'Failed to create project.');
              }
              return data;
            });
          })
          .then(function(data) {
            if (data.success) {
              if (nameInput) nameInput.value = '';
              if (descriptionInput) descriptionInput.value = '';
              if (deadlineInput) deadlineInput.value = '';
              if (deadlineTimeInput) deadlineTimeInput.value = '';
              if (statusInput) statusInput.value = 'planning';
              closeSocialModal('createProjectModal');

              var projectsList = document.querySelector('#projects-section .social-scroll-list');
              if (projectsList) {
                var emptyState = projectsList.querySelector('.text-muted.mb-0');
                if (emptyState) {
                  emptyState.remove();
                }

                var projectStatus = status && status.trim() ? status : 'planning';
                var newProjectHtml = '<div class="social-mini-item" data-social-item="project"><div class="social-mini-text"><h6>' + escapeHtml(name) + '</h6><small>' + escapeHtml(projectStatus) + '</small></div></div>';
                projectsList.innerHTML = newProjectHtml + projectsList.innerHTML;
              }
            } else {
              alert(data.message || 'Failed to create project.');
            }
          })
          .catch(function(error) {
            alert(error.message || 'Unable to create project.');
          })
          .finally(function() {
            if (submitButton) {
              submitButton.textContent = 'Create Project Space';
              submitButton.disabled = false;
            }
          });
      });
    }

    function fetchSharedFiles() {
      fetch(SOCIAL_API_BASE + '?resource=shared_files&action=list', {
        method: 'GET',
        headers: {
          'Accept': 'application/json'
        }
      })
        .then(function(response) {
          if (!response.ok) {
            throw new Error('Network response was not ok');
          }
          return response.json();
        })
        .then(function(data) {
          if (data && data.success && Array.isArray(data.data)) {
            renderSharedFiles(data.data);
          } else {
            document.getElementById('shared-files-list').innerHTML = '<p class="text-muted">No shared files yet.</p>';
          }
        })
        .catch(function() {
          document.getElementById('shared-files-list').innerHTML = '<p class="text-danger">Failed to load shared files.</p>';
        });
    }

    function renderSharedFiles(files) {
      if (!Array.isArray(files) || files.length === 0) {
        document.getElementById('shared-files-list').innerHTML = '<p class="text-muted">No shared files yet.</p>';
        return;
      }

      var html = '<div class="list-group">';
      files.forEach(function(file) {
        var fileIcon = getFileIcon(file.file_type);
        var fileSize = formatFileSize(file.file_size);
        var uploaderName = file.uploader_name || 'Unknown';
        var description = file.description || '';
        var createdAt = file.created_at || '';

        html += '<div class="list-group-item">'
          + '<div class="d-flex w-100 justify-content-between">'
          + '<div class="d-flex align-items-center">'
          + '<i class="' + fileIcon + ' mr-3 text-primary" style="font-size: 24px;"></i>'
          + '<div>'
          + '<h6 class="mb-1">' + escapeHtml(file.file_name) + '</h6>'
          + '<p class="mb-1 text-muted">' + escapeHtml(description) + '</p>'
          + '<small class="text-muted">Uploaded by ' + escapeHtml(uploaderName) + ' on ' + escapeHtml(createdAt) + ' • ' + fileSize + '</small>'
          + '</div>'
          + '</div>'
          + '<div class="d-flex align-items-center">'
          + '<a href="' + escapeHtml(file.file_path) + '" class="btn btn-sm btn-outline-primary" download><i class="fas fa-download"></i> Download</a>'
          + '</div>'
          + '</div>'
          + '</div>';
      });
      html += '</div>';

      document.getElementById('shared-files-list').innerHTML = html;
    }

    function getFileIcon(fileType) {
      var icons = {
        'pdf': 'fas fa-file-pdf',
        'doc': 'fas fa-file-word',
        'docx': 'fas fa-file-word',
        'xls': 'fas fa-file-excel',
        'xlsx': 'fas fa-file-excel',
        'txt': 'fas fa-file-alt',
        'jpg': 'fas fa-file-image',
        'jpeg': 'fas fa-file-image',
        'png': 'fas fa-file-image'
      };
      return icons[fileType] || 'fas fa-file';
    }

    function formatFileSize(bytes) {
      if (bytes === 0) return '0 Bytes';
      var k = 1024;
      var sizes = ['Bytes', 'KB', 'MB', 'GB'];
      var i = Math.floor(Math.log(bytes) / Math.log(k));
      return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }

    bindCommentForms();
    fetchSocialFeed();
  }

  function bindSocialGroupForms() {
    const groupForm = document.querySelector('.group-create-form');
    if (groupForm && groupForm.dataset.apiBound !== '1') {
      groupForm.dataset.apiBound = '1';
      groupForm.addEventListener('submit', function(event) {
        event.preventDefault();
        const formData = new FormData(groupForm);
        fetch(new URL('../api/group.php', engagementScript.src).href, {
          method: 'POST',
          body: new URLSearchParams({name: formData.get('group_name') || ''}),
          credentials: 'same-origin'
        }).then(function(response) { return response.json().then(function(data) { if (!response.ok || !data.success) throw new Error(data.message || 'Unable to create group.'); return data; }); })
          .then(function() { groupForm.reset(); loadSocialPageData(); })
          .catch(function(error) { window.alert(error.message); });
      });
    }

    var modalMemberForm = document.getElementById('group-members-modal-form');
    if (modalMemberForm && modalMemberForm.dataset.apiBound !== '1') {
      modalMemberForm.dataset.apiBound = '1';
      modalMemberForm.addEventListener('submit', function(event) {
        event.preventDefault();
        var groupId = document.getElementById('groupMembersModalGroupId')?.value || '';
        var employeeId = document.getElementById('groupMembersModalEmployeeId')?.value || '';
        if (!groupId || !employeeId) {
          window.alert('Please select an employee.');
          return;
        }

        fetch(new URL('../api/group_member.php', engagementScript.src).href, {
          method: 'POST',
          body: JSON.stringify({group_id: groupId, employee_id: employeeId}),
          headers: {'Content-Type': 'application/json'},
          credentials: 'same-origin'
        }).then(function(response) { return response.json().then(function(data) { if (!response.ok || !data.success) throw new Error(data.message || data.error || 'Unable to add group member.'); return data; }); })
          .then(function() {
            modalMemberForm.reset();
            refreshGroupMembers(groupId);
            loadSocialPageData();
            closeSocialModal('groupMembersModal');
          })
          .catch(function(error) { window.alert(error.message); });
      });
    }

  }

  function loadSocialPageData() {
    bindSocialGroupForms();
    const apiUrl = window.location.pathname.split('/modules/engagement/')[0] + '/modules/engagement/api/social.php?action=page_data';
    const groupApiUrl = new URL('../api/group.php', engagementScript.src).href;

    function renderForums(forums) {
      const forumsList = document.getElementById('forums-list');
      if (!forumsList || !Array.isArray(forums)) return;
      forumsList.innerHTML = forums.length ? forums.map(function(forum) {
        return '<div class="card mb-3 forum-card"><div class="card-body"><h5 class="mb-1" style="font-weight:600;">' + escapeHtml(forum.title || 'Untitled Forum') + '</h5><p class="mb-1 text-muted">' + escapeHtml(forum.description || '') + '</p><small class="text-muted">Category: ' + escapeHtml(forum.category || 'General') + '</small><div class="d-flex justify-content-between text-muted small"><span>Created by: ' + escapeHtml(forum.creator_name || forum.created_by_employee_id || 'Unknown') + '</span><span>' + escapeHtml(forum.created_at || '') + '</span></div></div></div>';
      }).join('') : '<div class="alert alert-info">No forums available yet.</div>';
    }

    function renderGroups(groups, groupMembers) {
      const groupsGrid = document.querySelector('.existing-groups-grid');
      if (!groupsGrid || !Array.isArray(groups)) return;
      const membersByGroup = groupMembers || {};
      groupsGrid.innerHTML = groups.length ? groups.map(function(group) {
        const groupId = Number(group.eer_group_id || 0);
        const members = membersByGroup[groupId] || [];
        const memberMarkup = members.length
          ? '<ul class="list-group list-group-flush">' + members.map(function(member) { return '<li class="list-group-item py-1">Employee ID: ' + escapeHtml(member.employee_id || 'N/A') + (member.full_name ? ' - ' + escapeHtml(member.full_name) : '') + '</li>'; }).join('') + '</ul>'
          : '<p class="text-muted mb-0">No members yet.</p>';
        return '<div class="existing-group-card" data-group-id="' + groupId + '"><h5 class="mb-1">' + escapeHtml(group.name || 'Untitled Group') + '</h5><p class="mb-1 text-muted">ID: ' + groupId + '</p><p class="mb-1"><strong>Members:</strong></p><div id="group-members-' + groupId + '">' + memberMarkup + '</div></div>';
      }).join('') : '<p class="text-muted">No groups created yet.</p>';
    }

    fetch(groupApiUrl, {credentials: 'same-origin', cache: 'no-store'})
      .then(function(response) { return response.ok ? response.json() : null; })
      .then(function(result) {
        if (result && result.success && Array.isArray(result.data)) renderGroups(result.data, {});
      })
      .catch(function(error) { console.warn('[Social] Unable to load groups:', error.message); });

    fetch(apiUrl, { credentials: 'same-origin', cache: 'no-store' })
      .then(function(response) {
        if (!response.ok) throw new Error('Unable to load social page data.');
        return response.json();
      })
      .then(function(result) {
        const data = result.data || {};
        window.socialPageData = data;
        const feed = document.getElementById('social-feed');
        if (feed && data.current_employee_id) feed.dataset.employeeId = data.current_employee_id;

        if (Array.isArray(data.forums) && data.forums.length > 0) {
          renderForums(data.forums);
        }

        const projects = document.getElementById('projects-list');
        if (projects && Array.isArray(data.projects)) {
          projects.innerHTML = data.projects.length ? data.projects.map(function(project) {
            const status = String(project.status || 'unknown').toLowerCase();
            const statusClass = {active: 'badge-success', completed: 'badge-primary', 'on-hold': 'badge-warning', planning: 'badge-info'}[status] || 'badge-secondary';
            return '<div class="card mb-3 project-card"><div class="card-body"><h5 class="mb-1" style="font-weight:600;">' + escapeHtml(project.name || 'Untitled Project') + '</h5><p class="mb-1 text-muted">' + escapeHtml(project.description || '') + '</p><span class="badge ' + statusClass + '">' + escapeHtml(status.charAt(0).toUpperCase() + status.slice(1)) + '</span><small class="text-muted ml-3">Deadline: ' + escapeHtml(project.deadline || 'Not set') + '</small><div class="d-flex justify-content-between text-muted small"><span>Created by: ' + escapeHtml(project.creator_name || project.created_by_employee_id || 'Unknown') + '</span><span>' + escapeHtml(project.created_at || '') + '</span></div></div></div>';
          }).join('') : '<div class="alert alert-info">No project spaces available yet.</div>';
        }

        const groupSelect = document.getElementById('group-id');
        if (groupSelect && Array.isArray(data.groups)) {
          groupSelect.innerHTML = '<option value="">Choose group</option>' + data.groups.map(function(group) {
            return '<option value="' + Number(group.eer_group_id || 0) + '">' + escapeHtml((group.name || 'Untitled Group') + ' (ID: ' + (group.eer_group_id || '') + ')') + '</option>';
          }).join('');
        }
        const employeeSelect = document.getElementById('employee-id');
        if (employeeSelect && Array.isArray(data.employees)) {
          employeeSelect.innerHTML = '<option value="">Choose employee</option>' + data.employees.map(function(employee) {
            return '<option value="' + Number(employee.employee_id || 0) + '">' + escapeHtml((employee.employee_id || '') + ' - ' + (employee.full_name || 'No name')) + '</option>';
          }).join('');
        }
        bindSocialGroupForms();

        renderGroups(data.groups, data.group_members);

      })
      .catch(function(error) {
        console.warn('[Social] API page-data load failed; existing specialized loaders remain active.', error);
      });
  }

  function loadForumsFromDatabase() {
    const forumsList = document.getElementById('forums-list');
    if (!forumsList) return;

    fetch(window.location.origin + '/hrms-capstone/modules/engagement/api/forum.php?action=list&_=' + Date.now(), {
      credentials: 'same-origin',
      cache: 'no-store'
    })
      .then(function(response) {
        if (!response.ok) throw new Error('Forum API returned HTTP ' + response.status);
        return response.json();
      })
      .then(function(result) {
        if (!result || result.success !== true || !Array.isArray(result.data)) {
          throw new Error(result && result.message ? result.message : 'Invalid forum API response.');
        }
        forumsList.innerHTML = result.data.length ? result.data.map(function(forum) {
            return '<div class="card mb-3 forum-card"><div class="card-body"><h5 class="mb-1" style="font-weight:600;">' + escapeHtml(forum.title || 'Untitled Forum') + '</h5><p class="mb-1 text-muted">' + escapeHtml(forum.description || '') + '</p><small class="text-muted">Category: ' + escapeHtml(forum.category || 'General') + '</small><div class="d-flex justify-content-between text-muted small"><span>Created by: ' + escapeHtml(forum.creator_name || forum.created_by_employee_id || 'Unknown') + '</span><span>' + escapeHtml(forum.created_at || '') + '</span></div></div></div>';
          }).join('') : '<div class="alert alert-info">No forums available yet.</div>';
      })
      .catch(function(error) {
        console.warn('[Social Forums] Unable to load database records:', error.message);
      });
  }

  document.addEventListener('DOMContentLoaded', function() {
    initializeSocialFeed();
    loadForumsFromDatabase();
    loadSocialPageData();
  }, { once: true });
  window.addEventListener('page:loaded', function(event) {
    if (event.detail && event.detail.page === 'social') {
      initializeSocialFeed();
      loadForumsFromDatabase();
      loadSocialPageData();
    }
  });