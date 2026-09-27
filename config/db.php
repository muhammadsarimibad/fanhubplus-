<?php
// Match PHP's clock to the system/MySQL timezone so that things like
// "time ago" labels and reset-link expiry stay consistent with the database.
date_default_timezone_set('Asia/Karachi');

// Database connection settings for XAMPP's default MySQL setup.
// Change these only if your MySQL username/password is different.
define('DB_HOST', 'localhost');
define('DB_NAME', 'fanhub');
define('DB_USER', 'root');
define('DB_PASS', '');

try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );
} catch (PDOException $e) {
    die('
        <div style="font-family: sans-serif; max-width: 600px; margin: 60px auto; padding: 24px; border: 1px solid #FF3D9A; border-radius: 12px; background:#1a0f1f; color:#fff;">
            <h2 style="color:#FF3D9A;">Database not connected</h2>
            <p>Fan Hub Plus could not connect to the <code>fanhub</code> database.</p>
            <p>Please check:</p>
            <ol>
                <li>XAMPP MySQL is started (green in the control panel)</li>
                <li>You imported <code>database/fanhub.sql</code> in phpMyAdmin</li>
                <li>The settings in <code>config/db.php</code> match your MySQL login</li>
            </ol>
            <p style="opacity:.7;font-size:13px;">Technical detail: ' . htmlspecialchars($e->getMessage()) . '</p>
        </div>
    ');
}
