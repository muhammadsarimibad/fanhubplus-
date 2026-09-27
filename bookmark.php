<?php
// AJAX endpoint: toggles a bookmark for content, a character or a merch item.
// Called by the heart buttons across the site.
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';
if (session_status() === PHP_SESSION_NONE) session_start();

header('Content-Type: application/json');

if (!is_logged_in()) {
    http_response_code(401);
    echo json_encode(['error' => 'login_required', 'redirect' => '/fanhub/login.php']);
    exit;
}

$itemId = (int) ($_POST['content_id'] ?? 0);
$itemType = in_array($_POST['item_type'] ?? 'content', ['content', 'character', 'merchandise']) ? $_POST['item_type'] : 'content';

if (!$itemId) {
    http_response_code(400);
    echo json_encode(['error' => 'invalid_item']);
    exit;
}

$userId = current_user_id();

$stmt = $pdo->prepare("SELECT bookmark_id FROM bookmarks WHERE user_id = ? AND item_type = ? AND item_id = ?");
$stmt->execute([$userId, $itemType, $itemId]);
$existing = $stmt->fetch();

if ($existing) {
    $pdo->prepare("DELETE FROM bookmarks WHERE bookmark_id = ?")->execute([$existing['bookmark_id']]);
    echo json_encode(['bookmarked' => false]);
} else {
    $pdo->prepare("INSERT INTO bookmarks (user_id, item_type, item_id) VALUES (?, ?, ?)")->execute([$userId, $itemType, $itemId]);
    echo json_encode(['bookmarked' => true]);
}
