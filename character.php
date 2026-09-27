<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';
if (session_status() === PHP_SESSION_NONE) session_start();

$id = (int) ($_GET['id'] ?? 0);

$stmt = $pdo->prepare("
    SELECT character_profiles.*, categories.name AS category_name, categories.slug AS category_slug, categories.icon AS category_icon, categories.color AS category_color
    FROM character_profiles
    JOIN categories ON categories.category_id = character_profiles.category_id
    WHERE character_profiles.character_id = ?
");
$stmt->execute([$id]);
$char = $stmt->fetch();

if (!$char) redirect('/fanhub/characters.php');

$pageTitle = $char['name'];
$isBookmarked = in_array($char['character_id'], get_user_bookmarked_ids($pdo, current_user_id(), 'character'));

$stmt = $pdo->prepare("
    SELECT * FROM character_profiles
    WHERE category_id = ? AND character_id != ?
    ORDER BY RAND() LIMIT 4
");
$stmt->execute([$char['category_id'], $id]);
$related = $stmt->fetchAll();

require_once __DIR__ . '/includes/header.php';
?>
<section class="section" style="padding-top:150px;">
    <div class="container">
        <p class="reveal in-view" style="font-size:13px; color:var(--text-dim); margin-bottom:20px;">
            <a href="/fanhub/characters.php" style="color:var(--text-dim);">Characters</a> &rsaquo;
            <span style="color:var(--text);"><?= h($char['name']) ?></span>
        </p>

        <div class="category-grid reveal-stagger" style="grid-template-columns: 340px 1fr; align-items:start;">
            <div class="glass reveal" style="padding:0; overflow:hidden; --i:0;">
                <img src="<?= h($char['image_url'] ?: 'https://picsum.photos/seed/' . $id . '/500/650') ?>" style="width:100%; display:block;" alt="">
                <div style="padding:20px;">
                    <button class="bookmark-btn <?= $isBookmarked ? 'active' : '' ?>" data-content-id="<?= $char['character_id'] ?>" data-item-type="character" style="position:static; width:44px; height:44px; font-size:20px;" title="Bookmark"><?= $isBookmarked ? '♥' : '♡' ?></button>
                </div>
            </div>

            <div class="glass reveal" style="padding:28px; --i:1;">
                <span class="section-tag" style="color: <?= h($char['category_color']) ?>;"><?= $char['category_icon'] ?> <?= h($char['category_name']) ?></span>
                <h1 style="font-size:28px; margin-bottom:16px;"><?= h($char['name']) ?></h1>
                <p style="color:var(--text-dim); line-height:1.8;"><?= nl2br(h($char['bio'])) ?></p>

                <?php if ($related): ?>
                <div style="margin-top:32px; border-top:1px solid var(--border); padding-top:24px;">
                    <h5 style="font-size:12px; text-transform:uppercase; color:var(--text-dim); margin-bottom:16px;">More <?= h($char['category_name']) ?> Characters</h5>
                    <div style="display:flex; flex-wrap:wrap; gap:14px;">
                        <?php foreach ($related as $r): ?>
                            <a href="/fanhub/character.php?id=<?= $r['character_id'] ?>" style="width:100px; text-align:center;">
                                <img src="<?= h($r['image_url'] ?: 'https://picsum.photos/seed/' . $r['character_id'] . '/200/200') ?>" style="width:100px; height:100px; border-radius:12px; object-fit:cover; object-position:top center; margin-bottom:6px;" alt="">
                                <span style="font-size:12px; color:var(--text);"><?= h($r['name']) ?></span>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
