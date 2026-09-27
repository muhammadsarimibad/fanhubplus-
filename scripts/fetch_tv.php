<?php
// Imports popular TV shows from the free TVMaze API (no key needed).

function fetch_tv_shows(PDO $pdo) {
    $categoryId = fh_category_id($pdo, 'tv-shows');
    if (!$categoryId) return ['imported' => 0, 'skipped' => 0];

    $titles = [
        'Breaking Bad', 'Stranger Things', 'Game of Thrones', 'The Office', 'Friends',
        'The Boys', 'Money Heist', 'The Witcher', 'Peaky Blinders', 'Sherlock',
        'Dark', 'The Mandalorian', 'Wednesday', 'House', 'Better Call Saul',
    ];

    $imported = 0; $skipped = 0; $i = 0;
    foreach ($titles as $title) {
        $show = fh_get_json('https://api.tvmaze.com/singlesearch/shows?q=' . urlencode($title));
        if (!$show || empty($show['id'])) { $skipped++; $i++; continue; }

        $desc = $show['summary'] ? trim(strip_tags($show['summary'])) : null;
        $score = !empty($show['rating']['average']) ? (int) round($show['rating']['average'] * 10) : fh_score($i);

        $ok = fh_upsert_content($pdo, [
            'category_id' => $categoryId,
            'title' => $show['name'],
            'type' => 'video',
            'description' => $desc ? mb_substr($desc, 0, 600) : null,
            'image_url' => $show['image']['original'] ?? $show['image']['medium'] ?? null,
            'genre' => !empty($show['genres']) ? implode(', ', array_slice($show['genres'], 0, 3)) : null,
            'release_date' => $show['premiered'] ?: null,
            'popularity_score' => $score,
            'source_api' => 'tvmaze',
            'external_id' => (string) $show['id'],
        ]);
        $ok ? $imported++ : $skipped++;
        $i++;
    }
    return ['imported' => $imported, 'skipped' => $skipped];
}
