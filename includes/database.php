<?php

/**
 * مدیریت فایل‌های JSON به عنوان دیتابیس
 * با قفل‌گذاری فایل برای جلوگیری از تداخل همزمان
 */

define('DATA_DIR', __DIR__ . '/../data/');
require_once __DIR__ . '/env.php';

function db_read($filename)
{
    $path = DATA_DIR . $filename;
    if (!file_exists($path)) {
        return [];
    }
    $content = file_get_contents($path);
    $data = json_decode($content, true);
    return is_array($data) ? $data : [];
}

function db_write($filename, $data)
{
    $path = DATA_DIR . $filename;
    $json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    $fp = fopen($path, 'c');
    if (!$fp) return false;
    flock($fp, LOCK_EX);
    ftruncate($fp, 0);
    rewind($fp);
    fwrite($fp, $json);
    flock($fp, LOCK_UN);
    fclose($fp);
    return true;
}


function db_read_settings()
{
    $path = DATA_DIR . 'settings.json';
    $settings = [];
    if (file_exists($path)) {
        $content = file_get_contents($path);
        $settings = json_decode($content, true) ?: [];
    }

    // Override with environment variables (these take priority)
    $envMap = [
        'SITE_URL'              => 'site_url',
        'SITE_NAME'             => 'site_name',
        'SMTP_HOST'             => 'smtp_host',
        'SMTP_PORT'             => 'smtp_port',
        'SMTP_USERNAME'         => 'smtp_username',
        'SMTP_PASSWORD'         => 'smtp_password',
        'SMTP_FROM_EMAIL'       => 'smtp_from_email',
        'SMTP_FROM_NAME'        => 'smtp_from_name',
        'TELEGRAM_BOT_TOKEN'    => 'telegram_bot_token',
        'TELEGRAM_CHAT_ID'      => 'telegram_chat_id',
        'TELEGRAM_PROXY_URL'    => 'telegram_proxy_url',
        'RUBIKA_BOT_ID'         => 'rubika_bot_id',
        'RUBIKA_CHAT_ID'        => 'rubika_chat_id',
        'APP_SECRET'            => 'app_secret',
        'ADMIN_EMAIL'           => 'admin_email',
        'CONTACT_NAME'          => 'contact_name',
        'CONTACT_PHONE'         => 'contact_phone',
        'BANK_CARD_NAME'        => 'bank_card_name',
        'BANK_CARD_NUMBER'      => 'bank_card_number',
    ];

    foreach ($envMap as $envKey => $settingKey) {
        $value = getenv($envKey);
        if ($value !== false && $value !== '') {
            $settings[$settingKey] = $value;
        }
    }

    return $settings;
}

function db_write_settings($data)
{
    return db_write('settings.json', $data);
}

function db_next_id($filename)
{
    $data = db_read($filename);
    if (empty($data)) return 1;
    $maxId = 0;
    foreach ($data as $item) {
        if (isset($item['id']) && $item['id'] > $maxId) {
            $maxId = $item['id'];
        }
    }
    return $maxId + 1;
}

function db_find_by_id($filename, $id)
{
    $data = db_read($filename);
    foreach ($data as $item) {
        if (isset($item['id']) && $item['id'] == $id) {
            return $item;
        }
    }
    return null;
}

function db_update_by_id($filename, $id, $updates)
{
    $data = db_read($filename);
    $found = false;
    foreach ($data as $key => $item) {
        if (isset($item['id']) && $item['id'] == $id) {
            $data[$key] = array_merge($item, $updates);
            $found = true;
            break;
        }
    }
    if ($found) {
        db_write($filename, $data);
    }
    return $found;
}

function db_delete_by_id($filename, $id)
{
    $data = db_read($filename);
    $data = array_values(array_filter($data, function ($item) use ($id) {
        return !isset($item['id']) || $item['id'] != $id;
    }));
    return db_write($filename, $data);
}
