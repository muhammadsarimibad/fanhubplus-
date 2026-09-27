<?php
// Imports games from the free FreeToGame API (no key needed).

function fetch_games(PDO $pdo) {
    $categoryId = fh_category_id($pdo, 'gaming');
    if (!$categoryId) return ['imported' => 0, 'skipped' => 0];

    $games = fh_get_json('https://www.freetogame.com/api/games');
    if (!$games) return ['imported' => 0, 'skipped' => 0];

    $games = array_slice($games, 0, 18);
    $imported = 0; $skipped = 0; $i = 0;

    foreach ($games as $g) {
        $ok = fh_upsert_content($pdo, [
            'category_id' => $categoryId,
            'title' => $g['title'],
            'type' => 'article',
            'description' => $g['short_description'] ?? null,
            'image_url' => $g['thumbnail'] ?? null,
            'genre' => $g['genre'] ?? null,
            'release_date' => $g['release_date'] ?: null,
            'popularity_score' => fh_score($i),
            'source_api' => 'freetogame',
            'external_id' => (string) $g['id'],
        ]);
        $ok ? $imported++ : $skipped++;
        $i++;
    }
    return ['imported' => $imported, 'skipped' => $skipped];
}
