<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';
if (session_status() === PHP_SESSION_NONE) session_start();
if (is_logged_in()) redirect('/fanhub/dashboard.php');

$pageTitle = 'Login';
$errors = [];
$email = '';
$justReset = isset($_GET['reset']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim(strtolower($_POST['email'] ?? ''));
    $password = $_POST['password'] ?? '';

    $user = get_user_by_email($pdo, $email);

    if (!$user || !password_verify($password, $user['password_hash'])) {
        $errors[] = 'Invalid email or password.';
    } else {
        log_in_user($user);
        redirect('/fanhub/dashboard.php');
    }
}

require_once __DIR__ . '/includes/header.php';
?>
<section class="section" style="padding-top:170px; min-height:70vh;">
    <div class="container">
        <div class="glass form-card reveal in-view">
            <h2 class="text-gradient" style="text-align:center; font-size:26px;">Welcome back</h2>
            <p style="color:var(--text-dim); text-align:center; margin:10px 0 26px; font-size:13.5px;">Log in to your Fan Hub Plus account.</p>

            <?php if ($justReset): ?>
                <div class="form-alert success">Password reset! You can now log in with your new password.</div>
            <?php endif; ?>
            <?php if ($errors): ?>
                <div class="form-alert error"><?php foreach ($errors as $e) echo h($e) . '<br>'; ?></div>
            <?php endif; ?>

            <form method="POST" action="/fanhub/login.php" novalidate>
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" value="<?= h($email) ?>" required autofocus>
                </div>
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required>
                </div>
                <div style="text-align:right; margin:-8px 0 18px;">
                    <a href="/fanhub/forgot-password.php" style="font-size:12.5px; color:var(--text-dim);">Forgot password?</a>
                </div>
                <button type="submit" class="btn btn-gradient btn-block">Log In</button>
            </form>

            <p style="text-align:center; font-size:13.5px; color:var(--text-dim); margin-top:20px;">
                New here? <a href="/fanhub/register.php" style="color:var(--cyan); font-weight:700;">Create an account</a>
            </p>
            <p style="text-align:center; font-size:11.5px; color:var(--text-dimmer); margin-top:14px;">
                Admin demo login: admin@fanhub.local / Admin@123
            </p>
        </div>
    </div>
</section>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
