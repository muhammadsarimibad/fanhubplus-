<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/includes/guard.php';

$pageTitle = 'Feedback';
$activeAdminNav = 'feedback';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $id = (int) ($_POST['feedback_id'] ?? 0);
    if ($action === 'delete') {
        $pdo->prepare("DELETE FROM feedback WHERE feedback_id = ?")->execute([$id]);
    } elseif ($action === 'status') {
        $status = in_array($_POST['status'] ?? '', ['new', 'reviewed', 'resolved']) ? $_POST['status'] : 'new';
        $pdo->prepare("UPDATE feedback SET status = ? WHERE feedback_id = ?")->execute([$status, $id]);
    }
    redirect('/fanhub/admin/feedback.php');
}

$statusFilter = $_GET['status'] ?? '';
$where = $statusFilter ? 'WHERE feedback.status = ' . $pdo->quote($statusFilter) : '';

$items = $pdo->query("
    SELECT feedback.*, users.name AS user_name, users.email AS user_email
    FROM feedback
    LEFT JOIN users ON users.user_id = feedback.user_id
    $where
    ORDER BY feedback.created_at DESC
")->fetchAll();

$typeIcons = ['bug' => '🐞', 'suggestion' => '💡', 'query' => '❓'];

require_once __DIR__ . '/includes/layout_top.php';
?>
<div class="admin-topbar">
    <h1>Feedback Inbox</h1>
    <div class="filter-bar" style="margin-bottom:0;">
        <a href="/fanhub/admin/feedback.php" class="btn btn-sm <?= !$statusFilter ? 'btn-gradient' : 'btn-ghost' ?>">All</a>
        <a href="/fanhub/admin/feedback.php?status=new" class="btn btn-sm <?= $statusFilter === 'new' ? 'btn-gradient' : 'btn-ghost' ?>">New</a>
        <a href="/fanhub/admin/feedback.php?status=reviewed" class="btn btn-sm <?= $statusFilter === 'reviewed' ? 'btn-gradient' : 'btn-ghost' ?>">Reviewed</a>
        <a href="/fanhub/admin/feedback.php?status=resolved" class="btn btn-sm <?= $statusFilter === 'resolved' ? 'btn-gradient' : 'btn-ghost' ?>">Resolved</a>
    </div>
</div>

<div style="display:flex; flex-direction:column; gap:14px;">
    <?php foreach ($items as $item): ?>
        <div class="glass" style="padding:20px 24px;">
            <div style="display:flex; justify-content:space-between; align-items:flex-start; gap:16px; flex-wrap:wrap;">
                <div>
                    <div style="display:flex; align-items:center; gap:10px; margin-bottom:8px;">
                        <span style="font-size:18px;"><?= $typeIcons[$item['type']] ?? '💬' ?></span>
                        <strong style="text-transform:capitalize;"><?= h($item['type']) ?></strong>
                        <span class="badge-status <?= $item['status'] === 'new' ? 'pending' : ($item['status'] === 'resolved' ? 'approved' : 'rejected') ?>"><?= h($item['status']) ?></span>
                    </div>
                    <p style="color:var(--text-dim); font-size:13.5px; max-width:600px;"><?= nl2br(h($item['message'])) ?></p>
                    <p style="font-size:11.5px; color:var(--text-dimmer); margin-top:8px;">
                        <?= $item['user_name'] ? h($item['user_name']) . ' (' . h($item['user_email']) . ')' : 'Guest' ?> &middot; <?= time_ago($item['created_at']) ?>
                    </p>
                </div>
                <div style="display:flex; gap:8px; flex-wrap:wrap; align-items:center;">
                    <form method="POST" style="display:flex; gap:6px; align-items:center;">
                        <input type="hidden" name="action" value="status">
                        <input type="hidden" name="feedback_id" value="<?= $item['feedback_id'] ?>">
                        <select name="status" onchange="this.form.submit()" style="padding:6px 10px; border-radius:8px; background:var(--surface); border:1px solid var(--border); color:var(--text); font-size:12px;">
                            <option value="new" <?= $item['status'] === 'new' ? 'selected' : '' ?>>New</option>
                            <option value="reviewed" <?= $item['status'] === 'reviewed' ? 'selected' : '' ?>>Reviewed</option>
                            <option value="resolved" <?= $item['status'] === 'resolved' ? 'selected' : '' ?>>Resolved</option>
                        </select>
                    </form>
                    <form method="POST" onsubmit="return confirm('Delete this feedback?');">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="feedback_id" value="<?= $item['feedback_id'] ?>">
                        <button type="submit" class="btn btn-ghost btn-sm" style="color:#ff8fc4;">Delete</button>
                    </form>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
    <?php if (!$items): ?><p class="empty-note">No feedback yet.</p><?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/layout_bottom.php'; ?>
