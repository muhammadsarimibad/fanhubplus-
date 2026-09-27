<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';
if (session_status() === PHP_SESSION_NONE) session_start();
if (is_logged_in()) redirect('/fanhub/dashboard.php');

$pageTitle = 'Sign Up';
$errors = [];
$name = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim(strtolower($_POST['email'] ?? ''));
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    if (mb_strlen($name) < 2) $errors[] = 'Please enter your full name.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Please enter a valid email address.';
    if (mb_strlen($password) < 6) $errors[] = 'Password must be at least 6 characters.';
    if ($password !== $confirm) $errors[] = 'Passwords do not match.';

    if (!$errors && get_user_by_email($pdo, $email)) {
        $errors[] = 'An account with this email already exists. Try logging in instead.';
    }

    if (!$errors) {
        $stmt = $pdo->prepare("INSERT INTO users (name, email, password_hash, role) VALUES (?, ?, ?, 'user')");
        $stmt->execute([$name, $email, password_hash($password, PASSWORD_BCRYPT)]);
        $user = get_user_by_id($pdo, $pdo->lastInsertId());
        log_in_user($user);
        redirect('/fanhub/dashboard.php?welcome=1');
    }
}

require_once __DIR__ . '/includes/header.php';
?>
<section class="section" style="padding-top:170px; min-height:70vh;">
    <div class="container">
        <div class="glass form-card reveal in-view">
            <h2 class="text-gradient" style="text-align:center; font-size:26px;">Create your account</h2>
            <p style="color:var(--text-dim); text-align:center; margin:10px 0 26px; font-size:13.5px;">Join Fan Hub Plus to bookmark, rate and submit content.</p>

            <?php if ($errors): ?>
                <div class="form-alert error">
                    <?php foreach ($errors as $e) echo h($e) . '<br>'; ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="/fanhub/register.php" novalidate>
                <div class="form-group">
                    <label for="name">Full Name</label>
                    <input type="text" id="name" name="name" value="<?= h($name) ?>" required>
                </div>
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" value="<?= h($email) ?>" required>
                </div>
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" minlength="6" required>
                </div>
                <div class="form-group">
                    <label for="confirm_password">Confirm Password</label>
                    <input type="password" id="confirm_password" name="confirm_password" minlength="6" required>
                </div>
                <button type="submit" class="btn btn-gradient btn-block">Sign Up</button>
            </form>

            <p style="text-align:center; font-size:13.5px; color:var(--text-dim); margin-top:20px;">
                Already have an account? <a href="/fanhub/login.php" style="color:var(--cyan); font-weight:700;">Log in</a>
            </p>
        </div>
    </div>
</section>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
