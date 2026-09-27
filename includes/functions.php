<?php
// Small shared helper functions used across the site.

function h($value) {
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

// Renders an emoji as a crisp Twemoji SVG image instead of the raw character,
// so category icons look identical (and colorful) on every browser/OS.
function icon_img($emoji, $size = 24, $class = '') {
    $codepoints = [];
    $chars = preg_split('//u', $emoji, -1, PREG_SPLIT_NO_EMPTY);
    foreach ($chars as $char) {
        $code = mb_ord($char, 'UTF-8');
        if ($code === 0xFE0F) continue; // skip variation selector
        $codepoints[] = dechex($code);
    }
    $file = implode('-', $codepoints);
    $url = "https://cdn.jsdelivr.net/gh/twitter/twemoji@14.0.2/assets/svg/{$file}.svg";
    return '<img src="' . h($url) . '" alt="" class="icon-img ' . h($class) . '" style="width:' . (int) $size . 'px; height:' . (int) $size . 'px; vertical-align:middle; display:inline-block;">';
}

function get_all_categories(PDO $pdo) {
    return $pdo->query("SELECT * FROM categories ORDER BY category_id ASC")->fetchAll();
}

function get_category_by_slug(PDO $pdo, $slug) {
    $stmt = $pdo->prepare("SELECT * FROM categories WHERE slug = ?");
    $stmt->execute([$slug]);
    return $stmt->fetch();
}

function get_content_by_category(PDO $pdo, $category_id, $limit = 8) {
    $limit = (int) $limit;
    $stmt = $pdo->prepare("
        SELECT * FROM content
        WHERE category_id = ? AND status = 'approved'
        ORDER BY popularity_score DESC, content_id DESC
        LIMIT $limit
    ");
    $stmt->execute([$category_id]);
    return $stmt->fetchAll();
}

function get_latest_content(PDO $pdo, $limit = 12) {
    $limit = (int) $limit;
    return $pdo->query("
        SELECT content.*, categories.name AS category_name, categories.slug AS category_slug, categories.color AS category_color
        FROM content
        JOIN categories ON categories.category_id = content.category_id
        WHERE content.status = 'approved'
        ORDER BY content.content_id DESC
        LIMIT $limit
    ")->fetchAll();
}

function time_ago($datetime) {
    if (!$datetime) return '';
    $diff = time() - strtotime($datetime);
    if ($diff < 60) return 'just now';
    if ($diff < 3600) return floor($diff / 60) . 'm ago';
    if ($diff < 86400) return floor($diff / 3600) . 'h ago';
    if ($diff < 2592000) return floor($diff / 86400) . 'd ago';
    return date('M Y', strtotime($datetime));
}

function is_logged_in() {
    return !empty($_SESSION['user_id']);
}

function is_admin() {
    return is_logged_in() && ($_SESSION['role'] ?? '') === 'admin';
}

function current_user_id() {
    return $_SESSION['user_id'] ?? null;
}

function redirect($path) {
    header("Location: $path");
    exit;
}

function get_user_by_id(PDO $pdo, $user_id) {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
    $stmt->execute([$user_id]);
    return $stmt->fetch();
}

function get_user_by_email(PDO $pdo, $email) {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    return $stmt->fetch();
}

function get_distinct_genres(PDO $pdo) {
    $rows = $pdo->query("SELECT DISTINCT genre FROM content WHERE status = 'approved' AND genre IS NOT NULL AND genre != ''")->fetchAll();
    $genres = [];
    foreach ($rows as $row) {
        foreach (explode(',', $row['genre']) as $g) {
            $g = trim($g);
            if ($g !== '') $genres[$g] = true;
        }
    }
    $list = array_keys($genres);
    sort($list, SORT_STRING | SORT_FLAG_CASE);
    return $list;
}

function get_distinct_years(PDO $pdo) {
    $rows = $pdo->query("SELECT DISTINCT YEAR(release_date) AS y FROM content WHERE status = 'approved' AND release_date IS NOT NULL ORDER BY y DESC")->fetchAll();
    return array_column($rows, 'y');
}

function get_user_bookmarked_content_ids(PDO $pdo, $userId) {
    return get_user_bookmarked_ids($pdo, $userId, 'content');
}

function get_user_bookmarked_ids(PDO $pdo, $userId, $itemType = 'content') {
    if (!$userId) return [];
    $stmt = $pdo->prepare("SELECT item_id FROM bookmarks WHERE user_id = ? AND item_type = ?");
    $stmt->execute([$userId, $itemType]);
    return array_map('intval', array_column($stmt->fetchAll(), 'item_id'));
}

function log_in_user(array $user) {
    session_regenerate_id(true);
    $_SESSION['user_id'] = $user['user_id'];
    $_SESSION['user_name'] = $user['name'];
    $_SESSION['role'] = $user['role'];
    $_SESSION['avatar'] = $user['avatar'];
    $_SESSION['dark_mode'] = (int) $user['dark_mode'];
}

