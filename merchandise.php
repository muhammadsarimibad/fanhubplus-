<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';
if (session_status() === PHP_SESSION_NONE) session_start();

$pageTitle = 'Merchandise';
$activeNav = 'merchandise';

$categories = get_all_categories($pdo);
$categorySlug = $_GET['category'] ?? '';

$where = [];
$params = [];
if ($categorySlug !== '') { $where[] = 'categories.slug = ?'; $params[] = $categorySlug; }
$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$stmt = $pdo->prepare("
    SELECT merchandise_items.*, categories.name AS category_name, categories.slug AS category_slug, categories.icon AS category_icon
    FROM merchandise_items
    JOIN categories ON categories.category_id = merchandise_items.category_id
    $whereSql
    ORDER BY merchandise_items.item_id ASC
");
$stmt->execute($params);
$items = $stmt->fetchAll();

$bookmarkedIds = get_user_bookmarked_ids($pdo, current_user_id(), 'merchandise');

require_once __DIR__ . '/includes/header.php';
?>
<section class="section" style="padding-top:150px;">
    <div class="container">
        <div class="section-head reveal">
            <div>
                <span class="section-tag">Fan Shop</span>
                <h2>Merchandise</h2>
                <p>Collectibles, apparel and limited-edition items across every fandom.</p>
            </div>
        </div>

        <form method="GET" action="/fanhub/merchandise.php" class="filter-bar glass reveal in-view" style="padding:18px 20px;">
            <div class="form-group">
                <label>Category</label>
                <select name="category" onchange="this.form.submit()">
                    <option value="">All Categories</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= h($cat['slug']) ?>" <?= $categorySlug === $cat['slug'] ? 'selected' : '' ?>><?= $cat['icon'] ?> <?= h($cat['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php if ($categorySlug !== ''): ?><a href="/fanhub/merchandise.php" class="btn btn-ghost btn-sm">Reset</a><?php endif; ?>
        </form>

        <?php if (!$items): ?>
            <p class="empty-note reveal">No merchandise in this category yet.</p>
        <?php else: ?>
        <div class="category-grid reveal-stagger" style="grid-template-columns: repeat(4, 1fr);">
            <?php foreach ($items as $i => $item): ?>
                <a href="/fanhub/merchandise-item.php?id=<?= $item['item_id'] ?>" class="content-card glass reveal" style="--i:<?= $i % 8 ?>;">
                    <div class="thumb" style="background-image:url('<?= h($item['image_url'] ?: 'https://picsum.photos/seed/' . $item['item_id'] . '/400/300') ?>');">
                        <span class="badge"><?= $item['category_icon'] ?> <?= h($item['category_name']) ?></span>
                        <button class="bookmark-btn <?= in_array($item['item_id'], $bookmarkedIds) ? 'active' : '' ?>" data-content-id="<?= $item['item_id'] ?>" data-item-type="merchandise" title="Bookmark"><?= in_array($item['item_id'], $bookmarkedIds) ? '♥' : '♡' ?></button>
                    </div>
                    <div class="body">
                        <h4><?= h($item['name']) ?></h4>
                        <div class="meta">
                            <span><?= h($item['tag']) ?></span>
                            <?php if ($item['is_upcoming']): ?><span class="pop">Pre-Order</span><?php endif; ?>
                        </div>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</section>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
