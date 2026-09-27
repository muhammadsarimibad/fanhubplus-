<?php
// Replaces the Deezer widget (which showed "not available in your region"
// for some tracks) with real, permanently-hosted, Creative-Commons-licensed
// music from the free Jamendo API — streams directly on our own site via a
// native <audio> tag, no redirect, no region lock.
// Run: http://localhost/fanhub/scripts/fetch_jamendo_kpop.php

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/ai.php';
require_once __DIR__ . '/fetch_helpers.php';

set_time_limit(120);

function fh_jamendo_track($query, $offset = 0) {
    $url = 'https://api.jamendo.com/v3.0/tracks/?client_id=' . JAMENDO_CLIENT_ID
        . '&format=json&limit=1&offset=' . (int) $offset
        . '&search=' . urlencode($query) . '&audioformat=mp32';
    $data = fh_get_json($url);
    return $data['results'][0] ?? null;
}

$stmt = $pdo->query("SELECT content_id, title FROM content WHERE source_api = 'deezer'");
$rows = $stmt->fetchAll();

$queries = ['kpop', 'korean pop', 'kpop dance', 'kpop girl group', 'kpop boy group'];

$updated = 0; $failed = 0;
foreach ($rows as $i => $row) {
    $query = $queries[$i % count($queries)];
    $track = fh_jamendo_track($query, intdiv($i, count($queries)));

    if (!$track) { $failed++; continue; }

    $pdo->prepare("UPDATE content SET audio_url = ?, image_url = ?, source_api = 'jamendo', external_id = ? WHERE content_id = ?")
        ->execute([$track['audio'], $track['album_image'], $track['id'], $row['content_id']]);
    $updated++;
    usleep(250000);
}

echo "Updated with Jamendo tracks: $updated, failed: $failed.";
