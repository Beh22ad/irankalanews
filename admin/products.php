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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_product'])) {
    if (!csrf_verify()) {
        $error = 'درخواست نامعتبر.';
    } else {
        $name = clean($_POST['name'] ?? '');
        $slug = clean($_POST['slug'] ?? '');
        $salt = clean($_POST['salt'] ?? '');
        $description = clean($_POST['description'] ?? '');
        // آیکون SVG — بدون clean چون باید raw ذخیره شود (فقط ادمین می‌تواند ویرایش کند)
        $icon = trim($_POST['icon'] ?? '');

        if (empty($name) || empty($slug) || empty($salt)) {
            $error = 'نام، slug و salt الزامی هستند.';
        } elseif (!is_valid_slug($slug)) {
            $error = 'Slug فقط می‌تواند شامل حروف انگلیسی کوچک، عدد و خط تیره باشد.';
        } else {
            $products = db_read('products.json');
            foreach ($products as $p) {
                if ($p['slug'] === $slug) {
                    $error = 'این slug قبلاً استفاده شده است.';
                    break;
                }
            }
            if (empty($error)) {
                $products[] = [
                    'id' => db_next_id('products.json'),
                    'name' => $name,
                    'slug' => $slug,
                    'salt' => $salt,
                    'description' => $description,
                    'icon' => $icon,
                    'created_at' => date('Y-m-d H:i:s')
                ];
                db_write('products.json', $products);
                $success = 'محصول اضافه شد.';
            }
        }
    }
}

if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    db_delete_by_id('products.json', (int)$_GET['delete']);
    $success = 'محصول حذف شد.';
}

$products = db_read('products.json');
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>مدیریت محصولات</title>
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
                <a href="/admin/products" class="active">محصولات</a>
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
            <?php if ($error): ?><div class="flash flash-error"><?php echo $error; ?></div><?php endif; ?>
            <?php if ($success): ?><div class="flash flash-success"><?php echo $success; ?></div><?php endif; ?>

            <div class="card">
                <h3 class="card-title">افزودن محصول جدید</h3>
                <form method="POST">
                    <?php echo csrf_field(); ?>
                    <div class="form-row">
                        <div class="form-group">
                            <label>نام محصول</label>
                            <input type="text" name="name" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label>Slug (انگلیسی)</label>
                            <input type="text" name="slug" class="form-control" required dir="ltr"
                                placeholder="iron-api">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Salt (برای تولید API Key)</label>
                            <input type="text" name="salt" class="form-control" required dir="ltr"
                                placeholder="iron_secret_xxx">
                        </div>
                        <div class="form-group">
                            <label>توضیحات</label>
                            <input type="text" name="description" class="form-control">
                        </div>
                        <div class="form-group">
                            <label>آیکون SVG (Inline)</label>
                            <textarea name="icon" class="form-control" rows="3" dir="ltr"
                                placeholder="<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' ...>...</svg>"
                                style="font-family:monospace;font-size:0.82rem;"></textarea>
                            <p class="text-muted mt-1" style="font-size:0.82rem;">
                                کد کامل SVG را اینجا قرار دهید. پیشنهاد: از سایت‌های
                                <a href="https://lucide.dev/" target="_blank">Lucide</a> یا
                                <a href="https://heroicons.com/" target="_blank">Heroicons</a>
                                استفاده کنید.
                            </p>
                        </div>
                    </div>
                    <button type="submit" name="add_product" class="btn btn-primary">افزودن</button>
                </form>
            </div>

            <div class="card">
                <h3 class="card-title">محصولات موجود</h3>
                <div class="table-wrapper">
                    <table>
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>نام</th>
                                <th>Slug</th>
                                <th>Salt</th>
                                <th>توضیحات</th>
                                <th>عملیات</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($products as $p): ?>
                            <tr>
                                <td><?php echo $p['id']; ?></td>
                                <td><?php echo clean($p['name']); ?></td>
                                <td dir="ltr" style="font-size:0.85rem;"><?php echo clean($p['slug']); ?></td>
                                <td dir="ltr" style="font-size:0.8rem;"><?php echo clean($p['salt']); ?></td>
                                <td><?php echo clean($p['description']); ?></td>
                                <td>
                                    <a href="/admin/products?delete=<?php echo $p['id']; ?>"
                                        class="btn btn-danger btn-sm" data-confirm="آیا از حذف مطمئن هستید؟">حذف</a>
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