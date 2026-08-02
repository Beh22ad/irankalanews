<?php

/**
 * مدیریت احراز هویت با پشتیبانی ورود ماندگار از طریق کوکی
 */

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/functions.php';

// طول عمر کوکی: ۳۰ روز
define('REMEMBER_LIFE', 2592000);

if (session_status() === PHP_SESSION_NONE) {
    @ini_set('session.use_strict_mode', 1);
    @ini_set('session.cookie_httponly', 1);
    @ini_set('session.cookie_samesite', 'Lax');
    session_start();
}

/**
 * بازیابی خودکار session از کوکی "remember me"
 * در هر صفحه فراخوانی می‌شود
 */
function restore_session_from_cookie()
{
    if (isset($_SESSION['user_id'])) return;

    if (isset($_COOKIE['remember_me'])) {
        $parts = explode(':', $_COOKIE['remember_me'], 3);
        if (count($parts) !== 3) {
            // کوکی نامعتبر، پاک شود
            setcookie('remember_me', '', time() - 3600, '/');
            return;
        }

        $type = $parts[0];
        $userId = (int)$parts[1];
        $hash = $parts[2];

        if ($type === 'user') {
            $user = db_find_by_id('users.json', $userId);
            if (!$user) {
                setcookie('remember_me', '', time() - 3600, '/');
                return;
            }
            $expectedHash = hash_hmac('sha256', $userId . ':user:' . $user['password_hash'], db_read_settings()['app_secret'] ?? 'default');
            if (!hash_equals($expectedHash, $hash)) {
                setcookie('remember_me', '', time() - 3600, '/');
                return;
            }
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_type'] = 'user';
        } elseif ($type === 'admin') {
            $admin = db_find_by_id('admins.json', $userId);
            if (!$admin) {
                setcookie('remember_me', '', time() - 3600, '/');
                return;
            }
            $expectedHash = hash_hmac('sha256', $userId . ':admin:' . $admin['password_hash'], db_read_settings()['app_secret'] ?? 'default');
            if (!hash_equals($expectedHash, $hash)) {
                setcookie('remember_me', '', time() - 3600, '/');
                return;
            }
            $_SESSION['user_id'] = $admin['id'];
            $_SESSION['user_name'] = $admin['name'];
            $_SESSION['user_type'] = 'admin';
        }
    }
}

// تلاش برای بازیابی session — در هر بارگذاری صفحه صدا زده می‌شود
restore_session_from_cookie();

function user_login_check()
{
    return isset($_SESSION['user_id']) && isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'user';
}

function admin_login_check()
{
    return isset($_SESSION['user_id']) && isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'admin';
}

function require_login()
{
    if (!user_login_check()) {
        flash_set('error', 'لطفاً ابتدا وارد حساب کاربری خود شوید.');
        safe_redirect('/login');
    }
}

function require_admin()
{
    if (!admin_login_check()) {
        safe_redirect('/admin/login');
    }
}

function user_login($email, $password, $remember = false)
{
    $users = db_read('users.json');
    foreach ($users as $u) {
        if (isset($u['email']) && $u['email'] === $email && password_verify($password, $u['password_hash'])) {
            $_SESSION['user_id'] = $u['id'];
            $_SESSION['user_name'] = $u['name'];
            $_SESSION['user_type'] = 'user';

            if ($remember) {
                set_remember_cookie('user', $u['id'], $u['password_hash']);
            } else {
                // حذف کوکی قبلی اگر چک‌باکس خالی بود
                setcookie('remember_me', '', time() - 3600, '/');
            }
            return true;
        }
    }
    return false;
}

function admin_login($username, $password, $remember = false)
{
    $admins = db_read('admins.json');
    foreach ($admins as $a) {
        if (isset($a['username']) && $a['username'] === $username && password_verify($password, $a['password_hash'])) {
            $_SESSION['user_id'] = $a['id'];
            $_SESSION['user_name'] = $a['name'];
            $_SESSION['user_type'] = 'admin';

            if ($remember) {
                set_remember_cookie('admin', $a['id'], $a['password_hash']);
            } else {
                setcookie('remember_me', '', time() - 3600, '/');
            }
            return true;
        }
    }
    return false;
}

/**
 * تنظیم کوکی "remember me"
 */
function set_remember_cookie($type, $userId, $passwordHash)
{
    $secret = db_read_settings()['app_secret'] ?? 'default';
    $hash = hash_hmac('sha256', $userId . ':' . $type . ':' . $passwordHash, $secret);
    $value = $type . ':' . $userId . ':' . $hash;

    setcookie('remember_me', $value, [
        'expires' => time() + REMEMBER_LIFE,
        'path' => '/',
        'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
}

function logout()
{
    // حذف کوکی
    setcookie('remember_me', '', time() - 3600, '/');

    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 3600, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
}

function current_user()
{
    if (!user_login_check()) return null;
    return db_find_by_id('users.json', $_SESSION['user_id']);
}
