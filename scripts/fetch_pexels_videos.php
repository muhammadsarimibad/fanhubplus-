<?php
// Replaces YouTube-embedded trailers with real, permanently-hosted stock
// video files from the free Pexels Videos API — no YouTube branding, no
// expiring links, just direct MP4 files served from Pexels' own CDN.
// Run: http://localhost/fanhub/scripts/fetch_pexels_videos.php

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/fetch_helpers.php';
require_once __DIR__ . '/image_helpers.php'; // gives us PEXELS_API_KEY

set_time_limit(180);

function fh_pexels_video($query, $page = 1) {
    $data = fh_get_json(
        'https://api.pexels.com/videos/search?query=' . urlencode($query) . '&per_page=1&page=' . (int) $page . '&orientation=landscape',
        ['Authorization: ' . PEXELS_API_KEY]
    );
    $video = $data['videos'][0] ?? null;
    if (!$video) return null;

    // Pick a mid-quality file (keeps page load fast) if available, else the first.
    $files = $video['video_files'] ?? [];
    usort($files, fn($a, $b) => ($a['width'] ?? 0) <=> ($b['width'] ?? 0));
    foreach ($files as $f) {
        if (($f['width'] ?? 0) >= 640 && ($f['width'] ?? 0) <= 1280) return $f['link'];
    }
    return $files[0]['link'] ?? null;
}

$categoryQueries = [
    'anime' => 'japan city night neon',
    'movies' => 'cinematic movie scene',
    'tv-shows' => 'tv drama scene',
];

$stmt = $pdo->query("
    SELECT content.content_id, content.title, content.genre, categories.slug AS category_slug
    FROM content JOIN categories ON categories.category_id = content.category_id
    WHERE content.type = 'video'
");
$rows = $stmt->fetchAll();

$queryCounts = [];
$updated = 0; $failed = 0;
foreach ($rows as $row) {
    $firstGenre = $row['genre'] ? trim(explode(',', $row['genre'])[0]) : '';
    $query = $firstGenre ? "$firstGenre cinematic scene" : ($categoryQueries[$row['category_slug']] ?? 'cinematic scene');

    // Rotate through result pages so items sharing a genre don't all get the same clip.
    $queryCounts[$query] = ($queryCounts[$query] ?? 0) + 1;
    $videoUrl = fh_pexels_video($query, $queryCounts[$query]);
    if (!$videoUrl && $firstGenre) {
        $videoUrl = fh_pexels_video($categoryQueries[$row['category_slug']] ?? 'cinematic scene');
    }

    if ($videoUrl) {
        $pdo->prepare("UPDATE content SET video_url = ? WHERE content_id = ?")->execute([$videoUrl, $row['content_id']]);
        $updated++;
    } else {
        $pdo->prepare("UPDATE content SET video_url = NULL WHERE content_id = ?")->execute([$row['content_id']]);
        $failed++;
    }
    usleep(300000);
}

echo "Updated with Pexels videos: $updated, failed: $failed.";
