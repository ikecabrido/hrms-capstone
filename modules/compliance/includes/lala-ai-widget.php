<?php
$llAssistantEndpoint = '/modules/compliance/lib/ajax/lala-ai-chat.php';
$llEmployeeName = trim((string) ($_SESSION['employee_name'] ?? 'there'));
$llFirstName = explode(' ', $llEmployeeName)[0];
$llFirstNameEscaped = htmlspecialchars($llFirstName, ENT_QUOTES, 'UTF-8');
?>
<div id="ll-assistant-root" class="ll-assistant-root" data-ll-first-name="<?= $llFirstNameEscaped ?>">
    <button
        id="ll-assistant-toggle"
        class="ll-assistant-toggle"
        aria-label="Open Lala AI"
        aria-expanded="false"
        aria-controls="ll-assistant-panel"
        type="button"
    >
        <img src="/modules/compliance/assets/lala_ai.png" alt="" class="ll-assistant-toggle-img" />
    </button>

    <div
        id="ll-assistant-panel"
        class="ll-assistant-panel"
        aria-hidden="true"
        role="dialog"
        aria-modal="true"
        aria-label="Lala AI - HR Compliance Assistant"
    >
        <div class="ll-assistant-header">
            <div class="ll-assistant-header-left">
                <div style="position:relative;">
                    <img src="/modules/compliance/assets/lala_ai.png" alt="Lala AI" class="ll-assistant-header-img" />
                </div>
                <div>
                    <strong>Lala AI</strong>
                    <div class="ll-assistant-sub">Labor Law Assistant</div>
                </div>
            </div>
            <div class="ll-assistant-header-actions">
                <button
                    id="ll-assistant-clear"
                    class="ll-assistant-action-btn"
                    aria-label="New conversation"
                    type="button"
                    title="New conversation"
                >
                    <i class="fa-solid fa-rotate-right"></i>
                </button>
                <button
                    id="ll-assistant-minimize"
                    class="ll-assistant-action-btn"
                    aria-label="Minimize assistant"
                    type="button"
                    title="Minimize"
                >
                    <i class="fa-solid fa-minus"></i>
                </button>
            </div>
        </div>

        <div id="ll-assistant-body" class="ll-assistant-body" aria-live="polite" aria-atomic="false"></div>

        <div id="ll-assistant-quick-replies" class="ll-assistant-quick-replies" aria-live="polite"></div>

        <div class="ll-assistant-footer">
            <form id="ll-assistant-form" class="ll-assistant-form" autocomplete="off" data-skip>
                <label for="ll-assistant-input" class="ll-sr-only">Describe your HR or workplace concern</label>
                <textarea
                    id="ll-assistant-input"
                    class="ll-assistant-input"
                    rows="1"
                    placeholder="Describe your HR or workplace concern..."
                    aria-label="Describe your HR or workplace concern"
                    maxlength="2000"
                ></textarea>
                <button
                    id="ll-assistant-submit"
                    class="ll-assistant-submit"
                    type="button"
                    aria-label="Send message"
                >
                    <i class="fa-solid fa-paper-plane"></i>
                </button>
            </form>
        </div>
    </div>
</div>

<link rel="stylesheet" href="/modules/compliance/includes/lala-ai-widget.css?v=1790791968">
<link rel="stylesheet" href="/modules/compliance/css/pages/lala-ai-developer.css">
<script src="js/pages/lala-ai-chat.js?v=1790791769"></script>

