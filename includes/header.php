<?php
// Expects (optionally) $pageTitle and $activeNav to be set before including this file.
if (!isset($pageTitle)) $pageTitle = 'Fan Hub Plus';
if (!isset($activeNav)) $activeNav = '';
if (session_status() === PHP_SESSION_NONE) session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= h($pageTitle) ?> | Fan Hub Plus</title>
<meta name="description" content="Fan Hub Plus — the Fandom Universe portal for Anime, Gaming, Movies, TV Shows, K-Pop, Comics, Manga and Cosplay fans.">
<link rel="icon" href="data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 100 100%22><circle cx=%2250%22 cy=%2250%22 r=%2245%22 fill=%22%237A5CFF%22/><text x=%2250%22 y=%2266%22 font-size=%2250%22 text-anchor=%22middle%22 fill=%22white%22 font-family=%22sans-serif%22>F</text></svg>">
<link rel="stylesheet" href="/fanhub/assets/css/style.css?v=<?= filemtime(__DIR__ . '/../assets/css/style.css') ?>">
</head>
<?php
$serverThemeClass = '';
if (is_logged_in() && isset($_SESSION['dark_mode']) && !$_SESSION['dark_mode']) {
    $serverThemeClass = 'light-mode';
}
?>
<body class="<?= h($serverThemeClass) ?>" data-logged-in="<?= is_logged_in() ? '1' : '0' ?>">
<div class="bg-blobs"><span></span><span></span><span></span><span></span></div>

<nav class="navbar">
    <div class="container">
        <a href="/fanhub/index.php" class="brand">
            <svg class="brand-logo" viewBox="0 0 64 64" xmlns="http://www.w3.org/2000/svg">
                <defs>
                    <linearGradient id="logoGrad" x1="0" y1="0" x2="1" y2="1">
                        <stop offset="0%" stop-color="#FF3D9A"/>
                        <stop offset="55%" stop-color="#7A5CFF"/>
                        <stop offset="100%" stop-color="#22E4FF"/>
                    </linearGradient>
                </defs>
                <path d="M32,8 L55,24 L9,24 Z" fill="#FF9A3D"/>
                <rect x="13" y="22" width="38" height="38" rx="8" fill="url(#logoGrad)"/>
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
            <span class="brand-name">FAN HUB<span class="plus">+</span></span>
        </a>

        <ul class="nav-links">
            <li><a href="/fanhub/index.php" class="<?= $activeNav === 'home' ? 'active' : '' ?>">Home</a></li>
            <li><a href="/fanhub/explore.php" class="<?= $activeNav === 'explore' ? 'active' : '' ?>">Explore</a></li>
            <li><a href="/fanhub/characters.php" class="<?= $activeNav === 'characters' ? 'active' : '' ?>">Characters</a></li>
            <li><a href="/fanhub/merchandise.php" class="<?= $activeNav === 'merchandise' ? 'active' : '' ?>">Merch</a></li>
            <li><a href="/fanhub/events.php" class="<?= $activeNav === 'events' ? 'active' : '' ?>">Events</a></li>
            <li><a href="/fanhub/index.php#sitemap" class="<?= $activeNav === 'sitemap' ? 'active' : '' ?>">Sitemap</a></li>
            <?php if (is_logged_in()): ?>
                <li><a href="/fanhub/dashboard.php" class="<?= $activeNav === 'dashboard' ? 'active' : '' ?>">Dashboard</a></li>
            <?php endif; ?>
            <li class="nav-auth-mobile">
                <?php if (is_logged_in()): ?>
                    <a href="/fanhub/logout.php" class="btn btn-ghost btn-sm btn-block">Logout</a>
                <?php else: ?>
                    <a href="/fanhub/login.php" class="btn btn-ghost btn-sm btn-block">Login</a>
                    <a href="/fanhub/register.php" class="btn btn-gradient btn-sm btn-block" style="margin-top:8px;">Sign Up</a>
                <?php endif; ?>
            </li>
        </ul>

        <div class="nav-actions">
            <button class="icon-btn" data-theme-toggle title="Toggle theme"><?= $serverThemeClass === 'light-mode' ? '🌙' : '☀️' ?></button>
            <div class="nav-auth-desktop">
                <?php if (is_logged_in()): ?>
                    <a href="/fanhub/dashboard.php" class="user-chip">
                        <?php if (!empty($_SESSION['avatar'])): ?>
                            <img src="/fanhub/<?= h($_SESSION['avatar']) ?>" alt="">
                        <?php else: ?>
                            <span class="avatar-fallback"><?= h(strtoupper(substr($_SESSION['user_name'] ?? 'U', 0, 1))) ?></span>
                        <?php endif; ?>
                        <?= h($_SESSION['user_name'] ?? 'Account') ?>
                    </a>
                    <a href="/fanhub/logout.php" class="btn btn-ghost btn-sm">Logout</a>
                <?php else: ?>
                    <a href="/fanhub/login.php" class="btn btn-ghost btn-sm">Login</a>
                    <a href="/fanhub/register.php" class="btn btn-gradient btn-sm">Sign Up</a>
                <?php endif; ?>
            </div>
            <button class="nav-toggle">☰</button>
        </div>
    </div>
</nav>
