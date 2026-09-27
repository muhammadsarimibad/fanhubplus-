<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/includes/guard.php';

$pageTitle = 'Content';
$activeAdminNav = 'content';

// Handle delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    $id = (int) ($_POST['content_id'] ?? 0);
    if ($id) {
        $pdo->prepare("DELETE FROM content WHERE content_id = ?")->execute([$id]);
    }
    redirect('/fanhub/admin/content.php?msg=deleted');
}

$categories = get_all_categories($pdo);

$categorySlug = $_GET['category'] ?? '';
$type = $_GET['type'] ?? '';
$status = $_GET['status'] ?? '';
$search = trim($_GET['q'] ?? '');

$where = [];
$params = [];
if ($categorySlug) { $where[] = 'categories.slug = ?'; $params[] = $categorySlug; }
if ($type) { $where[] = 'content.type = ?'; $params[] = $type; }
if ($status) { $where[] = 'content.status = ?'; $params[] = $status; }
if ($search) { $where[] = 'content.title LIKE ?'; $params[] = "%$search%"; }
$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$stmt = $pdo->prepare("
    SELECT content.*, categories.name AS category_name, categories.slug AS category_slug
    FROM content
    JOIN categories ON categories.category_id = content.category_id
    $whereSql
    ORDER BY content.content_id DESC
    LIMIT 200
");
$stmt->execute($params);
$items = $stmt->fetchAll();

require_once __DIR__ . '/includes/layout_top.php';
?>

<div class="admin-topbar">
    <h1>Content Library</h1>
    <a href="/fanhub/admin/content-form.php" class="btn btn-gradient btn-sm">+ Add Content</a>
</div>

<?php if (($_GET['msg'] ?? '') === 'deleted'): ?>
    <div class="form-alert success" style="max-width:none;">Content deleted.</div>
<?php elseif (($_GET['msg'] ?? '') === 'saved'): ?>
    <div class="form-alert success" style="max-width:none;">Content saved successfully.</div>
<?php endif; ?>

<form method="GET" action="/fanhub/admin/content.php" class="filter-bar glass" style="padding:18px 20px;">
    <div class="form-group">
        <label>Search</label>
        <input type="text" name="q" value="<?= h($search) ?>" placeholder="Search by title...">
    </div>
    <div class="form-group">
        <label>Category</label>
        <select name="category">
            <option value="">All</option>
            <?php foreach ($categories as $cat): ?>
                <option value="<?= h($cat['slug']) ?>" <?= $categorySlug === $cat['slug'] ? 'selected' : '' ?>><?= h($cat['name']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="form-group">
        <label>Type</label>
        <select name="type">
            <option value="">All</option>
            <?php foreach (['article','video','audio','image'] as $t): ?>
                <option value="<?= $t ?>" <?= $type === $t ? 'selected' : '' ?>><?= ucfirst($t) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="form-group">
        <label>Status</label>
        <select name="status">
            <option value="">All</option>
            <?php foreach (['approved','pending','rejected'] as $s): ?>
                <option value="<?= $s ?>" <?= $status === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <button type="submit" class="btn btn-ghost btn-sm">Filter</button>
    <a href="/fanhub/admin/content.php" class="btn btn-ghost btn-sm">Reset</a>
</form>

<div class="glass admin-table-wrap" style="padding:8px 20px;">
    <table class="admin-table">
        <thead>
            <tr>
                <th></th><th>Title</th><th>Category</th><th>Type</th><th>Tag</th><th>Popularity</th><th>Status</th><th>Added</th><th>Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($items as $item): ?>
            <tr>
                <td data-label="Image"><img class="thumb-sm" src="<?= h($item['image_url'] ?: 'https://picsum.photos/seed/' . $item['content_id'] . '/100/100') ?>" alt=""></td>
                <td class="wrap" data-label="Title"><?= h($item['title']) ?></td>
                <td data-label="Category"><?= h($item['category_name']) ?></td>
                <td data-label="Type"><?= h($item['type']) ?></td>
                <td data-label="Tag"><?= h($item['tag'] ?: '—') ?></td>
                <td data-label="Popularity"><?= (int) $item['popularity_score'] ?></td>
                <td data-label="Status"><span class="badge-status <?= $item['status'] ?>"><?= h($item['status']) ?></span></td>
                <td data-label="Added"><?= time_ago($item['created_at']) ?></td>
                <td data-label="Actions">
                    <div class="table-actions">
                        <a href="/fanhub/admin/content-form.php?id=<?= $item['content_id'] ?>" class="btn btn-ghost">Edit</a>
                        <form method="POST" action="/fanhub/admin/content.php" onsubmit="return confirm('Delete this item? This cannot be undone.');">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="content_id" value="<?= $item['content_id'] ?>">
                            <button type="submit" class="btn btn-ghost" style="color:#ff8fc4;">Delete</button>
                        </form>
                    </div>
                </td>
            </tr>
        <?php endforeach; ?>
        <?php if (!$items): ?>
            <tr><td colspan="9"><p class="empty-note">No content matches these filters.</p></td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>

<?php require_once __DIR__ . '/includes/layout_bottom.php'; ?>
