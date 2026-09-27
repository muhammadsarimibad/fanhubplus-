<?php
// Imports K-Pop tracks (with real 30-second preview audio) from the free Deezer API (no key needed).

function fetch_kpop(PDO $pdo) {
    $categoryId = fh_category_id($pdo, 'kpop');
    if (!$categoryId) return ['imported' => 0, 'skipped' => 0];

    $queries = [
        'BTS' => 'BTS Dynamite', 'BLACKPINK' => 'BLACKPINK Kill This Love', 'NewJeans' => 'NewJeans Super Shy',
        'Stray Kids' => 'Stray Kids S-Class', 'TWICE' => 'TWICE Fancy', 'SEVENTEEN' => 'SEVENTEEN Super',
        'IVE' => 'IVE I AM', 'aespa' => 'aespa Supernova', 'ENHYPEN' => 'ENHYPEN Bite Me',
        'LE SSERAFIM' => 'LE SSERAFIM Easy', 'ITZY' => 'ITZY Cheshire', 'TOMORROW X TOGETHER' => 'TOMORROW X TOGETHER Deja Vu',
        'NCT DREAM' => 'NCT DREAM Smoothie', 'JUNG KOOK' => 'Jungkook Standing Next to You', 'PSY' => 'PSY Gangnam Style',
    ];

    $imported = 0; $skipped = 0; $i = 0;
    foreach ($queries as $expectedArtist => $q) {
        // Ask for a few results and pick the one that's really by the expected
        // artist (Deezer's catalog is full of covers that otherwise rank first).
        $data = fh_get_json('https://api.deezer.com/search?q=' . urlencode($q) . '&limit=5');
        $results = $data['data'] ?? [];
        $normalize = fn($s) => strtolower(str_replace(' ', '', $s));
        $track = null;
        foreach ($results as $r) {
            if (str_contains($normalize($r['artist']['name']), $normalize($expectedArtist))) {
                $track = $r;
                break;
            }
        }
        $track = $track ?? ($results[0] ?? null);
        if (!$track) { $skipped++; $i++; continue; }

        $ok = fh_upsert_content($pdo, [
            'category_id' => $categoryId,
            'title' => $track['title'] . ' — ' . $track['artist']['name'],
            'type' => 'audio',
            'description' => 'K-Pop track "' . $track['title'] . '" by ' . $track['artist']['name'] . ' from the album "' . $track['album']['title'] . '".',
            'image_url' => $track['album']['cover_big'] ?? $track['album']['cover_medium'] ?? null,
            'audio_url' => $track['preview'] ?? null,
            'genre' => 'K-Pop',
            'popularity_score' => fh_score($i),
            'source_api' => 'deezer',
            'external_id' => (string) $track['id'],
        ]);
        $ok ? $imported++ : $skipped++;
        $i++;
    }
    return ['imported' => $imported, 'skipped' => $skipped];
}
