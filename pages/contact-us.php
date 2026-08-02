<?php
$pageTitle = 'تماس با ما';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/captcha.php';
require_once __DIR__ . '/../includes/telegram.php';

$settings = db_read_settings();
$telegramUrl = $settings['telegram_url'] ?? '';
$rubikaUrl = $settings['rubika_url'] ?? '';
$contactPhone = $settings['contact_phone'] ?? '';
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        $error = 'درخواست نامعتبر است.';
    } elseif (!captcha_verify($_POST['captcha'] ?? '')) {
        $error = 'کد تصویری اشتباه است.';
    } else {
        $name = clean($_POST['name'] ?? '');
        $mobile = clean($_POST['mobile'] ?? '');
        $email = clean($_POST['email'] ?? '');
        $message = clean($_POST['message'] ?? '');

        if (empty($name) || empty($mobile) || empty($email) || empty($message)) {
            $error = 'تمام فیلدها الزامی هستند.';
        } elseif (!is_valid_mobile($mobile)) {
            $error = 'شماره موبایل نامعتبر است.';
        } elseif (!is_valid_email($email)) {
            $error = 'ایمیل نامعتبر است.';
        } elseif (mb_strlen($message) < 10) {
            $error = 'پیام باید حداقل ۱۰ کاراکتر باشد.';
        } else {
            // ارسال به تلگرام، روبیکا و ایمیل
            send_contact_notification($name, $mobile, $email, $message);
            $success = 'پیام شما با موفقیت ارسال شد. به زودی پاسخ می‌دهیم.';
        }
    }
}
require_once __DIR__ . '/../includes/header.php';
?>
<section class="section">
    <div class="container" style="max-width:700px;">
        <div class="section-header">
            <h2>تماس با ما</h2>
            <p>سؤالات، پیشنهادات یا مشکلات خود را از طریق فرم زیر ارسال کنید.</p>
        </div>

        <?php if ($error): ?>
            <div class="flash flash-error"><?php echo $error; ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="flash flash-success"><?php echo $success; ?></div>
        <?php endif; ?>

        <div class="contact-layout">
            <!-- فرم تماس -->
            <?php if (empty($success)): ?>
                <div class="contact-form-wrap">
                    <div class="card">
                        <h3 class="card-title">ارسال پیام</h3>
                        <form method="POST">
                            <?php echo csrf_field(); ?>
                            <div class="form-group">
                                <label for="name">نام و نام خانوادگی</label>
                                <input type="text" id="name" name="name" class="form-control"
                                    value="<?php echo clean($_POST['name'] ?? ''); ?>" required>
                            </div>
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="mobile">شماره موبایل</label>
                                    <input type="text" id="mobile" name="mobile" class="form-control" dir="ltr"
                                        value="<?php echo clean($_POST['mobile'] ?? ''); ?>" required
                                        placeholder="09120000000">
                                </div>
                                <div class="form-group">
                                    <label for="email">ایمیل</label>
                                    <input type="email" id="email" name="email" class="form-control" dir="ltr"
                                        value="<?php echo clean($_POST['email'] ?? ''); ?>" required>
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="message">پیام</label>
                                <textarea id="message" name="message" class="form-control" rows="6" required
                                    placeholder="پیام خود را بنویسید..."><?php echo clean($_POST['message'] ?? ''); ?></textarea>
                            </div>
                            <div class="form-group">
                                <label>کد تصویری</label>
                                <div class="captcha-row">
                                    <img src="/captcha.php" alt="کپچا" class="captcha-image" title="برای رفرش کلیک کنید">
                                    <input type="text" name="captcha" class="form-control captcha-input" required dir="ltr"
                                        placeholder="کد بالا" autocomplete="off">
                                </div>
                            </div>
                            <button type="submit" class="btn btn-primary btn-block">ارسال پیام</button>
                        </form>
                    </div>
                </div>
            <?php endif; ?>
            <!-- اطلاعات تماس -->
            <div class="contact-info-wrap">
                <div class="card">
                    <h3 class="card-title">راه‌های ارتباط</h3>
                    <?php if (!empty($contactPhone)): ?>
                        <div class="contact-info-item">
                            <span class="ci-icon">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="16" height="16"
                                    fill="currentColor" aria-hidden="true">
                                    <path
                                        d="M6.62 10.79a15.91 15.91 0 0 0 6.59 6.59l2.2-2.2a1 1 0 0 1 1-.24c1.12.37 2.33.56 3.59.56a1 1 0 0 1 1 1V20a1 1 0 0 1-1 1C10.3 21 3 13.7 3 4a1 1 0 0 1 1-1h3.5a1 1 0 0 1 1 1c0 1.26.19 2.47.56 3.59a1 1 0 0 1-.25 1z" />
                                </svg>
                            </span>
                            <div class="ci-text">
                                <a href="tel:<?php echo clean($contactPhone); ?>"
                                    dir="ltr"><?php echo clean($contactPhone); ?></a>
                            </div>
                        </div>
                    <?php endif; ?>
                    <?php if (!empty($telegramUrl)): ?>
                        <div class="contact-info-item">
                            <span class="ci-icon">
                                <svg viewBox="0 0 24 24" width="20" height="20" fill="currentColor">
                                    <path
                                        d="M11.944 0A12 12 0 0 0 0 12a12 12 0 0 0 12 12 12 12 0 0 0 12-12A12 12 0 0 0 12 0a4.844 4.844 0 0 0-.056 0zm4.962 7.224c.1-.002.321.023.465.14a.506.506 0 0 1 .171.325c.016.093.036.306.02.472-.18 1.898-.962 6.502-1.36 8.627-.168.9-.499 1.201-.82 1.23-.696.065-1.225-.46-1.9-.902-1.056-.693-1.653-1.124-2.678-1.8-1.185-.78-.417-1.21.258-1.91.177-.184 3.247-2.977 3.307-3.23.007-.032.014-.15-.056-.212s-.174-.041-.249-.024c-.106.024-1.793 1.14-5.061 3.345-.479.33-.913.49-1.302.48-.428-.008-1.252-.241-1.865-.44-.752-.245-1.349-.374-1.297-.789.027-.216.325-.437.893-.663 3.498-1.524 5.83-2.529 6.998-3.014 3.332-1.386 4.025-1.627 4.476-1.635z" />
                                </svg>
                            </span>
                            <div class="ci-text">
                                <a href="<?php echo clean($telegramUrl); ?>" target="_blank">تلگرام</a>
                            </div>
                        </div>
                    <?php endif; ?>
                    <?php if (!empty($rubikaUrl)): ?>
                        <div class="contact-info-item">
                            <span class="ci-icon">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 131.066 144.768" width="20"
                                    height="20">
                                    <path
                                        d="M-117.712-34.033a21.25 21.25 0 0 0-10.623 2.846l-44.286 25.57a21.25 21.25 0 0 0-10.624 18.399v51.137a21.25 21.25 0 0 0 10.624 18.4l44.286 25.57a21.25 21.25 0 0 0 21.247 0l44.286-25.57a21.25 21.25 0 0 0 10.623-18.4V12.782a21.25 21.25 0 0 0-10.623-18.4l-44.286-25.569a21.25 21.25 0 0 0-10.624-2.846m0 34.686 32.647 18.849v37.697l-32.647 18.85-32.647-18.85V19.502Z"
                                        transform="translate(183.245 34.033)" fill="currentColor" />
                                </svg>
                            </span>
                            <div class="ci-text">
                                <a href="<?php echo clean($rubikaUrl); ?>" target="_blank">روبیکا</a>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</section>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>