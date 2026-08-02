<?php
session_start();
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/functions.php';

if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    header('Location: /admin/login');
    exit;
}

$error = '';
$success = '';
$settings = db_read_settings();

// ============================================
// تابع کمکی برای mask کردن مقادیر محرمانه
// ============================================
function mask_secret($value, $showLast = 4)
{
    if (empty($value)) return '—';
    $len = mb_strlen($value);
    if ($len <= $showLast) return str_repeat('•', $len);
    return str_repeat('•', $len - $showLast) . mb_substr($value, -$showLast);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        $error = 'درخواست نامعتبر.';
    } else {
        // ⚠️ فقط فیلدهای عمومی (غیر محرمانه) ذخیره می‌شوند
        // مقادیر محرمانه از فایل .env خوانده می‌شوند و اینجا قابل تغییر نیستند
        $settings['site_description'] = clean($_POST['site_description'] ?? '');
        $settings['telegram_username'] = clean($_POST['telegram_username'] ?? '');
        $settings['telegram_url'] = clean($_POST['telegram_url'] ?? '');
        $settings['rubika_url'] = clean($_POST['rubika_url'] ?? '');
        $settings['copyright_text'] = clean($_POST['copyright_text'] ?? '');

        $linkLabels = $_POST['link_label'] ?? [];
        $linkUrls = $_POST['link_url'] ?? [];
        $footerLinks = [];
        if (is_array($linkLabels) && is_array($linkUrls)) {
            $count = min(count($linkLabels), count($linkUrls));
            for ($i = 0; $i < $count; $i++) {
                $label = trim($linkLabels[$i]);
                $url = trim($linkUrls[$i]);
                if (!empty($label) && !empty($url)) {
                    $footerLinks[] = ['label' => $label, 'url' => $url];
                }
            }
        }
        $settings['footer_links'] = $footerLinks;

        db_write_settings($settings);
        $success = 'تنظیمات ذخیره شد.';
    }
}
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تنظیمات</title>
    <link rel="stylesheet" href="/assets/fonts/stylesheet.css">
    <link rel="stylesheet" href="/assets/css/style.css">
</head>

<body>
    <header class="site-header">
        <div class="container header-inner">
            <a href="/admin" class="logo">مدیریت</a>
            <button class="hamburger" id="hamburgerBtn"
                aria-label="منو"><span></span><span></span><span></span></button>
            <nav class="main-nav" id="mainNav">
                <a href="/admin">داشبورد</a>
                <a href="/admin/orders">سفارش‌ها</a>
                <a href="/admin/users">کاربران</a>
                <a href="/admin/products">محصولات</a>
                <a href="/admin/plans">پلن‌ها</a>
                <a href="/admin/posts">مستندات</a>
                <a href="/admin/settings" class="active">تنظیمات</a>
                <a href="/admin/sitemap">Sitemap</a>
                <a href="/">سایت</a>
                <a href="/admin/logout">خروج</a>
            </nav>
        </div>
    </header>
    <main class="site-main">
        <div class="container" style="max-width:700px;">
            <?php if ($error): ?><div class="flash flash-error"><?php echo $error; ?></div><?php endif; ?>
            <?php if ($success): ?><div class="flash flash-success"><?php echo $success; ?></div><?php endif; ?>

            <form method="POST">
                <?php echo csrf_field(); ?>

                <!-- ============================================ -->
                <!-- معرفی سایت (عمومی) -->
                <!-- ============================================ -->
                <div class="card">
                    <h3 class="card-title">معرفی سایت</h3>
                    <div class="form-group">
                        <label>نام سایت <span class="text-muted" style="font-size:0.8rem;">(از .env)</span></label>
                        <input type="text" class="form-control" dir="ltr"
                            value="<?php echo clean($settings['site_name'] ?? ''); ?>" disabled
                            style="background:#f3f4f6;cursor:not-allowed;">
                    </div>
                    <div class="form-group">
                        <label>توضیحات کوتاه</label>
                        <input type="text" name="site_description" class="form-control"
                            value="<?php echo clean($settings['site_description'] ?? ''); ?>"
                            placeholder="سرویس تخصصی ارائه قیمت...">
                    </div>
                </div>

                <!-- ============================================ -->
                <!-- اطلاعات عمومی سایت (محرمانه - read only) -->
                <!-- ============================================ -->
                <div class="card">
                    <h3 class="card-title">اطلاعات عمومی سایت</h3>
                    <div class="flash flash-warning" style="margin-bottom:16px;">
                        🔒 این مقادیر از فایل <code>.env</code> خوانده می‌شوند. برای تغییر، فایل <code>.env</code> را در
                        سرور ویرایش کنید.
                    </div>
                    <dl class="membership-info">
                        <dt>آدرس سایت (SITE_URL):</dt>
                        <dd dir="ltr" style="font-size:0.88rem;"><?php echo clean($settings['site_url'] ?? '—'); ?></dd>
                        <dt>ایمیل مدیریت (ADMIN_EMAIL):</dt>
                        <dd dir="ltr" style="font-size:0.88rem;"><?php echo clean($settings['admin_email'] ?? '—'); ?>
                        </dd>
                    </dl>
                </div>

                <!-- ============================================ -->
                <!-- اطلاعات تماس (محرمانه - read only) -->
                <!-- ============================================ -->
                <div class="card">
                    <h3 class="card-title">اطلاعات تماس</h3>
                    <div class="flash flash-warning" style="margin-bottom:16px;">
                        🔒 این مقادیر از فایل <code>.env</code> خوانده می‌شوند.
                    </div>
                    <dl class="membership-info">
                        <dt>نام و نام خانوادگی:</dt>
                        <dd><?php echo clean($settings['contact_name'] ?? '—'); ?></dd>
                        <dt>شماره تماس:</dt>
                        <dd dir="ltr"><?php echo clean($settings['contact_phone'] ?? '—'); ?></dd>
                    </dl>
                </div>

                <!-- ============================================ -->
                <!-- کارت بانکی (محرمانه - read only) -->
                <!-- ============================================ -->
                <div class="card">
                    <h3 class="card-title">کارت بانکی</h3>
                    <div class="flash flash-warning" style="margin-bottom:16px;">
                        🔒 این مقادیر از فایل <code>.env</code> خوانده می‌شوند.
                    </div>
                    <dl class="membership-info">
                        <dt>به نام:</dt>
                        <dd><?php echo clean($settings['bank_card_name'] ?? '—'); ?></dd>
                        <dt>شماره کارت:</dt>
                        <dd dir="ltr" style="font-family:monospace;">
                            <?php echo clean($settings['bank_card_number'] ?? '—'); ?>
                        </dd>
                    </dl>
                </div>

                <!-- ============================================ -->
                <!-- تلگرام (محرمانه - read only) -->
                <!-- ============================================ -->
                <div class="card">
                    <h3 class="card-title">تلگرام</h3>
                    <div class="flash flash-warning" style="margin-bottom:16px;">
                        🔒 مقادیر محرمانه از فایل <code>.env</code> خوانده می‌شوند.
                        فقط لینک عمومی تلگرام قابل ویرایش است.
                    </div>
                    <dl class="membership-info">
                        <dt>Bot Token:</dt>
                        <dd dir="ltr" style="font-family:monospace;font-size:0.85rem;">
                            <?php echo clean(mask_secret($settings['telegram_bot_token'] ?? '')); ?>
                        </dd>
                        <dt>Chat ID:</dt>
                        <dd dir="ltr" style="font-family:monospace;">
                            <?php echo clean(mask_secret($settings['telegram_chat_id'] ?? '', 3)); ?>
                        </dd>
                        <dt>Proxy URL:</dt>
                        <dd dir="ltr" style="font-family:monospace;font-size:0.82rem;">
                            <?php echo clean(mask_secret($settings['telegram_proxy_url'] ?? '', 8)); ?>
                        </dd>
                    </dl>
                    <div class="form-group" style="margin-top:16px;">
                        <label>لینک عمومی تلگرام (برای فوتر سایت)</label>
                        <input type="text" name="telegram_url" class="form-control" dir="ltr"
                            value="<?php echo clean($settings['telegram_url'] ?? ''); ?>"
                            placeholder="https://t.me/yourtelegram">
                    </div>
                    <div class="form-group">
                        <label>نام کاربری تلگرام (اختیاری)</label>
                        <input type="text" name="telegram_username" class="form-control" dir="ltr"
                            value="<?php echo clean($settings['telegram_username'] ?? ''); ?>"
                            placeholder="@yourchannel">
                    </div>
                </div>

                <!-- ============================================ -->
                <!-- روبیکا (محرمانه - read only) -->
                <!-- ============================================ -->
                <div class="card">
                    <h3 class="card-title">روبیکا</h3>
                    <div class="flash flash-warning" style="margin-bottom:16px;">
                        🔒 مقادیر محرمانه از فایل <code>.env</code> خوانده می‌شوند.
                    </div>
                    <dl class="membership-info">
                        <dt>Bot ID:</dt>
                        <dd dir="ltr" style="font-family:monospace;font-size:0.85rem;">
                            <?php echo clean(mask_secret($settings['rubika_bot_id'] ?? '')); ?>
                        </dd>
                        <dt>Chat ID:</dt>
                        <dd dir="ltr" style="font-family:monospace;font-size:0.85rem;">
                            <?php echo clean(mask_secret($settings['rubika_chat_id'] ?? '', 6)); ?>
                        </dd>
                    </dl>
                    <div class="form-group" style="margin-top:16px;">
                        <label>لینک عمومی روبیکا (برای فوتر سایت)</label>
                        <input type="text" name="rubika_url" class="form-control" dir="ltr"
                            value="<?php echo clean($settings['rubika_url'] ?? ''); ?>"
                            placeholder="https://rubika.ir/yourrubika">
                    </div>
                </div>

                <!-- ============================================ -->
                <!-- SMTP (محرمانه - read only) -->
                <!-- ============================================ -->
                <div class="card">
                    <h3 class="card-title">تنظیمات ایمیل (SMTP)</h3>
                    <div class="flash flash-warning" style="margin-bottom:16px;">
                        🔒 تمام تنظیمات SMTP از فایل <code>.env</code> خوانده می‌شوند.
                        برای تغییر، فایل <code>.env</code> را ویرایش کنید.
                    </div>
                    <dl class="membership-info">
                        <dt>SMTP Host:</dt>
                        <dd dir="ltr" style="font-family:monospace;font-size:0.85rem;">
                            <?php echo clean($settings['smtp_host'] ?? '—'); ?>
                        </dd>
                        <dt>SMTP Port:</dt>
                        <dd dir="ltr" style="font-family:monospace;">
                            <?php echo clean($settings['smtp_port'] ?? '—'); ?>
                        </dd>
                        <dt>SMTP Username:</dt>
                        <dd dir="ltr" style="font-family:monospace;font-size:0.85rem;">
                            <?php echo clean(mask_secret($settings['smtp_username'] ?? '', 6)); ?>
                        </dd>
                        <dt>SMTP Password:</dt>
                        <dd dir="ltr" style="font-family:monospace;">
                            <?php echo clean(mask_secret($settings['smtp_password'] ?? '')); ?>
                        </dd>
                        <dt>From Email:</dt>
                        <dd dir="ltr" style="font-family:monospace;font-size:0.85rem;">
                            <?php echo clean($settings['smtp_from_email'] ?? '—'); ?>
                        </dd>
                        <dt>From Name:</dt>
                        <dd><?php echo clean($settings['smtp_from_name'] ?? '—'); ?></dd>
                        <dt>Encryption:</dt>
                        <dd dir="ltr"><?php echo clean(strtoupper($settings['smtp_encryption'] ?? '—')); ?></dd>
                    </dl>
                </div>

                <!-- ============================================ -->
                <!-- امنیت (محرمانه - read only) -->
                <!-- ============================================ -->
                <div class="card">
                    <h3 class="card-title">امنیت</h3>
                    <div class="flash flash-warning" style="margin-bottom:16px;">
                        🔒 این مقدار از فایل <code>.env</code> خوانده می‌شود.
                        <br><strong style="color:var(--danger);">هشدار:</strong> تغییر App Secret باعث نامعتبر شدن
                        تمام API Key‌های موجود و کوکی‌های "مرا به خاطر بسپار" می‌شود.
                    </div>
                    <dl class="membership-info">
                        <dt>App Secret:</dt>
                        <dd dir="ltr" style="font-family:monospace;font-size:0.85rem;word-break:break-all;">
                            <?php echo clean(mask_secret($settings['app_secret'] ?? '', 6)); ?>
                        </dd>
                    </dl>
                </div>

                <!-- ============================================ -->
                <!-- کپی‌رایت (عمومی) -->
                <!-- ============================================ -->
                <div class="card">
                    <h3 class="card-title">عمومی</h3>
                    <div class="form-group">
                        <label>متن کپی‌رایت فوتر</label>
                        <input type="text" name="copyright_text" class="form-control"
                            value="<?php echo clean($settings['copyright_text'] ?? ''); ?>">
                    </div>
                </div>

                <!-- ============================================ -->
                <!-- لینک‌های فوتر (عمومی) -->
                <!-- ============================================ -->
                <div class="card">
                    <h3 class="card-title">لینک‌های فوتر</h3>
                    <div id="footerLinks">
                        <?php
                        $links = $settings['footer_links'] ?? [];
                        if (empty($links)) $links = [['label' => '', 'url' => '']];
                        foreach ($links as $idx => $link):
                        ?>
                            <div class="form-row footer-link-row" style="margin-bottom:10px;">
                                <div class="form-group" style="margin-bottom:0;">
                                    <input type="text" name="link_label[]" class="form-control" placeholder="عنوان"
                                        value="<?php echo clean($link['label']); ?>">
                                </div>
                                <div class="form-group" style="margin-bottom:0;">
                                    <input type="text" name="link_url[]" class="form-control" placeholder="URL" dir="ltr"
                                        value="<?php echo clean($link['url']); ?>">
                                </div>
                                <button type="button" class="btn btn-danger btn-sm"
                                    onclick="this.closest('.footer-link-row').remove()"
                                    style="align-self:start;">حذف</button>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <button type="button" class="btn btn-outline btn-sm" onclick="addFooterLink()">+ افزودن
                        لینک</button>
                </div>

                <button type="submit" class="btn btn-primary btn-block mt-2" style="padding:14px;">ذخیره
                    تنظیمات</button>
            </form>
        </div>
    </main>
    <script src="/assets/js/app.js"></script>
    <script>
        function addFooterLink() {
            const container = document.getElementById('footerLinks');
            const row = document.createElement('div');
            row.className = 'form-row footer-link-row';
            row.style.marginBottom = '10px';
            row.innerHTML = `
        <div class="form-group" style="margin-bottom:0;">
            <input type="text" name="link_label[]" class="form-control" placeholder="عنوان">
        </div>
        <div class="form-group" style="margin-bottom:0;">
            <input type="text" name="link_url[]" class="form-control" placeholder="URL" dir="ltr">
        </div>
        <button type="button" class="btn btn-danger btn-sm" onclick="this.closest('.footer-link-row').remove()" style="align-self:start;">حذف</button>
    `;
            container.appendChild(row);
        }
    </script>
</body>

</html>