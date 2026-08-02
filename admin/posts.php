<?php
session_start();
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/jalali.php';

if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    header('Location: /admin/login');
    exit;
}

$error = '';
$success = '';
$editPost = null;

if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $editPost = db_find_by_id('posts.json', (int)$_GET['edit']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        $error = 'درخواست نامعتبر.';
    } else {
        $title = clean($_POST['title'] ?? '');
        $slug = clean($_POST['slug'] ?? '');
        $description = clean($_POST['description'] ?? '');
        $status = ($_POST['status'] ?? 'draft') === 'published' ? 'published' : 'draft';

        if (empty($title) || empty($slug)) {
            $error = 'عنوان و slug الزامی هستند.';
        } elseif (!is_valid_slug($slug)) {
            $error = 'Slug فقط می‌تواند شامل حروف انگلیسی کوچک، عدد و خط تیره باشد.';
        } else {
            $posts = db_read('posts.json');

            if (isset($_POST['edit_id']) && is_numeric($_POST['edit_id'])) {
                $editId = (int)$_POST['edit_id'];
                $slugExists = false;
                foreach ($posts as $p) {
                    if ($p['slug'] === $slug && $p['id'] != $editId) {
                        $slugExists = true;
                        break;
                    }
                }
                if ($slugExists) {
                    $error = 'این slug قبلاً استفاده شده است.';
                } else {
                    db_update_by_id('posts.json', $editId, [
                        'title' => $title,
                        'slug' => $slug,
                        'description' => $description,
                        'status' => $status,
                        'updated_at' => date('Y-m-d H:i:s')
                    ]);
                    $success = 'مستندات به‌روزرسانی شد.';
                    $editPost = null;
                }
            } else {
                $slugExists = false;
                foreach ($posts as $p) {
                    if ($p['slug'] === $slug) {
                        $slugExists = true;
                        break;
                    }
                }
                if ($slugExists) {
                    $error = 'این slug قبلاً استفاده شده است.';
                } else {
                    $posts[] = [
                        'id' => db_next_id('posts.json'),
                        'title' => $title,
                        'slug' => $slug,
                        'description' => $description,
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s'),
                        'status' => $status
                    ];
                    db_write('posts.json', $posts);
                    $success = 'مستندات اضافه شد. فایل HTML را در پوشه /docs/ آپلود کنید.';
                }
            }
        }
    }
}

if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    db_delete_by_id('posts.json', (int)$_GET['delete']);
    $success = 'مستندات حذف شد.';
}

$posts = db_read('posts.json');
usort($posts, fn($a, $b) => $b['id'] - $a['id']);
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>مدیریت مستندات</title>
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
                <a href="/admin/posts" class="active">مستندات</a>
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
                <h3 class="card-title"><?php echo $editPost ? 'ویرایش مستندات' : 'افزودن مستندات جدید'; ?></h3>
                <p class="text-muted" style="margin-bottom:16px;font-size:0.88rem;">
                    توجه: محتوای HTML مستندات باید به صورت دستی در پوشه <code>/docs/</code> با نام
                    <code>{slug}.html</code> آپلود شود.
                </p>
                <form method="POST">
                    <?php echo csrf_field(); ?>
                    <?php if ($editPost): ?>
                        <input type="hidden" name="edit_id" value="<?php echo $editPost['id']; ?>">
                    <?php endif; ?>
                    <div class="form-row">
                        <div class="form-group">
                            <label>عنوان</label>
                            <input type="text" name="title" class="form-control" required
                                value="<?php echo $editPost ? clean($editPost['title']) : ''; ?>">
                        </div>
                        <div class="form-group">
                            <label>Slug (نام فایل HTML)</label>
                            <input type="text" name="slug" class="form-control" required dir="ltr"
                                value="<?php echo $editPost ? clean($editPost['slug']) : ''; ?>" placeholder="iron-api">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>توضیحات</label>
                            <input type="text" name="description" class="form-control"
                                value="<?php echo $editPost ? clean($editPost['description']) : ''; ?>">
                        </div>
                        <div class="form-group">
                            <label>وضعیت</label>
                            <select name="status" class="form-control">
                                <option value="published"
                                    <?php echo ($editPost && $editPost['status'] === 'published') || !$editPost ? 'selected' : ''; ?>>
                                    منتشر شده</option>
                                <option value="draft"
                                    <?php echo $editPost && $editPost['status'] === 'draft' ? 'selected' : ''; ?>>پیش‌نویس
                                </option>
                            </select>
                        </div>
                    </div>
                    <div class="gap-2">
                        <button type="submit"
                            class="btn btn-primary"><?php echo $editPost ? 'به‌روزرسانی' : 'افزودن'; ?></button>
                        <?php if ($editPost): ?>
                            <a href="/admin/posts" class="btn btn-outline">انصراف</a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>

            <div class="card">
                <h3 class="card-title">مستندات موجود</h3>
                <div class="table-wrapper">
                    <table>
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>عنوان</th>
                                <th>Slug</th>
                                <th>وضعیت</th>
                                <th>فایل HTML</th>
                                <th>تاریخ</th>
                                <th>عملیات</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($posts as $p):
                                $hasFile = file_exists(__DIR__ . '/../docs/' . $p['slug'] . '.html');
                            ?>
                                <tr>
                                    <td><?php echo $p['id']; ?></td>
                                    <td><?php echo clean($p['title']); ?></td>
                                    <td dir="ltr" style="font-size:0.85rem;"><?php echo clean($p['slug']); ?></td>
                                    <td>
                                        <?php if ($p['status'] === 'published'): ?>
                                            <span class="badge badge-approved">منتشر</span>
                                        <?php else: ?>
                                            <span class="badge badge-pending">پیش‌نویس</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($hasFile): ?>
                                            <span style="color:var(--success);">&#10003; وجود دارد</span>
                                        <?php else: ?>
                                            <span style="color:var(--danger);">&#10007; وجود ندارد</span>
                                        <?php endif; ?>
                                    </td>
                                    <td style="font-size:0.82rem;"><?php echo to_jalali_datetime($p['updated_at']); ?></td>
                                    <td>
                                        <a href="/admin/posts?edit=<?php echo $p['id']; ?>"
                                            class="btn btn-outline btn-sm">ویرایش</a>
                                        <a href="/doc?slug=<?php echo $p['slug']; ?>" class="btn btn-primary btn-sm"
                                            target="_blank">مشاهده</a>
                                        <a href="/admin/posts?delete=<?php echo $p['id']; ?>" class="btn btn-danger btn-sm"
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