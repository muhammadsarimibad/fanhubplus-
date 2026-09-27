<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';
if (session_status() === PHP_SESSION_NONE) session_start();

$pageTitle = 'Characters';
$activeNav = 'characters';

$categories = get_all_categories($pdo);
$categorySlug = $_GET['category'] ?? '';
$q = trim($_GET['q'] ?? '');

$where = [];
$params = [];
if ($categorySlug !== '') { $where[] = 'categories.slug = ?'; $params[] = $categorySlug; }
if ($q !== '') { $where[] = 'character_profiles.name LIKE ?'; $params[] = "%$q%"; }
$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$stmt = $pdo->prepare("
    SELECT character_profiles.*, categories.name AS category_name, categories.slug AS category_slug, categories.icon AS category_icon
    FROM character_profiles
    JOIN categories ON categories.category_id = character_profiles.category_id
    $whereSql
    ORDER BY character_profiles.character_id ASC
    LIMIT 100
");
$stmt->execute($params);
$characters = $stmt->fetchAll();

$bookmarkedIds = get_user_bookmarked_ids($pdo, current_user_id(), 'character');

require_once __DIR__ . '/includes/header.php';
?>
<section class="section" style="padding-top:150px;">
    <div class="container">
        <div class="section-head reveal">
            <div>
                <span class="section-tag">Fandom Faces</span>
                <h2>Character Profiles</h2>
                <p><?= count($characters) ?> characters from your favorite anime &amp; manga.</p>
            </div>
        </div>

        <form method="GET" action="/fanhub/characters.php" class="filter-bar glass reveal in-view" style="padding:18px 20px;">
            <div class="form-group">
                <label>Search</label>
                <input type="text" name="q" value="<?= h($q) ?>" placeholder="Search character names...">
            </div>
            <div class="form-group">
                <label>Category</label>
                <select name="category">
                    <option value="">All Categories</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= h($cat['slug']) ?>" <?= $categorySlug === $cat['slug'] ? 'selected' : '' ?>><?= $cat['icon'] ?> <?= h($cat['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="submit" class="btn btn-gradient btn-sm">Apply</button>
            <a href="/fanhub/characters.php" class="btn btn-ghost btn-sm">Reset</a>
        </form>

        <?php if (!$characters): ?>
            <p class="empty-note reveal">No characters match your search.</p>
        <?php else: ?>
        <div class="category-grid reveal-stagger" style="grid-template-columns: repeat(4, 1fr);">
            <?php foreach ($characters as $i => $c): ?>
                <a href="/fanhub/character.php?id=<?= $c['character_id'] ?>" class="content-card glass reveal" style="--i:<?= $i % 8 ?>;">
                    <div class="thumb" style="height:220px; background-image:url('<?= h($c['image_url'] ?: 'https://picsum.photos/seed/' . $c['character_id'] . '/400/500') ?>'); background-position:top center;">
                        <span class="badge"><?= $c['category_icon'] ?> <?= h($c['category_name']) ?></span>
                        <button class="bookmark-btn <?= in_array($c['character_id'], $bookmarkedIds) ? 'active' : '' ?>" data-content-id="<?= $c['character_id'] ?>" data-item-type="character" title="Bookmark"><?= in_array($c['character_id'], $bookmarkedIds) ? '♥' : '♡' ?></button>
                    </div>
                    <div class="body">
                        <h4><?= h($c['name']) ?></h4>
                        <p style="font-size:12px; color:var(--text-dim); margin:0; display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; overflow:hidden;"><?= h($c['bio']) ?></p>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</section>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
