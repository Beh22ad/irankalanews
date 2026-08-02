<?php
session_start();
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/captcha.php';

if (isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'admin') {
    header('Location: /admin');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        $error = 'درخواست نامعتبر.';
    } elseif (!captcha_verify($_POST['captcha'] ?? '')) {
        $error = 'کد تصویری اشتباه است.';
    } else {
        $username = clean($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $remember = isset($_POST['remember']);

        if (empty($username) || empty($password)) {
            $error = 'نام کاربری و رمز عبور را وارد کنید.';
        } else {
            $admins = db_read('admins.json');
            $found = false;
            foreach ($admins as $a) {
                if ($a['username'] === $username && password_verify($password, $a['password_hash'])) {
                    $_SESSION['user_id'] = $a['id'];
                    $_SESSION['user_name'] = $a['name'];
                    $_SESSION['user_type'] = 'admin';
                    $found = true;
                    break;
                }
            }
            if ($found) {
                // کوکی remember me
                if ($remember) {
                    $secret = db_read_settings()['app_secret'] ?? 'default';
                    $hash = hash_hmac('sha256', $a['id'] . ':admin:' . $a['password_hash'], $secret);
                    setcookie('remember_me', 'admin:' . $a['id'] . ':' . $hash, [
                        'expires' => time() + 2592000,
                        'path' => '/',
                        'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
                        'httponly' => true,
                        'samesite' => 'Lax',
                    ]);
                } else {
                    setcookie('remember_me', '', time() - 3600, '/');
                }
                header('Location: /admin');
                exit;
            } else {
                $error = 'نام کاربری یا رمز عبور اشتباه است.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ورود مدیریت</title>
    <link rel="stylesheet" href="/assets/fonts/stylesheet.css">
    <link rel="stylesheet" href="/assets/css/style.css">
</head>

<body style="background:var(--primary-darker);">
    <div class="auth-wrapper" style="margin-top:10vh;">
        <div class="card" style="border:none;box-shadow:var(--shadow-lg);">
            <h2 style="color:var(--primary-darker);">ورود به پنل مدیریت</h2>
            <?php if ($error): ?>
                <div class="flash flash-error"><?php echo $error; ?></div>
            <?php endif; ?>
            <form method="POST">
                <?php echo csrf_field(); ?>
                <div class="form-group">
                    <label for="username">نام کاربری</label>
                    <input type="text" id="username" name="username" class="form-control" required dir="ltr">
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
        </div>
    </div>
    <script src="/assets/js/app.js"></script>
</body>

</html>