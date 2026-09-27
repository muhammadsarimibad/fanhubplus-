<?php
// Expects $pageTitle and $activeAdminNav to be set before including this file.
if (!isset($pageTitle)) $pageTitle = 'Admin';
if (!isset($activeAdminNav)) $activeAdminNav = '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= h($pageTitle) ?> | Fan Hub Plus Admin</title>
<link rel="stylesheet" href="/fanhub/assets/css/style.css?v=<?= filemtime(__DIR__ . '/../../assets/css/style.css') ?>">
<link rel="stylesheet" href="/fanhub/assets/css/admin.css?v=<?= filemtime(__DIR__ . '/../../assets/css/admin.css') ?>">
</head>
<body>
<div class="bg-blobs"><span></span><span></span><span></span><span></span></div>

<div class="admin-shell">
    <aside class="admin-sidebar glass">
        <a href="/fanhub/admin/index.php" class="brand">
            <svg class="brand-logo" viewBox="0 0 64 64" xmlns="http://www.w3.org/2000/svg" width="34" height="34">
                <path d="M32,8 L55,24 L9,24 Z" fill="#FF9A3D"/>
                <rect x="13" y="22" width="38" height="38" rx="8" fill="#7A5CFF"/>
                <path d="M30,0 H34 V4 H38 V8 H34 V12 H30 V8 H26 V4 H30 Z" fill="#ffffff"/>
                <g transform="translate(32,41)">
                    <g class="fan-blades" fill="#07050c">
                        <path d="M-2,-5 L-5,-16 Q0,-19 5,-16 L2,-5 Z" transform="rotate(45)"/>
                        <path d="M-2,-5 L-5,-16 Q0,-19 5,-16 L2,-5 Z" transform="rotate(135)"/>
                        <path d="M-2,-5 L-5,-16 Q0,-19 5,-16 L2,-5 Z" transform="rotate(225)"/>
                        <path d="M-2,-5 L-5,-16 Q0,-19 5,-16 L2,-5 Z" transform="rotate(315)"/>
                    </g>
                    <circle r="5" fill="#07050c"/>
                </g>
            </svg>
            <span class="brand-name" style="font-size:13px;">FAN HUB<span class="plus">+</span> ADMIN</span>
        </a>
        <nav class="admin-nav">
            <a href="/fanhub/admin/index.php" class="<?= $activeAdminNav === 'dashboard' ? 'active' : '' ?>">📊 Dashboard</a>
            <a href="/fanhub/admin/content.php" class="<?= $activeAdminNav === 'content' ? 'active' : '' ?>">🗂️ Content</a>
            <a href="/fanhub/admin/characters.php" class="<?= $activeAdminNav === 'characters' ? 'active' : '' ?>">🧑 Characters</a>
            <a href="/fanhub/admin/merchandise.php" class="<?= $activeAdminNav === 'merchandise' ? 'active' : '' ?>">🛍️ Merchandise</a>
            <a href="/fanhub/admin/feedback.php" class="<?= $activeAdminNav === 'feedback' ? 'active' : '' ?>">💬 Feedback</a>
        </nav>
        <a href="/fanhub/index.php" class="back-link">&larr; Back to site</a>
        <a href="/fanhub/logout.php" class="back-link">Logout <span class="admin-username-full">(<?= h($_SESSION['user_name'] ?? '') ?>)</span></a>
    </aside>

    <main class="admin-main">
