<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/includes/guard.php';

$pageTitle = 'Dashboard';
$activeAdminNav = 'dashboard';

$totalContent = (int) $pdo->query("SELECT COUNT(*) c FROM content")->fetch()['c'];
$totalUsers = (int) $pdo->query("SELECT COUNT(*) c FROM users WHERE role = 'user'")->fetch()['c'];
$pendingContent = (int) $pdo->query("SELECT COUNT(*) c FROM content WHERE status = 'pending'")->fetch()['c'];
$totalBookmarks = (int) $pdo->query("SELECT COUNT(*) c FROM bookmarks")->fetch()['c'];
$newFeedback = (int) $pdo->query("SELECT COUNT(*) c FROM feedback WHERE status = 'new'")->fetch()['c'];
$totalCharacters = (int) $pdo->query("SELECT COUNT(*) c FROM character_profiles")->fetch()['c'];
$totalMerch = (int) $pdo->query("SELECT COUNT(*) c FROM merchandise_items")->fetch()['c'];

$byCategory = $pdo->query("
    SELECT categories.name, categories.icon, categories.color, COUNT(content.content_id) AS total
    FROM categories
    LEFT JOIN content ON content.category_id = categories.category_id
    GROUP BY categories.category_id
    ORDER BY total DESC
")->fetchAll();

$recentUsers = $pdo->query("SELECT name, email, created_at FROM users ORDER BY user_id DESC LIMIT 6")->fetchAll();

require_once __DIR__ . '/includes/layout_top.php';
?>

<div class="admin-topbar">
    <h1>Welcome back, <?= h($_SESSION['user_name']) ?> 👋</h1>
    <a href="/fanhub/admin/content.php?action=new" class="btn btn-gradient btn-sm">+ Add Content</a>
</div>

<div class="stat-grid" style="grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));">
    <div class="glass stat-card"><div class="num"><?= $totalContent ?></div><div class="label">Total content items</div></div>
    <div class="glass stat-card"><div class="num"><?= $totalCharacters ?></div><div class="label">Character profiles</div></div>
    <div class="glass stat-card"><div class="num"><?= $totalMerch ?></div><div class="label">Merchandise items</div></div>
    <div class="glass stat-card"><div class="num"><?= $totalUsers ?></div><div class="label">Registered users</div></div>
    <div class="glass stat-card"><div class="num"><?= $pendingContent ?></div><div class="label">Pending review</div></div>
    <div class="glass stat-card"><div class="num"><?= $totalBookmarks ?></div><div class="label">Bookmarks saved</div></div>
    <div class="glass stat-card"><div class="num"><?= $newFeedback ?></div><div class="label">New feedback</div></div>
</div>

<div class="category-grid" style="grid-template-columns: 1.3fr 1fr; align-items:start;">
    <div class="glass" style="padding:26px;">
        <h3 style="margin-bottom:16px;">Content by Category</h3>
        <div style="display:flex; flex-direction:column; gap:12px;">
            <?php foreach ($byCategory as $cat):
                $pct = $totalContent ? round(($cat['total'] / $totalContent) * 100) : 0;
            ?>
                <div>
                    <div style="display:flex; justify-content:space-between; font-size:13px; margin-bottom:6px;">
                        <span><?= icon_img($cat['icon'], 15) ?> <?= h($cat['name']) ?></span>
                        <span style="color:var(--text-dim);"><?= (int) $cat['total'] ?> items</span>
                    </div>
                    <div style="height:8px; border-radius:999px; background:var(--surface-strong); overflow:hidden;">
                        <div style="height:100%; width:<?= $pct ?>%; background:<?= h($cat['color']) ?>;"></div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="glass" style="padding:26px;">
        <h3 style="margin-bottom:16px;">Recently Joined</h3>
        <div style="display:flex; flex-direction:column; gap:14px;">
            <?php foreach ($recentUsers as $u): ?>
                <div style="display:flex; justify-content:space-between; font-size:13px;">
                    <div>
                        <div style="font-weight:700;"><?= h($u['name']) ?></div>
                        <div style="color:var(--text-dimmer); font-size:11.5px;"><?= h($u['email']) ?></div>
                    </div>
                    <div style="color:var(--text-dim); font-size:11.5px;"><?= time_ago($u['created_at']) ?></div>
                </div>
            <?php endforeach; ?>
            <?php if (!$recentUsers): ?><p class="empty-note">No registered users yet.</p><?php endif; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/layout_bottom.php'; ?>
