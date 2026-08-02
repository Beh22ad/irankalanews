<?php
$settings = db_read_settings();
$footerLinks = $settings['footer_links'] ?? [];
$telegramUrl = $settings['telegram_url'] ?? '';
$rubikaUrl = $settings['rubika_url'] ?? '';
$copyrightText = $settings['copyright_text'] ?? '';
$siteName = $settings['site_name'] ?? '';
$siteDesc = $settings['site_description'] ?? '';
$contactName = $settings['contact_name'] ?? '';
$contactPhone = $settings['contact_phone'] ?? '';
?>
</main>
<footer class="site-footer">
    <div class="container footer-inner">

        <div class="footer-section footer-about">
            <a href="/" class="footer-brand"><?php echo clean($siteName); ?></a>
            <p class="footer-desc"><?php echo clean($siteDesc); ?></p>
        </div>

        <div class="footer-section">
            <h4>ارتباط با ما</h4>
            <ul class="footer-contact-list">
                <?php if (!empty($contactName)): ?>
                    <li>
                        <span class="fc-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="16" height="16"
                                fill="currentColor" aria-hidden="true">
                                <path
                                    d="M12 12a5 5 0 1 0-5-5 5 5 0 0 0 5 5zm0 2c-4.42 0-8 1.79-8 4v2h16v-2c0-2.21-3.58-4-8-4z" />
                            </svg>
                        </span>
                        <?php echo clean($contactName); ?>
                    </li>
                <?php endif; ?>

                <?php if (!empty($contactPhone)): ?>
                    <li>
                        <span class="fc-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="16" height="16"
                                fill="currentColor" aria-hidden="true">
                                <path
                                    d="M6.62 10.79a15.91 15.91 0 0 0 6.59 6.59l2.2-2.2a1 1 0 0 1 1-.24c1.12.37 2.33.56 3.59.56a1 1 0 0 1 1 1V20a1 1 0 0 1-1 1C10.3 21 3 13.7 3 4a1 1 0 0 1 1-1h3.5a1 1 0 0 1 1 1c0 1.26.19 2.47.56 3.59a1 1 0 0 1-.25 1z" />
                            </svg>
                        </span>
                        <a href="tel:<?php echo clean($contactPhone); ?>" dir="ltr">
                            <?php echo clean($contactPhone); ?>
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
            <div class="footer-socials">
                <?php if (!empty($telegramUrl)): ?>
                    <a href="<?php echo clean($telegramUrl); ?>" target="_blank" class="social-icon" title="تلگرام">
                        <svg viewBox="0 0 24 24" width="22" height="22" fill="currentColor">
                            <path
                                d="M11.944 0A12 12 0 0 0 0 12a12 12 0 0 0 12 12 12 12 0 0 0 12-12A12 12 0 0 0 12 0a4.844 4.844 0 0 0-.056 0zm4.962 7.224c.1-.002.321.023.465.14a.506.506 0 0 1 .171.325c.016.093.036.306.02.472-.18 1.898-.962 6.502-1.36 8.627-.168.9-.499 1.201-.82 1.23-.696.065-1.225-.46-1.9-.902-1.056-.693-1.653-1.124-2.678-1.8-1.185-.78-.417-1.21.258-1.91.177-.184 3.247-2.977 3.307-3.23.007-.032.014-.15-.056-.212s-.174-.041-.249-.024c-.106.024-1.793 1.14-5.061 3.345-.479.33-.913.49-1.302.48-.428-.008-1.252-.241-1.865-.44-.752-.245-1.349-.374-1.297-.789.027-.216.325-.437.893-.663 3.498-1.524 5.83-2.529 6.998-3.014 3.332-1.386 4.025-1.627 4.476-1.635z" />
                        </svg>
                    </a>
                <?php endif; ?>
                <?php if (!empty($rubikaUrl)): ?>
                    <a href="<?php echo clean($rubikaUrl); ?>" target="_blank" class="social-icon" title="روبیکا">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 131.066 144.768" width="22" height="22">
                            <path
                                d="M-117.712-34.033a21.25 21.25 0 0 0-10.623 2.846l-44.286 25.57a21.25 21.25 0 0 0-10.624 18.399v51.137a21.25 21.25 0 0 0 10.624 18.4l44.286 25.57a21.25 21.25 0 0 0 21.247 0l44.286-25.57a21.25 21.25 0 0 0 10.623-18.4V12.782a21.25 21.25 0 0 0-10.623-18.4l-44.286-25.569a21.25 21.25 0 0 0-10.624-2.846m0 34.686 32.647 18.849v37.697l-32.647 18.85-32.647-18.85V19.502Z"
                                transform="translate(183.245 34.033)" fill="currentColor" />
                        </svg>
                    </a>
                <?php endif; ?>
            </div>
        </div>

        <div class="footer-section custom-links">
            <h4>دسترسی سریع</h4>
            <ul>
                <?php foreach ($footerLinks as $link): ?>
                    <li><a href="<?php echo clean($link['url']); ?>"><?php echo clean($link['label']); ?></a></li>
                <?php endforeach; ?>
            </ul>
        </div>

    </div>
    <div class="footer-bottom">
        <div class="container"><?php echo clean($copyrightText); ?></div>
    </div>
</footer>
<script src="/assets/js/app.js"></script>
</body>

</html>