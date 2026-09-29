(function() {
    'use strict';

    var chatInput = document.getElementById('lcChatInput');
    var chatSend = document.getElementById('lcChatSend');
    var chatMessages = document.getElementById('lcChatMessages');
    var chatEndpoint = '/modules/compliance/lib/ajax/legal-case-chat.php';

    if (!chatInput || !chatSend || !chatMessages) return;

    function appendMessage(text, type) {
        var msg = document.createElement('div');
        msg.className = 'lc-chat-msg lc-chat-msg--' + type;
        var bubble = document.createElement('div');
        bubble.className = 'lc-chat-bubble';
        bubble.textContent = text;
        msg.appendChild(bubble);
        chatMessages.appendChild(msg);
        chatMessages.scrollTop = chatMessages.scrollHeight;
    }

    function setLoading(loading) {
        chatSend.disabled = loading;
        chatInput.disabled = loading;
        if (loading) {
            chatSend.textContent = '...';
        } else {
            chatSend.textContent = 'Send';
            chatInput.focus();
        }
    }

    function sendMessage() {
        var query = chatInput.value.trim();
        if (!query) return;

        appendMessage(query, 'user');
        chatInput.value = '';
        setLoading(true);

        var xhr = new XMLHttpRequest();
        xhr.open('POST', chatEndpoint, true);
        xhr.setRequestHeader('Content-Type', 'application/json');
        xhr.onreadystatechange = function() {
            if (xhr.readyState !== 4) return;
            setLoading(false);
            if (xhr.status >= 200 && xhr.status < 300) {
                try {
                    var data = JSON.parse(xhr.responseText);
                    if (data.success && data.message) {
                        appendMessage(data.message, 'bot');
                    } else {
                        appendMessage('I couldn\'t process that request. Please try again.', 'bot');
                    }
                } catch (e) {
                    appendMessage('I couldn\'t process that request. Please try again.', 'bot');
                }
            } else {
                appendMessage('I couldn\'t reach the analytics service. Please try again.', 'bot');
            }
        };
        xhr.onerror = function() {
            setLoading(false);
            appendMessage('I couldn\'t reach the analytics service. Please try again.', 'bot');
        };

        try {
            xhr.send(JSON.stringify({ query: query }));
        } catch (e) {
            setLoading(false);
            appendMessage('I couldn\'t send your question. Please try again.', 'bot');
        }
    }

    chatSend.addEventListener('click', sendMessage);

    chatInput.addEventListener('keydown', function(e) {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            sendMessage();
        }
    });
})();

