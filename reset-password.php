<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';
if (session_status() === PHP_SESSION_NONE) session_start();

$pageTitle = 'Reset Password';
$token = $_GET['token'] ?? $_POST['token'] ?? '';
$errors = [];

$stmt = $pdo->prepare("SELECT * FROM users WHERE reset_token = ? AND reset_token_expires > NOW()");
$stmt->execute([$token]);
$user = $stmt->fetch();

if (!$user) {
    $errors[] = 'This reset link is invalid or has expired. Please request a new one.';
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    if (mb_strlen($password) < 6) $errors[] = 'Password must be at least 6 characters.';
    if ($password !== $confirm) $errors[] = 'Passwords do not match.';

    if (!$errors) {
        $stmt = $pdo->prepare("UPDATE users SET password_hash = ?, reset_token = NULL, reset_token_expires = NULL WHERE user_id = ?");
        $stmt->execute([password_hash($password, PASSWORD_BCRYPT), $user['user_id']]);
        redirect('/fanhub/login.php?reset=1');
    }
}

require_once __DIR__ . '/includes/header.php';
?>
<section class="section" style="padding-top:170px; min-height:70vh;">
    <div class="container">
        <div class="glass form-card reveal in-view">
            <h2 class="text-gradient" style="text-align:center; font-size:24px;">Set a new password</h2>

            <?php if ($errors): ?>
                <div class="form-alert error"><?php foreach ($errors as $e) echo h($e) . '<br>'; ?></div>
            <?php endif; ?>

            <?php if ($user): ?>
                <form method="POST" action="/fanhub/reset-password.php" novalidate>
                    <input type="hidden" name="token" value="<?= h($token) ?>">
                    <div class="form-group">
                        <label for="password">New Password</label>
                        <input type="password" id="password" name="password" minlength="6" required autofocus>
                    </div>
                    <div class="form-group">
                        <label for="confirm_password">Confirm New Password</label>
                        <input type="password" id="confirm_password" name="confirm_password" minlength="6" required>
                    </div>
                    <button type="submit" class="btn btn-gradient btn-block">Update Password</button>
                </form>
            <?php else: ?>
                <p style="text-align:center;"><a href="/fanhub/forgot-password.php" class="btn btn-gradient">Request a new link</a></p>
            <?php endif; ?>
        </div>
    </div>
</section>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
