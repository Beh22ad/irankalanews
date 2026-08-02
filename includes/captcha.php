<?php

/**
 * کپچای تصویری بدون ذخیره فایل
 */

if (session_status() === PHP_SESSION_NONE) session_start();

function captcha_generate_code($length = 4)
{
    $chars = '23456789ABCDEFGHJKLMNPQRSTUVWXYZ';
    $code = '';
    $max = strlen($chars) - 1;
    for ($i = 0; $i < $length; $i++) {
        $code .= $chars[random_int(0, $max)];
    }
    return $code;
}

function captcha_set()
{
    $code = captcha_generate_code(4);
    $_SESSION['captcha_code'] = $code;
    return $code;
}

function captcha_verify($input)
{
    if (!isset($_SESSION['captcha_code'])) return false;
    $result = mb_strtoupper(trim($input)) === $_SESSION['captcha_code'];
    unset($_SESSION['captcha_code']);
    return $result;
}

function captcha_render()
{
    $code = captcha_set();

    // مرحله ۱: رندر متن در بوم کوچک
    $smallW = 100;
    $smallH = 40;

    $small = imagecreatetruecolor($smallW, $smallH);
    $bg = imagecolorallocate($small, 245, 247, 246);
    imagefill($small, 0, 0, $bg);

    $colors = [
        imagecolorallocate($small, 19, 78, 74),
        imagecolorallocate($small, 13, 148, 136),
        imagecolorallocate($small, 100, 50, 50),
        imagecolorallocate($small, 50, 80, 100),
        imagecolorallocate($small, 180, 80, 20),
    ];

    $codeLen = strlen($code);
    $charW = 22;
    $totalW = $codeLen * $charW;
    $startX = (int)(($smallW - $totalW) / 2);

    for ($i = 0; $i < $codeLen; $i++) {
        $color = $colors[$i % count($colors)];
        $x = $startX + ($i * $charW) + random_int(-1, 1);
        $y = 10 + random_int(-2, 2);
        imagestring($small, 5, $x, $y, $code[$i], $color);
    }

    // مرحله ۲: اسکیل UP — متن بزرگ و صاف می‌شود
    $finalW = 180;
    $finalH = 72;
    $final = imagecreatetruecolor($finalW, $finalH);
    imagecopyresampled($final, $small, 0, 0, 0, 0, $finalW, $finalH, $smallW, $smallH);

    // مرحله ۳: نویز روی تصویر نهایی
    for ($i = 0; $i < 6; $i++) {
        $lc = imagecolorallocate($final, random_int(170, 215), random_int(170, 215), random_int(170, 215));
        imageline($final, random_int(0, $finalW), random_int(0, $finalH), random_int(0, $finalW), random_int(0, $finalH), $lc);
    }
    for ($i = 0; $i < 100; $i++) {
        $dc = imagecolorallocate($final, random_int(140, 200), random_int(140, 200), random_int(140, 200));
        imagesetpixel($final, random_int(0, $finalW - 1), random_int(0, $finalH - 1), $dc);
    }

    header('Content-Type: image/png');
    header('Cache-Control: no-store, no-cache, must-revalidate');
    header('Pragma: no-cache');
    header('Expires: 0');
    imagepng($final);
    imagedestroy($small);
    imagedestroy($final);
    exit;
}
