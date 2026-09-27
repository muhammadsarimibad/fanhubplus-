<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';

$pageTitle = 'Explore';
$activeNav = 'explore';

if (session_status() === PHP_SESSION_NONE) session_start();

$categories = get_all_categories($pdo);
$genres = get_distinct_genres($pdo);
$years = get_distinct_years($pdo);
$bookmarkedIds = get_user_bookmarked_content_ids($pdo, current_user_id());

$q = trim($_GET['q'] ?? '');
$categorySlug = $_GET['category'] ?? '';
$genre = $_GET['genre'] ?? '';
$type = $_GET['type'] ?? '';
$year = $_GET['year'] ?? '';
$sort = $_GET['sort'] ?? 'latest';
$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 12;

$where = ["content.status = 'approved'"];
$params = [];

if ($q !== '') { $where[] = '(content.title LIKE ? OR content.description LIKE ?)'; $params[] = "%$q%"; $params[] = "%$q%"; }
if ($categorySlug !== '') { $where[] = 'categories.slug = ?'; $params[] = $categorySlug; }
if ($genre !== '') { $where[] = 'content.genre LIKE ?'; $params[] = "%$genre%"; }
if ($type !== '') { $where[] = 'content.type = ?'; $params[] = $type; }
if ($year !== '') { $where[] = 'YEAR(content.release_date) = ?'; $params[] = (int) $year; }

$whereSql = 'WHERE ' . implode(' AND ', $where);

$orderSql = match ($sort) {
    'popular' => 'content.popularity_score DESC, content.content_id DESC',
    'az' => 'content.title ASC',
    default => 'content.content_id DESC',
};

$countStmt = $pdo->prepare("
    SELECT COUNT(*) AS c
    FROM content JOIN categories ON categories.category_id = content.category_id
    $whereSql
");
$countStmt->execute($params);
$totalResults = (int) $countStmt->fetch()['c'];
$totalPages = max(1, (int) ceil($totalResults / $perPage));
$page = min($page, $totalPages);
$offset = ($page - 1) * $perPage;

$stmt = $pdo->prepare("
    SELECT content.*, categories.name AS category_name, categories.slug AS category_slug, categories.color AS category_color, categories.icon AS category_icon
    FROM content
    JOIN categories ON categories.category_id = content.category_id
    $whereSql
    ORDER BY $orderSql
    LIMIT $perPage OFFSET $offset
");
$stmt->execute($params);
$items = $stmt->fetchAll();

// Helper to rebuild the query string while overriding one param (used for pagination + sort links)
function explore_url($overrides = []) {
    $params = array_merge($_GET, $overrides);
    $params = array_filter($params, fn($v) => $v !== '' && $v !== null);
    return '/fanhub/explore.php?' . http_build_query($params);
}

require_once __DIR__ . '/includes/header.php';
?>

<section class="section" style="padding-top:150px;">
    <div class="container">
        <div class="section-head reveal">
            <div>
                <span class="section-tag">Fandom Content Explorer</span>
                <h2>Search &amp; Discover</h2>
                <p><?= $totalResults ?> item<?= $totalResults === 1 ? '' : 's' ?> found across every fandom.</p>
            </div>
        </div>

        <form method="GET" action="/fanhub/explore.php" class="filter-bar glass reveal in-view" style="padding:22px 24px; margin-bottom:32px;">
            <div class="form-group" style="flex:2; min-width:220px;">
                <label>Search</label>
                <input type="text" name="q" value="<?= h($q) ?>" placeholder="Search titles &amp; descriptions...">
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
            <div class="form-group">
                <label>Genre</label>
                <select name="genre">
                    <option value="">All Genres</option>
                    <?php foreach ($genres as $g): ?>
                        <option value="<?= h($g) ?>" <?= $genre === $g ? 'selected' : '' ?>><?= h($g) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Type</label>
                <select name="type">
                    <option value="">All Types</option>
                    <?php foreach (['article','video','audio','image'] as $t): ?>
                        <option value="<?= $t ?>" <?= $type === $t ? 'selected' : '' ?>><?= ucfirst($t) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Year</label>
                <select name="year">
                    <option value="">Any Year</option>
                    <?php foreach ($years as $y): ?>
                        <option value="<?= $y ?>" <?= (string) $year === (string) $y ? 'selected' : '' ?>><?= $y ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="submit" class="btn btn-gradient btn-sm">Apply</button>
            <a href="/fanhub/explore.php" class="btn btn-ghost btn-sm">Reset</a>
        </form>

        <div class="section-head reveal" style="margin-bottom:20px;">
            <div style="display:flex; gap:8px; flex-wrap:wrap;">
                <a href="<?= h(explore_url(['sort' => 'latest', 'page' => 1])) ?>" class="btn btn-sm <?= $sort === 'latest' ? 'btn-gradient' : 'btn-ghost glass' ?>">Latest</a>
                <a href="<?= h(explore_url(['sort' => 'popular', 'page' => 1])) ?>" class="btn btn-sm <?= $sort === 'popular' ? 'btn-gradient' : 'btn-ghost glass' ?>">Most Popular</a>
                <a href="<?= h(explore_url(['sort' => 'az', 'page' => 1])) ?>" class="btn btn-sm <?= $sort === 'az' ? 'btn-gradient' : 'btn-ghost glass' ?>">A–Z</a>
            </div>
        </div>

        <?php if (!$items): ?>
            <p class="empty-note reveal">No content matches your filters. Try widening your search.</p>
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
                        <p style="font-size:12px; color:var(--text-dim); margin:0 0 8px;"><?= icon_img($item['category_icon'], 14) ?> <?= h($item['category_name']) ?><?= $item['tag'] ? ' · ' . h($item['tag']) : '' ?></p>
                        <div class="meta">
                            <span><?= h($item['genre'] ?: $item['category_name']) ?></span>
                            <span class="pop">★ <?= (int) $item['popularity_score'] ?></span>
                        </div>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>

        <?php if ($totalPages > 1): ?>
        <div style="display:flex; justify-content:center; gap:8px; margin-top:36px; flex-wrap:wrap;">
            <?php for ($p = 1; $p <= $totalPages; $p++): ?>
                <a href="<?= h(explore_url(['page' => $p])) ?>" class="btn btn-sm <?= $p === $page ? 'btn-gradient' : 'btn-ghost glass' ?>"><?= $p ?></a>
            <?php endfor; ?>
        </div>
        <?php endif; ?>
        <?php endif; ?>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
