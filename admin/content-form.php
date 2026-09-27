<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/includes/guard.php';

$categories = get_all_categories($pdo);
$id = (int) ($_GET['id'] ?? $_POST['content_id'] ?? 0);
$item = null;
$errors = [];

if ($id) {
    $stmt = $pdo->prepare("SELECT * FROM content WHERE content_id = ?");
    $stmt->execute([$id]);
    $item = $stmt->fetch();
    if (!$item) redirect('/fanhub/admin/content.php');
}

$pageTitle = $item ? 'Edit Content' : 'Add Content';
$activeAdminNav = 'content';

$form = [
    'category_id' => $item['category_id'] ?? '',
    'title' => $item['title'] ?? '',
    'type' => $item['type'] ?? 'article',
    'description' => $item['description'] ?? '',
    'body' => $item['body'] ?? '',
    'image_url' => $item['image_url'] ?? '',
    'video_url' => $item['video_url'] ?? '',
    'audio_url' => $item['audio_url'] ?? '',
    'genre' => $item['genre'] ?? '',
    'tag' => $item['tag'] ?? '',
    'release_date' => $item['release_date'] ?? '',
    'popularity_score' => $item['popularity_score'] ?? 50,
    'status' => $item['status'] ?? 'approved',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $form['category_id'] = (int) ($_POST['category_id'] ?? 0);
    $form['title'] = trim($_POST['title'] ?? '');
    $form['type'] = $_POST['type'] ?? 'article';
    $form['description'] = trim($_POST['description'] ?? '');
    $form['body'] = trim($_POST['body'] ?? '');
    $form['image_url'] = trim($_POST['image_url'] ?? '');
    $form['video_url'] = trim($_POST['video_url'] ?? '');
    $form['audio_url'] = trim($_POST['audio_url'] ?? '');
    $form['genre'] = trim($_POST['genre'] ?? '');
    $form['tag'] = trim($_POST['tag'] ?? '');
    $form['release_date'] = $_POST['release_date'] ?? '';
    $form['popularity_score'] = (int) ($_POST['popularity_score'] ?? 50);
    $form['status'] = in_array($_POST['status'] ?? '', ['approved','pending','rejected']) ? $_POST['status'] : 'approved';

    if (!$form['category_id']) $errors[] = 'Please choose a category.';
    if (mb_strlen($form['title']) < 2) $errors[] = 'Please enter a title.';
    if (!in_array($form['type'], ['article','video','audio','image'])) $errors[] = 'Invalid content type.';
    if ($form['popularity_score'] < 0 || $form['popularity_score'] > 100) $errors[] = 'Popularity score must be between 0 and 100.';

    if (!$errors) {
        $releaseDate = $form['release_date'] ?: null;

        if ($item) {
            $stmt = $pdo->prepare("
                UPDATE content SET category_id=?, title=?, type=?, description=?, body=?, image_url=?, video_url=?, audio_url=?, genre=?, tag=?, release_date=?, popularity_score=?, status=?
                WHERE content_id=?
            ");
            $stmt->execute([
                $form['category_id'], $form['title'], $form['type'], $form['description'], $form['body'],
                $form['image_url'], $form['video_url'], $form['audio_url'], $form['genre'], $form['tag'],
                $releaseDate, $form['popularity_score'], $form['status'], $id,
            ]);
        } else {
            $stmt = $pdo->prepare("
                INSERT INTO content (category_id, title, type, description, body, image_url, video_url, audio_url, genre, tag, release_date, popularity_score, status, source_api)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'admin')
            ");
            $stmt->execute([
                $form['category_id'], $form['title'], $form['type'], $form['description'], $form['body'],
                $form['image_url'], $form['video_url'], $form['audio_url'], $form['genre'], $form['tag'],
                $releaseDate, $form['popularity_score'], $form['status'],
            ]);
        }
        redirect('/fanhub/admin/content.php?msg=saved');
    }
}

require_once __DIR__ . '/includes/layout_top.php';
?>

<div class="admin-topbar">
    <h1><?= $item ? 'Edit' : 'Add' ?> Content</h1>
    <a href="/fanhub/admin/content.php" class="btn btn-ghost btn-sm">&larr; Back to list</a>
</div>

<?php if ($errors): ?>
    <div class="form-alert error" style="max-width:none;"><?php foreach ($errors as $e) echo h($e) . '<br>'; ?></div>
<?php endif; ?>

<div class="category-grid" style="grid-template-columns: 1fr 320px; align-items:start;">
    <div class="glass" style="padding:30px;">
        <form method="POST" action="/fanhub/admin/content-form.php<?= $id ? '?id=' . $id : '' ?>" novalidate>
            <input type="hidden" name="content_id" value="<?= $id ?>">

            <div class="category-grid" style="grid-template-columns: 1fr 1fr; margin-bottom:0;">
                <div class="form-group">
                    <label for="title">Title</label>
                    <input type="text" id="title" name="title" value="<?= h($form['title']) ?>" required>
                </div>
                <div class="form-group">
                    <label for="category_id">Category</label>
                    <select id="category_id" name="category_id" required>
                        <option value="">Choose category</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= $cat['category_id'] ?>" <?= (int) $form['category_id'] === (int) $cat['category_id'] ? 'selected' : '' ?>><?= $cat['icon'] ?> <?= h($cat['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="category-grid" style="grid-template-columns: 1fr 1fr 1fr; margin-bottom:0;">
                <div class="form-group">
                    <label for="type">Type</label>
                    <select id="type" name="type">
                        <?php foreach (['article','video','audio','image'] as $t): ?>
                            <option value="<?= $t ?>" <?= $form['type'] === $t ? 'selected' : '' ?>><?= ucfirst($t) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="genre">Genre</label>
                    <input type="text" id="genre" name="genre" value="<?= h($form['genre']) ?>" placeholder="Action, Drama...">
                </div>
                <div class="form-group">
                    <label for="tag">Tag</label>
                    <input type="text" id="tag" name="tag" value="<?= h($form['tag']) ?>" placeholder="Trending, New, Limited Edition...">
                </div>
            </div>

            <div class="form-group">
                <label for="description">Short Description</label>
                <textarea id="description" name="description" rows="3"><?= h($form['description']) ?></textarea>
            </div>

            <div class="form-group">
                <label for="body">Full Article Body (optional)</label>
                <textarea id="body" name="body" rows="6"><?= h($form['body']) ?></textarea>
            </div>

            <div class="form-group">
                <label for="image_url">Image URL</label>
                <input type="text" id="image_url" name="image_url" value="<?= h($form['image_url']) ?>" placeholder="https://...">
            </div>
            <div class="category-grid" style="grid-template-columns: 1fr 1fr; margin-bottom:0;">
                <div class="form-group">
                    <label for="video_url">Video URL (optional)</label>
                    <input type="text" id="video_url" name="video_url" value="<?= h($form['video_url']) ?>" placeholder="https://...">
                </div>
                <div class="form-group">
                    <label for="audio_url">Audio URL (optional)</label>
                    <input type="text" id="audio_url" name="audio_url" value="<?= h($form['audio_url']) ?>" placeholder="https://...">
                </div>
            </div>

            <div class="category-grid" style="grid-template-columns: 1fr 1fr 1fr; margin-bottom:0;">
                <div class="form-group">
                    <label for="release_date">Release Date</label>
                    <input type="date" id="release_date" name="release_date" value="<?= h($form['release_date']) ?>">
                </div>
                <div class="form-group">
                    <label for="popularity_score">Popularity (0-100)</label>
                    <input type="number" id="popularity_score" name="popularity_score" min="0" max="100" value="<?= (int) $form['popularity_score'] ?>">
                </div>
                <div class="form-group">
                    <label for="status">Status</label>
                    <select id="status" name="status">
                        <?php foreach (['approved','pending','rejected'] as $s): ?>
                            <option value="<?= $s ?>" <?= $form['status'] === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <button type="submit" class="btn btn-gradient"><?= $item ? 'Save Changes' : 'Add Content' ?></button>
        </form>
    </div>

    <div class="glass" style="padding:22px;">
        <label style="font-size:13px; font-weight:700; color:var(--text-dim); display:block; margin-bottom:10px;">Image Preview</label>
        <img id="imgPreview" src="<?= h($form['image_url'] ?: 'https://picsum.photos/seed/preview/400/300') ?>" style="width:100%; border-radius:12px; aspect-ratio:4/3; object-fit:cover;">
    </div>
</div>

<script>
document.getElementById('image_url').addEventListener('input', function () {
    document.getElementById('imgPreview').src = this.value || 'https://picsum.photos/seed/preview/400/300';
});
</script>

<?php require_once __DIR__ . '/includes/layout_bottom.php'; ?>
