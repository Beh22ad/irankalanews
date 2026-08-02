<?php
session_start();
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/functions.php';

if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    header('Location: /admin/login');
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_plan'])) {
    if (!csrf_verify()) {
        $error = 'درخواست نامعتبر.';
    } else {
        $productId = (int)$_POST['product_id'];
        $name = clean($_POST['name'] ?? '');
        $durationMonths = (int)$_POST['duration_months'];
        $price = (int)str_replace(',', '', $_POST['price'] ?? '0');

        if (empty($name) || $durationMonths < 1 || $price < 0) {
            $error = 'تمام فیلدها الزامی و معتبر هستند.';
        } else {
            $plans = db_read('plans.json');
            $plans[] = [
                'id' => db_next_id('plans.json'),
                'product_id' => $productId,
                'name' => $name,
                'duration_months' => $durationMonths,
                'price' => $price,
                'created_at' => date('Y-m-d H:i:s')
            ];
            db_write('plans.json', $plans);
            $success = 'پلن اضافه شد.';
        }
    }
}

if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    db_delete_by_id('plans.json', (int)$_GET['delete']);
    $success = 'پلن حذف شد.';
}

$plans = db_read('plans.json');
$products = db_read('products.json');
$productsMap = [];
foreach ($products as $p) $productsMap[$p['id']] = $p;
usort($plans, fn($a, $b) => $a['product_id'] <=> $b['product_id'] ?: $a['duration_months'] <=> $b['duration_months']);
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>مدیریت پلن‌ها</title>
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
                <a href="/admin/orders">سفارش‌ها</a>
                <a href="/admin/users">کاربران</a>
                <a href="/admin/products">محصولات</a>
                <a href="/admin/plans" class="active">پلن‌ها</a>
                <a href="/admin/posts">مستندات</a>
                <a href="/admin/settings">تنظیمات</a>
                <a href="/">سایت</a>
                <a href="/admin/logout">خروج</a>
            </nav>
        </div>
    </header>
    <main class="site-main">
        <div class="container">
            <?php if ($error): ?><div class="flash flash-error"><?php echo $error; ?></div><?php endif; ?>
            <?php if ($success): ?><div class="flash flash-success"><?php echo $success; ?></div><?php endif; ?>

            <div class="card">
                <h3 class="card-title">افزودن پلن جدید</h3>
                <form method="POST">
                    <?php echo csrf_field(); ?>
                    <div class="form-row">
                        <div class="form-group">
                            <label>محصول</label>
                            <select name="product_id" class="form-control" required>
                                <option value="">انتخاب کنید...</option>
                                <?php foreach ($products as $p): ?>
                                    <option value="<?php echo $p['id']; ?>"><?php echo clean($p['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>نام پلن</label>
                            <input type="text" name="name" class="form-control" required placeholder="مثال: ۳ ماهه">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>مدت (ماه)</label>
                            <input type="number" name="duration_months" class="form-control" required min="1" max="120"
                                dir="ltr">
                        </div>
                        <div class="form-group">
                            <label>قیمت (تومان)</label>
                            <input type="text" name="price" class="form-control" required dir="ltr"
                                placeholder="1200000">
                        </div>
                    </div>
                    <button type="submit" name="add_plan" class="btn btn-primary">افزودن</button>
                </form>
            </div>

            <div class="card">
                <h3 class="card-title">پلن‌های موجود</h3>
                <div class="table-wrapper">
                    <table>
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>محصول</th>
                                <th>نام پلن</th>
                                <th>مدت</th>
                                <th>قیمت</th>
                                <th>عملیات</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($plans as $pl):
                                $prod = $productsMap[$pl['product_id']] ?? null;
                            ?>
                                <tr>
                                    <td><?php echo $pl['id']; ?></td>
                                    <td><?php echo $prod ? clean($prod['name']) : '—'; ?></td>
                                    <td><?php echo clean($pl['name']); ?></td>
                                    <td><?php echo $pl['duration_months']; ?> ماه</td>
                                    <td><?php echo format_price($pl['price']); ?></td>
                                    <td>
                                        <a href="/admin/plans?delete=<?php echo $pl['id']; ?>" class="btn btn-danger btn-sm"
                                            data-confirm="آیا از حذف مطمئن هستید؟">حذف</a>
                                    </td>
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