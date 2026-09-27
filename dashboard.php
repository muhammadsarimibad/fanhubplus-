<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';
if (session_status() === PHP_SESSION_NONE) session_start();
if (!is_logged_in()) redirect('/fanhub/login.php');

$pageTitle = 'Dashboard';
$activeNav = 'dashboard';
$userId = current_user_id();
$user = get_user_by_id($pdo, $userId);
$categories = get_all_categories($pdo);

$stmt = $pdo->prepare("SELECT category_id FROM user_favorite_categories WHERE user_id = ?");
$stmt->execute([$userId]);
$favoriteIds = array_column($stmt->fetchAll(), 'category_id');

$stmt = $pdo->prepare("
    SELECT content.*, categories.name AS category_name, categories.icon AS category_icon
    FROM bookmarks
    JOIN content ON content.content_id = bookmarks.item_id AND bookmarks.item_type = 'content'
    JOIN categories ON categories.category_id = content.category_id
    WHERE bookmarks.user_id = ?
    ORDER BY bookmarks.created_at DESC
");
$stmt->execute([$userId]);
$bookmarkedItems = $stmt->fetchAll();

$errors = [];
$success = isset($_GET['welcome']) ? 'Welcome to Fan Hub Plus! Your account is ready.' : null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $bio = trim($_POST['bio'] ?? '');
    $darkMode = isset($_POST['dark_mode']) ? 1 : 0;
    $fontSize = in_array($_POST['font_size'] ?? '', ['small', 'medium', 'large']) ? $_POST['font_size'] : 'medium';
    $selectedCategories = array_map('intval', $_POST['favorite_categories'] ?? []);

    if (mb_strlen($name) < 2) $errors[] = 'Please enter your full name.';

    $avatarPath = $user['avatar'];
    if (!empty($_FILES['avatar']['name'])) {
        $file = $_FILES['avatar'];
        $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/gif' => 'gif', 'image/webp' => 'webp'];
        $mime = mime_content_type($file['tmp_name']);

        if ($file['error'] !== UPLOAD_ERR_OK) {
            $errors[] = 'Avatar upload failed. Please try again.';
        } elseif (!isset($allowed[$mime])) {
            $errors[] = 'Avatar must be a JPG, PNG, GIF or WEBP image.';
        } elseif ($file['size'] > 2 * 1024 * 1024) {
            $errors[] = 'Avatar image must be smaller than 2MB.';
        } else {
            $newName = 'user' . $userId . '_' . uniqid() . '.' . $allowed[$mime];
            $dest = __DIR__ . '/assets/uploads/avatars/' . $newName;
            if (move_uploaded_file($file['tmp_name'], $dest)) {
                $avatarPath = 'assets/uploads/avatars/' . $newName;
            } else {
                $errors[] = 'Could not save the uploaded avatar.';
            }
        }
    }

    if (!$errors) {
        $stmt = $pdo->prepare("UPDATE users SET name = ?, bio = ?, avatar = ?, dark_mode = ?, font_size = ? WHERE user_id = ?");
        $stmt->execute([$name, $bio, $avatarPath, $darkMode, $fontSize, $userId]);

        $pdo->prepare("DELETE FROM user_favorite_categories WHERE user_id = ?")->execute([$userId]);
        if ($selectedCategories) {
            $ins = $pdo->prepare("INSERT INTO user_favorite_categories (user_id, category_id) VALUES (?, ?)");
            foreach ($selectedCategories as $catId) $ins->execute([$userId, $catId]);
        }

        $_SESSION['user_name'] = $name;
        $_SESSION['avatar'] = $avatarPath;

        $user = get_user_by_id($pdo, $userId);
        $favoriteIds = $selectedCategories;
        $success = 'Profile updated successfully.';
    }
}

require_once __DIR__ . '/includes/header.php';
?>
<section class="section" style="padding-top:150px;">
    <div class="container">
        <div class="section-head reveal">
            <div>
                <span class="section-tag">Your Space</span>
                <h2>Hey, <?= h(explode(' ', $user['name'])[0]) ?> 👋</h2>
                <p>Manage your profile, favorite fandoms and display preferences.</p>
            </div>
        </div>

        <?php if ($success): ?><div class="form-alert success reveal in-view" style="max-width:none;"><?= h($success) ?></div><?php endif; ?>
        <?php if ($errors): ?><div class="form-alert error reveal in-view" style="max-width:none;"><?php foreach ($errors as $e) echo h($e) . '<br>'; ?></div><?php endif; ?>

        <div class="category-grid reveal-stagger" style="grid-template-columns: 320px 1fr; align-items:start;">
            <div class="glass reveal" style="padding:28px; text-align:center; --i:0;">
                <?php if (!empty($user['avatar'])): ?>
                    <img src="/fanhub/<?= h($user['avatar']) ?>" alt="" style="width:110px; height:110px; border-radius:50%; object-fit:cover; margin:0 auto 16px; border:3px solid var(--border);">
                <?php else: ?>
                    <div style="width:110px; height:110px; border-radius:50%; margin:0 auto 16px; background:var(--gradient-main); display:flex; align-items:center; justify-content:center; font-size:38px; font-weight:800; color:#08060f;">
                        <?= h(strtoupper(substr($user['name'], 0, 1))) ?>
                    </div>
                <?php endif; ?>
                <h3 style="margin-bottom:4px;"><?= h($user['name']) ?></h3>
                <p style="color:var(--text-dim); font-size:13px; margin-bottom:4px;"><?= h($user['email']) ?></p>
                <p style="color:var(--text-dimmer); font-size:12px;">Member since <?= date('M Y', strtotime($user['created_at'])) ?></p>
                <?php if ($user['role'] === 'admin'): ?>
                    <a href="/fanhub/admin/index.php" class="btn btn-gradient btn-sm" style="margin-top:16px;">Admin Panel</a>
                <?php endif; ?>
            </div>

            <div class="glass reveal" style="padding:32px; --i:1;">
                <form method="POST" action="/fanhub/dashboard.php" enctype="multipart/form-data" novalidate>
                    <div class="form-group">
                        <label for="name">Full Name</label>
                        <input type="text" id="name" name="name" value="<?= h($user['name']) ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="bio">Bio</label>
                        <textarea id="bio" name="bio" rows="3" placeholder="Tell other fans about yourself..."><?= h($user['bio']) ?></textarea>
                    </div>
                    <div class="form-group">
                        <label for="avatar">Profile Picture</label>
                        <input type="file" id="avatar" name="avatar" accept="image/png, image/jpeg, image/gif, image/webp">
                    </div>

                    <div class="form-group">
                        <label>Favorite Fandoms</label>
                        <div style="display:flex; flex-wrap:wrap; gap:10px;">
                            <?php foreach ($categories as $cat): ?>
                                <label style="display:flex; align-items:center; gap:6px; padding:8px 14px; border-radius:999px; background:var(--surface); border:1px solid var(--border); font-size:13px; cursor:pointer;">
                                    <input type="checkbox" name="favorite_categories[]" value="<?= $cat['category_id'] ?>" <?= in_array($cat['category_id'], $favoriteIds) ? 'checked' : '' ?> style="width:auto;">
                                    <?= icon_img($cat['icon'], 15) ?> <?= h($cat['name']) ?>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="font_size">Font Size</label>
                        <select id="font_size" name="font_size">
                            <option value="small" <?= $user['font_size'] === 'small' ? 'selected' : '' ?>>Small</option>
                            <option value="medium" <?= $user['font_size'] === 'medium' ? 'selected' : '' ?>>Medium</option>
                            <option value="large" <?= $user['font_size'] === 'large' ? 'selected' : '' ?>>Large</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label style="display:flex; align-items:center; gap:8px; cursor:pointer;">
                            <input type="checkbox" name="dark_mode" value="1" <?= $user['dark_mode'] ? 'checked' : '' ?> style="width:auto;">
                            Prefer dark mode by default
                        </label>
                    </div>

                    <button type="submit" class="btn btn-gradient">Save Changes</button>
                </form>
            </div>
        </div>

        <div class="glass reveal" style="padding:28px; margin-top:24px;">
            <h3 style="margin-bottom:16px;">🔖 Your Bookmarks (<?= count($bookmarkedItems) ?>)</h3>
            <?php if (!$bookmarkedItems): ?>
                <p class="empty-note">No bookmarks yet — tap the ♡ on any content card to save it here.</p>
            <?php else: ?>
                <div class="category-grid bookmarks-list" style="grid-template-columns: repeat(4, 1fr);">
                    <?php foreach ($bookmarkedItems as $i => $item): ?>
                        <a href="/fanhub/content.php?id=<?= $item['content_id'] ?>" class="content-card glass" style="--i:<?= $i % 8 ?>;">
                            <div class="thumb" style="background-image:url('<?= h($item['image_url'] ?: 'https://picsum.photos/seed/' . $item['content_id'] . '/400/300') ?>');">
                                <span class="badge"><?= h($item['type']) ?></span>
                                <button class="bookmark-btn active" data-content-id="<?= $item['content_id'] ?>" title="Remove bookmark">♥</button>
                            </div>
                            <div class="body">
                                <h4><?= h($item['title']) ?></h4>
                                <div class="meta">
                                    <span><?= icon_img($item['category_icon'], 14) ?> <?= h($item['category_name']) ?></span>
                                    <span class="pop">★ <?= (int) $item['popularity_score'] ?></span>
                                </div>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
