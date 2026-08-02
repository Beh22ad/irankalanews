<?php
$pageTitle = 'خانه - پورتال API';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/header.php';

$products = db_read('products.json');
$plans = db_read('plans.json');
$posts = db_read('posts.json');

$productsWithPlans = [];
foreach ($products as $p) {
    $pPlans = array_filter($plans, fn($pl) => $pl['product_id'] == $p['id']);
    $productsWithPlans[] = [
        'product' => $p,
        'plans' => array_values($pPlans)
    ];
}

$publishedDocs = array_filter($posts, fn($post) => $post['status'] === 'published');
$isLoggedIn = user_login_check();
?>

<?php
// Schema صفحه اصلی
$homeSchema = [
    "@context" => "https://schema.org",  // Fixed: should be full URL
    "@type" => "WebPage",
    "@id" => "page-home",
    "name" => "ایران کالانیوز - سرویس API قیمت روز کالاها",
    "description" => "ارائه دهنده API رایگان و پریمیوم قیمت آهن و سایر کالاهای بازار ایران",
    "url" => (isset($siteUrl) ? $siteUrl : '/'),
    "breadcrumb" => [  // Fixed: use array syntax
        [
            "@type" => "ListItem",
            "position" => 1,
            "name" => "خانه",
            "item" => (isset($siteUrl) ? $siteUrl : '/')
        ]
    ],
    "faq" => [  // Fixed: use array syntax
        [
            "@type" => "Question",
            "name" => "چطور API بخرید؟",
            "acceptedAnswer" => "از صفحه محصول پلن مورد نظر را انتخاب کرده و فیش پرداخت آپلود کنید. پس از تأیید مدیریت، کلید API در داشبورد نمایش داده می‌شود."
        ],
        [
            "@type" => "Question",
            "name" => "پشتیبانی چگونه کار می‌کند؟",
            "acceptedAnswer" => "از طریق تلگرام و ایمیل پشتیبانی انجام می‌شود."
        ]
    ]
];
?>
<?php if (isset($homeSchema)): ?>
    <script type="application/ld+json">
        <?php echo json_encode($homeSchema, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT); ?>
    </script>
<?php endif; ?>

<section class="hero-section">
    <div class="hero-bg-shapes">
        <div class="shape shape-1"></div>
        <div class="shape shape-2"></div>
        <div class="shape shape-3"></div>
    </div>
    <div class="container hero-content">
        <div class="hero-badge">ابزار حرفه‌ای اتوماسیون</div>
        <h1>دسترسی آنی به <span class="text-accent">قیمت روز بازار</span></h1>
        <p class="hero-desc">
            با اشتراک API‌های ما، قیمت‌های لحظه‌ای محصولات را
            از طریق یک کلید اختصاصی دریافت کنید. بدون پیچیدگی، بدون دردسر.
        </p>
        <div class="hero-actions">
            <?php if ($isLoggedIn): ?>
                <a href="/products" class="btn btn-accent btn-lg">مشاهده پلن‌ها</a>
                <a href="/dashboard" class="btn btn-glass btn-lg">داشبورد من</a>
            <?php else: ?>
                <a href="/register" class="btn btn-accent btn-lg">شروع رایگان</a>
                <a href="/products" class="btn btn-glass btn-lg">مشاهده محصولات</a>
            <?php endif; ?>
        </div>
        <div class="hero-stats">
            <div class="hero-stat">
                <span class="hero-stat-num"><?php echo count($products); ?>+</span>
                <span class="hero-stat-label">API فعال</span>
            </div>
            <div class="hero-stat-divider"></div>
            <div class="hero-stat">
                <span class="hero-stat-num">99.9%</span>
                <span class="hero-stat-label">آپتایم</span>
            </div>
            <div class="hero-stat-divider"></div>
            <div class="hero-stat">
                <span class="hero-stat-num">24/7</span>
                <span class="hero-stat-label">پشتیبانی</span>
            </div>
        </div>
    </div>
</section>

<section class="section">
    <div class="container">
        <div class="section-header">
            <h2>چطوری کار می‌کنه؟</h2>
            <p>در سه قدم ساده اشتراک بگیرید و شروع کنید</p>
        </div>
        <div class="steps-grid">
            <div class="step-card">
                <div class="step-number">1</div>
                <div class="step-icon-wrap"><span class="step-emoji">&#128100;</span></div>
                <h3>ثبت‌نام</h3>
                <p>یک حساب کاربری رایگان بسازید و آدرس وب‌سایت خود را وارد کنید.</p>
            </div>
            <div class="step-connector">&#8592;</div>
            <div class="step-card">
                <div class="step-number">2</div>
                <div class="step-icon-wrap"><span class="step-emoji">&#128179;</span></div>
                <h3>انتخاب و پرداخت</h3>
                <p>API و پلن مورد نظر را انتخاب کنید و فیش واریزی را آپلود کنید.</p>
            </div>
            <div class="step-connector">&#8592;</div>
            <div class="step-card">
                <div class="step-number">3</div>
                <div class="step-icon-wrap"><span class="step-emoji">&#128273;</span></div>
                <h3>دریافت API Key</h3>
                <p>پس از تأیید پرداخت، کلید API اختصاصی شما فعال می‌شود.</p>
            </div>
        </div>
    </div>
</section>

<section class="section section-alt">
    <div class="container">
        <div class="section-header">
            <h2>محصولات و پلن‌ها</h2>
            <p>API مورد نظر خود را انتخاب کنید...</p>
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
                    $hasDocs = false;
                    foreach ($publishedDocs as $doc) {
                        if ($doc['slug'] === $p['slug']) {
                            $hasDocs = true;
                            break;
                        }
                    }
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
                                            <?php if ($isLoggedIn): ?>
                                                <form method="POST" action="/purchase" style="display:inline;">
                                                    <?php echo csrf_field(); ?>
                                                    <input type="hidden" name="product_id" value="<?php echo $p['id']; ?>">
                                                    <input type="hidden" name="plan_id" value="<?php echo $plan['id']; ?>">
                                                    <button type="submit" class="btn btn-primary btn-sm">خرید</button>
                                                </form>
                                            <?php else: ?>
                                                <a href="/login" class="btn btn-outline btn-sm">ورود</a>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                        <?php if ($hasDocs): ?>
                            <div class="pricing-footer">
                                <a href="/doc?slug=<?php echo $p['slug']; ?>" class="btn btn-glass btn-sm" style="width:100%;">
                                    &#128196; مشاهده مستندات
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<section class="section">
    <div class="container">
        <div class="section-header">
            <h2>چرا ما را انتخاب کنید؟</h2>
            <p>مزایای استفاده از سرویس API ما</p>
        </div>
        <div class="features-grid">
            <div class="feature-card-new">
                <div class="fci" style="background:linear-gradient(135deg,#0d9488,#14b8a6);">&#9889;</div>
                <h3>پاسخ سریع</h3>
                <p>زمان پاسخ زیر ۲۰۰ میلی‌ثانیه برای درخواست‌ها</p>
            </div>
            <div class="feature-card-new">
                <div class="fci" style="background:linear-gradient(135deg,#7c3aed,#a78bfa);">&#128274;</div>
                <h3>امنیت بالا</h3>
                <p>هر کاربر کلید HMAC اختصاصی خود را دارد</p>
            </div>
            <div class="feature-card-new">
                <div class="fci" style="background:linear-gradient(135deg,#dc2626,#f87171);">&#128202;</div>
                <h3>داده دقیق</h3>
                <p>اطلاعات از منابع معتبر و به‌روز جمع‌آوری می‌شود</p>
            </div>
            <div class="feature-card-new">
                <div class="fci" style="background:linear-gradient(135deg,#d97706,#fbbf24);">&#128172;</div>
                <h3>پشتیبانی قوی</h3>
                <p>سؤالات و مشکلات از طریق تلگرام و روبیکا پاسخ داده می‌شود</p>
            </div>
            <div class="feature-card-new">
                <div class="fci" style="background:linear-gradient(135deg,#2563eb,#60a5fa);">&#128640;</div>
                <h3>شروع سریع</h3>
                <p>با یک درخواست HTTP ساده شروع به استفاده کنید</p>
            </div>
            <div class="feature-card-new">
                <div class="fci" style="background:linear-gradient(135deg,#059669,#34d399);">&#9989;</div>
                <h3>آپتایم بالا</h3>
                <p>تضمین ۹۹.۹٪ آپتایم با سرورهای قدرتمند</p>
            </div>
        </div>
    </div>
</section>

<?php if (!empty($publishedDocs)): ?>
    <section class="section section-alt">
        <div class="container">
            <div class="section-header">
                <h2>مستندات API</h2>
                <p>راهنمای استفاده از هر API</p>
            </div>
            <div class="docs-grid">
                <?php foreach ($publishedDocs as $doc): ?>
                    <a href="/doc?slug=<?php echo $doc['slug']; ?>" class="doc-link-card">
                        <div class="doc-link-icon">&#128196;</div>
                        <div class="doc-link-info">
                            <h4><?php echo clean($doc['title']); ?></h4>
                            <p><?php echo clean($doc['description']); ?></p>
                        </div>
                        <div class="doc-link-arrow">&#8592;</div>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
<?php endif; ?>

<section class="section cta-section">
    <div class="container" style="text-align:center;">
        <h2>آماده‌اید شروع کنید؟</h2>
        <p class="cta-desc">
            همین الان ثبت‌نام کنید و در کمتر از ۵ دقیقه اولین درخواست API خود را ارسال کنید.
        </p>
        <?php if ($isLoggedIn): ?>
            <a href="/products" class="btn btn-accent btn-lg">مشاهده پلن‌ها</a>
        <?php else: ?>
            <a href="/register" class="btn btn-accent btn-lg">ثبت‌نام رایگان</a>
        <?php endif; ?>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>