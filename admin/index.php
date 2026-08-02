<?php
session_start();
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/jalali.php';

if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    header('Location: /admin/login');
    exit;
}

$users = db_read('users.json');
$orders = db_read('orders.json');
$products = db_read('products.json');

$totalUsers = count($users);
$pendingOrders = count(array_filter($orders, fn($o) => $o['status'] === 'pending'));
$activeMemberships = count(array_filter($orders, fn($o) => is_membership_active($o)));
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>داشبورد مدیریت</title>
    <link rel="stylesheet" href="/assets/fonts/stylesheet.css">
    <link rel="stylesheet" href="/assets/css/style.css">
</head>

<body>
    <header class="site-header">
        <div class="container header-inner">
            <a href="/admin" class="logo">مدیریت</a>
            <button class="hamburger" id="hamburgerBtn"
                aria-label="منو"><span></span><span></span><span></span></button>
            <nav class="main-nav" id="mainNav">
                <a href="/admin" class="active">داشبورد</a>
                <a href="/admin/orders">سفارش‌ها</a>
                <a href="/admin/users">کاربران</a>
                <a href="/admin/products">محصولات</a>
                <a href="/admin/plans">پلن‌ها</a>
                <a href="/admin/posts">مستندات</a>
                <a href="/admin/settings">تنظیمات</a>
                <a href="/">سایت</a>
                <a href="/admin/logout">خروج</a>
            </nav>
        </div>
    </header>
    <main class="site-main">
        <div class="container">
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-number"><?php echo $totalUsers; ?></div>
                    <div class="stat-label">کل کاربران</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo $pendingOrders; ?></div>
                    <div class="stat-label">سفارش‌های در انتظار</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo $activeMemberships; ?></div>
                    <div class="stat-label">اشتراک‌های فعال</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo count($products); ?></div>
                    <div class="stat-label">محصولات</div>
                </div>
            </div>

            <?php if ($pendingOrders > 0): ?>
                <div class="card">
                    <h3 class="card-title">سفارش‌های در انتظار تأیید</h3>
                    <div class="table-wrapper">
                        <table>
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>کاربر</th>
                                    <th>موبایل</th>
                                    <th>محصول</th>
                                    <th>مبلغ</th>
                                    <th>تاریخ</th>
                                    <th>عملیات</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $pendingList = array_filter($orders, fn($o) => $o['status'] === 'pending');
                                $usersMap = [];
                                foreach ($users as $u) $usersMap[$u['id']] = $u;
                                $productsMap = [];
                                foreach ($products as $p) $productsMap[$p['id']] = $p;
                                $plans = db_read('plans.json');
                                $plansMap = [];
                                foreach ($plans as $p) $plansMap[$p['id']] = $p;

                                foreach (array_slice($pendingList, 0, 10) as $o):
                                    $u = $usersMap[$o['user_id']] ?? null;
                                    $p = $productsMap[$o['product_id']] ?? null;
                                ?>
                                    <tr>
                                        <td><?php echo $o['id']; ?></td>
                                        <td><?php echo $u ? clean($u['name']) : '—'; ?></td>
                                        <td dir="ltr"><?php echo $u ? clean($u['mobile']) : '—'; ?></td>
                                        <td><?php echo $p ? clean($p['name']) : '—'; ?></td>
                                        <td><?php echo format_price($o['amount']); ?></td>
                                        <td style="font-size:0.85rem;"><?php echo to_jalali_datetime($o['created_at']); ?></td>
                                        <td><a href="/admin/orders?action=approve&id=<?php echo $o['id']; ?>"
                                                class="btn btn-primary btn-sm">بررسی</a></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </main>
    <script src="/assets/js/app.js"></script>
</body>

</html>