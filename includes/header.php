<?php
if (session_status() === PHP_SESSION_NONE) session_start();
$isLoggedIn = isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'user';
$isAdmin = isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'admin';
$userName = $_SESSION['user_name'] ?? '';
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? $pageTitle : "ایران‌کالا‌نیوز"; ?></title>
    <link rel="stylesheet" href="/assets/fonts/stylesheet.css">
    <link rel="stylesheet" href="/assets/css/style.css">
    <script type="application/ld+json">
        {
            "@context": "https://schema.org",
            "@type": "WebSite",
            "@id": "website",
            "name": "ایران کالانیوز",
            "url": "<?php echo $siteUrl ?? '/'; ?>",
            "description": "خرید API کالاهای بازار ایران",
            "publisher": {
                "@type": "Organization",
                "name": "ایران کالانیوز",
                "url": "<?php echo $siteUrl ?? '/'; ?>",
                "contactPoint": {
                    "@type": "ContactPoint",
                    "telephone": "<?php echo $settings['contact_phone'] ?? ''; ?>",
                    "contactType": "customer service"
                }
            },
            "potentialAction": {
                "@type": "SearchAction",
                "target": "<?php echo $siteUrl ?? '/products'; ?>",
                "query-input": "required name=q"
            }
        }
    </script>
</head>

<body>
    <header class="site-header">
        <div class="container header-inner">
            <a href="/" class="logo">ایران‌کالا‌نیوز</a>
            <button class="hamburger" id="hamburgerBtn" aria-label="منو">
                <span></span><span></span><span></span>
            </button>
            <nav class="main-nav" id="mainNav">
                <?php if ($isAdmin): ?>
                    <a href="/admin">پنل مدیریت</a>
                    <a href="/admin/logout">خروج</a>
                <?php elseif ($isLoggedIn): ?>
                    <a href="/dashboard" class="<?php echo $currentPage === 'dashboard.php' ? 'active' : ''; ?>">داشبورد</a>
                    <a href="/orders" class="<?php echo $currentPage === 'orders.php' ? 'active' : ''; ?>">سفارش‌های من</a>
                    <a href="/products" class="<?php echo $currentPage === 'products.php' ? 'active' : ''; ?>">محصولات</a>
                    <a href="/logout">خروج</a>
                <?php else: ?>
                    <a href="/products" class="<?php echo $currentPage === 'products.php' ? 'active' : ''; ?>">محصولات</a>
                    <a href="/doc?slug=iron-api">مستندات API</a>
                    <a href="/pages/contact-us.php"> تماس باما</a>
                    <a href="/login" class="<?php echo $currentPage === 'login.php' ? 'active' : ''; ?>">حساب کاربری</a>
                <?php endif; ?>
            </nav>
        </div>
    </header>
    <main class="site-main">