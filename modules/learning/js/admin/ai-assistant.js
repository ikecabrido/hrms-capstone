/**
 * Admin AI Assistant — tab accessibility, safe AJAX actions, and filters.
 *
 * The shared tab module handles the visible tab switch. This file adds ARIA
 * state, keyboard navigation, cross-tab actions, and the P4 settings/key/model
 * forms. It is idempotent because the Learning shell re-executes page scripts
 * after AJAX navigation.
 */
(function () {
    'use strict';

    var RELOAD_TAB_KEY = 'aiAssistantReloadTab';

    function escapeHtml(value) {
        return String(value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function notify(message, type) {
        if (typeof window.showToast === 'function') {
            window.showToast(escapeHtml(message), type || 'info');
        }
    }

    function setStatus(element, message, isError) {
        if (!element) {
            return;
        }
        element.textContent = message;
        element.classList.toggle('is-error', !!isError);
    }

    function responseMessage(result, fallback) {
        if (result && Array.isArray(result.errors) && result.errors.length) {
            return result.errors.join(' ');
        }
        if (result && typeof result.error === 'string' && result.error) {
            return result.error;
        }
        if (result && typeof result.message === 'string' && result.message) {
            return result.message;
        }
        return fallback;
    }

    function init() {
        var root = document.querySelector('.ai-assistant-page');
        if (!root || root.dataset.aiInit === '1') {
            return;
        }

        var tabList = root.querySelector('.ai-tab-list');
        var tabs = Array.prototype.slice.call(root.querySelectorAll('.ai-tab-list .tab-item'));
        if (!tabList || !tabs.length) {
            return;
        }

        root.dataset.aiInit = '1';

        tabList.setAttribute('role', 'tablist');
        tabList.setAttribute('aria-label', 'AI Assistant sections');

        function panelFor(tab) {
            var id = tab.getAttribute('data-tab');
            return id ? root.querySelector('.tab-content[data-tab="' + id + '"]') : null;
        }

        function activeTabId() {
            var active = root.querySelector('.ai-tab-list .tab-item.active');
            return active ? active.getAttribute('data-tab') : 'ai-keys';
        }

        function reloadWithTab(tabId) {
            try {
                window.sessionStorage.setItem(RELOAD_TAB_KEY, tabId || 'ai-keys');
            } catch (error) {
                // Storage can be disabled; a plain reload still works.
            }
            window.setTimeout(function () {
                window.location.reload();
            }, 650);
        }

        function restoreReloadTab() {
            var saved = null;
            try {
                saved = window.sessionStorage.getItem(RELOAD_TAB_KEY);
                window.sessionStorage.removeItem(RELOAD_TAB_KEY);
            } catch (error) {
                saved = null;
            }
            if (!saved) {
                return;
            }

            var tab = tabs.filter(function (item) {
                return item.getAttribute('data-tab') === saved;
            })[0];
            if (tab) {
                tab.click();
            }
        }

        tabs.forEach(function (tab, index) {
            var panel = panelFor(tab);
            if (!tab.id) {
                tab.id = 'ai-tab-' + index;
            }
            tab.setAttribute('role', 'tab');
            tab.setAttribute('aria-selected', tab.classList.contains('active') ? 'true' : 'false');
            tab.setAttribute('tabindex', tab.classList.contains('active') ? '0' : '-1');

            if (panel) {
                if (!panel.id) {
                    panel.id = 'ai-panel-' + index;
                }
                tab.setAttribute('aria-controls', panel.id);
                panel.setAttribute('role', 'tabpanel');
                panel.setAttribute('aria-labelledby', tab.id);
            }
        });

        function syncAria() {
            tabs.forEach(function (tab) {
                var selected = tab.classList.contains('active');
                var panel = panelFor(tab);
                tab.setAttribute('aria-selected', selected ? 'true' : 'false');
                tab.setAttribute('tabindex', selected ? '0' : '-1');
                if (panel) {
                    panel.setAttribute('aria-hidden', selected ? 'false' : 'true');
                }
            });
        }

        function scheduleSync() {
            // The shared tab module is attached after this inline script, so run
            // the ARIA sync after every click listener in the current task has
            // toggled the active classes. A microtask is not throttled when the
            // tab is in the background, unlike requestAnimationFrame.
            if (typeof window.queueMicrotask === 'function') {
                window.queueMicrotask(syncAria);
            } else {
                window.setTimeout(syncAria, 0);
            }
        }

        tabList.addEventListener('click', function (event) {
            var tab = event.target.closest ? event.target.closest('.tab-item') : null;
            if (tab && tabs.indexOf(tab) !== -1) {
                scheduleSync();
            }
        });

        tabList.addEventListener('keydown', function (event) {
            var current = tabs.indexOf(document.activeElement);
            if (current === -1) {
                return;
            }

            var next = -1;
            if (event.key === 'ArrowRight') {
                next = (current + 1) % tabs.length;
            } else if (event.key === 'ArrowLeft') {
                next = (current - 1 + tabs.length) % tabs.length;
            } else if (event.key === 'Home') {
                next = 0;
            } else if (event.key === 'End') {
                next = tabs.length - 1;
            }

            if (next === -1) {
                return;
            }

            event.preventDefault();
            tabs[next].focus();
            tabs[next].click();
            scheduleSync();
        });

        root.querySelectorAll('[data-ai-tab-target]').forEach(function (button) {
            button.addEventListener('click', function () {
                var target = button.getAttribute('data-ai-tab-target');
                var tab = tabs.filter(function (item) {
                    return item.getAttribute('data-tab') === target;
                })[0];

                if (!tab) {
                    return;
                }

                tab.click();
                tab.focus();
                scheduleSync();
            });
        });

        function csrfToken() {
            var input = root.querySelector('input[name="csrf_token"]');
            return input ? input.value : '';
        }

        function post(endpoint, formData) {
            if (!endpoint) {
                return Promise.reject(new Error('The request endpoint is missing.'));
            }

            var headers = {
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-Token': csrfToken()
            };

            return window.fetch(endpoint, {
                method: 'POST',
                credentials: 'same-origin',
                headers: headers,
                body: formData
            }).then(function (response) {
                return response.json().catch(function () {
                    return {
                        success: false,
                        error: response.status === 403
                            ? 'The security token expired. Reload the page and try again.'
                            : 'The server returned an unreadable response.'
                    };
                }).then(function (result) {
                    if (!response.ok && !result.error && !result.message && !result.errors) {
                        result.error = 'The request failed with HTTP ' + response.status + '.';
                    }
                    return result;
                });
            });
        }

        var wizardState = {
            selected: null,
            selectedSkillIds: [],
            excluded: [],
            searchTimer: null,
            searchSeq: 0,
            contextSeq: 0,
            onTraineeChange: null
        };

        var contextPanel = root.querySelector('[data-ai-context-panel]');
        if (contextPanel) {
            initTraineeContext(wizardState);
        }

        var wizardPanel = root.querySelector('[data-ai-wizard]');
        if (wizardPanel) {
            initCourseWizard(wizardPanel, wizardState);
        }

        function initTraineeContext(state) {
            var searchInput = contextPanel.querySelector('[data-ai-trainee-search]');
            var searchStatus = contextPanel.querySelector('[data-ai-search-status]');
            var resultsBox = contextPanel.querySelector('[data-ai-trainee-results]');
            var selectedBox = contextPanel.querySelector('[data-ai-selected-trainee]');
            var previewBox = contextPanel.querySelector('[data-ai-context-preview]');
            var searchEndpoint = contextPanel.getAttribute('data-ai-search-endpoint') || '';
            var contextEndpoint = contextPanel.getAttribute('data-ai-context-endpoint') || '';

            if (!searchInput || !resultsBox || !selectedBox || !previewBox) {
                return;
            }

            function clearNode(node) {
                while (node && node.firstChild) {
                    node.removeChild(node.firstChild);
                }
            }

            function make(tag, className, text) {
                var node = document.createElement(tag);
                if (className) {
                    node.className = className;
                }
                if (text !== undefined && text !== null) {
                    node.textContent = String(text);
                }
                return node;
            }

            function icon(className) {
                var node = document.createElement('i');
                node.className = 'fas ' + className;
                node.setAttribute('aria-hidden', 'true');
                return node;
            }

            function formatDate(value) {
                if (!value) {
                    return 'Not recorded';
                }
                var text = String(value).replace('T', ' ');
                return text.length >= 16 ? text.slice(0, 16) : text;
            }

            function formatLabel(value) {
                var text = String(value || '').replace(/_/g, ' ');
                return text.charAt(0).toUpperCase() + text.slice(1);
            }

            function formatScore(value) {
                if (value === null || value === undefined || value === '') {
                    return '—';
                }
                var number = Number(value);
                return Number.isFinite(number) ? number.toFixed(1).replace(/\.0$/, '') + '%' : '—';
            }

            function formatMeta(parts) {
                return parts.filter(function (part) {
                    return part !== null && part !== undefined && String(part).trim() !== '';
                }).join(' · ');
            }

            function appendChips(container, values, emptyText) {
                if (!Array.isArray(values) || !values.length) {
                    container.appendChild(make('p', 'ai-context-empty', emptyText));
                    return;
                }
                var chips = make('div', 'ai-skill-chips');
                values.forEach(function (value) {
                    chips.appendChild(make('span', 'ai-skill-chip', value));
                });
                container.appendChild(chips);
            }

            function renderResults(trainees) {
                clearNode(resultsBox);
                if (!trainees.length) {
                    resultsBox.appendChild(make('div', 'ai-trainee-result-empty', 'No active trainees matched.'));
                    return;
                }
                trainees.forEach(function (trainee) {
                    var button = make('button', 'ai-trainee-result');
                    button.type = 'button';
                    button.setAttribute('role', 'option');
                    button.setAttribute('aria-selected', state.selected
                        && Number(state.selected.id) === Number(trainee.id) ? 'true' : 'false');
                    button.appendChild(make('strong', '', trainee.name || 'Unnamed trainee'));
                    button.appendChild(make('span', '', formatMeta([
                        trainee.department || 'No department',
                        trainee.position || 'No position',
                        trainee.employment_status || 'Active'
                    ])));
                    button.addEventListener('click', function () {
                        selectTrainee(trainee);
                    });
                    resultsBox.appendChild(button);
                });
            }

            function runSearch() {
                var term = searchInput.value.trim();
                if (term.length < 2) {
                    state.searchSeq++;
                    clearNode(resultsBox);
                    setStatus(searchStatus, 'Type at least two characters.', false);
                    return;
                }

                var seq = ++state.searchSeq;
                setStatus(searchStatus, 'Searching...', false);
                var formData = new FormData();
                formData.append('csrf_token', csrfToken());
                formData.append('q', term);

                post(searchEndpoint, formData).then(function (result) {
                    if (seq !== state.searchSeq) {
                        return;
                    }
                    if (!result || !result.success) {
                        clearNode(resultsBox);
                        setStatus(searchStatus, responseMessage(result, 'Trainee search failed.'), true);
                        return;
                    }
                    var trainees = Array.isArray(result.trainees) ? result.trainees : [];
                    renderResults(trainees);
                    setStatus(searchStatus, result.message || (trainees.length
                        ? trainees.length + ' active trainee' + (trainees.length === 1 ? '' : 's') + ' found.'
                        : 'No active trainees matched.'), false);
                }).catch(function (error) {
                    if (seq !== state.searchSeq) {
                        return;
                    }
                    clearNode(resultsBox);
                    setStatus(searchStatus, error && error.message
                        ? error.message
                        : 'Trainee search failed.', true);
                });
            }

            function renderSelectedTrainee() {
                clearNode(selectedBox);
                selectedBox.hidden = !state.selected;
                if (!state.selected) {
                    return;
                }

                var trainee = state.selected;
                var card = make('div', 'ai-selected-card');
                var head = make('div', 'ai-selected-card-head');
                var info = make('div');
                info.appendChild(make('strong', '', trainee.name || 'Selected trainee'));
                info.appendChild(make('p', '', formatMeta([
                    trainee.department || 'No department',
                    trainee.position || 'No position',
                    trainee.employment_status || 'Active'
                ])));
                head.appendChild(info);

                var clear = make('button', 'ai-context-clear', 'Change');
                clear.type = 'button';
                clear.addEventListener('click', function () {
                    state.selected = null;
                    state.selectedSkillIds = [];
                    state.excluded = [];
                    state.contextSeq++;
                    searchInput.value = '';
                    clearNode(resultsBox);
                    setStatus(searchStatus, 'Type at least two characters.', false);
                    renderSelectedTrainee();
                    renderEmptyPreview();
                    if (typeof state.onTraineeChange === 'function') {
                        state.onTraineeChange();
                    }
                    searchInput.focus();
                });
                head.appendChild(clear);
                card.appendChild(head);

                var actions = make('div', 'ai-selected-card-actions');
                if (trainee.profile_url) {
                    var profile = make('a', 'ai-profile-link', 'Open trainee profile');
                    profile.href = trainee.profile_url;
                    actions.appendChild(profile);
                }
                actions.appendChild(make('span', 'ai-context-hint', 'Context is refreshed when evidence changes.'));
                card.appendChild(actions);
                selectedBox.appendChild(card);
            }

            function renderEmptyPreview() {
                clearNode(previewBox);
                var empty = make('div', 'ai-empty ai-empty--compact');
                var emptyIcon = make('span', 'ai-empty-icon');
                emptyIcon.appendChild(icon('fa-user-check'));
                empty.appendChild(emptyIcon);
                empty.appendChild(make('h3', '', 'Select a trainee'));
                empty.appendChild(make('p', '', 'The context preview will show skill gaps, course history, assessment results, and any limited-evidence warnings.'));
                previewBox.appendChild(empty);
            }

            function renderError(message) {
                clearNode(previewBox);
                var box = make('div', 'ai-context-error', message);
                var retry = make('button', 'ai-btn ai-btn--ghost', 'Try again');
                retry.type = 'button';
                retry.addEventListener('click', loadContext);
                box.appendChild(retry);
                previewBox.appendChild(box);
            }

            function selectTrainee(trainee) {
                state.selected = trainee;
                state.selectedSkillIds = [];
                state.excluded = [];
                state.searchSeq++;
                searchInput.value = trainee.name || '';
                clearNode(resultsBox);
                setStatus(searchStatus, 'Selected ' + (trainee.name || 'trainee') + '.', false);
                renderSelectedTrainee();
                if (typeof state.onTraineeChange === 'function') {
                    state.onTraineeChange();
                }
                loadContext();
            }

            function loadContext() {
                if (!state.selected) {
                    return;
                }

                var seq = ++state.contextSeq;
                previewBox.classList.add('is-loading');
                previewBox.setAttribute('aria-busy', 'true');
                var formData = new FormData();
                formData.append('csrf_token', csrfToken());
                formData.append('employee_id', String(state.selected.id));
                state.selectedSkillIds.forEach(function (skillId) {
                    formData.append('selected_skill_ids[]', skillId);
                });
                state.excluded.forEach(function (key) {
                    formData.append('excluded[]', key);
                });

                post(contextEndpoint, formData).then(function (result) {
                    if (seq !== state.contextSeq) {
                        return;
                    }
                    if (!result || !result.success) {
                        renderError(responseMessage(result, 'The trainee context could not be prepared.'));
                        return;
                    }
                    if (result.meta && result.meta.trainee) {
                        state.selected = result.meta.trainee;
                        renderSelectedTrainee();
                        if (typeof state.onTraineeChange === 'function') {
                            state.onTraineeChange();
                        }
                    }
                    renderContext(result);
                }).catch(function (error) {
                    if (seq !== state.contextSeq) {
                        return;
                    }
                    renderError(error && error.message
                        ? error.message
                        : 'The trainee context could not be prepared.');
                }).finally(function () {
                    if (seq === state.contextSeq) {
                        previewBox.classList.remove('is-loading');
                        previewBox.removeAttribute('aria-busy');
                    }
                });
            }

            function renderSummary(context, meta) {
                var trainee = meta.trainee || {};
                var summary = make('div', 'ai-context-summary');
                var main = make('div', 'ai-context-summary-main');
                main.appendChild(make('h3', 'ai-context-name', trainee.name || 'Selected trainee'));
                main.appendChild(make('p', 'ai-context-meta', formatMeta([
                    trainee.department || 'No department',
                    trainee.position || 'No position',
                    trainee.hire_date ? 'Hired ' + formatDate(trainee.hire_date) : '',
                    trainee.employment_status || 'Active'
                ])));

                var badges = make('div', 'ai-context-badges');
                var source = context.skills && context.skills.source ? context.skills.source : 'limited';
                var sourceLabel = source === 'live'
                    ? 'Live skill data'
                    : (source === 'snapshot' ? 'Snapshot fallback' : 'Limited evidence');
                var sourceClass = source === 'live'
                    ? 'ai-context-badge--live'
                    : (source === 'snapshot' ? 'ai-context-badge--snapshot' : 'ai-context-badge--limited');
                var sourceBadge = make('span', 'ai-context-badge ' + sourceClass);
                sourceBadge.appendChild(icon(source === 'live' ? 'fa-circle-check' : 'fa-triangle-exclamation'));
                sourceBadge.appendChild(document.createTextNode(sourceLabel));
                badges.appendChild(sourceBadge);

                if (context.limited_evidence) {
                    badges.appendChild(make('span', 'ai-context-badge ai-context-badge--limited', 'Limited evidence'));
                }
                main.appendChild(badges);
                summary.appendChild(main);

                if (trainee.profile_url) {
                    var profile = make('a', 'ai-profile-link', 'Open profile');
                    profile.href = trainee.profile_url;
                    summary.appendChild(profile);
                }

                return summary;
            }

            function renderWarnings(warnings) {
                var box = make('div', 'ai-context-warnings');
                box.setAttribute('role', 'status');
                box.appendChild(make('strong', '', 'Review before generating'));
                var list = make('ul');
                warnings.forEach(function (warning) {
                    list.appendChild(make('li', '', warning));
                });
                box.appendChild(list);
                return box;
            }

            function renderEvidence(evidence) {
                var box = make('div', 'ai-evidence');
                var head = make('div', 'ai-evidence-head');
                head.appendChild(make('strong', '', 'Evidence sources'));
                head.appendChild(make('span', '', 'Uncheck a source to remove it from the preview, prompt, and skill-gap calculation.'));
                box.appendChild(head);

                var list = make('div', 'ai-evidence-list');
                evidence.forEach(function (item) {
                    var label = make('label', 'ai-evidence-option');
                    var input = document.createElement('input');
                    input.type = 'checkbox';
                    input.checked = !!item.included;
                    input.setAttribute('data-ai-evidence-key', item.key || '');
                    input.addEventListener('change', function () {
                        var key = item.key;
                        if (input.checked) {
                            state.excluded = state.excluded.filter(function (value) {
                                return value !== key;
                            });
                        } else if (state.excluded.indexOf(key) === -1) {
                            state.excluded.push(key);
                        }
                        loadContext();
                    });
                    label.appendChild(input);
                    label.appendChild(make('span', '', item.label || item.key || 'Evidence'));
                    label.appendChild(make('small', '', '(' + Number(item.count || 0) + ')'));
                    list.appendChild(label);
                });
                box.appendChild(list);
                return box;
            }

            function renderSkills(context, meta) {
                var section = make('section', 'ai-context-section');
                section.appendChild(make('h3', '', 'Skills'));
                var skills = context.skills || {};
                section.appendChild(make('p', 'ai-context-meta', 'Gap source: '
                    + formatLabel(skills.source || 'limited') + '.'));

                var options = Array.isArray(meta.skill_options) ? meta.skill_options : [];
                if (options.length) {
                    section.appendChild(make('h4', '', 'Select focus skills'));
                    var optionWrap = make('div', 'ai-skill-chips');
                    options.forEach(function (skill) {
                        if (skill.id === null || skill.id === undefined) {
                            optionWrap.appendChild(make('span', 'ai-skill-chip', skill.name || 'Unnamed skill'));
                            return;
                        }
                        var label = make('label', 'ai-skill-option');
                        var input = document.createElement('input');
                        input.type = 'checkbox';
                        input.value = String(skill.id);
                        input.checked = state.selectedSkillIds.indexOf(String(skill.id)) !== -1;
                        input.addEventListener('change', function () {
                            var id = String(skill.id);
                            if (input.checked) {
                                if (state.selectedSkillIds.indexOf(id) === -1) {
                                    state.selectedSkillIds.push(id);
                                }
                            } else {
                                state.selectedSkillIds = state.selectedSkillIds.filter(function (value) {
                                    return value !== id;
                                });
                            }
                            loadContext();
                        });
                        label.appendChild(input);
                        label.appendChild(make('span', '', skill.name || 'Unnamed skill'));
                        optionWrap.appendChild(label);
                    });
                    section.appendChild(optionWrap);
                }

                section.appendChild(make('h4', '', 'Current gaps'));
                appendChips(section, skills.gaps, 'No current skill gaps were found.');
                section.appendChild(make('h4', '', 'Acquired skills'));
                appendChips(section, skills.acquired, 'No acquired skills were found.');

                if (Array.isArray(skills.selected) && skills.selected.length) {
                    section.appendChild(make('h4', '', 'Selected focus'));
                    appendChips(section, skills.selected, '');
                }

                return section;
            }

            function renderCourses(title, courses, emptyText, isCurrent) {
                var section = make('section', 'ai-context-section');
                section.appendChild(make('h3', '', title));
                if (!Array.isArray(courses) || !courses.length) {
                    section.appendChild(make('p', 'ai-context-empty', emptyText));
                    return section;
                }

                var list = make('ul', 'ai-context-list');
                courses.forEach(function (course) {
                    var item = make('li', 'ai-context-item');
                    item.appendChild(make('strong', '', course.title || 'Untitled course'));
                    var metaText = isCurrent
                        ? formatMeta([formatLabel(course.status || ''), course.enrolled_at ? 'Enrolled ' + formatDate(course.enrolled_at) : ''])
                        : (course.completed_at ? 'Completed ' + formatDate(course.completed_at) : 'Completion date not recorded');
                    item.appendChild(make('span', '', metaText));
                    if (Array.isArray(course.skills) && course.skills.length) {
                        var chips = make('div', 'ai-skill-chips');
                        course.skills.forEach(function (skill) {
                            chips.appendChild(make('span', 'ai-skill-chip', skill));
                        });
                        item.appendChild(chips);
                    }
                    list.appendChild(item);
                });
                section.appendChild(list);
                return section;
            }

            function renderQuiz(quiz) {
                var section = make('section', 'ai-context-section');
                section.appendChild(make('h3', '', 'Quiz and evaluation performance'));
                if (quiz === null) {
                    var excluded = make('p', 'ai-context-excluded');
                    excluded.appendChild(icon('fa-eye-slash'));
                    excluded.appendChild(document.createTextNode('Excluded from this preview.'));
                    section.appendChild(excluded);
                    return section;
                }
                if (!quiz || !quiz.available) {
                    section.appendChild(make('p', 'ai-context-empty', 'Quiz performance is unavailable.'));
                    return section;
                }

                var summary = quiz.summary || {};
                var stats = make('div', 'ai-context-stat-grid');
                [
                    ['Attempts', summary.attempts || 0],
                    ['Average score', formatScore(summary.average_score)],
                    ['Passed', summary.passed || 0]
                ].forEach(function (pair) {
                    var stat = make('div', 'ai-context-stat');
                    stat.appendChild(make('span', '', pair[0]));
                    stat.appendChild(make('strong', '', pair[1]));
                    stats.appendChild(stat);
                });
                section.appendChild(stats);

                if (!Array.isArray(quiz.recent) || !quiz.recent.length) {
                    section.appendChild(make('p', 'ai-context-empty', 'No submitted or expired quiz or evaluation sessions were found.'));
                    return section;
                }

                var list = make('ul', 'ai-context-list');
                quiz.recent.forEach(function (attempt) {
                    var item = make('li', 'ai-context-item');
                    item.appendChild(make('strong', '', attempt.title || 'Untitled assessment'));
                    item.appendChild(make('span', '', formatMeta([
                        formatLabel(attempt.type || ''),
                        attempt.course_title || '',
                        'Score ' + formatScore(attempt.score),
                        attempt.passed === true ? 'Passed' : (attempt.passed === false ? 'Not passed' : ''),
                        formatLabel(attempt.status || ''),
                        formatDate(attempt.submitted_at)
                    ])));
                    list.appendChild(item);
                });
                section.appendChild(list);
                return section;
            }

            function renderProgram(program) {
                var section = make('section', 'ai-context-section');
                section.appendChild(make('h3', '', 'Program assessments'));
                if (program === null) {
                    var excluded = make('p', 'ai-context-excluded');
                    excluded.appendChild(icon('fa-eye-slash'));
                    excluded.appendChild(document.createTextNode('Excluded from this preview.'));
                    section.appendChild(excluded);
                    return section;
                }
                if (!program || !program.available) {
                    section.appendChild(make('p', 'ai-context-empty', 'Program assessment data is unavailable.'));
                    return section;
                }

                var summary = program.summary || {};
                if (!summary.count) {
                    section.appendChild(make('p', 'ai-context-empty', 'No completed program assessments were found.'));
                    return section;
                }

                var stats = make('div', 'ai-context-stat-grid');
                [
                    ['Assessments', summary.count || 0],
                    ['Passed', summary.passed || 0],
                    ['Average overall', formatScore(summary.average_overall)]
                ].forEach(function (pair) {
                    var stat = make('div', 'ai-context-stat');
                    stat.appendChild(make('span', '', pair[0]));
                    stat.appendChild(make('strong', '', pair[1]));
                    stats.appendChild(stat);
                });
                section.appendChild(stats);

                var list = make('ul', 'ai-context-list');
                (program.recent || []).forEach(function (assessment) {
                    var item = make('li', 'ai-context-item');
                    item.appendChild(make('strong', '', assessment.program_title || 'Untitled program'));
                    item.appendChild(make('span', '', formatMeta([
                        formatLabel(assessment.result || ''),
                        'Overall ' + formatScore(assessment.overall_score),
                        assessment.assessed_at ? 'Assessed ' + formatDate(assessment.assessed_at) : ''
                    ])));
                    list.appendChild(item);
                });
                section.appendChild(list);
                return section;
            }

            function renderContext(result) {
                var context = result.context || {};
                var meta = result.meta || {};
                clearNode(previewBox);
                var body = make('div', 'ai-context-body');
                body.appendChild(renderSummary(context, meta));
                if (Array.isArray(meta.evidence) && meta.evidence.length) {
                    body.appendChild(renderEvidence(meta.evidence));
                }
                if (Array.isArray(context.warnings) && context.warnings.length) {
                    body.appendChild(renderWarnings(context.warnings));
                }
                body.appendChild(renderSkills(context, meta));
                body.appendChild(renderCourses(
                    'Completed courses',
                    context.completed_courses,
                    'No completed courses were found.',
                    false
                ));
                body.appendChild(renderCourses(
                    'Current enrollments',
                    context.current_courses,
                    'No current enrollments were found.',
                    true
                ));
                body.appendChild(renderQuiz(context.quiz_performance));
                body.appendChild(renderProgram(context.program_assessments));
                previewBox.appendChild(body);
            }

            searchInput.addEventListener('input', function () {
                window.clearTimeout(state.searchTimer);
                state.searchTimer = window.setTimeout(runSearch, 280);
            });

            searchInput.addEventListener('keydown', function (event) {
                if (event.key === 'Enter') {
                    event.preventDefault();
                    window.clearTimeout(state.searchTimer);
                    runSearch();
                }
            });

            renderEmptyPreview();
        }

        function initCourseWizard(wizard, state) {
            var form = wizard.querySelector('[data-ai-course-form]');
            var traineeBox = wizard.querySelector('[data-ai-wizard-trainee]');
            var formStatus = wizard.querySelector('[data-ai-wizard-form-status]');
            var emptyBox = wizard.querySelector('[data-ai-wizard-empty]');
            var workspace = wizard.querySelector('[data-ai-wizard-workspace]');
            var jobHeader = wizard.querySelector('[data-ai-job-header]');
            var outlineSection = wizard.querySelector('[data-ai-outline-section]');
            var outlineEditor = wizard.querySelector('[data-ai-outline-editor]');
            var outlineStatus = wizard.querySelector('[data-ai-outline-status]');
            var progressSection = wizard.querySelector('[data-ai-progress-section]');
            var progressBox = wizard.querySelector('[data-ai-progress]');
            var progressBadge = wizard.querySelector('[data-ai-progress-badge]');
            var progressActions = wizard.querySelector('[data-ai-progress-actions]');
            var reviewSection = wizard.querySelector('[data-ai-review-section]');
            var reviewBox = wizard.querySelector('[data-ai-review]');
            var reviewBadge = wizard.querySelector('[data-ai-review-badge]');
            var generateButton = form ? form.querySelector('[data-ai-generate-outline]') : null;
            var approveButton = outlineSection ? outlineSection.querySelector('[data-ai-approve-outline]') : null;
            var endpoint = wizard.getAttribute('data-ai-job-endpoint') || '';
            var jobsPanel = root.querySelector('[data-ai-jobs-panel]');
            var jobsList = jobsPanel ? jobsPanel.querySelector('[data-ai-jobs-list]') : null;
            var jobsEndpoint = jobsPanel ? (jobsPanel.getAttribute('data-ai-job-endpoint') || endpoint) : endpoint;
            var canGenerate = !!(generateButton && !generateButton.disabled);
            var jobState = {
                job: null,
                outline: null,
                running: false,
                runSeq: 0
            };

            if (!form || !traineeBox || !emptyBox || !workspace || !jobHeader
                || !outlineSection || !outlineEditor || !progressSection || !progressBox
                || !reviewSection || !reviewBox) {
                return;
            }

            function clearNode(node) {
                while (node && node.firstChild) {
                    node.removeChild(node.firstChild);
                }
            }

            function make(tag, className, text) {
                var node = document.createElement(tag);
                if (className) {
                    node.className = className;
                }
                if (text !== undefined && text !== null) {
                    node.textContent = String(text);
                }
                return node;
            }

            function icon(className) {
                var node = document.createElement('i');
                node.className = 'fas ' + className;
                node.setAttribute('aria-hidden', 'true');
                return node;
            }

            function formatDate(value) {
                if (!value) {
                    return 'Not recorded';
                }
                var text = String(value).replace('T', ' ');
                return text.length >= 16 ? text.slice(0, 16) : text;
            }

            function formatLabel(value) {
                var text = String(value || '').replace(/_/g, ' ');
                return text.charAt(0).toUpperCase() + text.slice(1);
            }

            function formatMeta(parts) {
                return parts.filter(function (part) {
                    return part !== null && part !== undefined && String(part).trim() !== '';
                }).join(' · ');
            }

            function formatMoney(value) {
                var number = Number(value);
                return '$' + (Number.isFinite(number) ? number.toFixed(2) : '0.00');
            }

            function statusLabel(status) {
                return {
                    draft: 'Outline draft',
                    generating: 'Generating',
                    ready: 'Ready for review',
                    applied: 'Applied',
                    failed: 'Failed',
                    cancelled: 'Cancelled'
                }[String(status || '')] || formatLabel(status || 'Unknown');
            }

            function stepLabel(step) {
                var key = String(step.key || '');
                var type = String(step.type || '');
                if (type === 'outline') {
                    return 'Outline';
                }
                if (type === 'evaluation') {
                    return 'Final evaluation';
                }
                if (type === 'quality') {
                    return 'Quality checks';
                }
                var match = key.match(/^module-(\d+)$/);
                return match ? 'Module ' + match[1] : formatLabel(type || key || 'Step');
            }

            function stepIcon(status) {
                return {
                    done: 'fa-check',
                    running: 'fa-spinner fa-spin',
                    failed: 'fa-xmark',
                    skipped: 'fa-forward',
                    pending: 'fa-circle'
                }[String(status || '')] || 'fa-circle';
            }

            function setBusy(busy) {
                if (generateButton) {
                    generateButton.disabled = !!busy || !canGenerate;
                }
                if (approveButton) {
                    approveButton.disabled = !!busy;
                }
            }

            function setFormMessage(element, message, isError) {
                if (!element) {
                    return;
                }
                element.textContent = message || '';
                element.classList.toggle('is-error', !!isError);
            }

            function renderTraineeState() {
                clearNode(traineeBox);
                traineeBox.classList.toggle('is-selected', !!state.selected);
                var badge = make('span', 'ai-wizard-trainee-icon');
                badge.appendChild(icon('fa-user-check'));
                traineeBox.appendChild(badge);
                var info = make('div');
                if (!state.selected) {
                    info.appendChild(make('strong', '', 'No trainee selected'));
                    info.appendChild(make('p', '', 'Select a trainee in Step 1 to unlock the brief.'));
                } else {
                    info.appendChild(make('strong', '', state.selected.name || 'Selected trainee'));
                    info.appendChild(make('p', '', formatMeta([
                        state.selected.department || 'No department',
                        state.selected.position || 'No position',
                        state.selected.employment_status || 'Active'
                    ])));
                }
                traineeBox.appendChild(info);
            }

            function renderJobHeader(job) {
                clearNode(jobHeader);
                var main = make('div', 'ai-job-header-main');
                var goal = job.brief && job.brief.goal ? String(job.brief.goal) : 'Course generation job #' + job.id;
                main.appendChild(make('strong', '', goal));
                main.appendChild(make('span', '', formatMeta([
                    job.learner && job.learner.name ? 'Trainee: ' + job.learner.name : '',
                    job.owner && job.owner.name ? 'Owner: ' + job.owner.name : '',
                    'Job #' + job.id,
                    'Created ' + formatDate(job.created_at)
                ])));
                jobHeader.appendChild(main);

                var meta = make('div', 'ai-job-header-meta');
                [
                    statusLabel(job.status),
                    job.model || 'No model',
                    formatMoney(job.estimated_cost_usd),
                    (job.completed_steps || 0) + '/' + (job.total_steps || 0) + ' steps'
                ].forEach(function (label) {
                    meta.appendChild(make('span', 'ai-job-chip', label));
                });
                jobHeader.appendChild(meta);
            }

            function outlineField(labelText, control, wide) {
                var label = make('label', 'ai-outline-field' + (wide ? ' ai-outline-field--wide' : ''));
                label.appendChild(make('span', '', labelText));
                label.appendChild(control);
                return label;
            }

            function textControl(tag, value, attr) {
                var control = document.createElement(tag);
                control.value = value === undefined || value === null ? '' : String(value);
                if (attr) {
                    control.setAttribute(attr, '1');
                }
                return control;
            }

            function renderOutline(job) {
                var outline = job.outline || jobState.outline;
                clearNode(outlineEditor);
                if (!outline || !Array.isArray(outline.modules)) {
                    outlineEditor.appendChild(make('div', 'ai-wizard-error', 'The outline is not available yet.'));
                    return;
                }
                jobState.outline = outline;
                var course = outline.course || {};
                var courseCard = make('div', 'ai-outline-card');
                courseCard.appendChild(make('h4', '', 'Course'));
                var courseGrid = make('div', 'ai-outline-grid');
                courseGrid.appendChild(outlineField(
                    'Course title',
                    textControl('input', course.title || '', 'data-outline-course-title'),
                    true
                ));
                courseGrid.appendChild(outlineField(
                    'Course description',
                    textControl('textarea', course.description || '', 'data-outline-course-description'),
                    true
                ));
                courseGrid.appendChild(outlineField(
                    'Category',
                    textControl('input', course.category || '', 'data-outline-course-category')
                ));
                var delivery = document.createElement('select');
                delivery.setAttribute('data-outline-course-delivery', '1');
                [['online', 'Online'], ['face_to_face', 'Face to face'], ['hybrid', 'Hybrid']].forEach(function (pair) {
                    var option = document.createElement('option');
                    option.value = pair[0];
                    option.textContent = pair[1];
                    if (String(course.delivery_mode || 'online') === pair[0]) {
                        option.selected = true;
                    }
                    delivery.appendChild(option);
                });
                courseGrid.appendChild(outlineField('Delivery mode', delivery));
                courseCard.appendChild(courseGrid);
                if (Array.isArray(course.skills) && course.skills.length) {
                    var skillChips = make('div', 'ai-skill-chips');
                    course.skills.forEach(function (skill) {
                        skillChips.appendChild(make('span', 'ai-skill-chip', skill.name || skill));
                    });
                    courseCard.appendChild(skillChips);
                }
                outlineEditor.appendChild(courseCard);

                (outline.modules || []).forEach(function (module, moduleIndex) {
                    var moduleCard = make('div', 'ai-outline-card');
                    moduleCard.setAttribute('data-outline-module-index', String(moduleIndex));
                    moduleCard.appendChild(make('h4', '', 'Module ' + (moduleIndex + 1)));
                    var moduleGrid = make('div', 'ai-outline-grid');
                    moduleGrid.appendChild(outlineField(
                        'Module title',
                        textControl('input', module.title || '', 'data-outline-module-title'),
                        true
                    ));
                    moduleGrid.appendChild(outlineField(
                        'Module description',
                        textControl('textarea', module.description || '', 'data-outline-module-description'),
                        true
                    ));
                    moduleCard.appendChild(moduleGrid);
                    if (Array.isArray(module.skills) && module.skills.length) {
                        var moduleChips = make('div', 'ai-skill-chips');
                        module.skills.forEach(function (skill) {
                            moduleChips.appendChild(make('span', 'ai-skill-chip', skill.name || skill));
                        });
                        moduleCard.appendChild(moduleChips);
                    }

                    var lessonList = make('div', 'ai-outline-lessons');
                    (module.lessons || []).forEach(function (lesson, lessonIndex) {
                        var lessonCard = make('div', 'ai-outline-lesson');
                        lessonCard.setAttribute('data-outline-lesson-index', String(lessonIndex));
                        var head = make('div', 'ai-outline-lesson-head');
                        head.appendChild(make('span', '', 'Lesson ' + (lessonIndex + 1)));
                        head.appendChild(make('span', '', (lesson.estimated_minutes || 20) + ' min'));
                        lessonCard.appendChild(head);
                        var lessonGrid = make('div', 'ai-outline-grid');
                        lessonGrid.appendChild(outlineField(
                            'Lesson title',
                            textControl('input', lesson.title || '', 'data-outline-lesson-title'),
                            true
                        ));
                        lessonGrid.appendChild(outlineField(
                            'Objectives (one per line)',
                            textControl('textarea', (lesson.objectives || []).join('\n'), 'data-outline-lesson-objectives'),
                            true
                        ));
                        lessonGrid.appendChild(outlineField(
                            'Estimated minutes',
                            textControl('input', lesson.estimated_minutes || 20, 'data-outline-lesson-minutes')
                        ));
                        lessonCard.appendChild(lessonGrid);
                        lessonList.appendChild(lessonCard);
                    });
                    moduleCard.appendChild(lessonList);
                    outlineEditor.appendChild(moduleCard);
                });

                if (Array.isArray(outline.warnings) && outline.warnings.length) {
                    var warningBox = make('div', 'ai-context-warnings');
                    warningBox.appendChild(make('strong', '', 'Outline warnings'));
                    var warningList = make('ul');
                    outline.warnings.forEach(function (warning) {
                        warningList.appendChild(make('li', '', warning));
                    });
                    warningBox.appendChild(warningList);
                    outlineEditor.appendChild(warningBox);
                }
            }

            function collectOutline() {
                var outline = JSON.parse(JSON.stringify(jobState.outline || {}));
                var course = outline.course || {};
                var courseTitle = outlineEditor.querySelector('[data-outline-course-title]');
                var courseDescription = outlineEditor.querySelector('[data-outline-course-description]');
                var courseCategory = outlineEditor.querySelector('[data-outline-course-category]');
                var courseDelivery = outlineEditor.querySelector('[data-outline-course-delivery]');
                if (courseTitle) {
                    course.title = courseTitle.value.trim();
                }
                if (courseDescription) {
                    course.description = courseDescription.value.trim();
                }
                if (courseCategory) {
                    course.category = courseCategory.value.trim();
                }
                if (courseDelivery) {
                    course.delivery_mode = courseDelivery.value;
                }
                outline.course = course;

                outline.modules = (outline.modules || []).map(function (module, moduleIndex) {
                    var moduleCard = outlineEditor.querySelector('[data-outline-module-index="' + moduleIndex + '"]');
                    if (!moduleCard) {
                        return module;
                    }
                    var moduleTitle = moduleCard.querySelector('[data-outline-module-title]');
                    var moduleDescription = moduleCard.querySelector('[data-outline-module-description]');
                    if (moduleTitle) {
                        module.title = moduleTitle.value.trim();
                    }
                    if (moduleDescription) {
                        module.description = moduleDescription.value.trim();
                    }
                    module.lessons = (module.lessons || []).map(function (lesson, lessonIndex) {
                        var lessonCard = moduleCard.querySelector('[data-outline-lesson-index="' + lessonIndex + '"]');
                        if (!lessonCard) {
                            return lesson;
                        }
                        var lessonTitle = lessonCard.querySelector('[data-outline-lesson-title]');
                        var objectives = lessonCard.querySelector('[data-outline-lesson-objectives]');
                        var minutes = lessonCard.querySelector('[data-outline-lesson-minutes]');
                        if (lessonTitle) {
                            lesson.title = lessonTitle.value.trim();
                        }
                        if (objectives) {
                            lesson.objectives = objectives.value.split(/\r?\n/).map(function (value) {
                                return value.trim();
                            }).filter(function (value) {
                                return value !== '';
                            });
                        }
                        if (minutes) {
                            var parsed = parseInt(minutes.value, 10);
                            if (Number.isFinite(parsed)) {
                                lesson.estimated_minutes = parsed;
                            }
                        }
                        return lesson;
                    });
                    return module;
                });

                return outline;
            }

            function renderProgress(job) {
                clearNode(progressBox);
                var total = Number(job.total_steps) || 0;
                var done = Number(job.completed_steps) || 0;
                var percent = total > 0 ? Math.max(0, Math.min(100, Math.round((done / total) * 100))) : 0;
                if (progressBadge) {
                    progressBadge.textContent = statusLabel(job.status);
                }

                var top = make('div', 'ai-progress-top');
                var left = make('div');
                left.appendChild(make('strong', '', statusLabel(job.status)));
                left.appendChild(make('span', '', ' ' + done + ' of ' + total + ' steps complete'));
                top.appendChild(left);
                top.appendChild(make('span', '', percent + '%'));
                progressBox.appendChild(top);

                var bar = make('div', 'ai-progress-bar');
                var fill = make('div', 'ai-progress-fill');
                fill.style.width = percent + '%';
                bar.appendChild(fill);
                progressBox.appendChild(bar);

                var list = make('ul', 'ai-step-list');
                (job.steps || []).forEach(function (step) {
                    var item = make('li', 'ai-step-item is-' + (step.status || 'pending'));
                    item.appendChild(icon(stepIcon(step.status)));
                    item.appendChild(make('strong', '', stepLabel(step)));
                    item.appendChild(make('small', '', formatLabel(step.status || 'pending')));
                    list.appendChild(item);
                });
                progressBox.appendChild(list);

                if (job.error_message) {
                    progressBox.appendChild(make('div', 'ai-wizard-error', job.error_message));
                }

                clearNode(progressActions);
                function addAction(label, className, handler, iconName) {
                    var button = make('button', className || 'ai-btn ai-btn--ghost', label);
                    button.type = 'button';
                    if (iconName) {
                        button.insertBefore(icon(iconName), button.firstChild);
                        button.insertBefore(document.createTextNode(' '), button.firstChild.nextSibling);
                    }
                    button.addEventListener('click', handler);
                    progressActions.appendChild(button);
                    return button;
                }

                if (job.status === 'generating') {
                    if (!jobState.running) {
                        addAction('Continue generation', 'ai-btn ai-btn--primary', function () {
                            runJob(job.id);
                        }, 'fa-play');
                    }
                    addAction('Cancel job', 'ai-btn ai-btn--danger', function () {
                        cancelJob(job.id);
                    }, 'fa-ban');
                } else if (job.status === 'failed') {
                    addAction('Retry failed step', 'ai-btn ai-btn--primary', function () {
                        retryJob(job.id);
                    }, 'fa-rotate-right');
                    addAction('Cancel job', 'ai-btn ai-btn--danger', function () {
                        cancelJob(job.id);
                    }, 'fa-ban');
                } else if (job.status === 'ready' || job.status === 'applied') {
                    addAction('Review generated draft', 'ai-btn ai-btn--primary', function () {
                        reviewSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
                    }, 'fa-eye');
                    if (job.status === 'ready') {
                        addAction('Cancel job', 'ai-btn ai-btn--danger', function () {
                            cancelJob(job.id);
                        }, 'fa-ban');
                    }
                } else if (job.status === 'cancelled') {
                    addAction('Start a new outline', 'ai-btn ai-btn--ghost', function () {
                        form.scrollIntoView({ behavior: 'smooth', block: 'start' });
                        form.querySelector('textarea[name="goal"]').focus();
                    }, 'fa-plus');
                }
            }

            function renderReview(job) {
                clearNode(reviewBox);
                var blueprint = job.blueprint;
                if (!blueprint || !Array.isArray(blueprint.modules)) {
                    reviewBox.appendChild(make('div', 'ai-wizard-error', 'The generated draft is not available yet.'));
                    return;
                }
                if (reviewBadge) {
                    reviewBadge.textContent = statusLabel(job.status);
                }

                var course = blueprint.course || {};
                var courseCard = make('div', 'ai-review-course');
                courseCard.appendChild(make('h4', '', course.title || 'Untitled course'));
                courseCard.appendChild(make('p', '', course.description || 'No course description.'));
                if (Array.isArray(course.skills) && course.skills.length) {
                    var courseChips = make('div', 'ai-skill-chips');
                    course.skills.forEach(function (skill) {
                        courseChips.appendChild(make('span', 'ai-skill-chip', skill.name || skill));
                    });
                    courseCard.appendChild(courseChips);
                }
                reviewBox.appendChild(courseCard);

                (blueprint.modules || []).forEach(function (module, moduleIndex) {
                    var moduleCard = make('div', 'ai-review-module');
                    var head = make('div', 'ai-review-module-head');
                    head.appendChild(make('h4', '', module.title || ('Module ' + (moduleIndex + 1))));
                    head.appendChild(make('span', '', (module.lessons || []).length + ' lessons · ' + (module.quiz && module.quiz.questions ? module.quiz.questions.length : 0) + ' quiz questions'));
                    moduleCard.appendChild(head);
                    if (module.description) {
                        moduleCard.appendChild(make('p', '', module.description));
                    }
                    if (Array.isArray(module.skills) && module.skills.length) {
                        var moduleChips = make('div', 'ai-skill-chips');
                        module.skills.forEach(function (skill) {
                            moduleChips.appendChild(make('span', 'ai-skill-chip', skill.name || skill));
                        });
                        moduleCard.appendChild(moduleChips);
                    }

                    (module.lessons || []).forEach(function (lesson) {
                        var lessonBox = make('div', 'ai-review-lesson');
                        lessonBox.appendChild(make('strong', '', lesson.title || 'Untitled lesson'));
                        lessonBox.appendChild(make('p', '', formatMeta([
                            (lesson.estimated_minutes || 20) + ' min',
                            Array.isArray(lesson.objectives) && lesson.objectives.length
                                ? lesson.objectives.length + ' objectives'
                                : ''
                        ])));
                        if (lesson.content_markdown) {
                            lessonBox.appendChild(make('div', 'ai-review-content', lesson.content_markdown));
                        }
                        moduleCard.appendChild(lessonBox);
                    });

                    if (module.quiz) {
                        var quizBox = make('div', 'ai-review-quiz');
                        quizBox.appendChild(make('strong', '', module.quiz.title || 'Module quiz'));
                        quizBox.appendChild(make('p', '', formatMeta([
                            (module.quiz.questions || []).length + ' questions',
                            'Passing ' + (module.quiz.passing_score !== undefined ? module.quiz.passing_score + '%' : '70%'),
                            (module.quiz.duration_seconds || 600) + ' seconds'
                        ])));
                        (module.quiz.questions || []).forEach(function (question, questionIndex) {
                            quizBox.appendChild(renderQuestion(question, questionIndex));
                        });
                        moduleCard.appendChild(quizBox);
                    }
                    reviewBox.appendChild(moduleCard);
                });

                if (blueprint.evaluation) {
                    var evaluation = blueprint.evaluation;
                    var evaluationCard = make('div', 'ai-review-module');
                    var evaluationHead = make('div', 'ai-review-module-head');
                    evaluationHead.appendChild(make('h4', '', evaluation.title || 'Final evaluation'));
                    evaluationHead.appendChild(make('span', '', (evaluation.questions || []).length + ' questions'));
                    evaluationCard.appendChild(evaluationHead);
                    evaluationCard.appendChild(make('p', '', formatMeta([
                        'Passing ' + (evaluation.passing_score !== undefined ? evaluation.passing_score + '%' : '70%'),
                        (evaluation.duration_seconds || 1200) + ' seconds',
                        'Max ' + (evaluation.max_attempts || 2) + ' attempts'
                    ])));
                    (evaluation.questions || []).forEach(function (question, questionIndex) {
                        evaluationCard.appendChild(renderQuestion(question, questionIndex));
                    });
                    reviewBox.appendChild(evaluationCard);
                }

                renderQuality(job, blueprint.quality || {});
            }

            function renderQuestion(question, questionIndex) {
                var box = make('div', 'ai-review-question');
                box.appendChild(make('strong', '', (questionIndex + 1) + '. ' + (question.question_text || 'Untitled question')));
                var options = make('ul', 'ai-review-options');
                (question.options || []).forEach(function (option) {
                    var item = make('li', 'ai-review-option' + (option.is_correct ? ' is-correct' : ''));
                    item.appendChild(icon(option.is_correct ? 'fa-check' : 'fa-circle'));
                    item.appendChild(make('span', '', option.text || 'Untitled option'));
                    options.appendChild(item);
                });
                box.appendChild(options);
                return box;
            }

            function renderQuality(job, quality) {
                var box = make('div', 'ai-quality');
                var head = make('div', 'ai-quality-head');
                head.appendChild(make('h4', '', 'Deterministic quality checks'));
                head.appendChild(make('span', 'ai-quality-score',
                    (quality.passed ? 'Passed' : 'Blocked') + ' · ' + (Number(quality.score) || 0) + '/100'));
                box.appendChild(head);

                var stats = quality.stats || {};
                var grid = make('div', 'ai-quality-grid');
                [
                    ['Modules', stats.modules || 0],
                    ['Lessons', stats.lessons || 0],
                    ['Quiz questions', stats.quiz_questions || 0],
                    ['Evaluation questions', stats.evaluation_questions || 0],
                    ['Skills', stats.skills || 0],
                    ['Estimated minutes', stats.estimated_minutes || 0]
                ].forEach(function (pair) {
                    var stat = make('div', 'ai-quality-stat');
                    stat.appendChild(make('span', '', pair[0]));
                    stat.appendChild(make('strong', '', pair[1]));
                    grid.appendChild(stat);
                });
                box.appendChild(grid);

                if (Array.isArray(quality.blocking) && quality.blocking.length) {
                    box.appendChild(make('strong', '', 'Blocking issues'));
                    var blocking = make('ul', 'ai-quality-list ai-quality-list--blocking');
                    quality.blocking.forEach(function (issue) {
                        blocking.appendChild(make('li', '', issue.message || issue.code || 'Blocking issue'));
                    });
                    box.appendChild(blocking);
                }
                if (Array.isArray(quality.warnings) && quality.warnings.length) {
                    box.appendChild(make('strong', '', 'Warnings'));
                    var warnings = make('ul', 'ai-quality-list ai-quality-list--warnings');
                    quality.warnings.forEach(function (warning) {
                        warnings.appendChild(make('li', '', warning));
                    });
                    box.appendChild(warnings);
                }
                if (Array.isArray(quality.model_warnings) && quality.model_warnings.length) {
                    box.appendChild(make('strong', '', 'Model assumptions and warnings'));
                    var modelWarnings = make('ul', 'ai-quality-list ai-quality-list--warnings');
                    quality.model_warnings.forEach(function (warning) {
                        modelWarnings.appendChild(make('li', '', warning));
                    });
                    box.appendChild(modelWarnings);
                }

                var applied = job.status === 'applied' && Number(job.course_id) > 0;
                var canApply = quality.passed && job.status === 'ready';
                var applyRow = make('div', 'ai-quality-apply');
                var applyButton = make('button', 'ai-btn ai-btn--primary', applied ? 'Open applied course' : 'Apply as draft course');
                applyButton.type = 'button';
                applyButton.setAttribute('data-ai-apply', String(job.id));
                applyButton.disabled = !applied && !canApply;
                applyButton.addEventListener('click', function () {
                    if (applied) {
                        window.location.href = '?page=instructor/elearning-subpage/course-structure&course_id=' + Number(job.course_id);
                        return;
                    }
                    applyCourse(job);
                });
                applyRow.appendChild(applyButton);
                applyRow.appendChild(make('p', '',
                    applied
                        ? 'This job is already applied to course #' + Number(job.course_id) + '.'
                        : (quality.passed
                            ? 'Apply creates a draft course only. No enrollment, progress, or grades are created.'
                            : 'Resolve every blocking issue before applying this draft.')));
                box.appendChild(applyRow);
                reviewBox.appendChild(box);
            }

            function renderJob(job) {
                if (!job) {
                    emptyBox.hidden = false;
                    workspace.hidden = true;
                    return;
                }
                jobState.job = job;
                jobState.outline = job.outline || null;
                emptyBox.hidden = true;
                workspace.hidden = false;
                renderJobHeader(job);

                var hasOutline = job.outline && Array.isArray(job.outline.modules);
                var hasBlueprint = job.blueprint && Array.isArray(job.blueprint.modules);
                var showOutline = job.status === 'draft' && hasOutline;
                var showProgress = !showOutline && ['generating', 'failed', 'cancelled', 'ready', 'applied'].indexOf(job.status) !== -1;
                var showReview = hasBlueprint && (job.status === 'ready' || job.status === 'applied');

                outlineSection.hidden = !showOutline;
                progressSection.hidden = !showProgress;
                reviewSection.hidden = !showReview;

                if (showOutline) {
                    renderOutline(job);
                    setFormMessage(outlineStatus, '', false);
                }
                if (showProgress) {
                    renderProgress(job);
                }
                if (showReview) {
                    renderReview(job);
                }
            }

            function stopRun(message, isError) {
                jobState.running = false;
                setBusy(false);
                if (message) {
                    notify(message, isError ? 'error' : 'success');
                }
                refreshJobs();
            }

            function loadJob(jobId) {
                if (!jobId) {
                    return;
                }
                var formData = new FormData();
                formData.append('csrf_token', csrfToken());
                formData.append('action', 'get');
                formData.append('job_id', String(jobId));
                setBusy(true);
                post(endpoint, formData).then(function (result) {
                    if (result && result.success && result.job) {
                        renderJob(result.job);
                        return;
                    }
                    notify(responseMessage(result, 'The job could not be loaded.'), 'error');
                }).catch(function (error) {
                    notify(error && error.message ? error.message : 'The job could not be loaded.', 'error');
                }).finally(function () {
                    setBusy(false);
                });
            }

            function renderJobs(jobs) {
                if (!jobsList) {
                    return;
                }
                clearNode(jobsList);
                if (!Array.isArray(jobs) || !jobs.length) {
                    var empty = make('div', 'ai-empty');
                    var emptyIcon = make('span', 'ai-empty-icon');
                    emptyIcon.appendChild(icon('fa-clock-rotate-left'));
                    empty.appendChild(emptyIcon);
                    empty.appendChild(make('h3', '', 'No jobs to show'));
                    empty.appendChild(make('p', '', 'Jobs will list their status, trainee, owner, model, token usage, and estimated cost.'));
                    jobsList.appendChild(empty);
                    return;
                }

                var wrap = make('div', 'ai-table-wrap ai-jobs-table');
                var table = make('table', 'ai-table');
                var thead = document.createElement('thead');
                var headRow = document.createElement('tr');
                ['Job', 'Trainee', 'Owner', 'Model', 'Status', 'Progress', 'Cost', 'Created', ''].forEach(function (label) {
                    var th = document.createElement('th');
                    th.scope = 'col';
                    th.textContent = label;
                    headRow.appendChild(th);
                });
                thead.appendChild(headRow);
                table.appendChild(thead);
                var tbody = document.createElement('tbody');
                jobs.forEach(function (job) {
                    var row = document.createElement('tr');
                    row.setAttribute('data-ai-job-row', String(job.id));
                    row.appendChild(make('td', '', '#' + job.id));
                    row.appendChild(make('td', '', job.learner_name || '—'));
                    row.appendChild(make('td', '', job.owner_name || '—'));
                    row.appendChild(make('td', '', job.model || '—'));
                    var statusCell = make('td');
                    statusCell.appendChild(make('span', 'ai-status ai-status--' + String(job.status || ''), statusLabel(job.status)));
                    row.appendChild(statusCell);
                    row.appendChild(make('td', '', (job.completed_steps || 0) + '/' + (job.total_steps || 0)));
                    row.appendChild(make('td', '', formatMoney(job.estimated_cost_usd)));
                    row.appendChild(make('td', '', formatDate(job.created_at)));
                    var actionCell = make('td');
                    var openButton = make('button', 'ai-text-action', 'Open');
                    openButton.type = 'button';
                    openButton.setAttribute('data-ai-open-job', String(job.id));
                    actionCell.appendChild(openButton);
                    row.appendChild(actionCell);
                    tbody.appendChild(row);
                });
                table.appendChild(tbody);
                wrap.appendChild(table);
                jobsList.appendChild(wrap);
            }

            function refreshJobs() {
                if (!jobsList) {
                    return;
                }
                var formData = new FormData();
                formData.append('csrf_token', csrfToken());
                formData.append('action', 'list');
                post(jobsEndpoint, formData).then(function (result) {
                    if (result && result.success) {
                        renderJobs(result.jobs || []);
                    }
                }).catch(function () {
                    // The server-rendered list remains visible if the refresh fails.
                });
            }

            function runJob(jobId) {
                if (jobState.running) {
                    return;
                }
                jobState.running = true;
                jobState.runSeq++;
                setBusy(true);
                stepLoop(jobId, jobState.runSeq);
            }

            function stepLoop(jobId, seq) {
                if (seq !== jobState.runSeq) {
                    return;
                }
                var formData = new FormData();
                formData.append('csrf_token', csrfToken());
                formData.append('action', 'advance');
                formData.append('job_id', String(jobId));
                post(endpoint, formData).then(function (result) {
                    if (seq !== jobState.runSeq) {
                        return;
                    }
                    if (result.job) {
                        renderJob(result.job);
                    }
                    if (result.success) {
                        if (result.job && result.job.status === 'generating') {
                            window.setTimeout(function () {
                                stepLoop(jobId, seq);
                            }, 250);
                            return;
                        }
                        stopRun(result.message || 'Generation finished.', false);
                        return;
                    }
                    if (result.busy) {
                        window.setTimeout(function () {
                            waitForRunning(jobId, seq);
                        }, 1500);
                        return;
                    }
                    stopRun(responseMessage(result, 'Generation stopped.'), true);
                }).catch(function (error) {
                    if (seq !== jobState.runSeq) {
                        return;
                    }
                    stopRun(error && error.message ? error.message : 'Generation stopped.', true);
                });
            }

            function waitForRunning(jobId, seq) {
                if (seq !== jobState.runSeq) {
                    return;
                }
                var formData = new FormData();
                formData.append('csrf_token', csrfToken());
                formData.append('action', 'get');
                formData.append('job_id', String(jobId));
                post(endpoint, formData).then(function (result) {
                    if (seq !== jobState.runSeq) {
                        return;
                    }
                    if (!result || !result.success || !result.job) {
                        stopRun(responseMessage(result, 'The job could not be refreshed.'), true);
                        return;
                    }
                    renderJob(result.job);
                    var hasRunning = (result.job.steps || []).some(function (step) {
                        return step.status === 'running';
                    });
                    if (hasRunning) {
                        window.setTimeout(function () {
                            waitForRunning(jobId, seq);
                        }, 2000);
                        return;
                    }
                    if (result.job.status === 'generating') {
                        stepLoop(jobId, seq);
                        return;
                    }
                    stopRun('', false);
                }).catch(function (error) {
                    if (seq !== jobState.runSeq) {
                        return;
                    }
                    stopRun(error && error.message ? error.message : 'The job could not be refreshed.', true);
                });
            }

            function cancelJob(jobId) {
                if (!window.confirm('Cancel this generation job? Pending steps will be skipped and the job cannot resume.')) {
                    return;
                }
                var formData = new FormData();
                formData.append('csrf_token', csrfToken());
                formData.append('action', 'cancel');
                formData.append('job_id', String(jobId));
                setBusy(true);
                post(endpoint, formData).then(function (result) {
                    if (result.job) {
                        renderJob(result.job);
                    }
                    notify(responseMessage(result, 'The job was cancelled.'), result.success ? 'success' : 'error');
                }).catch(function (error) {
                    notify(error && error.message ? error.message : 'The job could not be cancelled.', 'error');
                }).finally(function () {
                    jobState.running = false;
                    jobState.runSeq++;
                    setBusy(false);
                    refreshJobs();
                });
            }

            function retryJob(jobId) {
                var formData = new FormData();
                formData.append('csrf_token', csrfToken());
                formData.append('action', 'retry');
                formData.append('job_id', String(jobId));
                setBusy(true);
                post(endpoint, formData).then(function (result) {
                    if (result.job) {
                        renderJob(result.job);
                    }
                    if (result.success) {
                        notify(result.message || 'The failed step was retried.', 'success');
                        if (result.job && result.job.status === 'generating') {
                            runJob(jobId);
                        } else {
                            setBusy(false);
                        }
                    } else {
                        setBusy(false);
                        notify(responseMessage(result, 'The failed step could not be retried.'), 'error');
                    }
                }).catch(function (error) {
                    setBusy(false);
                    notify(error && error.message ? error.message : 'The failed step could not be retried.', 'error');
                }).finally(function () {
                    refreshJobs();
                });
            }

            function applyCourse(job) {
                if (!window.confirm('Create a new draft course from this blueprint? The course will not be published or assigned, and no trainee progress will be created.')) {
                    return;
                }
                var formData = new FormData();
                formData.append('csrf_token', csrfToken());
                formData.append('action', 'apply');
                formData.append('job_id', String(job.id));
                setBusy(true);
                post(endpoint, formData).then(function (result) {
                    if (result && result.success) {
                        notify(result.message || 'The draft course was created.', 'success');
                        var target = result.redirect
                            || ('?page=instructor/elearning-subpage/course-structure&course_id=' + Number(result.course_id || 0));
                        window.setTimeout(function () {
                            window.location.href = target;
                        }, 500);
                        return;
                    }
                    setBusy(false);
                    notify(responseMessage(result, 'The draft course could not be created.'), 'error');
                }).catch(function (error) {
                    setBusy(false);
                    notify(error && error.message ? error.message : 'The draft course could not be created.', 'error');
                });
            }

            function createOutline(event) {
                event.preventDefault();
                if (!state.selected) {
                    setFormMessage(formStatus, 'Select a trainee in Step 1 first.', true);
                    var search = root.querySelector('[data-ai-trainee-search]');
                    if (search) {
                        search.focus();
                    }
                    return;
                }
                if (!form.reportValidity()) {
                    return;
                }
                var formData = new FormData(form);
                formData.append('csrf_token', csrfToken());
                formData.append('action', 'create');
                formData.append('learner_id', String(state.selected.id));
                state.selectedSkillIds.forEach(function (skillId) {
                    formData.append('selected_skill_ids[]', skillId);
                });
                state.excluded.forEach(function (key) {
                    formData.append('excluded[]', key);
                });
                setBusy(true);
                setFormMessage(formStatus, 'Generating the outline. This can take a moment...', false);
                post(endpoint, formData).then(function (result) {
                    if (result.job) {
                        renderJob(result.job);
                    }
                    if (result.success) {
                        setFormMessage(formStatus, 'Outline ready. Review it below.', false);
                        notify(result.message || 'The outline is ready for review.', 'success');
                        refreshJobs();
                        return;
                    }
                    var message = responseMessage(result, 'The outline could not be generated.');
                    setFormMessage(formStatus, message, true);
                    notify(message, 'error');
                    refreshJobs();
                }).catch(function (error) {
                    var message = error && error.message ? error.message : 'The outline could not be generated.';
                    setFormMessage(formStatus, message, true);
                    notify(message, 'error');
                }).finally(function () {
                    setBusy(false);
                });
            }

            function approveOutline() {
                if (!jobState.job || !jobState.outline) {
                    setFormMessage(outlineStatus, 'Generate an outline before approving it.', true);
                    return;
                }
                var outline = collectOutline();
                var formData = new FormData();
                formData.append('csrf_token', csrfToken());
                formData.append('action', 'approve');
                formData.append('job_id', String(jobState.job.id));
                formData.append('outline_json', JSON.stringify(outline));
                setBusy(true);
                setFormMessage(outlineStatus, 'Approving the outline...', false);
                post(endpoint, formData).then(function (result) {
                    if (result.success && result.job) {
                        setFormMessage(outlineStatus, '', false);
                        notify(result.message || 'Outline approved.', 'success');
                        renderJob(result.job);
                        runJob(result.job.id);
                        refreshJobs();
                        return;
                    }
                    setBusy(false);
                    var message = responseMessage(result, 'The outline could not be approved.');
                    setFormMessage(outlineStatus, message, true);
                    notify(message, 'error');
                }).catch(function (error) {
                    setBusy(false);
                    var message = error && error.message ? error.message : 'The outline could not be approved.';
                    setFormMessage(outlineStatus, message, true);
                    notify(message, 'error');
                });
            }

            form.addEventListener('submit', createOutline);
            if (approveButton) {
                approveButton.addEventListener('click', approveOutline);
            }
            state.onTraineeChange = renderTraineeState;
            renderTraineeState();

            if (jobsList) {
                jobsList.addEventListener('click', function (event) {
                    var open = event.target.closest ? event.target.closest('[data-ai-open-job]') : null;
                    if (!open) {
                        return;
                    }
                    var jobId = open.getAttribute('data-ai-open-job');
                    var generateTab = tabs.filter(function (tab) {
                        return tab.getAttribute('data-tab') === 'ai-generate';
                    })[0];
                    if (generateTab) {
                        generateTab.click();
                        scheduleSync();
                    }
                    loadJob(jobId);
                });
            }

            if (jobsPanel) {
                var refreshButton = jobsPanel.querySelector('[data-ai-refresh-jobs]');
                if (refreshButton) {
                    refreshButton.addEventListener('click', refreshJobs);
                }
            }
        }

        var actionMap = {
            'key-test': {
                endpoint: 'pages/admin/ai-assistant/ajax/test-key.php',
                idField: 'key-id',
                reloadOnSuccess: true
            },
            'key-activate': {
                endpoint: 'pages/admin/ai-assistant/ajax/keys.php',
                action: 'activate',
                idField: 'key-id',
                reloadOnSuccess: true
            },
            'key-disable': {
                endpoint: 'pages/admin/ai-assistant/ajax/keys.php',
                action: 'disable',
                idField: 'key-id',
                confirm: 'Disable this key? If it is active, generation will be blocked until another key is activated.',
                reloadOnSuccess: true
            },
            'key-revoke': {
                endpoint: 'pages/admin/ai-assistant/ajax/keys.php',
                action: 'revoke',
                idField: 'key-id',
                confirm: 'Revoke this key permanently? This cannot be undone.',
                reloadOnSuccess: true
            },
            'model-test': {
                endpoint: 'pages/admin/ai-assistant/ajax/test-model.php',
                idField: 'model-id',
                reloadOnSuccess: false
            },
            'model-enable': {
                endpoint: 'pages/admin/ai-assistant/ajax/models.php',
                action: 'enable',
                idField: 'model-id',
                reloadOnSuccess: true
            },
            'model-disable': {
                endpoint: 'pages/admin/ai-assistant/ajax/models.php',
                action: 'disable',
                idField: 'model-id',
                confirm: 'Disable this model? It cannot stay as the default or fallback while disabled.',
                reloadOnSuccess: true
            },
            'model-default': {
                endpoint: 'pages/admin/ai-assistant/ajax/models.php',
                action: 'default',
                idField: 'model-id',
                reloadOnSuccess: true
            },
            'model-fallback': {
                endpoint: 'pages/admin/ai-assistant/ajax/models.php',
                action: 'fallback',
                idField: 'model-id',
                reloadOnSuccess: true
            },
            'sync-models': {
                endpoint: 'pages/admin/ai-assistant/ajax/sync-models.php',
                reloadOnSuccess: true
            }
        };

        root.querySelectorAll('[data-ai-action]').forEach(function (button) {
            button.addEventListener('click', function () {
                var action = button.getAttribute('data-ai-action');
                var config = actionMap[action];
                if (!config) {
                    return;
                }
                if (config.confirm && !window.confirm(config.confirm)) {
                    return;
                }

                var formData = new FormData();
                formData.append('csrf_token', csrfToken());
                if (config.action) {
                    formData.append('action', config.action);
                }
                if (config.idField) {
                    formData.append('id', button.getAttribute('data-' + config.idField) || '');
                }

                var endpoint = button.getAttribute('data-ai-endpoint') || config.endpoint;
                button.disabled = true;
                button.classList.add('is-loading');

                post(endpoint, formData).then(function (result) {
                    if (result && result.success) {
                        notify(responseMessage(result, 'Saved.'), 'success');
                        if (config.reloadOnSuccess) {
                            reloadWithTab(activeTabId());
                        }
                        return;
                    }

                    var message = responseMessage(result, 'The action could not be completed.');
                    notify(message, 'error');
                    if (action === 'key-test') {
                        // A failed test is still recorded server-side; refresh so
                        // the key card and history show the new test status.
                        reloadWithTab(activeTabId());
                    }
                }).catch(function (error) {
                    notify(error && error.message ? error.message : 'The action could not be completed.', 'error');
                }).finally(function () {
                    button.disabled = false;
                    button.classList.remove('is-loading');
                });
            });
        });

        var settingsForm = root.querySelector('#ai-settings-form');
        if (settingsForm) {
            settingsForm.addEventListener('submit', function (event) {
                event.preventDefault();
                var status = settingsForm.querySelector('[data-ai-form-status]');
                var submit = settingsForm.querySelector('button[type="submit"]');
                var costMode = settingsForm.querySelector('select[name="model_cost_mode"]');
                var initialCostMode = costMode ? costMode.value : '';
                var formData = new FormData(settingsForm);

                if (submit) {
                    submit.disabled = true;
                }
                setStatus(status, 'Saving...', false);

                post(settingsForm.getAttribute('data-ai-endpoint'), formData).then(function (result) {
                    if (result && result.success) {
                        setStatus(status, responseMessage(result, 'Settings saved.'), false);
                        notify('Settings saved.', 'success');
                        if (costMode && costMode.value !== initialCostMode) {
                            reloadWithTab('ai-settings');
                        }
                        return;
                    }
                    var message = responseMessage(result, 'Settings could not be saved.');
                    setStatus(status, message, true);
                    notify(message, 'error');
                }).catch(function (error) {
                    var message = error && error.message ? error.message : 'Settings could not be saved.';
                    setStatus(status, message, true);
                    notify(message, 'error');
                }).finally(function () {
                    if (submit) {
                        submit.disabled = false;
                    }
                });
            });
        }

        var keyForm = root.querySelector('#ai-key-form');
        if (keyForm) {
            keyForm.addEventListener('submit', function (event) {
                event.preventDefault();
                var status = keyForm.querySelector('[data-ai-form-status]');
                var submit = keyForm.querySelector('button[type="submit"]');
                var keyInput = keyForm.querySelector('input[name="api_key"]');
                var formData = new FormData(keyForm);

                if (!String(formData.get('api_key') || '').trim()) {
                    setStatus(status, 'Enter an OpenRouter API key.', true);
                    if (keyInput) {
                        keyInput.focus();
                    }
                    return;
                }

                if (submit) {
                    submit.disabled = true;
                }
                setStatus(status, 'Encrypting and saving...', false);

                post(keyForm.getAttribute('data-ai-endpoint'), formData).then(function (result) {
                    if (result && result.success) {
                        if (keyInput) {
                            keyInput.value = '';
                        }
                        setStatus(status, responseMessage(result, 'Key saved.'), false);
                        notify(responseMessage(result, 'Key saved.'), 'success');
                        reloadWithTab('ai-keys');
                        return;
                    }
                    var message = responseMessage(result, 'The key could not be saved.');
                    setStatus(status, message, true);
                    notify(message, 'error');
                }).catch(function (error) {
                    var message = error && error.message ? error.message : 'The key could not be saved.';
                    setStatus(status, message, true);
                    notify(message, 'error');
                }).finally(function () {
                    if (submit) {
                        submit.disabled = false;
                    }
                });
            });
        }

        var modelForm = root.querySelector('#ai-model-form');
        if (modelForm) {
            modelForm.addEventListener('submit', function (event) {
                event.preventDefault();
                var status = modelForm.querySelector('[data-ai-form-status]');
                var submit = modelForm.querySelector('button[type="submit"]');
                var formData = new FormData(modelForm);

                if (submit) {
                    submit.disabled = true;
                }
                setStatus(status, 'Adding...', false);

                post(modelForm.getAttribute('data-ai-endpoint'), formData).then(function (result) {
                    if (result && result.success) {
                        setStatus(status, responseMessage(result, 'Model added.'), false);
                        notify(responseMessage(result, 'Model added.'), 'success');
                        reloadWithTab('ai-keys');
                        return;
                    }
                    var message = responseMessage(result, 'The model could not be added.');
                    setStatus(status, message, true);
                    notify(message, 'error');
                }).catch(function (error) {
                    var message = error && error.message ? error.message : 'The model could not be added.';
                    setStatus(status, message, true);
                    notify(message, 'error');
                }).finally(function () {
                    if (submit) {
                        submit.disabled = false;
                    }
                });
            });
        }

        var modelSearch = root.querySelector('[data-ai-model-search]');
        if (modelSearch) {
            modelSearch.addEventListener('input', function () {
                var term = modelSearch.value.trim().toLowerCase();
                var rows = Array.prototype.slice.call(root.querySelectorAll('[data-ai-model-row]'));
                var visible = 0;
                rows.forEach(function (row) {
                    var haystack = (row.getAttribute('data-ai-model-search') || '').toLowerCase();
                    var show = term === '' || haystack.indexOf(term) !== -1;
                    row.hidden = !show;
                    if (show) {
                        visible++;
                    }
                });
                var empty = root.querySelector('[data-ai-model-empty]');
                if (empty) {
                    empty.hidden = visible !== 0;
                }
            });
        }

        syncAria();

        // A full reload after a key/model action returns to the same section.
        if (document.readyState === 'complete') {
            window.setTimeout(restoreReloadTab, 0);
        } else {
            window.addEventListener('load', function () {
                window.setTimeout(restoreReloadTab, 0);
            }, { once: true });
        }
    }

    window.__aiAssistantInit = init;

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    window.addEventListener('page:loaded', function (event) {
        var page = event && event.detail ? String(event.detail.page || '') : '';
        if (page.indexOf('admin/ai-assistant') !== -1) {
            init();
        }
    });
})();
