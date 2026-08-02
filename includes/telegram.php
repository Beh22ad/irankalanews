<?php

/**
 * ارسال نوتیفیکیشن تلگرام (از طریق پروکسی)
 * و ارسال به روبیکا
 */

/**
 * ساخت متن سفارش برای ارسال
 * در پروکسی تلگرام از __ برای خط جدید استفاده می‌شود
 */
function build_order_message($order, $user, $product, $plan)
{
    return 'پرداخت جدید__'
        . '__نام: ' . $user['name']
        . '__موبایل: ' . $user['mobile']
        . '__محصول: ' . $product['name']
        . '__پلن: ' . $plan['name']
        . '__مبلغ: ' . number_format($order['amount']) . ' تومان'
        . '__Website: ' . $user['website'];
}

/**
 * ساخت متن برای روبیکا (خط واقعی)
 */
function build_rubika_message($order, $user, $product, $plan)
{
    return "پرداخت جدید\n\n"
        . "نام: " . $user['name'] . "\n"
        . "موبایل: " . $user['mobile'] . "\n"
        . "محصول: " . $product['name'] . "\n"
        . "پلن: " . $plan['name'] . "\n"
        . "مبلغ: " . number_format($order['amount']) . " تومان\n"
        . "Website: " . $user['website'];
}

/**
 * ارسال به تلگرام از طریق پروکسی Cloudflare Worker
 * $imgUrl: آدرس کامل تصویر فیش (مثلاً https://site.com/card/123.jpg)
 */
function telegram_send_via_proxy($message, $imgUrl = null)
{
    $settings = db_read_settings();
    $proxyUrl = $settings['telegram_proxy_url'] ?? '';
    $botToken = $settings['telegram_bot_token'] ?? '';
    $chatId = $settings['telegram_chat_id'] ?? '';

    if (empty($proxyUrl) || empty($botToken) || empty($chatId)) {
        return false;
    }

    $data = [
        'bot' => $botToken,
        'id' => $chatId,
        'msg' => $message,
    ];

    if ($imgUrl) {
        $data['img'] = $imgUrl;
    }

    $ch = curl_init($proxyUrl);
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($data, JSON_UNESCAPED_UNICODE),
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json; charset=utf-8',
        ],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 4,
        CURLOPT_CONNECTTIMEOUT => 4,
        CURLOPT_FRESH_CONNECT => true,
        CURLOPT_FORBID_REUSE => true,
        CURLOPT_NOSIGNAL => true,
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    return $httpCode >= 200 && $httpCode < 300;
}

/**
 * ارسال به روبیکا
 * روبیکا فقط متن قبول می‌کند (بدون عکس)
 */
function rubika_send_message($text)
{
    $settings = db_read_settings();
    $botId = $settings['rubika_bot_id'] ?? '';
    $chatId = $settings['rubika_chat_id'] ?? '';

    if (empty($botId) || empty($chatId)) {
        return false;
    }

    $url = "https://botapi.rubika.ir/v3/" . $botId . "/sendMessage";

    $data = [
        'chat_id' => $chatId,
        'text' => $text,
    ];

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($data, JSON_UNESCAPED_UNICODE),
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json; charset=utf-8',
        ],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT_MS => 4000,
        CURLOPT_CONNECTTIMEOUT_MS => 4000,
        CURLOPT_FRESH_CONNECT => true,
        CURLOPT_FORBID_REUSE => true,
        CURLOPT_NOSIGNAL => true,
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return $httpCode >= 200 && $httpCode < 300;
}

/**
 * ارسال نوتیفیکیشن سفارش جدید به هر دو پلتفرم
 * $imgUrl: آدرس کامل تصویر ذخیره شده
 */
function notify_new_order($order, $user, $product, $plan, $imgUrl = null)
{
    // تلگرام با عکس
    $tgMessage = build_order_message($order, $user, $product, $plan);
    telegram_send_via_proxy($tgMessage, $imgUrl);

    // روبیکا فقط متن
    $rbMessage = build_rubika_message($order, $user, $product, $plan);
    rubika_send_message($rbMessage);
}


/**
 * ارسال ایمیل به ادمین با فیش پیوست
 */
function send_receipt_email($order, $user, $product, $plan, $filePath)
{
    require_once __DIR__ . '/email.php';
    $settings = db_read_settings();
    $adminEmail = $settings['admin_email'] ?? '';

    if (empty($adminEmail) || !file_exists($filePath)) {
        return false;
    }

    $subject = 'فیش پرداخت جدید - سفارش #' . $order['id'];

    $textBody = "پرداخت جدید\n"
        . "شماره سفارش: " . $order['id'] . "\n"
        . "نام: " . $user['name'] . "\n"
        . "موبایل: " . $user['mobile'] . "\n"
        . "محصول: " . ($product ? $product['name'] : '—') . "\n"
        . "پلن: " . ($plan ? $plan['name'] : '—') . "\n"
        . "مبلغ: " . number_format($order['amount']) . " تومان\n"
        . "تاریخ: " . $order['created_at'] . "\n"
        . "Website: " . $user['website'];

    $htmlBody = "<div dir='rtl' style='font-family:Tahoma,sans-serif;font-size:14px;line-height:1.8;'>"
        . "<h2 style='color:#0d9488;margin-bottom:16px;'>فیش پرداخت جدید</h2>"
        . "<table style='border-collapse:collapse;width:100%;'>"
        . "<tr><td style='padding:8px 12px;border:1px solid #ddd;background:#f9f9f9;font-weight:bold;'>شماره سفارش</td><td style='padding:8px 12px;border:1px solid #ddd;'>" . $order['id'] . "</td></tr>"
        . "<tr><td style='padding:8px 12px;border:1px solid #ddd;background:#f9f9f9;font-weight:bold;'>نام</td><td style='padding:8px 12px;border:1px solid #ddd;'>" . $user['name'] . "</td></tr>"
        . "<tr><td style='padding:8px 12px;border:1px solid #ddd;background:#f9f9f9;font-weight:bold;'>موبایل</td><td style='padding:8px 12px;border:1px solid #ddd;' dir='ltr'>" . $user['mobile'] . "</td></tr>"
        . "<tr><td style='padding:8px 12px;border:1px solid #ddd;background:#f9f9f9;font-weight:bold;'>محصول</td><td style='padding:8px 12px;border:1px solid #ddd;'>" . ($product ? $product['name'] : '—') . "</td></tr>"
        . "<tr><td style='padding:8px 12px;border:1px solid #ddd;background:#f9f9f9;font-weight:bold;'>پلن</td><td style='padding:8px 12px;border:1px solid #ddd;'>" . ($plan ? $plan['name'] : '—') . "</td></tr>"
        . "<tr><td style='padding:8px 12px;border:1px solid #ddd;background:#f9f9f9;font-weight:bold;'>مبلغ</td><td style='padding:8px 12px;border:1px solid #ddd;'>" . number_format($order['amount']) . " تومان</td></tr>"
        . "<tr><td style='padding:8px 12px;border:1px solid #ddd;background:#f9f9f9;font-weight:bold;'>تاریخ</td><td style='padding:8px 12px;border:1px solid #ddd;'>" . $order['created_at'] . "</td></tr>"
        . "<tr><td style='padding:8px 12px;border:1px solid #ddd;background:#f9f9f9;font-weight:bold;'>Website</td><td style='padding:8px 12px;border:1px solid #ddd;' dir='ltr'>" . $user['website'] . "</td></tr>"
        . "</table></div>";

    $attachments = [
        ['path' => $filePath, 'name' => 'receipt.jpg']
    ];

    return send_system_email($adminEmail, $subject, $htmlBody, $textBody, $attachments);
}

/**
 * ارسال پیام تماس به تلگرام، روبیکا و ایمیل
 */
function send_contact_notification($name, $mobile, $email, $message)
{
    $settings = db_read_settings();

    $text = "پیام تماس جدید\n\n"
        . "نام: " . $name . "\n"
        . "موبایل: " . $mobile . "\n"
        . "ایمیل: " . $email . "\n\n"
        . "پیام:\n" . $message;

    // ارسال به تلگرام (بدون عکس)
    telegram_send_via_proxy($text);

    // ارسال به روبیکا
    rubika_send_message($text);

    // ارسال ایمیل
    $adminEmail = $settings['admin_email'] ?? '';
    if (!empty($adminEmail)) {
        $subject = 'پیام تماس جدید از ' . $name;
        $bodyHtml = "<div dir='rtl' style='font-family:Tahoma,sans-serif;font-size:14px;line-height:1.8;'>"
            . "<h2 style='color:#0d9488;'>پیام تماس جدید</h2>"
            . "<table style='border-collapse:collapse;width:100%;'>"
            . "<tr><td style='padding:8px 12px;border:1px solid #ddd;background:#f9f9f9;font-weight:bold;'>نام</td><td style='padding:8px 12px;border:1px solid #ddd;'>" . $name . "</td></tr>"
            . "<tr><td style='padding:8px 12px;border:1px solid #ddd;background:#f9f9f9;font-weight:bold;'>موبایل</td><td style='padding:8px 12px;border:1px solid #ddd;' dir='ltr'>" . $mobile . "</td></tr>"
            . "<tr><td style='padding:8px 12px;border:1px solid #ddd;background:#f9f9f9;font-weight:bold;'>ایمیل</td><td style='padding:8px 12px;border:1px solid #ddd;' dir='ltr'>" . $email . "</td></tr>"
            . "<tr><td style='padding:8px 12px;border:1px solid #ddd;background:#f9f9f9;font-weight:bold;'>پیام</td><td style='padding:8px 12px;border:1px solid #ddd;'>" . nl2br(clean($message)) . "</td></tr>"
            . "</table></div>";

        $boundary = md5(time());

        $body = "--$boundary\r\n"
            . "Content-Type: text/html; charset=utf-8\r\n"
            . "Content-Transfer-Encoding: 8bit\r\n\r\n"
            . $bodyHtml . "\r\n\r\n"
            . "--$boundary--\r\n";

        $emailFrom = 'noreply@' . (isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost');

        // ارسال ایمیل
        $adminEmail = $settings['admin_email'] ?? '';
        if (!empty($adminEmail)) {
            require_once __DIR__ . '/email.php';
            $subject = 'پیام تماس جدید از ' . $name;
            $bodyHtml = "<div dir='rtl' style='font-family:Tahoma,sans-serif;font-size:14px;line-height:1.8;'>"
                . "<h2 style='color:#0d9488;'>پیام تماس جدید</h2>"
                . "<table style='border-collapse:collapse;width:100%;'>"
                . "<tr><td style='padding:8px 12px;border:1px solid #ddd;background:#f9f9f9;font-weight:bold;'>نام</td><td style='padding:8px 12px;border:1px solid #ddd;'>" . $name . "</td></tr>"
                . "<tr><td style='padding:8px 12px;border:1px solid #ddd;background:#f9f9f9;font-weight:bold;'>موبایل</td><td style='padding:8px 12px;border:1px solid #ddd;' dir='ltr'>" . $mobile . "</td></tr>"
                . "<tr><td style='padding:8px 12px;border:1px solid #ddd;background:#f9f9f9;font-weight:bold;'>ایمیل</td><td style='padding:8px 12px;border:1px solid #ddd;' dir='ltr'>" . $email . "</td></tr>"
                . "<tr><td style='padding:8px 12px;border:1px solid #ddd;background:#f9f9f9;font-weight:bold;'>پیام</td><td style='padding:8px 12px;border:1px solid #ddd;'>" . nl2br(clean($message)) . "</td></tr>"
                . "</table></div>";

            send_system_email($adminEmail, $subject, $bodyHtml, $text);
        }
    }
}
/**
 * پاکسازی توکن‌های منقضی و قدیمی
 */
function cleanup_reset_tokens()
{
    $path = DATA_DIR . 'password_resets.json';
    if (!file_exists($path)) return;

    $resets = db_read('password_resets.json');
    $cutoff = strtotime('-24 hours');

    $filtered = array_filter($resets, function ($r) use ($cutoff) {
        // نگه‌داشتن توکن‌های معتبر یا استفاده‌شده‌های اخیر
        if (!$r['used'] && strtotime($r['expires_at']) > time()) return true;
        if ($r['used'] && strtotime($r['created_at']) > $cutoff) return true;
        return false;
    });

    db_write('password_resets.json', array_values($filtered));
}

/**
 * ارسال ایمیل بازیابی رمز عبور به کاربر
 */
function send_password_reset_email($toEmail, $userName, $resetLink)
{
    $settings = db_read_settings();
    $siteName = $settings['site_name'] ?? 'ایران کالانیوز';
    $host     = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost';
    $fromEmail = 'noreply@' . $host;

    $subject = 'لینک بازیابی رمز عبور - ' . $siteName;

    $textBody = "سلام $userName عزیز،\n\n"
        . "درخواست بازیابی رمز عبور در سایت $siteName ثبت شده است.\n\n"
        . "برای تنظیم رمز عبور جدید، روی لینک زیر کلیک کنید:\n"
        . "$resetLink\n\n"
        . "این لینک تا ۱ ساعت معتبر است.\n\n"
        . "اگر شما این درخواست را ثبت نکرده‌اید، این ایمیل را نادیده بگیرید.\n\n"
        . "با احترام،\n"
        . "تیم $siteName";

    $htmlBody = "<div dir='rtl' style='font-family:Tahoma,sans-serif;font-size:14px;line-height:1.9;color:#333;max-width:600px;margin:auto;'>"
        . "<div style='background:linear-gradient(135deg,#0d9488,#134e4a);padding:24px;border-radius:10px 10px 0 0;text-align:center;'>"
        . "<h1 style='color:#fff;margin:0;font-size:20px;'>$siteName</h1>"
        . "</div>"
        . "<div style='background:#fff;padding:28px 24px;border:1px solid #e5e7eb;border-radius:0 0 10px 10px;'>"
        . "<p style='margin:0 0 16px;'>سلام <strong>$userName</strong> عزیز،</p>"
        . "<p style='margin:0 0 20px;'>درخواست بازیابی رمز عبور در سایت ثبت شده است. برای تنظیم رمز جدید روی دکمه زیر کلیک کنید:</p>"
        . "<p style='text-align:center;margin:28px 0;'>"
        . "<a href='$resetLink' style='background:#0d9488;color:#fff;padding:13px 32px;border-radius:8px;text-decoration:none;display:inline-block;font-weight:bold;font-size:15px;'>بازیابی رمز عبور</a>"
        . "</p>"
        . "<p style='font-size:12px;color:#6b7280;margin:20px 0 6px;'>یا این لینک را در مرورگر کپی کنید:</p>"
        . "<p dir='ltr' style='font-size:12px;color:#0d9488;word-break:break-all;background:#f0fdf4;padding:10px;border-radius:6px;margin:0 0 20px;'>$resetLink</p>"
        . "<div style='background:#fffbeb;border:1px solid #fde68a;padding:12px;border-radius:6px;font-size:12px;color:#92400e;margin-top:16px;'>"
        . "⏰ این لینک تا <strong>۱ ساعت</strong> معتبر است."
        . "<br>اگر شما این درخواست را ثبت نکرده‌اید، این ایمیل را نادیده بگیرید."
        . "</div>"
        . "</div>"
        . "<p style='text-align:center;font-size:11px;color:#9ca3af;margin-top:16px;'>© $siteName</p>"
        . "</div>";

    $boundary = md5(time() . $toEmail);

    $headers   = [];
    $headers[] = "From: $siteName <$fromEmail>";
    $headers[] = "Reply-To: $fromEmail";
    $headers[] = "MIME-Version: 1.0";
    $headers[] = "Content-Type: multipart/alternative; boundary=\"$boundary\"";
    $headers[] = "X-Mailer: PHP/" . phpversion();

    $body  = "--$boundary\r\n"
        . "Content-Type: text/plain; charset=UTF-8\r\n"
        . "Content-Transfer-Encoding: 8bit\r\n\r\n"
        . $textBody . "\r\n\r\n"
        . "--$boundary\r\n"
        . "Content-Type: text/html; charset=UTF-8\r\n"
        . "Content-Transfer-Encoding: 8bit\r\n\r\n"
        . $htmlBody . "\r\n\r\n"
        . "--$boundary--";

    $encodedSubject = '=?UTF-8?B?' . base64_encode($subject) . '?=';

    return @mail($toEmail, $encodedSubject, $body, implode("\r\n", $headers));
}
