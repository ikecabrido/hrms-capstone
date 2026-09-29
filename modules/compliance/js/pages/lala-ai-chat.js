(function () {
    'use strict';

    var ENDPOINT = '/modules/compliance/lib/ajax/lala-ai-chat.php?cb=' + Date.now();
    var CHAR_LIMIT = 2000;
    var SCROLL_THROTTLE = 50;

    var root = document.getElementById('ll-assistant-root');
    if (!root) return;

    if (root.dataset.llInitialized === 'true') return;
    root.dataset.llInitialized = 'true';

    var panel = root.querySelector('#ll-assistant-panel');
    var body = root.querySelector('#ll-assistant-body');
    var toggleBtn = root.querySelector('#ll-assistant-toggle');
    var minimizeBtn = root.querySelector('#ll-assistant-minimize');
    var clearBtn = root.querySelector('#ll-assistant-clear');
    var quickRepliesContainer = root.querySelector('#ll-assistant-quick-replies');
    var form = root.querySelector('#ll-assistant-form');
    var input = root.querySelector('#ll-assistant-input');
    var submitBtn = root.querySelector('#ll-assistant-submit');

    if (!panel || !body || !toggleBtn || !minimizeBtn || !clearBtn || !form || !input || !submitBtn) return;

    var lalaState = {
        conversationId: generateId(),
        messages: [],
        isProcessing: false,
        context: resetContext()
    };

    var STORAGE_KEY = 'll_assistant_state';
    var SCROLL_KEY = 'll_assistant_scroll';
    var OPEN_KEY = 'll_assistant_open';

    function generateId() {
        return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function (c) {
            var r = Math.random() * 16 | 0;
            var v = c === 'x' ? r : (r & 0x3 | 0x8);
            return v.toString(16);
        });
    }

    function escapeHtml(text) {
        if (text == null) return '';
        return String(text)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function resetContext() {
        return { topic: null, last_query: null, specifics: [] };
    }

    function saveState() {
        try {
            var state = {
                conversationId: lalaState.conversationId,
                messages: lalaState.messages.slice(-20),
                context: lalaState.context
            };
            sessionStorage.setItem(STORAGE_KEY, JSON.stringify(state));
        } catch (e) {}
    }

    function loadState() {
        try {
            var saved = sessionStorage.getItem(STORAGE_KEY);
            if (!saved) return;
            var state = JSON.parse(saved);
            if (!state || !Array.isArray(state.messages)) return;
            lalaState.conversationId = state.conversationId || generateId();
            lalaState.messages = state.messages;
            lalaState.context = state.context || resetContext();
        } catch (e) {}
    }

    function saveScroll() {
        try {
            if (body) {
                sessionStorage.setItem(SCROLL_KEY, String(body.scrollTop));
            }
        } catch (e) {}
    }

    function restoreScroll() {
        try {
            if (body) {
                var saved = sessionStorage.getItem(SCROLL_KEY);
                if (saved !== null) {
                    body.scrollTop = parseInt(saved, 10);
                    sessionStorage.removeItem(SCROLL_KEY);
                }
            }
        } catch (e) {}
    }

    function scrollToBottom() {
        requestAnimationFrame(function () {
            body.scrollTop = body.scrollHeight;
        });
    }

    var loadingTimer = null;
    var followUpTimer = null;
    var followUpIdleTimer = null;
    var LOADING_DELAYS = {
        SPINNER_AFTER: 350,
        SKELETON_AFTER: 1800
    };
    var FOLLOW_UP_DELAY = 120000;
    var FOLLOW_UP_IDLE_DELAY = 120000;

    function getLoadingMessage(query, phase) {
        var lower = String(query || '').toLowerCase();
        var wordCount = lower.split(/\s+/).filter(Boolean).length;

        if (phase === 'spinner') {
            if (wordCount > 10) {
                return 'Lala AI is analyzing your concern...';
            }
            if (/\b(salary|wage|pay|compensation|minimum wage|overtime|deduction|13th month|night differential)\b/.test(lower)) {
                return 'Searching labor law references on salary and compensation...';
            }
            if (/\b(leave|maternity|paternity|solo parent|parental|vacation|sick|holiday)\b/.test(lower)) {
                return 'Searching labor law references on leave and benefits...';
            }
            if (/\b(safety|injury|accident|ppe|hazard|workplace safety|occupational)\b/.test(lower)) {
                return 'Searching labor law references on workplace safety...';
            }
            if (/\b(harassment|discrimination|safe spaces|gender|violence|sexual)\b/.test(lower)) {
                return 'Searching labor law references on harassment and discrimination...';
            }
            if (/\b(termination|dismissal|separation|retirement|resignation|constructive)\b/.test(lower)) {
                return 'Searching labor law references on termination and separation...';
            }
            if (/\b(sss|philhealth|pagibig|government contribution|social security)\b/.test(lower)) {
                return 'Searching labor law references on government contributions...';
            }
            if (/\b(foreign|alien|permit|employment of foreign)\b/.test(lower)) {
                return 'Searching labor law references on foreign national employment...';
            }
            if (/\b(union|labor relations|collective bargaining|certification)\b/.test(lower)) {
                return 'Searching labor law references on labor relations...';
            }
            if (/\b(privacy|data privacy|personal data|confidential)\b/.test(lower)) {
                return 'Searching labor law references on data privacy...';
            }
            return 'Lala AI is checking the references...';
        }

        if (phase === 'skeleton') {
            return 'Refining results from the labor law database...';
        }

        return 'Lala AI is checking the references...';
    }

    function removeLoadingIndicator() {
        clearTimeout(loadingTimer);
        var existing = document.getElementById('ll-assistant-loading');
        if (existing) existing.remove();
    }

    function removeSkeletonCards() {
        var skeletons = document.querySelectorAll('.ll-assistant-skeleton');
        skeletons.forEach(function (el) { el.remove(); });
    }

    function removeProgressIndicator() {
        var prog = document.getElementById('ll-assistant-progress');
        if (prog) prog.remove();
    }

    function renderLoadingBubble(message) {
        removeLoadingIndicator();
        removeSkeletonCards();
        removeProgressIndicator();

        var div = document.createElement('div');
        div.id = 'll-assistant-loading';
        div.className = 'll-assistant-msg ll-assistant-msg--bot ll-assistant-loading';
        div.setAttribute('aria-busy', 'true');
        div.setAttribute('role', 'status');
        div.setAttribute('aria-label', message || 'Lala AI is checking the references...');
        div.innerHTML = '<div class="ll-assistant-bubble">' +
            '<div class="ll-loading-row">' +
                '<span class="ll-dots" aria-hidden="true">' +
                    '<span class="ll-dot"></span>' +
                    '<span class="ll-dot"></span>' +
                    '<span class="ll-dot"></span>' +
                '</span>' +
                '<div>' +
                    '<div class="ll-loading-text">' + escapeHtml(message || 'Lala AI is checking the references...') + '</div>' +
                '</div>' +
            '</div>' +
            '<div class="ll-progress" aria-hidden="true">' +
                '<div class="ll-progress-bar"></div>' +
            '</div>' +
        '</div>';
        body.appendChild(div);
        scrollToBottom();
    }

    function renderSkeletonCards(count) {
        removeLoadingIndicator();
        removeSkeletonCards();
        removeProgressIndicator();

        var wrapper = document.createElement('div');
        wrapper.className = 'll-assistant-msg ll-assistant-msg--bot ll-assistant-skeleton';
        wrapper.setAttribute('aria-busy', 'true');
        wrapper.setAttribute('role', 'status');
        wrapper.setAttribute('aria-label', 'Loading results...');

        var html = '<div class="ll-assistant-bubble">' +
            '<div style="font-size:0.72rem;color:var(--text-500, #6b7280);margin-bottom:8px;">Loading labor law references...</div>' +
            '<div class="ll-skeleton-list">';

        for (var i = 0; i < count; i++) {
            html += '<div class="ll-skeleton-card">' +
                        '<div class="ll-skeleton-line ll-skeleton-title"></div>' +
                        '<div class="ll-skeleton-line ll-skeleton-meta"></div>' +
                        '<div class="ll-skeleton-line ll-skeleton-meta-sm"></div>' +
                        '<div class="ll-skeleton-line ll-skeleton-actions"></div>' +
                    '</div>';
        }

        html += '</div></div>';
        wrapper.innerHTML = html;
        body.appendChild(wrapper);
        scrollToBottom();
    }

    function showLoadingPhase(phase, message, skeletonCount) {
        clearTimeout(loadingTimer);

        if (phase === 'none') {
            removeLoadingIndicator();
            removeSkeletonCards();
            removeProgressIndicator();
            return;
        }

        if (phase === 'spinner') {
            loadingTimer = setTimeout(function () {
                renderLoadingBubble(message);
            }, LOADING_DELAYS.SPINNER_AFTER);
        }

        if (phase === 'skeleton') {
            loadingTimer = setTimeout(function () {
                removeLoadingIndicator();
                renderSkeletonCards(skeletonCount || 3);
            }, LOADING_DELAYS.SKELETON_AFTER);
        }
    }

    function clearAllLoadingIndicators() {
        clearTimeout(loadingTimer);
        clearTimeout(followUpTimer);
        clearTimeout(followUpIdleTimer);
        removeLoadingIndicator();
        removeSkeletonCards();
        removeProgressIndicator();
    }

    function promptIdleCheck() {
        clearQuickReplies();
        var idleHtml = '<div style="margin-top:10px;font-size:0.85rem;color:var(--text-700, #3b4252);">Are you still there?</div>';
        addMessage('bot', idleHtml);
        showQuickReplies([
            { label: 'Yes, still here', value: 'yes still here' },
            { label: 'No, that\'s all', value: 'no that is all' },
        ]);
    }

    function startFollowUpCountdown() {
        clearTimeout(followUpIdleTimer);
        followUpIdleTimer = setTimeout(promptIdleCheck, FOLLOW_UP_IDLE_DELAY);
    }

    function scheduleFollowUp(options) {
        clearTimeout(followUpTimer);
        followUpTimer = setTimeout(function () {
            if (options && options.length) {
                showQuickReplies(options);
            }
        }, FOLLOW_UP_DELAY);
    }

    function setProcessing(processing) {
        lalaState.isProcessing = processing;
        submitBtn.disabled = processing;
        input.disabled = processing;
    }

    function addMessage(role, html) {
        lalaState.messages.push({ role: role, html: html, time: Date.now() });
        var div = document.createElement('div');
        div.className = 'll-assistant-msg ll-assistant-msg--' + (role === 'user' ? 'user' : 'bot');
        div.innerHTML = '<div class="ll-assistant-bubble">' + html + '</div>';
        body.appendChild(div);
        if (role === 'bot') {
            requestAnimationFrame(function () {
                var rect = div.getBoundingClientRect();
                var parentRect = body.getBoundingClientRect();
                body.scrollTop += rect.top - parentRect.top;
            });
        } else {
            scrollToBottom();
        }
        saveState();
    }

    function clearMessages() {
        body.innerHTML = '';
        lalaState.messages = [];
        lalaState.context = resetContext();
        saveState();
    }

    function showQuickReplies(options) {
        if (!options || !options.length) {
            quickRepliesContainer.innerHTML = '';
            return;
        }
        quickRepliesContainer.innerHTML = '';
        options.forEach(function (opt) {
            var btn = document.createElement('button');
            btn.className = 'll-assistant-quick-reply-btn';
            btn.textContent = opt.label;
            btn.setAttribute('data-value', opt.value);
            btn.setAttribute('data-label', opt.label);
            if (opt.specifics && opt.specifics.length) {
                btn.setAttribute('data-specifics', JSON.stringify(opt.specifics));
            }
            quickRepliesContainer.appendChild(btn);
        });
    }

    function clearQuickReplies() {
        quickRepliesContainer.innerHTML = '';
    }

    function sendToAssistant(query) {
        if (lalaState.isProcessing) return;

        var trimmed = query.trim();
        if (!trimmed) {
            addMessage('bot', 'Please describe the HR or workplace concern you want to search for.');
            return;
        }
        if (trimmed.length > CHAR_LIMIT) {
            addMessage('bot', 'That\'s a bit long. Please keep your message under ' + CHAR_LIMIT + ' characters so I can process it properly.');
            return;
        }

        clearQuickReplies();
        clearAllLoadingIndicators();
        addMessage('user', escapeHtml(trimmed));
        input.value = '';

        setProcessing(true);
        var msg = getLoadingMessage(trimmed, 'spinner');
        showLoadingPhase('spinner', msg);

        var requestContext = {
            topic: lalaState.context.topic,
            last_query: lalaState.context.last_query,
            specifics: lalaState.context.specifics || []
        };

        fetch(ENDPOINT, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ query: trimmed, context: requestContext }),
            credentials: 'same-origin',
            cache: 'no-store'
        })
        .then(function (res) { return res.json(); })
        .then(function (data) {
            clearAllLoadingIndicators();
            setProcessing(false);
            if (!data.success) {
                addMessage('bot', escapeHtml(data.message || 'I couldn\'t check the references right now. Please try again.'));
                input.focus();
                return;
            }
            handleResponse(data);
            input.focus();
        })
        .catch(function () {
            clearAllLoadingIndicators();
            setProcessing(false);
            addMessage('bot', 'I couldn\'t check the references right now. Please try again.');
            input.focus();
        });
    }

    function handleResponse(data) {
        var type = data.type || 'results';
        var message = data.message || '';

        if (data.context) {
            lalaState.context = data.context;
            saveState();
        }

        if (type === 'results') {
            removeSkeletonCards();
            var html = escapeHtml(message);
            html += '<div class="ll-results-list">';
            (data.results || []).forEach(function (r) {
                var refUrl = '?page=labor-law-reference-detail&id=' + encodeURIComponent(r.id);
                var btnLabel = 'View Reference';
                html += '<div class="ll-result-item">';
                html += '<div class="ll-result-title">' + escapeHtml(r.title || r.short_title || 'Untitled Reference') + '</div>';
                html += '<div class="ll-result-meta">';
                if (r.reference_number) html += '<div>' + escapeHtml(r.reference_number) + '</div>';
                if (r.reference_type) html += '<div>Type: ' + escapeHtml(r.reference_type) + '</div>';
                if (r.issuing_authority) html += '<div>Authority: ' + escapeHtml(r.issuing_authority) + '</div>';
                if (r.status) html += '<div>Status: ' + escapeHtml(r.status) + '</div>';
                html += '</div>';
                if (r.matched_concepts) {
                    html += '<div class="ll-result-matched">Why this may be relevant: ' + escapeHtml(r.matched_concepts) + '</div>';
                }
                html += '<div class="ll-result-actions">';
                html += '<a href="' + escapeHtml(refUrl) + '" class="ll-result-btn ll-result-btn--primary" target="_blank" rel="noopener">' + btnLabel + '</a>';
                html += '</div></div>';
            });
            html += '</div>';
            if (data.follow_up_message) {
                html += '<div style="margin-top:10px;font-size:0.85rem;color:var(--text-700, #3b4252);">' + escapeHtml(data.follow_up_message) + '</div>';
            }
            addMessage('bot', html);
            if (data.follow_up && data.follow_up_options) {
                showQuickReplies(data.follow_up_options);
            } else if (data.options) {
                showQuickReplies(data.options);
            }
            startFollowUpCountdown();
            return;
        }

        if (type === 'partial_match') {
            removeSkeletonCards();
            var html = escapeHtml(message);
            html += '<div class="ll-results-list">';
            (data.results || []).forEach(function (r) {
                var refUrl = '?page=labor-law-reference-detail&id=' + encodeURIComponent(r.id);
                var btnLabel = 'View Reference';
                html += '<div class="ll-result-item">';
                html += '<div class="ll-result-title">' + escapeHtml(r.title || r.short_title || 'Untitled Reference') + '</div>';
                html += '<div class="ll-result-meta">';
                if (r.reference_number) html += '<div>' + escapeHtml(r.reference_number) + '</div>';
                if (r.reference_type) html += '<div>Type: ' + escapeHtml(r.reference_type) + '</div>';
                if (r.issuing_authority) html += '<div>Authority: ' + escapeHtml(r.issuing_authority) + '</div>';
                if (r.status) html += '<div>Status: ' + escapeHtml(r.status) + '</div>';
                html += '</div>';
                if (r.matched_concepts) {
                    html += '<div class="ll-result-matched">Why this may be relevant: ' + escapeHtml(r.matched_concepts) + '</div>';
                }
                html += '<div class="ll-result-actions">';
                html += '<a href="' + escapeHtml(refUrl) + '" class="ll-result-btn ll-result-btn--primary" target="_blank" rel="noopener">' + btnLabel + '</a>';
                html += '</div></div>';
            });
            html += '</div>';
            if (data.fallback_agency || data.fallback_url) {
                html += '<div style="margin-top:12px;padding:10px;border:1px solid var(--border, #e4e8ee);border-radius:8px;background:#fff;">';
                html += '<div style="font-size:0.78rem;font-weight:700;color:var(--text-900, #1b2430);margin-bottom:4px;">Recommended official source:</div>';
                if (data.fallback_agency) {
                    html += '<div style="font-size:0.78rem;color:var(--text-700, #3b4252);margin-bottom:6px;">' + escapeHtml(data.fallback_agency) + '</div>';
                }
                if (data.fallback_url) {
                    html += '<a href="' + escapeHtml(data.fallback_url) + '" class="ll-result-btn" target="_blank" rel="noopener">Official Source</a>';
                }
                if (data.fallback_why) {
                    html += '<div style="font-size:0.72rem;color:var(--text-500, #6b7280);margin-top:6px;">' + escapeHtml(data.fallback_why) + '</div>';
                }
                html += '</div>';
            }
            if (data.follow_up_message) {
                html += '<div style="margin-top:10px;font-size:0.85rem;color:var(--text-700, #3b4252);">' + escapeHtml(data.follow_up_message) + '</div>';
            }
            addMessage('bot', html);
            if (data.follow_up && data.follow_up_options) {
                showQuickReplies(data.follow_up_options);
            } else if (data.options) {
                showQuickReplies(data.options);
            }
            startFollowUpCountdown();
            return;
        }

        if (type === 'fallback') {
            removeSkeletonCards();
            var html = escapeHtml(message);
            if (data.fallback_agency || data.fallback_url) {
                html += '<div style="margin-top:12px;padding:10px;border:1px solid var(--border, #e4e8ee);border-radius:8px;background:#fff;">';
                html += '<div style="font-size:0.78rem;font-weight:700;color:var(--text-900, #1b2430);margin-bottom:4px;">Recommended official source:</div>';
                if (data.fallback_agency) {
                    html += '<div style="font-size:0.78rem;color:var(--text-700, #3b4252);margin-bottom:6px;">' + escapeHtml(data.fallback_agency) + '</div>';
                }
                if (data.fallback_url) {
                    html += '<a href="' + escapeHtml(data.fallback_url) + '" class="ll-result-btn ll-result-btn--primary" target="_blank" rel="noopener">Official Source</a>';
                }
                if (data.fallback_why) {
                    html += '<div style="font-size:0.72rem;color:var(--text-500, #6b7280);margin-top:6px;">' + escapeHtml(data.fallback_why) + '</div>';
                }
                html += '</div>';
            }
            addMessage('bot', html);
            if (data.follow_up && data.follow_up_options) {
                showQuickReplies(data.follow_up_options);
            } else if (data.options) {
                showQuickReplies(data.options);
            }
            startFollowUpCountdown();
            return;
        }

        clearAllLoadingIndicators();

        if (type === 'developer') {
            addMessage('bot', data.message || '');
            if (data.options) showQuickReplies(data.options);
            return;
        }

        if (type === 'greeting' || type === 'clarify' || type === 'unrelated' || type === 'no_results' || type === 'goodbye' || type === 'help' || type === 'thanks') {
            addMessage('bot', escapeHtml(message));
            if (type === 'goodbye') {
                clearQuickReplies();
            } else if (data.options) {
                showQuickReplies(data.options);
            } else if (data.follow_up_options) {
                showQuickReplies(data.follow_up_options);
            }
            return;
        }

        addMessage('bot', escapeHtml(data.message || 'I couldn\'t check the references right now. Please try again.'));
        if (data.options) showQuickReplies(data.options);
    }

    function openPanel() {
        panel.classList.add('ll-assistant-panel--open');
        root.classList.add('ll-assistant-root--open');
        root.classList.remove('ll-assistant-root--minimized');
        panel.setAttribute('aria-hidden', 'false');
        toggleBtn.setAttribute('aria-expanded', 'true');
        toggleBtn.setAttribute('aria-label', 'Close Lala AI');
        minimizeBtn.setAttribute('aria-label', 'Minimize assistant');
        minimizeBtn.innerHTML = '<i class="fa-solid fa-minus"></i>';
        input.focus();
        try { sessionStorage.setItem(OPEN_KEY, '1'); } catch (e) {}
    }

    function closePanel() {
        panel.classList.remove('ll-assistant-panel--open');
        root.classList.remove('ll-assistant-root--open');
        root.classList.remove('ll-assistant-root--minimized');
        minimizeBtn.blur();
        requestAnimationFrame(function() {
            panel.setAttribute('aria-hidden', 'true');
            toggleBtn.setAttribute('aria-expanded', 'false');
            toggleBtn.setAttribute('aria-label', 'Open Lala AI');
            try { sessionStorage.setItem(OPEN_KEY, '0'); } catch (e) {}
        });
    }

    function minimizePanel() {
        closePanel();
        minimizeBtn.setAttribute('aria-label', 'Expand assistant');
        minimizeBtn.innerHTML = '<i class="bi bi-chevron-up"></i>';
    }

    function startNewConversation() {
        clearMessages();
        clearQuickReplies();
        lalaState.conversationId = generateId();
        lalaState.isProcessing = false;
        clearAllLoadingIndicators();
        submitBtn.disabled = false;
        input.disabled = false;
        input.value = '';
        input.focus();

        var firstName = escapeHtml(root.dataset.llFirstName || 'there');
        var welcomeHtml = '<strong>Hi, ' + firstName + '! 👋</strong><br><br>';
        welcomeHtml += 'I\'m <strong>Lala AI</strong>, your Labor Law Reference Assistant.<br><br>';
        welcomeHtml += 'Tell me what\'s happening at work, and I\'ll help you find the most relevant labor-law references.<br><br>';
        welcomeHtml += '<strong>Describe what you need in your own words.</strong>';
        addMessage('bot', welcomeHtml);
        showQuickReplies([
            { label: 'Labor Law', value: 'labor law reference' },
            { label: 'Wages & Salary', value: 'salary concern' },
            { label: 'Leave', value: 'leave concern' },
            { label: 'Working Hours', value: 'working hours concern' },
            { label: 'Government Contributions', value: 'government contributions concern' },
            { label: 'Workplace Concern', value: 'workplace concern' }
        ]);
    }

    function handleQuickReplyClick(e) {
        var btn = e.target.closest('button');
        if (!btn) return;
        if (lalaState.isProcessing) return;

        var value = btn.getAttribute('data-value');
        var label = btn.getAttribute('data-label');
        var specifics = [];
        try { specifics = JSON.parse(btn.getAttribute('data-specifics') || '[]'); } catch (ex) {}

        if (!value || !label) return;

        clearQuickReplies();
        clearAllLoadingIndicators();
        addMessage('user', escapeHtml(label));
        input.value = '';

        if (value === '__other__') {
            addMessage('bot', 'No problem. 😊 Tell me a little more about the concern in your own words.');
            lalaState.context.topic = null;
            lalaState.context.specifics = [];
            saveState();
            input.focus();
            return;
        }

        if (specifics && specifics.length) {
            lalaState.context.specifics = specifics;
        }

        setProcessing(true);
        var msg = getLoadingMessage(value, 'spinner');
        showLoadingPhase('spinner', msg);

        var requestContext = {
            topic: lalaState.context.topic,
            last_query: lalaState.context.last_query,
            specifics: lalaState.context.specifics || []
        };

        fetch(ENDPOINT, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ query: value, context: requestContext }),
            credentials: 'same-origin',
            cache: 'no-store'
        })
        .then(function (res) { return res.json(); })
        .then(function (data) {
            clearAllLoadingIndicators();
            setProcessing(false);
            if (!data.success) {
                addMessage('bot', escapeHtml(data.message || 'I couldn\'t check the references right now. Please try again.'));
                input.focus();
                return;
            }
            handleResponse(data);
            input.focus();
        })
        .catch(function () {
            clearAllLoadingIndicators();
            setProcessing(false);
            addMessage('bot', 'I couldn\'t check the references right now. Please try again.');
            input.focus();
        });
    }

    function handleSubmit() {
        if (lalaState.isProcessing) return;
        var text = input.value.trim();
        if (!text) {
            addMessage('bot', 'Please describe the HR or workplace concern you want to search for.');
            return;
        }
        if (text.length > CHAR_LIMIT) {
            addMessage('bot', 'That\'s a bit long. Please keep your message under ' + CHAR_LIMIT + ' characters so I can process it properly.');
            return;
        }
        sendToAssistant(text);
    }

    function init() {
        loadState();
        restoreScroll();

        var initialFirstName = escapeHtml(root.dataset.llFirstName || 'there');
        if (!lalaState.messages.length) {
            var welcomeHtml = '<strong>Hi, ' + initialFirstName + '! 👋</strong><br><br>';
            welcomeHtml += 'I\'m <strong>Lala AI</strong>, your Labor Law Reference Assistant.<br><br>';
            welcomeHtml += 'Tell me what\'s happening at work, and I\'ll help you find the most relevant labor-law references.<br><br>';
            welcomeHtml += '<strong>Describe what you need in your own words.</strong>';
            lalaState.messages.push({ role: 'bot', html: welcomeHtml, time: Date.now() });
            var welcomeDiv = document.createElement('div');
            welcomeDiv.className = 'll-assistant-msg ll-assistant-msg--bot';
            welcomeDiv.innerHTML = '<div class="ll-assistant-bubble">' + welcomeHtml + '</div>';
            body.appendChild(welcomeDiv);
            showQuickReplies([
                { label: 'Labor Law', value: 'labor law reference' },
                { label: 'Wages & Salary', value: 'salary concern' },
                { label: 'Leave', value: 'leave concern' },
                { label: 'Working Hours', value: 'working hours concern' },
                { label: 'Government Contributions', value: 'government contributions concern' },
                { label: 'Workplace Concern', value: 'workplace concern' }
            ]);
        } else {
            lalaState.messages.forEach(function (msg) {
                var div = document.createElement('div');
                div.className = 'll-assistant-msg ll-assistant-msg--' + (msg.role === 'user' ? 'user' : 'bot');
                div.innerHTML = '<div class="ll-assistant-bubble">' + msg.html + '</div>';
                body.appendChild(div);
            });
        }

        var llScrollTimer;
        if (body) {
            body.addEventListener('scroll', function () {
                clearTimeout(llScrollTimer);
                llScrollTimer = setTimeout(saveScroll, SCROLL_THROTTLE);
            });
        }
        window.addEventListener('beforeunload', function () {
            saveScroll();
            saveState();
        });

        toggleBtn.addEventListener('click', function () {
            if (panel.classList.contains('ll-assistant-panel--open')) {
                closePanel();
            } else {
                openPanel();
            }
        });

        if (minimizeBtn) {
            minimizeBtn.addEventListener('click', function (e) {
                e.stopPropagation();
                minimizePanel();
            });
        }

        clearBtn.addEventListener('click', startNewConversation);

        submitBtn.addEventListener('click', function (e) {
            e.preventDefault();
            e.stopPropagation();
            handleSubmit();
        });

        form.addEventListener('submit', function (e) {
            e.preventDefault();
            e.stopPropagation();
            handleSubmit();
        });

        quickRepliesContainer.addEventListener('click', handleQuickReplyClick);

        input.addEventListener('keydown', function (e) {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                handleSubmit();
            }
        });

        input.addEventListener('input', function () {
            this.style.height = 'auto';
            this.style.height = Math.min(this.scrollHeight, 100) + 'px';
        });

        var openState = false;
        try { openState = sessionStorage.getItem(OPEN_KEY) === '1'; } catch (e) {}
        if (openState) {
            openPanel();
        }
    }

    init();
})();

