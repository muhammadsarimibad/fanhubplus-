<?php
// AJAX endpoint: saves a logged-in user's dark/light mode choice to their
// account so it follows them across devices (guests just use localStorage).
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';
if (session_status() === PHP_SESSION_NONE) session_start();

header('Content-Type: application/json');

if (!is_logged_in()) {
    http_response_code(401);
    echo json_encode(['error' => 'login_required']);
    exit;
}

$darkMode = ($_POST['dark_mode'] ?? '1') === '1' ? 1 : 0;
$pdo->prepare("UPDATE users SET dark_mode = ? WHERE user_id = ?")->execute([$darkMode, current_user_id()]);
$_SESSION['dark_mode'] = $darkMode;

echo json_encode(['ok' => true]);
