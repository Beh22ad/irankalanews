<?php
$pageTitle = 'محصولات';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/header.php';

$products = db_read('products.json');
$plans = db_read('plans.json');
$posts = db_read('posts.json');

$postsBySlug = [];
foreach ($posts as $post) {
    if ($post['status'] === 'published') {
        $postsBySlug[$post['slug']] = $post;
    }
}

$isLoggedIn = user_login_check();

$productsWithPlans = [];
foreach ($products as $p) {
    $pPlans = array_filter($plans, fn($pl) => $pl['product_id'] == $p['id']);
    $productsWithPlans[] = [
        'product' => $p,
        'plans' => array_values($pPlans)
    ];
}
?>

<?php
// Schema صفحه محصولات
$productsList = [];
foreach ($productsWithPlans as $item) {
    $p = $item['product'];
    $pLow = PHP_INT_MAX;
    $pHigh = 0;
    foreach ($item['plans'] as $pl) {
        if ($pl['price'] < $pLow) $pLow = $pl['price'];
        if ($pl['price'] > $pHigh) $pHigh = $pl['price'];  // Fixed: was $p['price'] instead of $pl['price']
    }
    $productsList[] = [
        "@type" => "ListItem",
        "position" => count($productsList) + 1,
        "name" => $p['name'],
        "item" => (isset($siteUrl) ? $siteUrl . '/product/' . $p['slug'] : '/product/' . $p['slug']),  // Fixed: missing closing parenthesis
        "offer" => [
            "@type" => "Offer",
            "lowPrice" => $pLow,
            "highPrice" => $pHigh,
            "priceCurrency" => "IRR"
        ]
    ];
}

$productsSchema = [
    "@context" => "https://schema.org",  // Fixed: should be full URL
    "@type" => "ItemList",  // Fixed: was incorrectly mixing with breadcrumb
    "itemListElement" => $productsList  // Fixed: use itemListElement for ListItems
];
?>
<?php echo isset($productsSchema) ? '<script type="application/ld+json">' . json_encode($productsSchema, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . '</script>' : ''; ?>

<section class="section">
    <div class="container">
        <div class="section-header">
            <h2>محصولات و پلن‌ها</h2>
            <p>API مورد نظر خود را انتخاب کنید</p>
        </div>

        <?php if (empty($productsWithPlans)): ?>
            <div class="empty-state card">
                <p>محصولی موجود نیست.</p>
            </div>
        <?php else: ?>
            <div class="pricing-grid">
                <?php foreach ($productsWithPlans as $item):
                    $p = $item['product'];
                    $pPlans = $item['plans'];
                ?>
                    <div class="pricing-card">
                        <div class="pricing-header">
                            <div class="pricing-icon"><?php echo $p['icon'] ?? '&#128268;'; ?></div>
                            <h3><?php echo clean($p['name']); ?></h3>
                            <p><?php echo clean($p['description']); ?></p>
                        </div>
                        <div class="pricing-body">
                            <?php if (empty($pPlans)): ?>
                                <p class="text-muted" style="text-align:center;padding:16px 0;">پلنی تعریف نشده</p>
                            <?php else: ?>
                                <?php foreach ($pPlans as $plan): ?>
                                    <div class="pricing-plan-row">
                                        <div class="pricing-plan-info">
                                            <span class="pricing-plan-name"><?php echo clean($plan['name']); ?></span>
                                            <span class="pricing-plan-duration"><?php echo $plan['duration_months']; ?> ماه</span>
                                        </div>
                                        <div class="pricing-plan-action">
                                            <span class="pricing-plan-price"><?php echo format_price($plan['price']); ?></span>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                        <div class="pricing-footer">
                            <a href="/product/<?php echo $p['slug']; ?>" class="btn btn-accent btn-sm" style="width:100%;">
                                مشاهده جزئیات و خرید
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>
<?php require_once __DIR__ . '/includes/footer.php'; ?>