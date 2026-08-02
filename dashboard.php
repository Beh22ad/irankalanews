<?php
$pageTitle = 'داشبورد';
require_once __DIR__ . '/includes/auth.php';
require_login();

$user = current_user();
$orders = db_read('orders.json');
$myOrders = array_filter($orders, fn($o) => $o['user_id'] == $user['id']);
$products = db_read('products.json');
$plans = db_read('plans.json');

$productsMap = [];
foreach ($products as $p) $productsMap[$p['id']] = $p;
$plansMap = [];
foreach ($plans as $p) $plansMap[$p['id']] = $p;

// محاسبه روزهای باقیمانده
function days_remaining($expireDate)
{
    if (empty($expireDate)) return null;
    $diff = strtotime($expireDate) - time();
    if ($diff < 0) return 0;
    return (int)ceil($diff / 86400);
}

$flash = flash_get();
require_once __DIR__ . '/includes/header.php';
?>
<div class="container">
    <div class="dashboard-welcome">
        <br>
        <h3>سلام <?php echo clean($user['name']); ?></h3>
        <p>در داشبورد خود میتوانید سفارش‌های خود را مدیریت کنید.</p>
    </div>

    <?php if ($flash): ?>
        <div class="flash flash-<?php echo $flash['type']; ?>"><?php echo $flash['message']; ?></div>
    <?php endif; ?>

    <h3 class="card-title">اشتراک‌های شما</h3>

    <?php if (empty($myOrders)): ?>
        <div class="empty-state card">
            <p>هنوز اشتراکی ندارید.</p>
            <a href="/products" class="btn btn-primary mt-2">مشاهده محصولات</a>
        </div>
    <?php else: ?>
        <?php foreach ($myOrders as $order):
            $product = $productsMap[$order['product_id']] ?? null;
            $plan = $plansMap[$order['plan_id']] ?? null;
            $active = is_membership_active($order);
            $expired = $order['status'] === 'approved' && !$active;
            $cardClass = $expired ? 'expired' : ($order['status'] === 'rejected' ? 'rejected' : '');
            $daysLeft = days_remaining($order['expire_date']);
        ?>
            <div class="card membership-card <?php echo $cardClass; ?>">
                <div class="mc-top">
                    <div>
                        <h4 style="font-weight:700;margin-bottom:4px;">
                            <?php echo $product ? clean($product['name']) : 'محصول حذف شده'; ?>
                        </h4>
                        <?php if ($order['status'] === 'pending'): ?>
                            <span class="badge badge-pending">در انتظار تأیید</span>
                        <?php elseif ($active): ?>
                            <span class="badge badge-approved">فعال</span>
                        <?php elseif ($expired): ?>
                            <span class="badge badge-expired">منقضی شده</span>
                        <?php elseif ($order['status'] === 'rejected'): ?>
                            <span class="badge badge-rejected">رد شده</span>
                        <?php endif; ?>
                    </div>
                    <?php if ($active && $daysLeft !== null): ?>
                        <div class="mc-days <?php echo $daysLeft <= 7 ? 'mc-days-warn' : ''; ?>">
                            <span class="mc-days-num"><?php echo $daysLeft; ?></span>
                            <span class="mc-days-label">روز مانده</span>
                        </div>
                    <?php endif; ?>
                </div>

                <dl class="membership-info">
                    <dt>پلن:</dt>
                    <dd><?php echo $plan ? clean($plan['name']) : '—'; ?></dd>
                    <dt>مبلغ:</dt>
                    <dd><?php echo format_price($order['amount']); ?></dd>
                    <dt>تاریخ ثبت:</dt>
                    <dd><?php echo to_jalali_datetime($order['created_at']); ?></dd>
                    <?php if ($order['status'] === 'approved' && !empty($order['expire_date'])): ?>
                        <dt>تاریخ انقضا:</dt>
                        <dd class="expire-date <?php echo $daysLeft !== null && $daysLeft <= 7 ? 'expire-warn' : ''; ?>">
                            <?php echo to_jalali($order['expire_date']); ?>
                            <span class="expire-gregorian">(<?php echo $order['expire_date']; ?>)</span>
                        </dd>
                    <?php endif; ?>
                </dl>

                <?php if ($active && !empty($order['api_key'])): ?>
                    <div class="api-key-box">
                        <span class="api-key-text"><?php echo clean($order['api_key']); ?></span>
                        <button class="copy-btn" data-key="<?php echo clean($order['api_key']); ?>">کپی</button>
                    </div>
                <?php endif; ?>

                <?php if ($order['status'] === 'pending'): ?>
                    <div class="mt-2">
                        <a href="/purchase?order_id=<?php echo $order['id']; ?>" class="btn btn-outline btn-sm">آپلود فیش پرداخت</a>
                    </div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>