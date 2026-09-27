<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/includes/guard.php';

$pageTitle = 'Merchandise';
$activeAdminNav = 'merchandise';
$categories = get_all_categories($pdo);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    $pdo->prepare("DELETE FROM merchandise_items WHERE item_id = ?")->execute([(int) $_POST['item_id']]);
    redirect('/fanhub/admin/merchandise.php?msg=deleted');
}

$editId = (int) ($_GET['edit'] ?? 0);
$edit = null;
if ($editId) {
    $stmt = $pdo->prepare("SELECT * FROM merchandise_items WHERE item_id = ?");
    $stmt->execute([$editId]);
    $edit = $stmt->fetch();
}

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'save') {
    $categoryId = (int) $_POST['category_id'];
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $image = trim($_POST['image_url'] ?? '');
    $tag = trim($_POST['tag'] ?? '');
    $isUpcoming = isset($_POST['is_upcoming']) ? 1 : 0;
    $id = (int) ($_POST['item_id'] ?? 0);

    if (!$categoryId) $errors[] = 'Choose a category.';
    if (mb_strlen($name) < 2) $errors[] = 'Please enter a name.';

    if (!$errors) {
        if ($id) {
            $pdo->prepare("UPDATE merchandise_items SET category_id=?, name=?, description=?, image_url=?, tag=?, is_upcoming=? WHERE item_id=?")
                ->execute([$categoryId, $name, $description, $image, $tag, $isUpcoming, $id]);
        } else {
            $pdo->prepare("INSERT INTO merchandise_items (category_id, name, description, image_url, tag, is_upcoming) VALUES (?, ?, ?, ?, ?, ?)")
                ->execute([$categoryId, $name, $description, $image, $tag, $isUpcoming]);
        }
        redirect('/fanhub/admin/merchandise.php?msg=saved');
    }
    $edit = ['item_id' => $id, 'category_id' => $categoryId, 'name' => $name, 'description' => $description, 'image_url' => $image, 'tag' => $tag, 'is_upcoming' => $isUpcoming];
}

$items = $pdo->query("
    SELECT merchandise_items.*, categories.name AS category_name
    FROM merchandise_items JOIN categories ON categories.category_id = merchandise_items.category_id
    ORDER BY merchandise_items.item_id DESC
")->fetchAll();

require_once __DIR__ . '/includes/layout_top.php';
?>
<div class="admin-topbar"><h1>Merchandise</h1></div>

<?php if (($_GET['msg'] ?? '') === 'saved'): ?><div class="form-alert success" style="max-width:none;">Saved.</div><?php endif; ?>
<?php if (($_GET['msg'] ?? '') === 'deleted'): ?><div class="form-alert success" style="max-width:none;">Deleted.</div><?php endif; ?>
<?php if ($errors): ?><div class="form-alert error" style="max-width:none;"><?php foreach ($errors as $e) echo h($e) . '<br>'; ?></div><?php endif; ?>

<div class="category-grid" style="grid-template-columns: 1fr 320px; align-items:start;">
    <div class="glass admin-table-wrap" style="padding:8px 20px;">
        <table class="admin-table">
            <thead><tr><th></th><th>Name</th><th>Category</th><th>Tag</th><th>Upcoming</th><th>Actions</th></tr></thead>
            <tbody>
            <?php foreach ($items as $item): ?>
                <tr>
                    <td data-label="Image"><img class="thumb-sm" src="<?= h($item['image_url'] ?: 'https://picsum.photos/seed/' . $item['item_id'] . '/100/100') ?>" alt=""></td>
                    <td data-label="Name"><?= h($item['name']) ?></td>
                    <td data-label="Category"><?= h($item['category_name']) ?></td>
                    <td data-label="Tag"><?= h($item['tag'] ?: '—') ?></td>
                    <td data-label="Upcoming"><?= $item['is_upcoming'] ? 'Yes' : 'No' ?></td>
                    <td data-label="Actions">
                        <div class="table-actions">
                            <a href="/fanhub/admin/merchandise.php?edit=<?= $item['item_id'] ?>" class="btn btn-ghost">Edit</a>
                            <form method="POST" onsubmit="return confirm('Delete this item?');">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="item_id" value="<?= $item['item_id'] ?>">
                                <button type="submit" class="btn btn-ghost" style="color:#ff8fc4;">Delete</button>
                            </form>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$items): ?><tr><td colspan="6"><p class="empty-note">No merchandise yet.</p></td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>

    <div class="glass" style="padding:24px;">
        <h3 style="margin-bottom:16px; font-size:16px;"><?= $edit ? 'Edit' : 'Add' ?> Merchandise</h3>
        <form method="POST" action="/fanhub/admin/merchandise.php">
            <input type="hidden" name="action" value="save">
            <input type="hidden" name="item_id" value="<?= (int) ($edit['item_id'] ?? 0) ?>">
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
                <label>Description</label>
                <textarea name="description" rows="3"><?= h($edit['description'] ?? '') ?></textarea>
            </div>
            <div class="form-group">
                <label>Image URL</label>
                <input type="text" name="image_url" value="<?= h($edit['image_url'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>Tag</label>
                <input type="text" name="tag" value="<?= h($edit['tag'] ?? '') ?>" placeholder="Limited Edition, New, Collectible...">
            </div>
            <div class="form-group">
                <label style="display:flex; align-items:center; gap:8px; cursor:pointer;">
                    <input type="checkbox" name="is_upcoming" value="1" <?= !empty($edit['is_upcoming']) ? 'checked' : '' ?> style="width:auto;"> Pre-Order / Upcoming
                </label>
            </div>
            <button type="submit" class="btn btn-gradient btn-block"><?= $edit ? 'Save Changes' : 'Add Item' ?></button>
            <?php if ($edit): ?><a href="/fanhub/admin/merchandise.php" class="btn btn-ghost btn-block" style="margin-top:8px;">Cancel</a><?php endif; ?>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/includes/layout_bottom.php'; ?>
