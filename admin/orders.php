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
$products = db_read('products.json');
$plans = db_read('plans.json');
$settings = db_read_settings();
$orders = db_read('orders.json');

$usersMap = [];
foreach ($users as $u) $usersMap[$u['id']] = $u;
$productsMap = [];
foreach ($products as $p) $productsMap[$p['id']] = $p;
$plansMap = [];
foreach ($plans as $p) $plansMap[$p['id']] = $p;

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (!csrf_verify()) {
        $error = 'درخواست نامعتبر.';
    } else {
        $orderId = (int)$_POST['order_id'];
        $action = $_POST['action'];
        $order = null;
        foreach ($orders as $o) {
            if ($o['id'] === $orderId) {
                $order = $o;
                break;
            }
        }

        if (!$order) {
            $error = 'سفارش یافت نشد.';
        } elseif ($action === 'approve') {
            $expireJY = (int)$_POST['expire_year'];
            $expireJM = (int)$_POST['expire_month'];
            $expireJD = (int)$_POST['expire_day'];

            if ($expireJY < 1400 || $expireJM < 1 || $expireJM > 12 || $expireJD < 1 || $expireJD > 31) {
                $error = 'تاریخ انقضا نامعتبر است.';
            } else {
                list($gy, $gm, $gd) = jalali_to_gregorian($expireJY, $expireJM, $expireJD);
                $expireDate = sprintf('%04d-%02d-%02d', $gy, $gm, $gd);

                $user = $usersMap[$order['user_id']] ?? null;
                $product = $productsMap[$order['product_id']] ?? null;
                $apiKey = '';
                if ($user && $product) {
                    $apiKey = generate_api_key(
                        $user['website'],
                        $product['salt'],
                        $order['id'],
                        $settings['app_secret'] ?? 'default_secret'
                    );
                }

                db_update_by_id('orders.json', $orderId, [
                    'status' => 'approved',
                    'approved_at' => date('Y-m-d H:i:s'),
                    'expire_date' => $expireDate,
                    'api_key' => $apiKey
                ]);
                $orders = db_read('orders.json');
                $success = 'سفارش #' . $orderId . ' تأیید شد. API Key: ' . $apiKey;
            }
        } elseif ($action === 'reject') {
            db_update_by_id('orders.json', $orderId, ['status' => 'rejected']);
            $orders = db_read('orders.json');
            $success = 'سفارش #' . $orderId . ' رد شد.';
        }
    }
}

$approveOrder = null;
if (isset($_GET['action']) && $_GET['action'] === 'approve' && isset($_GET['id'])) {
    $oid = (int)$_GET['id'];
    foreach ($orders as $o) {
        if ($o['id'] === $oid && $o['status'] === 'pending') {
            $approveOrder = $o;
            break;
        }
    }
}

usort($orders, fn($a, $b) => $b['id'] - $a['id']);

$filter = $_GET['status'] ?? '';
if ($filter && in_array($filter, ['pending', 'approved', 'rejected'])) {
    $orders = array_filter($orders, fn($o) => $o['status'] === $filter);
}
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>مدیریت سفارش‌ها</title>
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
                <a href="/admin">داشبورد</a>
                <a href="/admin/orders" class="active">سفارش‌ها</a>
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
            <?php if (!empty($error)): ?><div class="flash flash-error"><?php echo $error; ?></div><?php endif; ?>
            <?php if (!empty($success)): ?><div class="flash flash-success"><?php echo $success; ?></div><?php endif; ?>

            <div class="card" style="padding:14px 20px;">
                <div class="gap-2" style="align-items:center;">
                    <strong>فیلتر:</strong>
                    <a href="/admin/orders"
                        class="btn btn-sm <?php echo !$filter ? 'btn-primary' : 'btn-outline'; ?>">همه</a>
                    <a href="/admin/orders?status=pending"
                        class="btn btn-sm <?php echo $filter === 'pending' ? 'btn-primary' : 'btn-outline'; ?>">در
                        انتظار</a>
                    <a href="/admin/orders?status=approved"
                        class="btn btn-sm <?php echo $filter === 'approved' ? 'btn-primary' : 'btn-outline'; ?>">تأیید
                        شده</a>
                    <a href="/admin/orders?status=rejected"
                        class="btn btn-sm <?php echo $filter === 'rejected' ? 'btn-primary' : 'btn-outline'; ?>">رد
                        شده</a>
                </div>
            </div>

            <div class="card">
                <div class="table-wrapper">
                    <table>
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>کاربر</th>
                                <th>موبایل</th>
                                <th>محصول</th>
                                <th>پلن</th>
                                <th>مبلغ</th>
                                <th>وضعیت</th>
                                <th>تاریخ</th>
                                <th>API Key</th>
                                <th>عملیات</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($orders as $o):
                                $u = $usersMap[$o['user_id']] ?? null;
                                $p = $productsMap[$o['product_id']] ?? null;
                                $pl = $plansMap[$o['plan_id']] ?? null;
                            ?>
                                <tr>
                                    <td><?php echo $o['id']; ?></td>
                                    <td><?php echo $u ? clean($u['name']) : '—'; ?></td>
                                    <td dir="ltr" style="font-size:0.85rem;"><?php echo $u ? clean($u['mobile']) : '—'; ?>
                                    </td>
                                    <td><?php echo $p ? clean($p['name']) : '—'; ?></td>
                                    <td><?php echo $pl ? clean($pl['name']) : '—'; ?></td>
                                    <td><?php echo format_price($o['amount']); ?></td>
                                    <td>
                                        <?php if ($o['status'] === 'pending'): ?><span class="badge badge-pending">در
                                                انتظار</span>
                                        <?php elseif ($o['status'] === 'approved'): ?><span
                                                class="badge badge-approved">تأیید</span>
                                        <?php else: ?><span class="badge badge-rejected">رد</span><?php endif; ?>
                                    </td>
                                    <td style="font-size:0.82rem;"><?php echo to_jalali_datetime($o['created_at']); ?></td>
                                    <td dir="ltr" style="font-size:0.8rem;max-width:140px;word-break:break-all;">
                                        <?php echo $o['api_key'] ? clean($o['api_key']) : '—'; ?></td>
                                    <td>
                                        <?php if ($o['status'] === 'pending'): ?>
                                            <a href="/admin/orders?action=approve&id=<?php echo $o['id']; ?>"
                                                class="btn btn-primary btn-sm">تأیید</a>
                                            <form method="POST" style="display:inline;"
                                                data-confirm="آیا از رد این سفارش مطمئن هستید؟">
                                                <?php echo csrf_field(); ?>
                                                <input type="hidden" name="order_id" value="<?php echo $o['id']; ?>">
                                                <input type="hidden" name="action" value="reject">
                                                <button type="submit" class="btn btn-danger btn-sm">رد</button>
                                            </form>
                                        <?php else: ?>
                                            —
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <?php if ($approveOrder):
                $ao_user = $usersMap[$approveOrder['user_id']] ?? null;
                $ao_product = $productsMap[$approveOrder['product_id']] ?? null;
                $ao_plan = $plansMap[$approveOrder['plan_id']] ?? null;
                $defaultExpire = strtotime('+' . ($ao_plan['duration_months'] ?? 1) . ' months');
                $de = gregorian_to_jalali(date('Y', $defaultExpire), date('m', $defaultExpire), date('d', $defaultExpire));
            ?>
                <div
                    style="display:block;position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:200;overflow-y:auto;padding:20px;">
                    <div class="card" style="max-width:480px;margin:40px auto;animation:none;">
                        <h3 class="card-title">تأیید سفارش #<?php echo $approveOrder['id']; ?></h3>
                        <dl class="membership-info">
                            <dt>کاربر:</dt>
                            <dd><?php echo $ao_user ? clean($ao_user['name']) : '—'; ?></dd>
                            <dt>موبایل:</dt>
                            <dd dir="ltr"><?php echo $ao_user ? clean($ao_user['mobile']) : '—'; ?></dd>
                            <dt>محصول:</dt>
                            <dd><?php echo $ao_product ? clean($ao_product['name']) : '—'; ?></dd>
                            <dt>پلن:</dt>
                            <dd><?php echo $ao_plan ? clean($ao_plan['name']) : '—'; ?></dd>
                            <dt>مبلغ:</dt>
                            <dd><?php echo format_price($approveOrder['amount']); ?></dd>
                        </dl>
                        <form method="POST" style="margin-top:20px;">
                            <?php echo csrf_field(); ?>
                            <input type="hidden" name="order_id" value="<?php echo $approveOrder['id']; ?>">
                            <input type="hidden" name="action" value="approve">
                            <h4 style="font-weight:700;margin-bottom:12px;">تاریخ انقضا (شمسی)</h4>
                            <div class="form-row">
                                <div class="form-group">
                                    <label>سال</label>
                                    <input type="number" name="expire_year" class="form-control"
                                        value="<?php echo $de[0]; ?>" min="1400" max="1410" required dir="ltr">
                                </div>
                                <div class="form-group">
                                    <label>ماه</label>
                                    <select name="expire_month" class="form-control" required>
                                        <?php for ($m = 1; $m <= 12; $m++): ?>
                                            <option value="<?php echo $m; ?>" <?php echo $m == $de[1] ? 'selected' : ''; ?>>
                                                <?php echo $m . ' - ' . jalali_month_name($m); ?></option>
                                        <?php endfor; ?>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label>روز</label>
                                    <input type="number" name="expire_day" class="form-control"
                                        value="<?php echo $de[2]; ?>" min="1" max="31" required dir="ltr">
                                </div>
                            </div>
                            <div class="gap-2 mt-2">
                                <button type="submit" class="btn btn-primary">تأیید و تولید API Key</button>
                                <a href="/admin/orders" class="btn btn-outline">انصراف</a>
                            </div>
                        </form>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </main>
    <script src="/assets/js/app.js"></script>
</body>

</html>