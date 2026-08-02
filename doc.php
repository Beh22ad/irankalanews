<?php

/**
 * نمایش مستندات API
 */
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

$posts = db_read('posts.json');
$postMeta = null;
foreach ($posts as $p) {
    if ($p['slug'] === $slug && $p['status'] === 'published') {
        $postMeta = $p;
        break;
    }
}

if (!$postMeta) {
    http_response_code(404);
    $pageTitle = 'یافت نشد';
    require_once __DIR__ . '/includes/header.php';
    echo '<div class="container"><div class="card empty-state"><p>مستندات مورد نظر یافت نشد.</p></div></div>';
    require_once __DIR__ . '/includes/footer.php';
    exit;
}

$docPath = __DIR__ . '/docs/' . $slug . '.html';
if (!file_exists($docPath)) {
    http_response_code(404);
    $pageTitle = 'یافت نشد';
    require_once __DIR__ . '/includes/header.php';
    echo '<div class="container"><div class="card empty-state"><p>محتوای مستندات بارگذاری نشد. فایل HTML وجود ندارد.</p></div></div>';
    require_once __DIR__ . '/includes/footer.php';
    exit;
}

$docContent = file_get_contents($docPath);
$pageTitle = $postMeta['title'];

$publishedPosts = array_filter($posts, fn($p) => $p['status'] === 'published');

require_once __DIR__ . '/includes/header.php';

// Schema مستندات
$docSchema = null;
if ($postMeta) {
    $docSchema = [
        "@context" => "https://schema.org",  // Fixed: added full URL and correct syntax
        "@type" => "SoftwareApplication",
        "@id" => "doc-" . $postMeta['id'],
        "name" => $postMeta['title'],
        "description" => $postMeta['description'],
        "url" => (isset($siteUrl) ? $siteUrl . '/doc?slug=' . $slug : ''),
        "applicationCategory" => "DeveloperApplication",
        "operatingSystem" => "Any",
        "offers" => [
            "@type" => "Offer",
            "price" => 0,
            "priceCurrency" => "IRR",
            "availability" => "https://schema.org/InStock"
        ],
        "breadcrumb" => [  // Fixed: use array syntax instead of {}
            [
                "@type" => "ListItem",
                "position" => 1,
                "name" => "خانه",
                "item" => (isset($siteUrl) ? $siteUrl : '/')
            ],
            [
                "@type" => "ListItem",
                "position" => 2,
                "name" => "محصولات",
                "item" => (isset($siteUrl) ? $siteUrl . '/products/' : '/products/')
            ],
            [
                "@type" => "ListItem",
                "position" => 3,
                "name" => $postMeta['title'],
                "item" => (isset($siteUrl) ? $siteUrl . '/doc?slug=' . $slug : '/doc?slug=' . $slug)
            ]
        ]
    ];
}

?>
<div class="container">
    <div class="admin-layout">
        <aside class="admin-sidebar">
            <h4 style="font-size:0.9rem;font-weight:700;margin-bottom:10px;color:var(--text-muted);">مستندات</h4>
            <?php foreach ($publishedPosts as $pp): ?>
                <a href="/doc?slug=<?php echo $pp['slug']; ?>" class="<?php echo $pp['slug'] === $slug ? 'active' : ''; ?>">
                    <?php echo clean($pp['title']); ?>
                </a>
            <?php endforeach; ?>
        </aside>
        <div class="admin-content">
            <div class="doc-content">
                <?php echo $docContent; ?>
            </div>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>