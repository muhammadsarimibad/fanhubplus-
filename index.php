<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';

$pageTitle = 'Home';
$activeNav = 'home';

if (session_status() === PHP_SESSION_NONE) session_start();

$categories = get_all_categories($pdo);
$bookmarkedIds = get_user_bookmarked_content_ids($pdo, current_user_id());

$totalContent = (int) $pdo->query("SELECT COUNT(*) AS c FROM content WHERE status='approved'")->fetch()['c'];
$totalUsers = (int) $pdo->query("SELECT COUNT(*) AS c FROM users")->fetch()['c'];

require_once __DIR__ . '/includes/header.php';
?>

<?php if (isset($_GET['denied'])): ?>
<div class="container" style="padding-top:110px;">
    <div class="form-alert error reveal in-view" style="max-width:none;">You need an admin account to access that page.</div>
</div>
<?php endif; ?>

<section class="hero">
    <div class="container hero-grid">
        <div>
            <span class="hero-eyebrow glass hero-animate" style="border-color: rgba(255,61,154,.4); color:#ff8fc4; --d:0;">✨ Portal for Fans</span>
            <h1 class="hero-animate" style="--d:1;">One universe.<br>Every <span class="text-gradient text-shimmer">fandom</span> you love.</h1>
            <p class="lead hero-animate" style="--d:2;">Anime, Gaming, Movies, TV Shows, K-Pop, Comics, Manga and Cosplay — curated, searchable and beautifully presented in a single hub built for fans, by fans.</p>
            <div class="hero-actions hero-animate" style="--d:3;">
                <a href="/fanhub/explore.php" class="btn btn-gradient">Start Exploring</a>
                <a href="/fanhub/register.php" class="btn btn-ghost glass">Create Free Account</a>
            </div>
            <div class="hero-stats hero-animate" style="--d:4;">
                <div><strong><?= $totalContent ?>+</strong><span>Curated items</span></div>
                <div><strong>8</strong><span>Fandom categories</span></div>
                <div><strong><?= $totalUsers ?>+</strong><span>Community members</span></div>
            </div>
        </div>

        <div class="hero-visual">
            <div class="mascot-wrap">
                <video src="/fanhub/assets/video/character-transparent.webm" autoplay loop muted playsinline></video>
            </div>
            <div class="orbit-card glass c1"><span>🎬</span> Anime</div>
            <div class="orbit-card glass c2"><span>🎮</span> Gaming</div>
            <div class="orbit-card glass c3"><span>🎤</span> K-Pop</div>
        </div>
    </div>
</section>

<section class="section" id="categories">
    <div class="container">
        <div class="section-head reveal">
            <div>
                <span class="section-tag">Browse</span>
                <h2>Pick your fandom</h2>
                <p>Every category has its own curated library of articles, media, characters and merch.</p>
            </div>
        </div>
        <div class="category-grid reveal-stagger">
            <?php foreach ($categories as $i => $cat): ?>
                <a href="/fanhub/category.php?slug=<?= h($cat['slug']) ?>" class="category-card glass reveal" style="--card-color: <?= h($cat['color']) ?>; --i:<?= $i ?>;">
                    <div class="cat-icon" style="background: <?= h($cat['color']) ?>22;"><?= icon_img($cat['icon'], 28) ?></div>
                    <h3><?= h($cat['name']) ?></h3>
                    <p><?= h($cat['description']) ?></p>
                    <span class="cat-link">Explore <?= h($cat['name']) ?>
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none"><path d="M5 12h14M13 6l6 6-6 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </span>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<?php foreach ($categories as $cat):
    $items = get_content_by_category($pdo, $cat['category_id'], 10);
    if (empty($items)) continue;
?>
<section class="section">
    <div class="container">
        <div class="section-head reveal">
            <div>
                <span class="section-tag" style="color: <?= h($cat['color']) ?>;"><?= icon_img($cat['icon'], 16) ?> <?= h($cat['name']) ?></span>
                <h2>Trending in <?= h($cat['name']) ?></h2>
            </div>
            <a href="/fanhub/category.php?slug=<?= h($cat['slug']) ?>" class="btn btn-ghost glass btn-sm">View all</a>
        </div>
        <div class="content-row reveal">
            <?php foreach ($items as $item): ?>
                <a href="/fanhub/content.php?id=<?= $item['content_id'] ?>" class="content-card glass">
                    <div class="thumb" style="background-image:url('<?= h($item['image_url'] ?: 'https://picsum.photos/seed/' . $item['content_id'] . '/400/300') ?>');">
                        <span class="badge"><?= h($item['type']) ?></span>
                        <button class="bookmark-btn <?= in_array($item['content_id'], $bookmarkedIds) ? 'active' : '' ?>" data-content-id="<?= $item['content_id'] ?>" title="Bookmark"><?= in_array($item['content_id'], $bookmarkedIds) ? '♥' : '♡' ?></button>
                    </div>
                    <div class="body">
                        <h4><?= h($item['title']) ?></h4>
                        <div class="meta">
                            <span><?= h($item['genre'] ?: $cat['name']) ?></span>
                            <span class="pop">★ <?= (int) $item['popularity_score'] ?></span>
                        </div>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endforeach; ?>

<section class="section" id="sitemap">
    <div class="container">
        <div class="section-head reveal">
            <div>
                <span class="section-tag">Site Structure</span>
                <h2>Sitemap</h2>
                <p>How Visitors, Registered Users and Admins move through Fan Hub Plus.</p>
            </div>
        </div>
        <div class="category-grid reveal-stagger" style="grid-template-columns: repeat(3, 1fr);">
            <div class="glass reveal" style="padding:26px; --i:0;">
                <h3 style="color:var(--pink);">Visitor</h3>
                <ul style="display:flex; flex-direction:column; gap:10px; font-size:13.5px; color:var(--text-dim);">
                    <li>→ Homepage &amp; category browsing</li>
                    <li>→ Content details</li>
                    <li>→ FAQ Chatbot (optional)</li>
                    <li>→ Register account</li>
                </ul>
            </div>
            <div class="glass reveal" style="padding:26px; --i:1;">
                <h3 style="color:var(--cyan);">Registered User</h3>
                <ul style="display:flex; flex-direction:column; gap:10px; font-size:13.5px; color:var(--text-dim);">
                    <li>→ Login &amp; personalized dashboard</li>
                    <li>→ Explore, search, filter &amp; sort</li>
                    <li>→ Bookmark &amp; rate content</li>
                    <li>→ Submit fan content (awaits review)</li>
                    <li>→ Discover events on map &amp; calendar</li>
                </ul>
            </div>
            <div class="glass reveal" style="padding:26px; --i:2;">
                <h3 style="color:var(--orange);">Admin</h3>
                <ul style="display:flex; flex-direction:column; gap:10px; font-size:13.5px; color:var(--text-dim);">
                    <li>→ Secure admin login</li>
                    <li>→ Manage content, media &amp; characters</li>
                    <li>→ Moderate fan submissions</li>
                    <li>→ Manage chatbot knowledge base</li>
                    <li>→ View usage statistics</li>
                </ul>
            </div>
        </div>
    </div>
</section>

<section class="section" style="padding-top:0;">
    <div class="container">
        <div class="glass reveal" style="padding:44px; text-align:center;">
            <span class="section-tag" style="justify-content:center;">We're Listening</span>
            <h2 style="margin-bottom:12px;">Got an idea, bug or question?</h2>
            <p style="color:var(--text-dim); max-width:480px; margin:0 auto 24px;">Tell us what you love, what's broken, or what fandom feature you wish existed.</p>
            <a href="/fanhub/feedback.php" class="btn btn-gradient">💬 Send Feedback</a>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
