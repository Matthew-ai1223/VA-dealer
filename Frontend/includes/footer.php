    </main>
    <footer class="site-footer">
        <div class="container site-footer__grid">
            <div class="site-footer__brand">
                <div class="site-footer__logo">
                    <span class="logo-mark">VA</span>
                    <strong><?= sanitize($config['site_name']) ?></strong>
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

    <script src="<?= sanitize(url('Frontend/assets/js/main.js')) ?>?v=4"></script>
    <script src="<?= sanitize(url('Frontend/assets/js/ai-chat.js')) ?>?v=3"></script>
    <?php if (basename($_SERVER['PHP_SELF']) === 'car.php'): ?>
    <script src="<?= sanitize(url('Frontend/assets/js/car.js')) ?>"></script>
    <?php endif; ?>
</body>
</html>
