<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';
if (session_status() === PHP_SESSION_NONE) session_start();

$id = (int) ($_GET['id'] ?? 0);

$stmt = $pdo->prepare("
    SELECT merchandise_items.*, categories.name AS category_name, categories.slug AS category_slug, categories.icon AS category_icon, categories.color AS category_color
    FROM merchandise_items
    JOIN categories ON categories.category_id = merchandise_items.category_id
    WHERE merchandise_items.item_id = ?
");
$stmt->execute([$id]);
$item = $stmt->fetch();

if (!$item) redirect('/fanhub/merchandise.php');

$pdo->prepare("UPDATE merchandise_items SET view_count = view_count + 1 WHERE item_id = ?")->execute([$id]);

$pageTitle = $item['name'];
$isBookmarked = in_array($item['item_id'], get_user_bookmarked_ids($pdo, current_user_id(), 'merchandise'));

require_once __DIR__ . '/includes/header.php';
?>
<section class="section" style="padding-top:150px;">
    <div class="container">
        <p class="reveal in-view" style="font-size:13px; color:var(--text-dim); margin-bottom:20px;">
            <a href="/fanhub/merchandise.php" style="color:var(--text-dim);">Merchandise</a> &rsaquo;
            <span style="color:var(--text);"><?= h($item['name']) ?></span>
        </p>

        <div class="category-grid reveal-stagger" style="grid-template-columns: 1fr 1fr; align-items:start;">
            <div class="glass reveal" style="padding:0; overflow:hidden; --i:0;">
                <img src="<?= h($item['image_url'] ?: 'https://picsum.photos/seed/' . $id . '/600/500') ?>" style="width:100%; max-height:480px; object-fit:cover; display:block;" alt="">
            </div>

            <div class="glass reveal" style="padding:28px; --i:1;">
                <div style="display:flex; gap:10px; align-items:center; margin-bottom:14px; flex-wrap:wrap;">
                    <span class="badge-status pending"><?= h($item['tag']) ?></span>
                    <?php if ($item['is_upcoming']): ?><span class="badge-status approved">Pre-Order</span><?php endif; ?>
                    <span style="font-size:12.5px; color:var(--text-dim);"><?= $item['category_icon'] ?> <?= h($item['category_name']) ?></span>
                </div>
                <h1 style="font-size:26px; margin-bottom:14px;"><?= h($item['name']) ?></h1>
                <p style="color:var(--text-dim); line-height:1.7; margin-bottom:24px;"><?= h($item['description']) ?></p>

                <button class="bookmark-btn <?= $isBookmarked ? 'active' : '' ?>" data-content-id="<?= $item['item_id'] ?>" data-item-type="merchandise" style="position:static; width:48px; height:48px; font-size:22px;" title="Bookmark"><?= $isBookmarked ? '♥' : '♡' ?></button>

                <p style="font-size:12px; color:var(--text-dimmer); margin-top:24px;"><?= (int) $item['view_count'] ?> views &middot; This is a demo showcase item — no payment/checkout is included.</p>
            </div>
        </div>
    </div>
</section>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
