<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
$pageTitle = 'ورود';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/captcha.php';

if (user_login_check()) safe_redirect('/dashboard');
if (admin_login_check()) safe_redirect('/admin');

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        $error = 'درخواست نامعتبر است.';
    } elseif (!captcha_verify($_POST['captcha'] ?? '')) {
        $error = 'کد تصویری اشتباه است.';
    } else {
        $email = clean($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $remember = isset($_POST['remember']);
        if (empty($email) || empty($password)) {
            $error = 'ایمیل و رمز عبور را وارد کنید.';
        } elseif (!user_login($email, $password, $remember)) {
            $error = 'ایمیل یا رمز عبور اشتباه است.';
        } else {
            safe_redirect('/dashboard');
        }
    }
}
require_once __DIR__ . '/includes/header.php';
?>
<div class="auth-wrapper">
    <div class="card">
        <h2>ورود به حساب کاربری</h2>
        <?php if ($error): ?>
            <div class="flash flash-error"><?php echo $error; ?></div>
        <?php endif; ?>
        <form method="POST">
            <?php echo csrf_field(); ?>
            <div class="form-group">
                <label for="email">ایمیل</label>
                <input type="email" id="email" name="email" class="form-control"
                    value="<?php echo clean($_POST['email'] ?? ''); ?>" required dir="ltr">
            </div>
            <div class="form-group">
                <label for="password">رمز عبور</label>
                <input type="password" id="password" name="password" class="form-control" required dir="ltr">
            </div>
            <div class="form-group">
                <label>کد تصویری</label>
                <div class="captcha-row">
                    <img src="/captcha.php" alt="کپچا" class="captcha-image" title="برای رفرش کلیک کنید">
                    <input type="text" name="captcha" class="form-control captcha-input" required dir="ltr"
                        placeholder="کد بالا" autocomplete="off">
                </div>
            </div>
            <div class="form-group remember-group">
                <label class="remember-label">
                    <input type="checkbox" name="remember" id="remember" value="1" checked>
                    <span>مرا به خاطر بسپار</span>
                </label>
            </div>
            <button type="submit" class="btn btn-primary btn-block">ورود</button>
        </form>
        <div class="auth-footer">
            حساب کاربری ندارید؟ <a href="/register">ثبت‌نام کنید</a>
            <div style="margin-top:10px;">
                <a href="/forgot-password" style="color:var(--primary);font-weight:600;">فراموشی رمز</a>
            </div>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>