<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/includes/guard.php';

$pageTitle = 'Characters';
$activeAdminNav = 'characters';
$categories = get_all_categories($pdo);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    $pdo->prepare("DELETE FROM character_profiles WHERE character_id = ?")->execute([(int) $_POST['character_id']]);
    redirect('/fanhub/admin/characters.php?msg=deleted');
}

$editId = (int) ($_GET['edit'] ?? 0);
$edit = null;
if ($editId) {
    $stmt = $pdo->prepare("SELECT * FROM character_profiles WHERE character_id = ?");
    $stmt->execute([$editId]);
    $edit = $stmt->fetch();
}

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'save') {
    $categoryId = (int) $_POST['category_id'];
    $name = trim($_POST['name'] ?? '');
    $bio = trim($_POST['bio'] ?? '');
    $image = trim($_POST['image_url'] ?? '');
    $id = (int) ($_POST['character_id'] ?? 0);

    if (!$categoryId) $errors[] = 'Choose a category.';
    if (mb_strlen($name) < 2) $errors[] = 'Please enter a name.';

    if (!$errors) {
        if ($id) {
            $pdo->prepare("UPDATE character_profiles SET category_id=?, name=?, bio=?, image_url=? WHERE character_id=?")
                ->execute([$categoryId, $name, $bio, $image, $id]);
        } else {
            $pdo->prepare("INSERT INTO character_profiles (category_id, name, bio, image_url, source_api) VALUES (?, ?, ?, ?, 'admin')")
                ->execute([$categoryId, $name, $bio, $image]);
        }
        redirect('/fanhub/admin/characters.php?msg=saved');
    }
    $edit = ['character_id' => $id, 'category_id' => $categoryId, 'name' => $name, 'bio' => $bio, 'image_url' => $image];
}

$characters = $pdo->query("
    SELECT character_profiles.*, categories.name AS category_name
    FROM character_profiles JOIN categories ON categories.category_id = character_profiles.category_id
    ORDER BY character_profiles.character_id DESC
")->fetchAll();

require_once __DIR__ . '/includes/layout_top.php';
?>
<div class="admin-topbar"><h1>Characters</h1></div>

<?php if (($_GET['msg'] ?? '') === 'saved'): ?><div class="form-alert success" style="max-width:none;">Saved.</div><?php endif; ?>
<?php if (($_GET['msg'] ?? '') === 'deleted'): ?><div class="form-alert success" style="max-width:none;">Deleted.</div><?php endif; ?>
<?php if ($errors): ?><div class="form-alert error" style="max-width:none;"><?php foreach ($errors as $e) echo h($e) . '<br>'; ?></div><?php endif; ?>

<div class="category-grid" style="grid-template-columns: 1fr 320px; align-items:start;">
    <div class="glass admin-table-wrap" style="padding:8px 20px;">
        <table class="admin-table">
            <thead><tr><th></th><th>Name</th><th>Category</th><th>Bio</th><th>Actions</th></tr></thead>
            <tbody>
            <?php foreach ($characters as $c): ?>
                <tr>
                    <td data-label="Image"><img class="thumb-sm" src="<?= h($c['image_url'] ?: 'https://picsum.photos/seed/' . $c['character_id'] . '/100/100') ?>" alt=""></td>
                    <td data-label="Name"><?= h($c['name']) ?></td>
                    <td data-label="Category"><?= h($c['category_name']) ?></td>
                    <td class="wrap" data-label="Bio"><?= h(mb_substr($c['bio'], 0, 80)) ?>...</td>
                    <td data-label="Actions">
                        <div class="table-actions">
                            <a href="/fanhub/admin/characters.php?edit=<?= $c['character_id'] ?>" class="btn btn-ghost">Edit</a>
                            <form method="POST" onsubmit="return confirm('Delete this character?');">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="character_id" value="<?= $c['character_id'] ?>">
                                <button type="submit" class="btn btn-ghost" style="color:#ff8fc4;">Delete</button>
                            </form>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$characters): ?><tr><td colspan="5"><p class="empty-note">No characters yet.</p></td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>

    <div class="glass" style="padding:24px;">
        <h3 style="margin-bottom:16px; font-size:16px;"><?= $edit ? 'Edit' : 'Add' ?> Character</h3>
        <form method="POST" action="/fanhub/admin/characters.php">
            <input type="hidden" name="action" value="save">
            <input type="hidden" name="character_id" value="<?= (int) ($edit['character_id'] ?? 0) ?>">
            <div class="form-group">
                <label>Category</label>
                <select name="category_id" required>
                    <option value="">Choose</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= $cat['category_id'] ?>" <?= (int) ($edit['category_id'] ?? 0) === (int) $cat['category_id'] ? 'selected' : '' ?>><?= $cat['icon'] ?> <?= h($cat['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Name</label>
                <input type="text" name="name" value="<?= h($edit['name'] ?? '') ?>" required>
            </div>
            <div class="form-group">
                <label>Bio</label>
                <textarea name="bio" rows="4"><?= h($edit['bio'] ?? '') ?></textarea>
            </div>
            <div class="form-group">
                <label>Image URL</label>
                <input type="text" name="image_url" value="<?= h($edit['image_url'] ?? '') ?>">
            </div>
            <button type="submit" class="btn btn-gradient btn-block"><?= $edit ? 'Save Changes' : 'Add Character' ?></button>
            <?php if ($edit): ?><a href="/fanhub/admin/characters.php" class="btn btn-ghost btn-block" style="margin-top:8px;">Cancel</a><?php endif; ?>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/includes/layout_bottom.php'; ?>
