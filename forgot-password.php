<?php
$pageTitle = 'فراموشی رمز عبور';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/captcha.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/database.php';

// اگر کاربر لاگین است، به داشبورد هدایت شود
if (user_login_check()) {
    safe_redirect('/dashboard');
}
if (admin_login_check()) {
    safe_redirect('/admin');
}

$error = '';
$success = '';

/**
 * پاکسازی توکن‌های منقضی و قدیمی برای جلوگیری از باد کردن فایل JSON
 */
function cleanup_reset_tokens()
{
    $path = DATA_DIR . 'password_resets.json';
    if (!file_exists($path)) {
        return;
    }

    $resets = db_read('password_resets.json');
    $cutoff = strtotime('-24 hours');

    $filtered = array_filter($resets, function ($r) use ($cutoff) {
        // نگه‌داشتن توکن‌های معتبر (استفاده نشده و منقضی نشده)
        if (!$r['used'] && strtotime($r['expires_at']) > time()) {
            return true;
        }
        // نگه‌داشتن توکن‌های استفاده‌شده‌ی اخیر (برای دیباگ/لاگ تا ۲۴ ساعت)
        if ($r['used'] && strtotime($r['created_at']) > $cutoff) {
            return true;
        }
        return false;
    });

    db_write('password_resets.json', array_values($filtered));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        $error = 'درخواست نامعتبر است.';
    } elseif (!captcha_verify($_POST['captcha'] ?? '')) {
        $error = 'کد تصویری اشتباه است.';
    } else {
        $email = clean($_POST['email'] ?? '');

        if (empty($email) || !is_valid_email($email)) {
            $error = 'لطفاً یک ایمیل معتبر وارد کنید.';
        } else {
            // پاکسازی توکن‌های قدیمی
            cleanup_reset_tokens();

            $users = db_read('users.json');
            $user = null;
            foreach ($users as $u) {
                if ($u['email'] === $email) {
                    $user = $u;
                    break;
                }
            }

            if ($user) {
                $resets = db_read('password_resets.json');
                $hasActive = false;

                // بررسی اینکه آیا توکن فعال قبلی وجود دارد (جلوگیری از اسپم)
                foreach ($resets as $r) {
                    if ($r['email'] === $email && !$r['used'] && strtotime($r['expires_at']) > time()) {
                        $hasActive = true;
                        break;
                    }
                }

                if (!$hasActive) {
                    // تولید توکن امن ۶۴ کاراکتری
                    $token = bin2hex(random_bytes(32));
                    $expiresAt = date('Y-m-d H:i:s', strtotime('+1 hour'));

                    $resets[] = [
                        'token'      => $token,
                        'user_id'    => $user['id'],
                        'email'      => $email,
                        'expires_at' => $expiresAt,
                        'used'       => false,
                        'created_at' => date('Y-m-d H:i:s')
                    ];
                    db_write('password_resets.json', $resets);

                    // ==========================================
                    // ارسال ایمیل با استفاده از سیستم SMTP
                    // ==========================================
                    require_once __DIR__ . '/includes/email.php';

                    $settings = db_read_settings();
                    $siteUrl  = rtrim($settings['site_url'] ?? '', '/');
                    $resetLink = $siteUrl . '/reset-password?token=' . $token;
                    $siteName = $settings['site_name'] ?? 'ایران کالانیوز';

                    $subject = 'لینک بازیابی رمز عبور - ' . $siteName;

                    $textBody = "سلام {$user['name']} عزیز،\n\n"
                        . "درخواست بازیابی رمز عبور در سایت ثبت شده است.\n\n"
                        . "برای تنظیم رمز عبور جدید، روی لینک زیر کلیک کنید:\n"
                        . "$resetLink\n\n"
                        . "این لینک تا ۱ ساعت معتبر است.\n\n"
                        . "اگر شما این درخواست را ثبت نکرده‌اید، این ایمیل را نادیده بگیرید.";

                    $htmlBody = "<div dir='rtl' style='font-family:Tahoma,sans-serif;font-size:14px;line-height:1.9;color:#333;max-width:600px;margin:auto;'>"
                        . "<div style='background:linear-gradient(135deg,#0d9488,#134e4a);padding:24px;border-radius:10px 10px 0 0;text-align:center;'>"
                        . "<h1 style='color:#fff;margin:0;font-size:20px;'>$siteName</h1>"
                        . "</div>"
                        . "<div style='background:#fff;padding:28px 24px;border:1px solid #e5e7eb;border-radius:0 0 10px 10px;'>"
                        . "<p style='margin:0 0 16px;'>سلام <strong>{$user['name']}</strong> عزیز،</p>"
                        . "<p style='margin:0 0 20px;'>درخواست بازیابی رمز عبور در سایت ثبت شده است. برای تنظیم رمز جدید روی دکمه زیر کلیک کنید:</p>"
                        . "<p style='text-align:center;margin:28px 0;'>"
                        . "<a href='$resetLink' style='background:#0d9488;color:#fff;padding:13px 32px;border-radius:8px;text-decoration:none;display:inline-block;font-weight:bold;font-size:15px;'>بازیابی رمز عبور</a>"
                        . "</p>"
                        . "<p style='font-size:12px;color:#6b7280;margin:20px 0 6px;'>یا این لینک را در مرورگر کپی کنید:</p>"
                        . "<p dir='ltr' style='font-size:12px;color:#0d9488;word-break:break-all;background:#f0fdf4;padding:10px;border-radius:6px;margin:0 0 20px;'>$resetLink</p>"
                        . "<div style='background:#fffbeb;border:1px solid #fde68a;padding:12px;border-radius:6px;font-size:12px;color:#92400e;margin-top:16px;'>"
                        . "⏰ این لینک تا <strong>۱ ساعت</strong> معتبر است.<br>اگر شما این درخواست را ثبت نکرده‌اید، این ایمیل را نادیده بگیرید."
                        . "</div>"
                        . "</div>"
                        . "<p style='text-align:center;font-size:11px;color:#9ca3af;margin-top:16px;'>© $siteName</p>"
                        . "</div>";

                    // فراخوانی تابع SMTP که در مرحله قبل ساختیم
                    send_system_email($email, $subject, $htmlBody, $textBody);
                }
            }

            // ⚠️ امنیت: همیشه پیام موفقیت نمایش داده می‌شود (حتی اگر ایمیل وجود نداشته باشد)
            // این کار از "Email Enumeration" (لو رفتن لیست ایمیل‌های ثبت‌نام شده) جلوگیری می‌کند.
            $success = 'اگر ایمیل وارد شده در سیستم ثبت شده باشد، لینک بازیابی رمز به آن ارسال شد. لطفاً صندوق ورودی (و پوشه اسپم) خود را بررسی کنید.';
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="auth-wrapper">
    <div class="card">
        <h2>فراموشی رمز عبور</h2>

        <?php if ($error): ?>
            <div class="flash flash-error"><?php echo $error; ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="flash flash-success"><?php echo $success; ?></div>
        <?php endif; ?>

        <?php if (empty($success)): ?>
            <p class="text-muted" style="margin-bottom:18px;font-size:0.9rem;text-align:center;line-height:1.9;">
                ایمیل حساب کاربری خود را وارد کنید تا لینک بازیابی رمز برایتان ارسال شود.
            </p>
            <form method="POST">
                <?php echo csrf_field(); ?>

                <div class="form-group">
                    <label for="email">ایمیل</label>
                    <input type="email" id="email" name="email" class="form-control"
                        value="<?php echo clean($_POST['email'] ?? ''); ?>" required dir="ltr"
                        placeholder="example@mail.com" autocomplete="email">
                </div>

                <div class="form-group">
                    <label>کد تصویری</label>
                    <div class="captcha-row">
                        <img src="/captcha.php" alt="کپچا" class="captcha-image" title="برای رفرش کلیک کنید">
                        <input type="text" name="captcha" class="form-control captcha-input" required dir="ltr"
                            placeholder="کد بالا" autocomplete="off">
                    </div>
                </div>

                <button type="submit" class="btn btn-primary btn-block">ارسال لینک بازیابی</button>
            </form>
        <?php endif; ?>

        <div class="auth-footer" style="margin-top:20px;">
            <a href="/login">← بازگشت به صفحه ورود</a>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>