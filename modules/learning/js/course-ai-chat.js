(function () {
    var chat = document.getElementById('course-ai-chat');
    var openButton = document.querySelector('[data-course-ai-chat-open]');
    if (!chat || !openButton) return;

    var closeButton = chat.querySelector('[data-course-ai-chat-close]');
    var messagesElement = chat.querySelector('[data-course-ai-chat-messages]');
    var statusElement = chat.querySelector('[data-course-ai-chat-status]');
    var form = chat.querySelector('[data-course-ai-chat-form]');
    var input = form.querySelector('textarea[name="question"]');
    var sendButton = form.querySelector('button[type="submit"]');
    var history = [];

    function setOpen(isOpen) {
        chat.hidden = !isOpen;
        openButton.hidden = isOpen;
        openButton.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
        if (isOpen) window.requestAnimationFrame(function () { input.focus(); });
    }

    function setStatus(message, isError) {
        statusElement.textContent = message || '';
        statusElement.classList.toggle('is-error', Boolean(isError));
    }

    function appendMessage(role, content) {
        var message = document.createElement('div');
        message.className = 'course-ai-chat-message is-' + role;
        message.textContent = content;
        messagesElement.appendChild(message);
        messagesElement.scrollTop = messagesElement.scrollHeight;
    }

    function currentCourseContext() {
        var courseForm = document.getElementById('course-form');
        if (!courseForm) return {};
        function value(name) {
            var field = courseForm.querySelector('[name="' + name + '"]');
            return field ? field.value.trim() : '';
        }
        var modules = window.courseBuilder && window.courseBuilder.courseData
            ? window.courseBuilder.courseData.modules || []
            : [];
        return {
            title: value('title'),
            category: value('category'),
            difficulty: value('difficulty'),
            description: value('description'),
            modules: modules.slice(0, 8).map(function (module) {
                return {
                    title: module.title || '',
                    lessons: (module.lessons || []).slice(0, 12).map(function (lesson) {
                        return lesson.title || '';
                    })
                };
            })
        };
    }

    openButton.addEventListener('click', function () {
        setOpen(chat.hidden);
    });
    closeButton.addEventListener('click', function () {
        setOpen(false);
        openButton.focus();
    });
    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && !chat.hidden) {
            setOpen(false);
            openButton.focus();
        }
    });

    form.addEventListener('submit', function (event) {
        event.preventDefault();
        var question = input.value.trim();
        if (!question || sendButton.disabled) return;

        appendMessage('user', question);
        setStatus('Thinking…', false);
        sendButton.disabled = true;
        input.disabled = true;

        var csrfToken = window.CSRF_TOKEN
            || (document.querySelector('meta[name="csrf-token"]') || {}).content
            || '';
        fetch(chat.dataset.endpoint, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-Token': csrfToken
            },
            body: JSON.stringify({
                question: question,
                history: history.slice(-8),
                course: currentCourseContext()
            })
        })
        .then(function (response) {
            return response.text().then(function (body) {
                var data;
                try {
                    data = JSON.parse(body);
                } catch (error) {
                    var message = 'The server returned an unexpected response (HTTP ' + response.status + ').';
                    if (response.status >= 500) {
                        message += ' Check the server PHP error log.';
                    }
                    throw new Error(message);
                }
                if (!response.ok || !data.success || typeof data.reply !== 'string') {
                    var message = data.error || data.message || 'The assistant could not answer. Try again.';
                    if (Number.isInteger(data.provider_http_status)) {
                        message += data.provider_http_status > 0
                            ? ' (AI provider HTTP ' + data.provider_http_status + ').'
                            : ' (No HTTP response from the AI provider.)';
                    }
                    throw new Error(message);
                }
                return data;
            });
        })
        .then(function (data) {
            appendMessage('assistant', data.reply);
            history.push({ role: 'user', content: question });
            history.push({ role: 'assistant', content: data.reply });
            history = history.slice(-8);
            input.value = '';
            setStatus('', false);
        })
        .catch(function (error) {
            setStatus(error.message || 'The assistant could not answer. Try again.', true);
        })
        .finally(function () {
            sendButton.disabled = false;
            input.disabled = false;
            input.focus();
        });
    });
})();
