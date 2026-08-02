<?php
$pageTitle = 'سفارش‌های من';
require_once __DIR__ . '/includes/auth.php';
require_login();

$user = current_user();
$orders = db_read('orders.json');
$products = db_read('products.json');
$plans = db_read('plans.json');

$productsMap = [];
foreach ($products as $p) $productsMap[$p['id']] = $p;
$plansMap = [];
foreach ($plans as $p) $plansMap[$p['id']] = $p;

$myOrders = array_filter($orders, fn($o) => $o['user_id'] == $user['id']);
usort($myOrders, fn($a, $b) => $b['id'] - $a['id']);

require_once __DIR__ . '/includes/header.php';
?>
<div class="container">
    <h2 class="card-title" style="margin-bottom:20px;">سفارش‌های من</h2>

    <?php if (empty($myOrders)): ?>
        <div class="empty-state card">
            <p>سفارشی ثبت نشده است.</p>
            <a href="/products" class="btn btn-primary mt-2">مشاهده محصولات</a>
        </div>
    <?php else: ?>
        <div class="card">
            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>محصول</th>
                            <th>پلن</th>
                            <th>مبلغ</th>
                            <th>وضعیت</th>
                            <th>تاریخ</th>
                            <th>عملیات</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($myOrders as $o):
                            $product = $productsMap[$o['product_id']] ?? null;
                            $plan = $plansMap[$o['plan_id']] ?? null;
                            $active = is_membership_active($o);
                        ?>
                            <tr>
                                <td><?php echo $o['id']; ?></td>
                                <td><?php echo $product ? clean($product['name']) : '—'; ?></td>
                                <td><?php echo $plan ? clean($plan['name']) : '—'; ?></td>
                                <td><?php echo format_price($o['amount']); ?></td>
                                <td>
                                    <?php if ($o['status'] === 'pending'): ?>
                                        <span class="badge badge-pending">در انتظار</span>
                                    <?php elseif ($o['status'] === 'approved' && $active): ?>
                                        <span class="badge badge-approved">فعال</span>
                                    <?php elseif ($o['status'] === 'approved'): ?>
                                        <span class="badge badge-expired">منقضی</span>
                                    <?php else: ?>
                                        <span class="badge badge-rejected">رد شده</span>
                                    <?php endif; ?>
                                </td>
                                <td style="font-size:0.85rem;"><?php echo to_jalali_datetime($o['created_at']); ?></td>
                                <td>
                                    <?php if ($o['status'] === 'pending'): ?>
                                        <a href="/purchase?order_id=<?php echo $o['id']; ?>" class="btn btn-outline btn-sm">آپلود
                                            فیش</a>
                                    <?php elseif ($active && !empty($o['api_key'])): ?>
                                        <button class="copy-btn btn-sm" data-key="<?php echo clean($o['api_key']); ?>">کپی
                                            Key</button>
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
    <?php endif; ?>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>