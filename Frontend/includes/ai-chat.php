<?php
/** AI Customer Support Chat Widget — included on all public pages */
if (!function_exists('url')) {
    require_once __DIR__ . '/../../Backend/lib/helpers.php';
}
if (!isset($config)) {
    $config = appConfig();
}
$whatsappUrl = 'https://wa.me/' . ($config['whatsapp_number'] ?? '');
$chatApiUrl = url('Backend/api/chat.php');
?>
<div class="ai-chat" id="ai-chat" aria-hidden="true" data-api-url="<?= sanitize($chatApiUrl) ?>">
    <div class="ai-chat__panel" id="ai-chat-panel" role="dialog" aria-labelledby="ai-chat-title" aria-modal="true">
        <div class="ai-chat__header">
            <div class="ai-chat__header-info">
                <span class="ai-chat__avatar" aria-hidden="true">AI</span>
                <div>
                    <h3 class="ai-chat__title" id="ai-chat-title"><?= sanitize($config['site_name']) ?> Support</h3>
                    <p class="ai-chat__status"><span class="ai-chat__dot"></span> Powered by Groq AI</p>
                </div>
            </div>
            <button type="button" class="ai-chat__close" id="ai-chat-close" aria-label="Close chat">&times;</button>
        </div>

        <div class="ai-chat__messages" id="ai-chat-messages" role="log" aria-live="polite">
            <div class="ai-chat__msg ai-chat__msg--bot">
                <div class="ai-chat__bubble">
                    Hi! 👋 I'm your AI assistant. Ask me about our cars, prices, or how to contact us. How can I help you today?
                </div>
            </div>
        </div>

        <div class="ai-chat__quick" id="ai-chat-quick">
            <button type="button" class="ai-chat__quick-btn" data-prompt="What cars do you have available?">Available cars</button>
            <button type="button" class="ai-chat__quick-btn" data-prompt="What is your price range?">Price range</button>
            <button type="button" class="ai-chat__quick-btn" data-prompt="How do I contact you on WhatsApp?">WhatsApp</button>
        </div>

        <form class="ai-chat__input-wrap" id="ai-chat-form">
            <input
                type="text"
                id="ai-chat-input"
                class="ai-chat__input"
                placeholder="Type your message..."
                autocomplete="off"
                maxlength="500"
                aria-label="Chat message"
            >
            <button type="submit" class="ai-chat__send" id="ai-chat-send" aria-label="Send message">
                <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 2L11 13M22 2l-7 20-4-9-9-4 20-7z"/></svg>
            </button>
        </form>

        <div class="ai-chat__footer">
            <a href="<?= sanitize($whatsappUrl) ?>" target="_blank" rel="noopener" class="ai-chat__whatsapp">
                Prefer a human? Chat on WhatsApp
            </a>
        </div>
    </div>

    <button type="button" class="ai-chat__launcher" id="ai-chat-launcher" aria-label="Open AI support chat" aria-expanded="false">
        <svg class="ai-chat__launcher-icon ai-chat__launcher-icon--chat" viewBox="0 0 24 24" width="26" height="26" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 01-2 2H7l-4 4V5a2 2 0 012-2h14a2 2 0 012 2z"/></svg>
        <svg class="ai-chat__launcher-icon ai-chat__launcher-icon--close" viewBox="0 0 24 24" width="26" height="26" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6L6 18M6 6l12 12"/></svg>
        <span class="ai-chat__launcher-label">AI Support</span>
    </button>
</div>
<script>
(function () {
    var chat = document.getElementById('ai-chat');
    var launcher = document.getElementById('ai-chat-launcher');
    var closeBtn = document.getElementById('ai-chat-close');
    if (!chat || !launcher) return;

    function setOpen(open) {
        if (open) {
            chat.classList.add('is-open');
            chat.setAttribute('aria-hidden', 'false');
            launcher.setAttribute('aria-expanded', 'true');
            document.body.classList.add('ai-chat-open');
            var input = document.getElementById('ai-chat-input');
            if (input) setTimeout(function () { input.focus(); }, 200);
        } else {
            chat.classList.remove('is-open');
            chat.setAttribute('aria-hidden', 'true');
            launcher.setAttribute('aria-expanded', 'false');
            document.body.classList.remove('ai-chat-open');
        }
    }

    window.VA_AI_CHAT = {
        open: function () { setOpen(true); },
        close: function () { setOpen(false); },
        toggle: function () { setOpen(!chat.classList.contains('is-open')); },
        isOpen: function () { return chat.classList.contains('is-open'); }
    };

    launcher.addEventListener('click', function (e) {
        e.preventDefault();
        window.VA_AI_CHAT.toggle();
    });
    if (closeBtn) closeBtn.addEventListener('click', function () { window.VA_AI_CHAT.close(); });

    var footerBtn = document.getElementById('footer-open-chat');
    if (footerBtn) footerBtn.addEventListener('click', function () { window.VA_AI_CHAT.open(); });

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && window.VA_AI_CHAT.isOpen()) window.VA_AI_CHAT.close();
    });
})();
</script>
