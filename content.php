<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';
if (session_status() === PHP_SESSION_NONE) session_start();

$id = (int) ($_GET['id'] ?? 0);

$stmt = $pdo->prepare("
    SELECT content.*, categories.name AS category_name, categories.slug AS category_slug, categories.color AS category_color, categories.icon AS category_icon
    FROM content
    JOIN categories ON categories.category_id = content.category_id
    WHERE content.content_id = ?
");
$stmt->execute([$id]);
$item = $stmt->fetch();

if (!$item || ($item['status'] !== 'approved' && !is_admin())) {
    redirect('/fanhub/explore.php');
}

$pdo->prepare("UPDATE content SET view_count = view_count + 1 WHERE content_id = ?")->execute([$id]);

$pageTitle = $item['title'];
$bookmarkedIds = get_user_bookmarked_content_ids($pdo, current_user_id());
$isBookmarked = in_array($item['content_id'], $bookmarkedIds);

$stmt = $pdo->prepare("
    SELECT content.*, categories.icon AS category_icon
    FROM content JOIN categories ON categories.category_id = content.category_id
    WHERE content.category_id = ? AND content.content_id != ? AND content.status = 'approved'
    ORDER BY content.popularity_score DESC LIMIT 4
");
$stmt->execute([$item['category_id'], $id]);
$related = $stmt->fetchAll();

require_once __DIR__ . '/includes/header.php';
?>
<section class="section" style="padding-top:150px;">
    <div class="container">
        <p class="reveal in-view" style="font-size:13px; color:var(--text-dim); margin-bottom:20px;">
            <a href="/fanhub/index.php" style="color:var(--text-dim);">Home</a> &rsaquo;
            <a href="/fanhub/category.php?slug=<?= h($item['category_slug']) ?>" style="color:var(--text-dim);"><?= h($item['category_name']) ?></a> &rsaquo;
            <span style="color:var(--text);"><?= h($item['title']) ?></span>
        </p>

        <div class="category-grid reveal-stagger" style="grid-template-columns: 1.4fr 1fr; align-items:start;">
            <div class="glass reveal" style="padding:0; overflow:hidden; --i:0;">

                <?php if ($item['type'] === 'audio' && $item['audio_url']): ?>
                    <div class="thumb" style="height:220px; background-image:url('<?= h($item['image_url'] ?: 'https://picsum.photos/seed/' . $id . '/600/400') ?>');"></div>
                    <div style="padding:24px;">
                        <audio controls style="width:100%;" src="<?= h($item['audio_url']) ?>">Your browser does not support audio playback.</audio>
                        <p style="font-size:11.5px; color:var(--text-dimmer); margin-top:10px;">A free, Creative-Commons-licensed K-Pop-style track from Jamendo — matched to the vibe, not the official release.</p>
                    </div>

                <?php elseif ($item['type'] === 'video' && $item['video_url']): ?>
                    <div style="aspect-ratio:16/9; background:#000;">
                        <video controls style="width:100%; height:100%;" src="<?= h($item['video_url']) ?>" poster="<?= h($item['image_url']) ?>"></video>
                    </div>
                    <p style="font-size:11.5px; color:var(--text-dimmer); padding:10px 24px 0;">Mood clip from Pexels — a free stock-video match for this title's vibe, not the official trailer.</p>

                <?php else: ?>
                    <img src="<?= h($item['image_url'] ?: 'https://picsum.photos/seed/' . $id . '/800/500') ?>" style="width:100%; max-height:420px; object-fit:cover; display:block;" alt="">
                <?php endif; ?>

                <div style="padding:24px;">
                    <div style="display:flex; align-items:center; gap:10px; margin-bottom:14px; flex-wrap:wrap;">
                        <span class="badge-status approved"><?= h($item['type']) ?></span>
                        <?php if ($item['tag']): ?><span class="badge-status pending"><?= h($item['tag']) ?></span><?php endif; ?>
                        <span style="font-size:12.5px; color:var(--text-dim);"><?= icon_img($item['category_icon'], 14) ?> <?= h($item['category_name']) ?></span>
                    </div>
                    <h1 style="font-size:26px; margin-bottom:12px;"><?= h($item['title']) ?></h1>
                    <p style="color:var(--text-dim); line-height:1.7; margin-bottom:20px;"><?= h($item['description']) ?></p>
                    <?php if ($item['body']): ?>
                        <div style="color:var(--text-dim); line-height:1.8; font-size:14.5px; border-top:1px solid var(--border); padding-top:20px;">
                            <?= nl2br(h($item['body'])) ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="glass reveal" style="padding:24px; --i:1;">
                <button class="bookmark-btn <?= $isBookmarked ? 'active' : '' ?>" data-content-id="<?= $item['content_id'] ?>" style="position:static; width:44px; height:44px; font-size:20px; margin-bottom:18px;" title="Bookmark"><?= $isBookmarked ? '♥' : '♡' ?></button>

                <div style="display:flex; flex-direction:column; gap:14px; font-size:13.5px;">
                    <?php if ($item['genre']): ?><div><strong style="color:var(--text-dim); display:block; font-size:11.5px; text-transform:uppercase; margin-bottom:4px;">Genre</strong><?= h($item['genre']) ?></div><?php endif; ?>
                    <?php if ($item['release_date']): ?><div><strong style="color:var(--text-dim); display:block; font-size:11.5px; text-transform:uppercase; margin-bottom:4px;">Release Date</strong><?= date('F j, Y', strtotime($item['release_date'])) ?></div><?php endif; ?>
                    <div><strong style="color:var(--text-dim); display:block; font-size:11.5px; text-transform:uppercase; margin-bottom:4px;">Popularity</strong>★ <?= (int) $item['popularity_score'] ?> / 100</div>
                    <div><strong style="color:var(--text-dim); display:block; font-size:11.5px; text-transform:uppercase; margin-bottom:4px;">Views</strong><?= (int) $item['view_count'] ?></div>
                </div>

                <?php if ($related): ?>
                <div style="margin-top:28px; border-top:1px solid var(--border); padding-top:20px;">
                    <h5 style="font-size:12px; text-transform:uppercase; color:var(--text-dim); margin-bottom:14px;">More in <?= h($item['category_name']) ?></h5>
                    <div style="display:flex; flex-direction:column; gap:12px;">
                        <?php foreach ($related as $r): ?>
                            <a href="/fanhub/content.php?id=<?= $r['content_id'] ?>" style="display:flex; gap:10px; align-items:center;">
                                <img src="<?= h($r['image_url'] ?: 'https://picsum.photos/seed/' . $r['content_id'] . '/100/100') ?>" style="width:44px; height:44px; border-radius:8px; object-fit:cover;" alt="">
                                <span style="font-size:13px; color:var(--text);"><?= h($r['title']) ?></span>
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
