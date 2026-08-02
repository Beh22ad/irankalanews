<?php

/**
 * توابع کمکی عمومی
 */

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/jalali.php';
require_once __DIR__ . '/csrf.php';

// پاک‌سازی ورودی‌ها
function clean($str)
{
    if (is_array($str)) {
        return array_map('clean', $str);
    }
    return htmlspecialchars(trim($str), ENT_QUOTES, 'UTF-8');
}

// اعتبارسنجی شماره موبایل ایران
function is_valid_mobile($mobile)
{
    return preg_match('/^09[0-9]{9}$/', $mobile);
}

// اعتبارسنجی ایمیل
function is_valid_email($email)
{
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

// اعتبارسنجی slug
function is_valid_slug($slug)
{
    return preg_match('/^[a-z0-9\-]+$/', $slug);
}

// تولید API Key با HMAC-SHA256
function generate_api_key($website, $salt, $orderId, $secret)
{
    $input = $website . $salt . $orderId . $secret;
    $hash = hash_hmac('sha256', $input, $secret);
    $prefix = explode('_', $salt)[0];
    return $prefix . '_' . substr($hash, 0, 24);
}

// بررسی وضعیت اشتراک
function is_membership_active($order)
{
    if ($order['status'] !== 'approved') return false;
    if (empty($order['expire_date'])) return false;
    return strtotime($order['expire_date']) >= time();
}

// پیام‌های flash
function flash_set($type, $message)
{
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function flash_get()
{
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

// اعتبارسنجی آپلود فایل تصویر
function validate_upload($file, $maxSizeMB = 5)
{
    $errors = [];
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $errors[] = 'خطا در آپلود فایل';
        return $errors;
    }
    if ($file['size'] > $maxSizeMB * 1024 * 1024) {
        $errors[] = 'حجم فایل نباید بیشتر از ' . $maxSizeMB . ' مگابایت باشد';
    }
    $allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    if (!in_array($file['type'], $allowed)) {
        $errors[] = 'فقط فرمت‌های JPG, PNG, GIF, WebP مجاز هستند';
    }
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowedExt = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    if (!in_array($ext, $allowedExt)) {
        $errors[] = 'پسوند فایل نامعتبر است';
    }
    return $errors;
}

// ریدایرکت امن
function safe_redirect($url)
{
    header('Location: ' . $url);
    exit;
}

// دریافت آی‌پی کاربر
function get_client_ip()
{
    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
        return trim($ips[0]);
    }
    return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}
