    </main>
    <footer class="site-footer">
        <div class="container site-footer__grid">
            <div class="site-footer__brand">
                <div class="site-footer__logo">
                    <img
                        src="<?= sanitize($config['logo_url'] ?? url('Frontend/assets/images/log.jpg')) ?>"
                        alt="<?= sanitize($config['site_name']) ?>"
                        class="site-logo-img site-logo-img--footer"
                        width="160"
                        height="40"
                    >
                </div>
                <p><?= sanitize($config['site_tagline']) ?></p>
                <p class="site-footer__desc">Quality pre-owned vehicles with transparent pricing. Visit us or chat with our AI assistant anytime.</p>
            </div>

            <div class="site-footer__links">
                <h4>Quick Links</h4>
                <a href="<?= sanitize(url('Frontend/index.php')) ?>">Home</a>
                <a href="<?= sanitize(url('Frontend/listings.php')) ?>">Browse Cars</a>
                <a href="<?= sanitize(url('Backend/admin/login.php')) ?>">Admin</a>
            </div>

            <div class="site-footer__contact">
                <h4>Contact Us</h4>
                <a href="https://wa.me/<?= sanitize($config['whatsapp_number']) ?>" target="_blank" rel="noopener" class="btn btn--whatsapp btn--sm btn--block-footer">WhatsApp Us</a>
                <button type="button" class="btn btn--outline-light btn--sm btn--block-footer" id="footer-open-chat">AI Support Chat</button>
            </div>

            <div class="site-footer__social">
                <h4>Follow Us</h4>
                <div class="social-links">
                    <?php
                    require_once __DIR__ . '/social-icons.php';
                    $social = $config['social'] ?? [];
                    $socialLabels = [
                        'facebook'  => 'Facebook',
                        'instagram' => 'Instagram',
                        'twitter'   => 'X (Twitter)',
                        'tiktok'    => 'TikTok',
                        'youtube'   => 'YouTube',
                        'linkedin'  => 'LinkedIn',
                    ];
                    foreach ($socialLabels as $key => $label):
                        $url = trim($social[$key] ?? '');
                        if ($url === '') continue;
                    ?>
                    <a href="<?= sanitize($url) ?>" class="social-link social-link--<?= sanitize($key) ?>" target="_blank" rel="noopener noreferrer" aria-label="<?= sanitize($label) ?>" title="<?= sanitize($label) ?>">
                        <?= renderSocialIcon($key) ?>
                    </a>
                    <?php endforeach; ?>
                    <a href="https://wa.me/<?= sanitize($config['whatsapp_number']) ?>" class="social-link social-link--whatsapp" target="_blank" rel="noopener" aria-label="WhatsApp" title="WhatsApp">
                        <?= renderSocialIcon('whatsapp') ?>
                    </a>
                </div>
            </div>
        </div>
        <div class="site-footer__bottom container">
            <p>&copy; <?= date('Y') ?> <?= sanitize($config['site_name']) ?>. All rights reserved.</p>
            <p class="site-footer__powered">Customer support powered by Groq AI</p>
        </div>
    </footer>

    <?php include __DIR__ . '/ai-chat.php'; ?>

    <?php if (in_array(basename($_SERVER['PHP_SELF']), ['index.php', 'car.php', 'listings.php'], true)): ?>
    <?php include __DIR__ . '/lead-modal.php'; ?>
    <?php endif; ?>

    <?php if (in_array(basename($_SERVER['PHP_SELF']), ['index.php', 'listings.php'], true)): ?>
    <div class="image-lightbox" id="image-lightbox" aria-hidden="true" role="dialog" aria-modal="true" aria-label="Car photo viewer">
        <button type="button" class="image-lightbox__backdrop" aria-label="Close viewer"></button>
        <div class="image-lightbox__dialog">
            <div class="image-lightbox__header">
                <div>
                    <p class="image-lightbox__title" id="lightbox-title"></p>
                    <p class="image-lightbox__counter" id="lightbox-counter"></p>
                </div>
                <button type="button" class="image-lightbox__close" id="lightbox-close" aria-label="Close">&times;</button>
            </div>
            <div class="image-lightbox__stage">
                <button type="button" class="image-lightbox__nav image-lightbox__nav--prev" id="lightbox-prev" aria-label="Previous image">
                    <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2"><path d="M15 18l-6-6 6-6"/></svg>
                </button>
                <img src="" alt="" class="image-lightbox__img" id="lightbox-img">
                <button type="button" class="image-lightbox__nav image-lightbox__nav--next" id="lightbox-next" aria-label="Next image">
                    <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 18l6-6-6-6"/></svg>
                </button>
            </div>
            <div class="image-lightbox__thumbs" id="lightbox-thumbs"></div>
        </div>
    </div>
    <?php endif; ?>

    <script src="<?= sanitize(url('Frontend/assets/js/main.js')) ?>?v=5"></script>
    <script src="<?= sanitize(url('Frontend/assets/js/tracking.js')) ?>"></script>
    <script src="<?= sanitize(url('Frontend/assets/js/ai-chat.js')) ?>?v=3"></script>
    <?php if (in_array(basename($_SERVER['PHP_SELF']), ['index.php', 'car.php', 'listings.php'], true)): ?>
    <script src="<?= sanitize(url('Frontend/assets/js/leads.js')) ?>?v=4"></script>
    <?php endif; ?>
    <?php if (basename($_SERVER['PHP_SELF']) === 'car.php'): ?>
    <script src="<?= sanitize(url('Frontend/assets/js/car.js')) ?>"></script>
    <?php endif; ?>
    <!-- PWA Install Popup Prompt -->
    <div id="pwa-install-prompt" class="pwa-install-prompt" aria-hidden="true" role="dialog" aria-labelledby="pwa-prompt-title">
        <button type="button" class="pwa-install-prompt__close" id="pwa-prompt-close" aria-label="Close installation prompt">&times;</button>
        <div class="pwa-install-prompt__content">
            <div class="pwa-install-prompt__header">
                <div class="pwa-install-prompt__icon-container">
                    <img src="<?= sanitize(url('Frontend/assets/images/icon-192.png')) ?>" alt="<?= sanitize($config['site_name']) ?>" class="pwa-install-prompt__app-icon">
                    <div class="pwa-install-prompt__badge-notify" aria-hidden="true">
                        <svg viewBox="0 0 24 24" width="10" height="10" fill="currentColor">
                            <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-6h2v6zm0-8h-2V7h2v2z"/>
                        </svg>
                    </div>
                </div>
                <div class="pwa-install-prompt__title-area">
                    <h3 id="pwa-prompt-title">Get Instant Updates</h3>
                    <p class="pwa-install-prompt__subtitle">Install our PWA</p>
                </div>
            </div>
            
            <p class="pwa-install-prompt__body">
                Install our web app to receive instant notifications when new cars are posted to our showroom!
            </p>

            <!-- Standard Actions (for Chromium / Android / Desktop) -->
            <div class="pwa-install-prompt__actions" id="pwa-standard-actions">
                <button type="button" class="btn btn--primary" id="pwa-btn-install">Install App</button>
                <button type="button" class="btn btn--outline" id="pwa-btn-dismiss">Maybe Later</button>
            </div>

            <!-- iOS Specific Manual Install Instructions -->
            <div class="pwa-install-prompt__ios-instructions" id="pwa-ios-instructions" style="display: none;">
                <p class="pwa-install-prompt__ios-text">
                    Tap the share button <span class="pwa-ios-icon-inline"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M4 12v8a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-8"></path><polyline points="16 6 12 2 8 6"></polyline><line x1="12" y1="2" x2="12" y2="15"></line></svg></span> and select <strong>Add to Home Screen</strong> <span class="pwa-ios-icon-inline"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><line x1="12" y1="8" x2="12" y2="16"></line><line x1="8" y1="12" x2="16" y2="12"></line></svg></span>.
                </p>
            </div>
        </div>
    </div>

    <!-- PWA Service Worker & Install Prompt Logic -->
    <script>
    // PWA Service Worker Registration
    if ('serviceWorker' in navigator) {
        window.addEventListener('load', () => {
            navigator.serviceWorker.register('<?= sanitize(url("sw.js")) ?>', { scope: '<?= sanitize(url("")) ?>' })
                .then(reg => {
                    console.log('PWA ServiceWorker registered successfully for scope:', reg.scope);
                })
                .catch(err => {
                    console.error('PWA ServiceWorker registration failed:', err);
                });
        });
    }

    // PWA Install Prompt Handler
    (function() {
        let deferredPrompt = null;
        const promptEl = document.getElementById('pwa-install-prompt');
        const btnInstall = document.getElementById('pwa-btn-install');
        const btnDismiss = document.getElementById('pwa-btn-dismiss');
        const btnClose = document.getElementById('pwa-prompt-close');
        const standardActions = document.getElementById('pwa-standard-actions');
        const iosInstructions = document.getElementById('pwa-ios-instructions');

        const STORAGE_KEY = 'pwa-install-prompt-dismissed';
        const COOLDOWN_DAYS = 7; // Hide for 7 days after dismissal

        // Check if recently dismissed
        function isDismissed() {
            const dismissedTime = localStorage.getItem(STORAGE_KEY);
            if (!dismissedTime) return false;
            const elapsed = Date.now() - parseInt(dismissedTime, 10);
            return elapsed < COOLDOWN_DAYS * 24 * 60 * 60 * 1000;
        }

        // Save dismissal state
        function dismissPrompt() {
            localStorage.setItem(STORAGE_KEY, Date.now().toString());
            hidePrompt();
        }

        function showPrompt() {
            if (promptEl) {
                promptEl.classList.add('is-visible');
                promptEl.setAttribute('aria-hidden', 'false');
            }
        }

        function hidePrompt() {
            if (promptEl) {
                promptEl.classList.remove('is-visible');
                promptEl.setAttribute('aria-hidden', 'true');
            }
        }

        // Detect standalone (already installed) mode
        const isStandalone = window.navigator.standalone === true || window.matchMedia('(display-mode: standalone)').matches;

        // Detect iOS device
        const isIOS = /iPad|iPhone|iPod/.test(navigator.userAgent) && !window.MSStream;

        // Core initialization
        window.addEventListener('beforeinstallprompt', (e) => {
            // Prevent Chrome 67 and earlier from automatically showing the prompt
            e.preventDefault();
            // Stash the event so it can be triggered later.
            deferredPrompt = e;
            
            // Only show the prompt if not already installed and not dismissed recently
            if (!isStandalone && !isDismissed()) {
                // Show prompt after a short delay for better UX
                setTimeout(showPrompt, 2500);
            }
        });

        // Handle install button click
        if (btnInstall) {
            btnInstall.addEventListener('click', () => {
                if (!deferredPrompt) return;
                // Show the native browser install prompt
                deferredPrompt.prompt();
                // Wait for the user to respond to the prompt
                deferredPrompt.userChoice.then((choiceResult) => {
                    if (choiceResult.outcome === 'accepted') {
                        console.log('User accepted the PWA install prompt');
                    } else {
                        console.log('User dismissed the PWA install prompt');
                        // Treat as dismissal cooldown if they explicitly reject
                        dismissPrompt();
                    }
                    deferredPrompt = null;
                    hidePrompt();
                });
            });
        }

        // iOS Safari Flow
        if (isIOS && !isStandalone && !isDismissed()) {
            // Show iOS specific instructions instead of standard actions
            if (standardActions) standardActions.style.display = 'none';
            if (iosInstructions) iosInstructions.style.display = 'block';
            
            // Show prompt after a short delay for iOS
            setTimeout(showPrompt, 3000);
        }

        // Event listeners for closing
        if (btnDismiss) btnDismiss.addEventListener('click', dismissPrompt);
        if (btnClose) btnClose.addEventListener('click', dismissPrompt);

        // Hide prompt if successfully installed
        window.addEventListener('appinstalled', () => {
            console.log('PWA was installed successfully');
            hidePrompt();
        });
    })();
    </script>
</body>
</html>
