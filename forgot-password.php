<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';
if (session_status() === PHP_SESSION_NONE) session_start();

$pageTitle = 'Forgot Password';
$submitted = false;
$resetLink = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim(strtolower($_POST['email'] ?? ''));
    $user = get_user_by_email($pdo, $email);
    $submitted = true;

    if ($user) {
        $token = bin2hex(random_bytes(32));

        // Uses MySQL's own clock (NOW() + INTERVAL) for both setting and later
        // checking the expiry, so it can't drift out of sync with PHP's timezone.
        $stmt = $pdo->prepare("UPDATE users SET reset_token = ?, reset_token_expires = NOW() + INTERVAL 1 HOUR WHERE user_id = ?");
        $stmt->execute([$token, $user['user_id']]);

        $resetLink = "http://{$_SERVER['HTTP_HOST']}/fanhub/reset-password.php?token=$token";

        // Best-effort real email (only works if XAMPP's mail/SMTP is configured).
        @mail($user['email'], 'Reset your Fan Hub Plus password', "Click to reset your password: $resetLink");
    }
}

require_once __DIR__ . '/includes/header.php';
?>
<section class="section" style="padding-top:170px; min-height:70vh;">
    <div class="container">
        <div class="glass form-card reveal in-view">
            <h2 class="text-gradient" style="text-align:center; font-size:24px;">Reset your password</h2>
            <p style="color:var(--text-dim); text-align:center; margin:10px 0 26px; font-size:13.5px;">Enter your account email and we'll create a reset link.</p>

            <?php if ($submitted): ?>
                <div class="form-alert success">
                    If that email is registered, a reset link has been created.
                </div>
                <?php if ($resetLink): ?>
                    <div class="form-alert" style="background: rgba(122,92,255,.12); border:1px solid rgba(122,92,255,.4); color:#c9beff;">
                        <strong>Local testing note:</strong> this project isn't connected to a real email server, so here's your reset link directly:<br>
                        <a href="<?= h($resetLink) ?>" style="color:var(--cyan); word-break:break-all;"><?= h($resetLink) ?></a>
                    </div>
                <?php endif; ?>
            <?php else: ?>
                <form method="POST" action="/fanhub/forgot-password.php" novalidate>
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" required autofocus>
                    </div>
                    <button type="submit" class="btn btn-gradient btn-block">Send Reset Link</button>
                </form>
            <?php endif; ?>

            <p style="text-align:center; font-size:13.5px; color:var(--text-dim); margin-top:20px;">
                <a href="/fanhub/login.php" style="color:var(--cyan); font-weight:700;">Back to login</a>
            </p>
        </div>
    </div>
</section>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
