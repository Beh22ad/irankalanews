<?php
session_start();
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/functions.php';

if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    header('Location: /admin/login');
    exit;
}

$generated = false;
$error = '';

if (isset($_GET['generate'])) {
    $products = db_read('products.json');
    $posts = db_read('posts.php');
    $settings = db_read_settings();
    $siteUrl = rtrim($settings['site_url'] ?? 'https://localhost', '/');

    if (empty($siteUrl)) {
        $error = 'ابتدا آدرس سایت را در تنظیمات وارد کنید.';
    } else {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

        // صفحه اصلی
        $xml .= ' <url>' . "\n";
        $xml .= ' <loc>' . $siteUrl . '</loc>' . "\n";
        $xml .= ' <lastmod>' . date('Y-m-d') . '</lastmod>' . "\n";
        $xml .= ' <changefreq>monthly</changefreq>' . "\n";
        $xml .= ' <priority>1.0</priority>' . "\n";
        $xml .= ' </url>' . "\n";

        // صفحه محصولات
        $xml .= ' <url>' . "\n";
        $xml .= ' <loc>' . $siteUrl . '/products/</loc>' . "\n";
        $xml .= ' <lastmod>' . date('Y-m-d') . '</lastmod>' . "\n";
        $xml .= ' <changefreq>monthly</changefreq>' . "\n";
        $xml .= ' <priority>0.9</priority>' . "\n";
        $xml .= ' </url>' . "\n";

        // صفحه هر محصول
        foreach ($products as $p) {
            $xml .= ' <url>' . "\n";
            $xml .= ' <loc>' . $siteUrl . '/product/' . $p['slug'] . '</loc>' . "\n";
            $xml .= ' <lastmod>' . date('Y-m-d') . '</lastmod>' . "\n";
            $xml .= ' <changefreq>monthly</changefreq>' . "\n";
            $xml .= ' <priority>0.8</priority>' . "\n";
            $xml .= ' </url>' . "\n";
        }

        // صفحه هر مستندات منتشر شده
        foreach ($posts as $post) {
            if ($post['status'] !== 'published') continue;
            $xml .= ' <url>' . "\n";
            $xml .= ' <loc>' . $siteUrl . '/doc?slug=' . $url = $post['slug'] . '</loc>' . "\n";
            $xml .= ' <lastmod>' . date('Y-m-d', strtotime($post['updated_at'])) . '</lastmod>' . "\n";
            $xml .= ' <changefreq>monthly</changefreq>' . "\n";
            $xml .= ' <priority>0.7</priority>' . "\n";
            $xml .= ' </url>' . "\n";
        }

        $xml .= '</urlset>' . "\n";

        $savePath = __DIR__ . '/../sitemap.xml';
        $written = file_put_contents($savePath, $xml);

        if ($written !== false) {
            $generated = true;
        } else {
            $error = 'خطا در ذخیره فایل. پوشه ریشه قابل نوشتن نیست.';
        }
    }
}

// حذف فایل sitemap
if (isset($_GET['delete'])) {
    $savePath = __DIR__ . '/../sitemap.xml';
    if (file_exists($savePath)) {
        unlink($savePath);
        $generated = true;
        $deleted = true;
    }
}

$hasSitemap = file_exists(__DIR__ . '/../sitemap.xml');
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تنظیم Sitemap</title>
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
                <a href="/admin/plans">پلن‌ها</a>
                <a href="/admin/posts">مستندات</a>
                <a href="/admin/settings" class="active">Sitemap</a>
                <a href="/">سایت</a>
                <a href="/admin/logout">خروج</a>
            </nav>
        </div>
    </header>
    <main class="site-main">
        <div class="container">

            <?php if ($error): ?>
                <div class="flash flash-error"><?php echo $error; ?></div>
            <?php endif; ?>

            <?php if (isset($deleted)): ?>
                <div class="flash flash-success">فایل sitemap.xml حذف شد.</div>
            <?php endif; ?>

            <?php if ($generated): ?>
                <div class="flash flash-success">فایل sitemap.xml با موفقیت تولید شد.</div>
            <?php endif; ?>

            <div class="card">
                <h3 class="توضیحات</h3>
        <p class=" text-muted" style="margin-bottom:20px;">
                    با کلیک روی دکمه زیر، فایل <code>sitemap.xml</code> در ریشه سایت تولید و ذخیره می‌شود. این فایل را
                    می‌توانید در Google Search Console ثبت کنید.
                    </p>
                    <div style="display:flex;gap:10px;flex-wrap:wrap;">
                        <a href="/admin/sitemap?generate=1" class="btn btn-accent">تولید sitemap.xml</a>
                        <?php if ($hasSitemap): ?>
                            <a href="/admin/sitemap" class="btn btn-outline">مشاهده فایل فعلی</a>
                            <a href="/admin/sitemap?delete=1" class="btn btn-danger btn-sm"
                                data-confirm="آیا از حذف sitemap مطمئن هستید؟">حذف فایل</a>
                        <?php else: ?>
                            <span class="text-muted" style="padding:10px 0;font-size:0.9rem;">هنوز فایلی وجود ندارد.</span>
                        <?php endif; ?>
                    </div>
            </div>

            <?php if ($hasSitemap): ?>
                <div class="card">
                    <h3 class="محتوای فعلی sitemap.xml</h3>
        <div style=" margin-top:12px;">
                        <pre
                            style="background:#1e293b;color:#e2e8f0;padding:16px 20px;border-radius:8px;font-size:0.82rem;direction:ltr;text-align:left;overflow-x:auto;line-height:1.6;max-height:400px;"><?php
                                                                                                                                                                                                            echo clean(file_get_contents(__DIR__ . '/../sitemap.xml'));
                                                                                                                                                                                                            ?></pre>
                </div>
        </div>
    <?php endif; ?>

    </div>
    </main>
    <script src="/assets/js/app.js"></script>
</body>

</html>