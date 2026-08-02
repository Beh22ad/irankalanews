<?php
$pageTitle = 'ثبت‌نام';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/captcha.php';

if (user_login_check()) safe_redirect('/dashboard');

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        $error = 'درخواست نامعتبر است.';
    } elseif (!captcha_verify($_POST['captcha'] ?? '')) {
        $error = 'کد تصویری اشتباه است.';
    } else {
        $name = clean($_POST['name'] ?? '');
        $email = clean($_POST['email'] ?? '');
        $mobile = clean($_POST['mobile'] ?? '');
        $password = $_POST['password'] ?? '';
        $password2 = $_POST['password2'] ?? '';
        $website = clean($_POST['website'] ?? '');

        if (empty($name) || empty($email) || empty($mobile) || empty($password) || empty($website)) {
            $error = 'تمام فیلدها الزامی هستند.';
        } elseif (!is_valid_email($email)) {
            $error = 'ایمیل نامعتبر است.';
        } elseif (!is_valid_mobile($mobile)) {
            $error = 'شماره موبایل نامعتبر است (مثال: 09120000000).';
        } elseif (mb_strlen($password) < 6) {
            $error = 'رمز عبور باید حداقل ۶ کاراکتر باشد.';
        } elseif ($password !== $password2) {
            $error = 'رمز عبور و تکرار آن مطابقت ندارند.';
        } else {
            $users = db_read('users.json');
            foreach ($users as $u) {
                if ($u['email'] === $email) {
                    $error = 'این ایمیل قبلاً ثبت شده است.';
                    break;
                }
                if ($u['mobile'] === $mobile) {
                    $error = 'این شماره موبایل قبلاً ثبت شده است.';
                    break;
                }
            }
            if (empty($error)) {
                $newUser = [
                    'id' => db_next_id('users.json'),
                    'name' => $name,
                    'email' => $email,
                    'mobile' => $mobile,
                    'password_hash' => password_hash($password, PASSWORD_DEFAULT),
                    'website' => $website,
                    'created_at' => date('Y-m-d H:i:s')
                ];
                $users[] = $newUser;
                db_write('users.json', $users);
                user_login($email, $password);
                safe_redirect('/dashboard');
            }
        }
    }
}
require_once __DIR__ . '/includes/header.php';
?>
<div class="auth-wrapper">
    <div class="card">
        <h2>ثبت‌نام حساب جدید</h2>
        <?php if ($error): ?>
            <div class="flash flash-error"><?php echo $error; ?></div>
        <?php endif; ?>
        <form method="POST">
            <?php echo csrf_field(); ?>
            <div class="form-group">
                <label for="name">نام و نام خانوادگی</label>
                <input type="text" id="name" name="name" class="form-control"
                    value="<?php echo clean($_POST['name'] ?? ''); ?>" required>
            </div>
            <div class="form-group">
                <label for="email">ایمیل</label>
                <input type="email" id="email" name="email" class="form-control"
                    value="<?php echo clean($_POST['email'] ?? ''); ?>" required dir="ltr">
            </div>
            <div class="form-group">
                <label for="mobile">شماره موبایل</label>
                <input type="text" id="mobile" name="mobile" class="form-control"
                    value="<?php echo clean($_POST['mobile'] ?? ''); ?>" required dir="ltr" placeholder="09120000000">
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label for="password">رمز عبور</label>
                    <input type="password" id="password" name="password" class="form-control" required dir="ltr">
                </div>
                <div class="form-group">
                    <label for="password2">تکرار رمز عبور</label>
                    <input type="password" id="password2" name="password2" class="form-control" required dir="ltr">
                </div>
            </div>
            <div class="form-group">
                <label for="website">آدرس وب‌سایت</label>
                <input type="text" id="website" name="website" class="form-control"
                    value="<?php echo clean($_POST['website'] ?? ''); ?>" required dir="ltr" placeholder="example.com">
            </div>
            <div class="form-group">
                <label>کد تصویری</label>
                <div class="captcha-row">
                    <img src="/captcha.php" alt="کپچا" class="captcha-image" title="برای رفرش کلیک کنید">
                    <input type="text" name="captcha" class="form-control captcha-input" required dir="ltr"
                        placeholder="کد بالا" autocomplete="off">
                </div>
            </div>
            <button type="submit" class="btn btn-primary btn-block">ثبت‌نام</button>
        </form>
        <div class="auth-footer">
            قبلاً ثبت‌نام کرده‌اید؟ <a href="/login">وارد شوید</a>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>