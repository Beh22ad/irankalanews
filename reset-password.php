<?php
$pageTitle = 'بازیابی رمز عبور';
require_once __DIR__ . '/includes/auth.php';

$token = clean($_GET['token'] ?? '');
$error = '';
$success = '';
$validToken = false;
$resetData = null;

// اعتبارسنجی توکن
if (!empty($token)) {
    $resets = db_read('password_resets.json');
    foreach ($resets as $r) {
        if (
            $r['token'] === $token &&
            !$r['used'] &&
            strtotime($r['expires_at']) > time()
        ) {
            $validToken = true;
            $resetData = $r;
            break;
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $validToken) {
    if (!csrf_verify()) {
        $error = 'درخواست نامعتبر است.';
    } else {
        $password  = $_POST['password'] ?? '';
        $password2 = $_POST['password2'] ?? '';

        if (empty($password) || mb_strlen($password) < 6) {
            $error = 'رمز عبور باید حداقل ۶ کاراکتر باشد.';
        } elseif ($password !== $password2) {
            $error = 'رمز عبور و تکرار آن مطابقت ندارند.';
        } else {
            // به‌روزرسانی رمز کاربر
            $users = db_read('users.json');
            foreach ($users as &$u) {
                if ($u['id'] == $resetData['user_id']) {
                    $u['password_hash'] = password_hash($password, PASSWORD_DEFAULT);
                    break;
                }
            }
            unset($u);
            db_write('users.json', $users);

            // علامت‌گذاری توکن به عنوان استفاده‌شده
            $resets = db_read('password_resets.json');
            foreach ($resets as &$r) {
                if ($r['token'] === $token) {
                    $r['used'] = true;
                    $r['used_at'] = date('Y-m-d H:i:s');
                    break;
                }
            }
            unset($r);
            db_write('password_resets.json', $resets);

            $success = 'رمز عبور با موفقیت تغییر کرد. اکنون می‌توانید با رمز جدید وارد شوید.';
            $validToken = false;
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>
<div class="auth-wrapper">
    <div class="card">
        <h2>تنظیم رمز عبور جدید</h2>
        <?php if ($error): ?>
            <div class="flash flash-error"><?php echo $error; ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="flash flash-success"><?php echo $success; ?></div>
            <div class="auth-footer" style="margin-top:20px;">
                <a href="/login" class="btn btn-primary btn-block">ورود به حساب کاربری</a>
            </div>
        <?php elseif ($validToken): ?>
            <p class="text-muted" style="margin-bottom:16px;font-size:0.9rem;text-align:center;">
                یک رمز عبور قوی (حداقل ۶ کاراکتر) برای حساب خود انتخاب کنید.
            </p>
            <form method="POST">
                <?php echo csrf_field(); ?>
                <div class="form-group">
                    <label for="password">رمز عبور جدید</label>
                    <input type="password" id="password" name="password" class="form-control" required dir="ltr"
                        autocomplete="new-password">
                </div>
                <div class="form-group">
                    <label for="password2">تکرار رمز عبور</label>
                    <input type="password" id="password2" name="password2" class="form-control" required dir="ltr"
                        autocomplete="new-password">
                </div>
                <button type="submit" class="btn btn-primary btn-block">تغییر رمز عبور</button>
            </form>
        <?php else: ?>
            <div class="flash flash-error" style="text-align:center;">
                لینک بازیابی نامعتبر یا منقضی شده است.
            </div>
            <div class="auth-footer" style="margin-top:20px;">
                <a href="/forgot-password" class="btn btn-outline btn-block">درخواست لینک جدید</a>
            </div>
        <?php endif; ?>
    </div>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>