<?php

/**
 * تبدیل تاریخ میلادی به شمسی و برعکس
 * الگوریتم جلالی معتبر
 */

function gregorian_to_jalali($gy, $gm, $gd)
{
    $g_d_m = [0, 31, 59, 90, 120, 151, 181, 212, 243, 273, 304, 334];
    $jy = ($gy <= 1600) ? 0 : 979;
    $gy -= ($gy <= 1600) ? 621 : 1600;
    $gy2 = ($gm > 2) ? ($gy + 1) : $gy;
    $days = 365 * $gy + (int)(($gy2 + 3) / 4) - (int)(($gy2 + 99) / 100)
        + (int)(($gy2 + 399) / 400) - 80 + $gd + $g_d_m[$gm - 1];
    $jy += 33 * (int)($days / 12053);
    $days %= 12053;
    $jy += 4 * (int)($days / 1461);
    $days %= 1461;
    if ($days > 365) {
        $jy += (int)(($days - 1) / 365);
        $days = ($days - 1) % 365;
    }
    $jm = ($days < 186) ? 1 + (int)($days / 31) : 7 + (int)(($days - 186) / 30);
    $jd = 1 + (($days < 186) ? ($days % 31) : (($days - 186) % 30));
    return [$jy, $jm, $jd];
}

function jalali_to_gregorian($jy, $jm, $jd)
{
    $jy += 1595;
    $days = -355668 + (365 * $jy) + (int)($jy / 33) * 8
        + (int)(($jy % 33 + 3) / 4) + $jd + (($jm < 7) ? ($jm - 1) * 31 : (($jm - 7) * 30 + 186));
    $gy = 400 * (int)($days / 146097);
    $days %= 146097;
    if ($days > 36524) {
        $gy += 100 * (int)(--$days / 36524);
        $days %= 36524;
        if ($days >= 365) $days++;
    }
    $gy += 4 * (int)($days / 1461);
    $days %= 1461;
    if ($days > 365) {
        $gy += (int)(($days - 1) / 365);
        $days = ($days - 1) % 365;
    }
    $gd = $days + 1;
    $sal_a = [
        0,
        31,
        (($gy % 4 === 0 && $gy % 100 !== 0) || ($gy % 400 === 0)) ? 29 : 28,
        31,
        30,
        31,
        30,
        31,
        31,
        30,
        31,
        30,
        31
    ];
    $gm = 0;
    foreach ($sal_a as $m => $days_in_month) {
        if ($gd <= $days_in_month) {
            $gm = $m;
            break;
        }
        $gd -= $days_in_month;
    }
    return [$gy, $gm ?: 12, $gd];
}

function to_jalali($dateStr)
{
    if (empty($dateStr) || $dateStr === '0000-00-00') return '—';
    $parts = explode('-', substr($dateStr, 0, 10));
    if (count($parts) !== 3) return $dateStr;
    list($gy, $gm, $gd) = array_map('intval', $parts);
    list($jy, $jm, $jd) = gregorian_to_jalali($gy, $gm, $gd);
    return sprintf('%04d/%02d/%02d', $jy, $jm, $jd);
}

function to_jalali_datetime($dateStr)
{
    if (empty($dateStr)) return '—';
    $datePart = substr($dateStr, 0, 10);
    $timePart = substr($dateStr, 11, 5);
    return to_jalali($datePart) . (empty($timePart) ? '' : ' ' . $timePart);
}

function jalali_month_name($m)
{
    $months = [
        'فروردین',
        'اردیبهشت',
        'خرداد',
        'تیر',
        'مرداد',
        'شهریور',
        'مهر',
        'آبان',
        'آذر',
        'دی',
        'بهمن',
        'اسفند'
    ];
    return $months[$m - 1] ?? '';
}

function format_price($amount)
{
    return number_format($amount) . ' تومان';
}
