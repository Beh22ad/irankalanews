<?php
session_start();
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/jalali.php';

if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    header('Location: login.php');
    exit;
}

$users = db_read('users.json');
$orders = db_read('orders.json');
usort($users, fn($a, $b) => $b['id'] - $a['id']);

// آمار هر کاربر
$userStats = [];
foreach ($orders as $o) {
    $uid = $o['user_id'];
    if (!isset($userStats[$uid])) {
        $userStats[$uid] = ['total' => 0, 'active' => 0];
    }
    $userStats[$uid]['total']++;
    if (is_membership_active($o)) $userStats[$uid]['active']++;
}
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>مدیریت کاربران</title>
    <link href="/assets/fonts/stylesheet.css" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/style.css">
</head>

<body>
    <header class="site-header">
        <div class="container header-inner">
            <a href="/admin/index.php" class="logo">مدیریت</a>
            <button class="hamburger" id="hamburgerBtn"
                aria-label="منو"><span></span><span></span><span></span></button>
            <nav class="main-nav" id="mainNav">
                <a href="index.php">داشبورد</a>
                <a href="orders.php">سفارش‌ها</a>
                <a href="users.php" class="active">کاربران</a>
                <a href="products.php">محصولات</a>
                <a href="plans.php">پلن‌ها</a>
                <a href="posts.php">مستندات</a>
                <a href="settings.php">تنظیمات</a>
                <a href="/index.php">سایت</a>
                <a href="logout.php">خروج</a>
            </nav>
        </div>
    </header>
    <main class="site-main">
        <div class="container">
            <div class="card">
                <h3 class="card-title">لیست کاربران (<?php echo count($users); ?>)</h3>
                <div class="table-wrapper">
                    <table>
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>نام</th>
                                <th>ایمیل</th>
                                <th>موبایل</th>
                                <th>وب‌سایت</th>
                                <th>سفارش‌ها</th>
                                <th>فعال</th>
                                <th>تاریخ ثبت‌نام</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $u):
                                $stats = $userStats[$u['id']] ?? ['total' => 0, 'active' => 0];
                            ?>
                                <tr>
                                    <td><?php echo $u['id']; ?></td>
                                    <td><?php echo clean($u['name']); ?></td>
                                    <td dir="ltr" style="font-size:0.85rem;"><?php echo clean($u['email']); ?></td>
                                    <td dir="ltr" style="font-size:0.85rem;"><?php echo clean($u['mobile']); ?></td>
                                    <td dir="ltr" style="font-size:0.85rem;"><?php echo clean($u['website']); ?></td>
                                    <td><?php echo $stats['total']; ?></td>
                                    <td><span class="badge badge-approved"><?php echo $stats['active']; ?></span></td>
                                    <td style="font-size:0.82rem;"><?php echo to_jalali_datetime($u['created_at']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>
    <script src="/assets/js/app.js"></script>
</body>

</html>