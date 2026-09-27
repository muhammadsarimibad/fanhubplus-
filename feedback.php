<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';
if (session_status() === PHP_SESSION_NONE) session_start();

$pageTitle = 'Feedback';
$success = false;
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $type = in_array($_POST['type'] ?? '', ['bug', 'suggestion', 'query']) ? $_POST['type'] : 'suggestion';
    $message = trim($_POST['message'] ?? '');

    if (mb_strlen($message) < 5) $errors[] = 'Please write a bit more detail in your message.';

    if (!$errors) {
        $stmt = $pdo->prepare("INSERT INTO feedback (user_id, type, message) VALUES (?, ?, ?)");
        $stmt->execute([current_user_id(), $type, $message]);
        $success = true;
    }
}

require_once __DIR__ . '/includes/header.php';
?>
<section class="section" style="padding-top:170px; min-height:70vh;">
    <div class="container">
        <div class="glass form-card reveal in-view" style="max-width:520px;">
            <h2 class="text-gradient" style="text-align:center; font-size:24px;">Feedback &amp; Suggestions</h2>
            <p style="color:var(--text-dim); text-align:center; margin:10px 0 26px; font-size:13.5px;">Found a bug? Have an idea? Just want to say hi? We'd love to hear it.</p>

            <?php if ($success): ?>
                <div class="form-alert success">Thanks! Your feedback has been sent to the team.</div>
                <p style="text-align:center;"><a href="/fanhub/index.php" class="btn btn-gradient">Back to Home</a></p>
            <?php else: ?>
                <?php if ($errors): ?>
                    <div class="form-alert error"><?php foreach ($errors as $e) echo h($e) . '<br>'; ?></div>
                <?php endif; ?>
                <form method="POST" action="/fanhub/feedback.php" novalidate>
                    <div class="form-group">
                        <label for="type">Type</label>
                        <select id="type" name="type">
                            <option value="suggestion">💡 Suggestion</option>
                            <option value="bug">🐞 Bug Report</option>
                            <option value="query">❓ Question</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="message">Your Message</label>
                        <textarea id="message" name="message" rows="5" placeholder="Tell us what's on your mind..." required></textarea>
                    </div>
                    <button type="submit" class="btn btn-gradient btn-block">Send Feedback</button>
                    <?php if (!is_logged_in()): ?>
                        <p style="font-size:11.5px; color:var(--text-dimmer); text-align:center; margin-top:14px;">You're submitting as a guest. <a href="/fanhub/login.php" style="color:var(--cyan);">Log in</a> to let us follow up with you.</p>
                    <?php endif; ?>
                </form>
            <?php endif; ?>
        </div>
    </div>
</section>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
