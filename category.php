<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';

$slug = $_GET['slug'] ?? '';
$category = get_category_by_slug($pdo, $slug);

if (!$category) {
    header('Location: /fanhub/index.php');
    exit;
}

$pageTitle = $category['name'];
$activeNav = '';

if (session_status() === PHP_SESSION_NONE) session_start();
$bookmarkedIds = get_user_bookmarked_content_ids($pdo, current_user_id());

$stmt = $pdo->prepare("
    SELECT * FROM content
    WHERE category_id = ? AND status = 'approved'
    ORDER BY popularity_score DESC, content_id DESC
");
$stmt->execute([$category['category_id']]);
$items = $stmt->fetchAll();

require_once __DIR__ . '/includes/header.php';
?>

<section class="section" style="padding-top:150px;">
    <div class="container">
        <div class="section-head reveal">
            <div>
                <span class="section-tag" style="color: <?= h($category['color']) ?>;"><?= icon_img($category['icon'], 16) ?> Category</span>
                <h2><?= h($category['name']) ?></h2>
                <p><?= h($category['description']) ?></p>
            </div>
            <a href="/fanhub/explore.php?category=<?= h($category['slug']) ?>" class="btn btn-gradient btn-sm">Search &amp; Filter</a>
        </div>

        <?php if (empty($items)): ?>
            <p class="empty-note reveal">No content in this category yet. Check back soon, or explore another fandom from the homepage.</p>
        <?php else: ?>
        <div class="category-grid reveal-stagger" style="grid-template-columns: repeat(4, 1fr);">
            <?php foreach ($items as $i => $item): ?>
                <a href="/fanhub/content.php?id=<?= $item['content_id'] ?>" class="content-card glass reveal" style="--i:<?= $i % 8 ?>;">
                    <div class="thumb" style="background-image:url('<?= h($item['image_url'] ?: 'https://picsum.photos/seed/' . $item['content_id'] . '/400/300') ?>');">
                        <span class="badge"><?= h($item['type']) ?></span>
                        <button class="bookmark-btn <?= in_array($item['content_id'], $bookmarkedIds) ? 'active' : '' ?>" data-content-id="<?= $item['content_id'] ?>" title="Bookmark"><?= in_array($item['content_id'], $bookmarkedIds) ? '♥' : '♡' ?></button>
                    </div>
                    <div class="body">
                        <h4><?= h($item['title']) ?></h4>
                        <p style="font-size:12.5px; color:var(--text-dim); margin:0 0 10px; display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; overflow:hidden;"><?= h($item['description']) ?></p>
                        <div class="meta">
                            <span><?= h($item['genre'] ?: $category['name']) ?></span>
                            <span class="pop">★ <?= (int) $item['popularity_score'] ?></span>
                        </div>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
