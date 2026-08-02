<?php

/**
 * کپچای تصویری بدون ذخیره فایل
 * تصویر مستقیماً در حافظه ساخته و ارسال می‌شود
 */

if (session_status() === PHP_SESSION_NONE) session_start();

// تولید کد تصادفی
function captcha_generate_code($length = 5)
{
    $chars = '23456789ABCDEFGHJKLMNPQRSTUVWXYZ';
    $code = '';
    $max = strlen($chars) - 1;
    for ($i = 0; $i < $length; $i++) {
        $code .= $chars[random_int(0, $max)];
    }
    return $code;
}

// ذخیره کد در session
function captcha_set()
{
    $code = captcha_generate_code(5);
    $_SESSION['captcha_code'] = $code;
    return $code;
}

// بررسی کد وارد شده
function captcha_verify($input)
{
    if (!isset($_SESSION['captcha_code'])) return false;
    $result = mb_strtoupper(trim($input)) === $_SESSION['captcha_code'];
    // بعد از بررسی، کد قدیمی را پاک کن
    unset($_SESSION['captcha_code']);
    return $result;
}

// رندر تصویر (بدون ذخیره فایل)
function captcha_render()
{
    $code = captcha_set();

    $width = 140;
    $height = 44;

    $image = imagecreatetruecolor($width, $height);
    if (!$image) return;

    // پس‌زمینه
    $bgColor = imagecolorallocate($image, 243, 246, 245);
    imagefill($image, 0, 0, $bgColor);

    // خطوط نویز
    for ($i = 0; $i < 5; $i++) {
        $lineColor = imagecolorallocate(
            $image,
            random_int(180, 220),
            random_int(180, 220),
            random_int(180, 220)
        );
        imageline(
            $image,
            random_int(0, $width),
            random_int(0, $height),
            random_int(0, $width),
            random_int(0, $height),
            $lineColor
        );
    }

    // نقاط نویز
    for ($i = 0; $i < 50; $i++) {
        $dotColor = imagecolorallocate(
            $image,
            random_int(150, 200),
            random_int(150, 200),
            random_int(150, 200)
        );
        imagesetpixel($image, random_int(0, $width - 1), random_int(0, $height - 1), $dotColor);
    }

    // رسم کاراکترها
    $colors = [
        imagecolorallocate($image, 19, 78, 74),
        imagecolorallocate($image, 15, 118, 110),
        imagecolorallocate($image, 13, 148, 136),
        imagecolorallocate($image, 100, 50, 50),
        imagecolorallocate($image, 50, 80, 100),
    ];

    $codeLen = mb_strlen($code);
    $fontSize = 22;
    $spacing = ($width - 20) / $codeLen;

    for ($i = 0; $i < $codeLen; $i++) {
        $char = mb_substr($code, $i, 1);
        $color = $colors[$i % count($colors)];
        $x = 10 + ($i * $spacing);
        $y = 30 + random_int(-4, 4);
        $angle = random_int(-8, 8);

        imagettftext($image, $fontSize, $angle, $x, $y, $color, __DIR__ . '/../assets/fonts/Vazirmatn.woff2', $char);
    }

    // ارسال هدر و خروجی
    header('Content-Type: image/png');
    header('Cache-Control: no-store, no-cache, must-revalidate');
    header('Pragma: no-cache');
    header('Expires: 0');
    imagepng($image);

    // پاکسازی حافظه — هیچ فایلی ذخیره نمی‌شود
    imagedestroy($image);
    exit;
}
