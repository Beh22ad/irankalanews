<?php

// Display all errors
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/includes/auth.php';

$slug = clean($_GET['slug'] ?? '');

if (!is_valid_slug($slug)) {
    http_response_code(404);
    $pageTitle = 'خطا';
    require_once __DIR__ . '/includes/header.php';
    echo '<div class="container"><div class="card empty-state"><p>صفحه نامعتبر است.</p></div></div>';
    require_once __DIR__ . '/includes/footer.php';
    exit;
}

$products = db_read('products.json');
$product = null;
foreach ($products as $p) {
    if ($p['slug'] === $slug) {
        $product = $p;
        break;
    }
}

if (!$product) {
    http_response_code(404);
    $pageTitle = 'یافت نشد';
    require_once __DIR__ . '/includes/header.php';
    echo '<div class="container"><div class="card empty-state"><p>محصول مورد نظر یافت نشد.</p></div></div>';
    require_once __DIR__ . '/includes/footer.php';
    exit;
}

// ========== ADD THESE 3 LINES HERE (BEFORE Schema Product) ==========
$plans = db_read('plans.json');
$productPlans = array_filter($plans, fn($pl) => $pl['product_id'] == $product['id']);
$siteUrl = 'https://irankalanews.com'; // Change to your actual site URL
// ====================================================================

$pageTitle = $product['name'];
require_once __DIR__ . '/includes/header.php';

// Schema Product
$productSchema = null;
if ($product) {
    $pPrice = 0;
    foreach ($productPlans as $pl) {
        if ($pl['price'] > $pPrice) $pPrice = $pPrice;
    }
    $pLow = 0;
    foreach ($productPlans as $pl) {
        if ($pPrice === 0 || $pl['price'] < $pLow) $pLow = $pl['price'];
    }
    $pHigh = 0;
    foreach ($productPlans as $pl) {
        if ($pl['price'] > $pHigh) $pHigh = $pl['price'];
    }

    $priceSpecs = [];
    foreach ($productPlans as $pl) {
        $priceSpecs[] = [
            "@type" => "Offer",  // Fixed: use => instead of :
            "name" => $pl['name'],
            "price" => $pl['price'],
            "priceCurrency" => "IRR",
            "availability" => "https://schema.org/InStock"
        ];
    }

    $productSchema = [
        "@context" => "https://schema.org",
        "@type" => "Product",
        "@id" => "product-" . $product['id'],
        "name" => $product['name'],
        "description" => $product['description'],
        "url" => $siteUrl . '/product/' . $product['slug'],
        "brand" => [  // Fixed: use array syntax, not {}
            "@type" => "Brand",
            "name" => "ایران کالانیوز",
            "url" => $siteUrl
        ],
        "offers" => [
            "@type" => "AggregateOffer",
            "lowPrice" => $pLow,
            "highPrice" => $pHigh,
            "priceCurrency" => "IRR",
            "offerCount" => count($productPlans),
            "priceSpecification" => $priceSpecs
        ],
        "faq" => [  // Fixed: use array syntax
            [
                "@type" => "Question",
                "name" => "بروز رسانی API چگونه است؟",
                "acceptedAnswer" => "بروز رسانی قیمت ها به صورت خودکار و روزانه انجام میشود."
            ],
            [
                "@type" => "Question",
                "name" => "API Key کی دریافت می‌کنم؟",
                "acceptedAnswer" => "پس از تأیید پرداخت توسط مدیریت، API Key در داشبورد نمایش داده می‌شود."
            ]
        ]
    ];
}

// ========== REMOVE THESE 2 LINES FROM HERE (they're now moved up) ==========
// $plans = db_read('plans.json');
// $productPlans = array_filter($plans, fn($pl) => $pl['product_id'] == $product['id']);
// ============================================================================

$isLoggedIn = user_login_check();

// بررسی وجود اشتراک فعال برای این محصول
$userHasActive = false;
if ($isLoggedIn) {
    $user = current_user();
    $allOrders = db_read('orders.json');
    foreach ($allOrders as $o) {
        if (
            $o['user_id'] == $user['id']
            && $o['product_id'] == $product['id']
            && $o['status'] === 'approved'
            && is_membership_active($o)
        ) {
            $userHasActive = true;
            break;
        }
    }
}

// بررسی وجود مستندات
$posts = db_read('posts.json');
$hasDoc = false;
foreach ($posts as $post) {
    if ($post['slug'] === $slug && $post['status'] === 'published') {
        $hasDoc = true;
        break;
    }
}

// بارگذاری فایل HTML محصول از پوشه products
$htmlPath = __DIR__ . '/product-pages/' . $slug . '.html';
$hasHtml = file_exists($htmlPath);
$htmlContent = $hasHtml ? file_get_contents($htmlPath) : null;
?>

<?php if ($productSchema): ?>
    <script type="application/ld+json">
        <?php echo json_encode($productSchema, JSON_UNESCAPED_UNICODE); ?>
    </script>
<?php endif; ?>

<section class="section">
    <div class="container">

        <!-- بreadcrumb -->
        <div class="single-breadcrumb">
            <a href="/">خانه</a>
            <span class="sep">/</span>
            <a href="/products">محصولات</a>
            <span class="sep">/</span>
            <span><?php echo clean($product['name']); ?></span>
        </div>

        <div class="single-layout">

            <!-- ستون اصلی -->
            <div class="single-main">

                <!-- هدر محصول -->
                <div class="single-header">
                    <div class="single-icon"><?php echo $product['icon'] ?? '&#128268;'; ?></div>
                    <div class="single-title-wrap">
                        <h1><?php echo clean($product['name']); ?></h1>
                        <p class="single-desc"><?php echo clean($product['description']); ?></p>
                    </div>
                </div>

                <!-- محتوای HTML محصول -->
                <?php if ($hasHtml): ?>
                    <div class="single-html-content">
                        <?php echo $htmlContent; ?>
                    </div>
                <?php endif; ?>

                <!-- جدول پلن‌ها -->
                <div class="card" style="margin-top:24px;">
                    <h3 class="card-title">انتخاب پلن و خرید اشتراک</h3>
                    <?php if (empty($productPlans)): ?>
                        <p class="text-muted" style="text-align:center;padding:20px 0;">هنوز پلنی برای این محصول تعریف نشده
                            است.</p>
                    <?php else: ?>
                        <div class="single-plans-table">
                            <div class="plan-table-head">
                                <div class="pth-item">مدت اشتراک</div>
                                <div class="pth-item">قیمت</div>
                                <div class="pth-item">عملیات</div>
                            </div>
                            <?php foreach ($productPlans as $plan): ?>
                                <div class="plan-table-row">
                                    <div class="ptr-info">
                                        <span class="ptr-name"><?php echo clean($plan['name']); ?></span>
                                        <span class="ptr-dur"><?php echo $plan['duration_months']; ?> ماه</span>
                                    </div>
                                    <div class="ptr-price"><?php echo format_price($plan['price']); ?></div>
                                    <div class="ptr-action">
                                        <?php if ($userHasActive): ?>
                                            <span class="badge badge-approved">فعال</span>
                                        <?php elseif ($isLoggedIn): ?>
                                            <form method="POST" action="/purchase">
                                                <?php echo csrf_field(); ?>
                                                <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                                <input type="hidden" name="plan_id" value="<?php echo $plan['id']; ?>">
                                                <button type="submit" class="btn btn-accent">خرید</button>
                                            </form>
                                        <?php else: ?>
                                            <a href="/login" class="btn btn-outline">ابتدا وارد شوید</a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

            </div>

            <!-- سایدبار -->
            <aside class="single-sidebar">
                <div class="card single-sidebar-card">
                    <h4 style="font-weight:700;margin-bottom:16px;">خلاصه محصول</h4>
                    <ul class="single-features">
                        <li><span class="sf-icon">&#9889;</span><span>دسترسی آنی به قیمت روز</span></li>
                        <li><span class="sf-icon">&#128273;</span><span>دارای نسخه رایگان و پریمیم</span></li>
                        <li><span class="sf-icon">&#128172;</span><span>پشتیبانی دائمی</span></li>
                        <li><span class="sf-icon">&#9989;</span><span>آپتایم بالا</span></li>
                    </ul>
                    <?php if ($hasDoc): ?>
                        <a href="/doc?slug=<?php echo $slug; ?>" class="btn btn-outline btn-block mt-2">مستندات فنی API</a>
                    <?php endif; ?>
                    <?php if ($isLoggedIn): ?>
                        <a href="/dashboard" class="btn btn-primary btn-block mt-2">داشبورد من</a>
                    <?php else: ?>
                        <a href="/register" class="btn btn-accent btn-block mt-2">ثبت‌نام و خرید</a>
                    <?php endif; ?>
                </div>
            </aside>

        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>