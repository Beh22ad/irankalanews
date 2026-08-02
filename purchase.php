<?php

/**
 * ایجاد سفارش و آپلود فیش پرداخت
 */
$pageTitle = 'ثبت سفارش';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/telegram.php';
require_login();

$user = current_user();
$products = db_read('products.json');
$plans = db_read('plans.json');
$orders = db_read('orders.json');
$settings = db_read_settings();

$productsMap = [];
foreach ($products as $p) $productsMap[$p['id']] = $p;
$plansMap = [];
foreach ($plans as $p) $plansMap[$p['id']] = $p;

// فرمت شماره کارت 4 تا 4 تا
function format_card_number($number)
{
    return trim(chunk_split(preg_replace('/\D/', '', $number), 4, ' '));
}

$bankCardName = $settings['bank_card_name'] ?? '';
$bankCardNumber = format_card_number($settings['bank_card_number'] ?? '');

$error = '';
$flash = flash_get();

// ============================================
// POST درخواست‌ها
// ============================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (isset($_POST['product_id']) && isset($_POST['plan_id'])) {
        if (!csrf_verify()) {
            flash_set('error', 'درخواست نامعتبر.');
            safe_redirect('/products');
        }

        $productId = (int)$_POST['product_id'];
        $planId = (int)$_POST['plan_id'];
        $product = $productsMap[$productId] ?? null;
        $plan = $plansMap[$planId] ?? null;

        if (!$product || !$plan || $plan['product_id'] != $productId) {
            flash_set('error', 'محصول یا پلن نامعتبر است.');
            safe_redirect('/products');
        }

        $newOrder = [
            'id' => db_next_id('orders.json'),
            'user_id' => $user['id'],
            'product_id' => $productId,
            'plan_id' => $planId,
            'amount' => $plan['price'],
            'status' => 'pending',
            'created_at' => date('Y-m-d H:i:s'),
            'approved_at' => null,
            'expire_date' => null,
            'api_key' => null
        ];
        $orders[] = $newOrder;
        db_write('orders.json', $orders);

        safe_redirect('/purchase?order_id=' . $newOrder['id']);
    }

    if (isset($_FILES['receipt']) && isset($_POST['order_id'])) {
        if (!csrf_verify()) {
            flash_set('error', 'درخواست نامعتبر.');
            safe_redirect('/purchase?order_id=' . (int)$_POST['order_id']);
        }

        $uploadOrderId = (int)$_POST['order_id'];
        $order = null;
        foreach ($orders as $o) {
            if ($o['id'] == $uploadOrderId && $o['user_id'] == $user['id'] && $o['status'] === 'pending') {
                $order = $o;
                break;
            }
        }

        if (!$order) {
            flash_set('error', 'سفارش نامعتبر است.');
            safe_redirect('/products');
        }

        $errors = validate_upload($_FILES['receipt']);
        if (!empty($errors)) {
            $error = implode('<br>', $errors);
        } else {
            $ext = strtolower(pathinfo($_FILES['receipt']['name'], PATHINFO_EXTENSION));
            $allowedExt = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            if (!in_array($ext, $allowedExt)) {
                $ext = 'jpg';
            }
            $fileName = $order['id'] . '.' . $ext;
            $savePath = __DIR__ . '/card/' . $fileName;

            if (move_uploaded_file($_FILES['receipt']['tmp_name'], $savePath)) {
                $siteUrl = rtrim($settings['site_url'] ?? '', '/');
                $imgUrl = $siteUrl . '/card/' . $fileName;

                $product = $productsMap[$order['product_id']] ?? null;
                $plan = $plansMap[$order['plan_id']] ?? null;
                notify_new_order($order, $user, $product, $plan, $imgUrl);
                send_receipt_email($order, $user, $product, $plan, $savePath);
                flash_set('success', 'فیش پرداخت با موفقیت ثبت شد.');
                safe_redirect('/purchase?order_id=' . $order['id'] . '&uploaded=1');
            } else {
                flash_set('error', 'خطا در ذخیره فایل.');
                safe_redirect('/purchase?order_id=' . $order['id']);
            }
        }
    }
}

// ============================================
// GET درخواست
// ============================================
$uploaded = isset($_GET['uploaded']);
$orderId = (int)($_GET['order_id'] ?? 0);
$currentOrder = null;

if ($orderId > 0) {
    foreach ($orders as $o) {
        if ($o['id'] == $orderId && $o['user_id'] == $user['id'] && $o['status'] === 'pending') {
            $currentOrder = $o;
            break;
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>
<div class="container" style="max-width:560px;">
    <h2 class="card-title">ثبت سفارش</h2>

    <?php if ($error): ?>
        <div class="flash flash-error"><?php echo $error; ?></div>
    <?php endif; ?>

    <?php if ($flash): ?>
        <div class="flash flash-<?php echo $flash['type']; ?>"><?php echo $flash['message']; ?></div>
    <?php endif; ?>

    <?php if ($uploaded && $currentOrder): ?>
        <div class="card" style="text-align:center;padding:40px;">
            <div style="font-size:3rem;margin-bottom:16px;color:var(--success);">&#10003;</div>
            <h3 style="margin-bottom:8px;">فیش پرداخت با موفقیت ثبت شد.</h3>
            <p class="text-muted mb-2">سفارش شما در انتظار تأیید مدیریت است.</p>
            <a href="/purchase?order_id=<?php echo $currentOrder['id']; ?>" class="btn btn-outline">ثبت دوباره</a>
            <a href="/dashboard" class="btn btn-primary" style="margin-right:8px;">بازگشت به داشبورد</a>
        </div>
    <?php elseif ($currentOrder):
        $product = $productsMap[$currentOrder['product_id']] ?? null;
        $plan = $plansMap[$currentOrder['plan_id']] ?? null;
    ?>
        <!-- اطلاعات کارت بانکی -->
        <?php if (!empty($bankCardName) && !empty($bankCardNumber)): ?>
            <div class="bank-card-info">
                <div class="bank-card-info-title">
                    <span>&#128179;</span> مبلغ را به کارت زیر واریز کنید و فیش را آپلود نمایید:
                </div>
                <div class="bank-card-visual">
                    <div class="bank-card-chip"></div>
                    <div class="bank-card-number" dir="ltr"><?php echo $bankCardNumber; ?></div>
                    <div class="bank-card-bottom">
                        <span class="bank-card-holder"><?php echo clean($bankCardName); ?></span>
                        <span class="bank-card-label">کارت به کارت</span>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- جزئیات سفارش -->
        <div class="card">
            <h4 style="font-weight:700;margin-bottom:12px;">جزئیات سفارش</h4>
            <dl class="membership-info">
                <dt>محصول:</dt>
                <dd><?php echo $product ? clean($product['name']) : '—'; ?></dd>
                <dt>پلن:</dt>
                <dd><?php echo $plan ? clean($plan['name']) : '—'; ?></dd>
                <dt>مبلغ:</dt>
                <dd class="amount-highlight"><?php echo format_price($currentOrder['amount']); ?></dd>
            </dl>

            <h4 style="font-weight:700;margin:24px 0 12px;">آپلود فیش پرداخت</h4>
            <p class="text-muted" style="margin-bottom:16px;font-size:0.9rem;">
                تصویر فیش واریزی را آپلود کنید.
            </p>
            <form method="POST" enctype="multipart/form-data">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="order_id" value="<?php echo $currentOrder['id']; ?>">
                <div class="upload-area" id="uploadArea">
                    <input type="file" id="receiptFile" name="receipt" accept="image/*" required>
                    <div class="upload-icon">&#128206;</div>
                    <div class="upload-text">کلیک کنید یا فایل را بکشید اینجا رها کنید</div>
                    <div class="upload-preview">
                        <img id="previewImg" src="" alt="پیش‌نمایش" style="display:none;">
                    </div>
                </div>
                <button type="submit" class="btn btn-primary btn-block mt-2">ارسال فیش</button>
            </form>
        </div>
    <?php else: ?>
        <div class="card">
            <p class="text-muted">لطفاً از صفحه محصولات اقدام به خرید کنید.</p>
            <a href="/products" class="btn btn-primary mt-2">مشاهده محصولات</a>
        </div>
    <?php endif; ?>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>