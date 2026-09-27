<?php
// Uses free Unsplash/Pexels stock photos to:
//   1. Give the Cosplay category real cosplay photography (was using
//      generic convention building/logo photos from Wikipedia before).
//   2. Fill in the couple of content items that came back with no image.
// Run: http://localhost/fanhub/scripts/refresh_stock_images.php

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/fetch_helpers.php';
require_once __DIR__ . '/image_helpers.php';

set_time_limit(120);
$updated = 0; $failed = 0;

// --- Cosplay category: give each convention entry a real cosplay photo ---
$stmt = $pdo->prepare("
    SELECT content_id FROM content
    WHERE category_id = (SELECT category_id FROM categories WHERE slug = 'cosplay')
    ORDER BY content_id
");
$stmt->execute();
$cosplayIds = array_column($stmt->fetchAll(), 'content_id');

foreach ($cosplayIds as $i => $contentId) {
    $url = fh_stock_photo('cosplay costume convention', $i);
    if ($url) {
        $pdo->prepare("UPDATE content SET image_url = ? WHERE content_id = ?")->execute([$url, $contentId]);
        $updated++;
    } else {
        $failed++;
    }
    usleep(300000);
}

// --- Fill any content still missing an image with a relevant stock photo ---
$stmt = $pdo->query("SELECT content_id, title, genre FROM content WHERE image_url IS NULL OR image_url = ''");
foreach ($stmt->fetchAll() as $row) {
    $query = $row['title'] . ' ' . ($row['genre'] ?: 'poster');
    $url = fh_stock_photo($query);
    if ($url) {
        $pdo->prepare("UPDATE content SET image_url = ? WHERE content_id = ?")->execute([$url, $row['content_id']]);
        $updated++;
    } else {
        $failed++;
    }
    usleep(300000);
}

echo "Updated: $updated, failed: $failed.";
