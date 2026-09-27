<?php
// Comics and Cosplay have no good free structured API, so we pull real
// summaries + images from Wikipedia's free REST API (no key needed) for a
// curated list of well-known, non-copyrighted-content topics.

function fh_wikipedia_summary($title) {
    return fh_get_json('https://en.wikipedia.org/api/rest_v1/page/summary/' . str_replace(' ', '_', $title));
}

function fetch_comics(PDO $pdo) {
    $categoryId = fh_category_id($pdo, 'comics');
    if (!$categoryId) return ['imported' => 0, 'skipped' => 0];

    $topics = [
        'Spider-Man', 'Batman', 'Superman', 'Wonder Woman', 'Iron Man',
        'X-Men', 'The Flash (character)', 'Wolverine (character)', 'Deadpool',
        'Green Lantern', 'Captain America', 'Hulk (comics)', 'Aquaman', 'Thor (Marvel Comics)',
    ];

    $imported = 0; $skipped = 0; $i = 0;
    foreach ($topics as $topic) {
        $page = fh_wikipedia_summary($topic);
        if (!$page || empty($page['extract'])) { $skipped++; $i++; continue; }

        $ok = fh_upsert_content($pdo, [
            'category_id' => $categoryId,
            'title' => $page['title'],
            'type' => 'image',
            'description' => mb_substr($page['extract'], 0, 600),
            'image_url' => $page['thumbnail']['source'] ?? null,
            'genre' => 'Superhero',
            'popularity_score' => fh_score($i),
            'source_api' => 'wikipedia',
            'external_id' => (string) ($page['pageid'] ?? $topic),
        ]);
        $ok ? $imported++ : $skipped++;
        $i++;
        usleep(200000);
    }
    return ['imported' => $imported, 'skipped' => $skipped];
}

function fetch_cosplay(PDO $pdo) {
    $categoryId = fh_category_id($pdo, 'cosplay');
    if (!$categoryId) return ['imported' => 0, 'skipped' => 0];

    $topics = [
        'San Diego Comic-Con', 'New York Comic Con', 'Anime Expo', 'WonderCon',
        'Dragon Con', 'Gamescom', 'Japan Expo', 'Comiket', 'Fan Expo Canada', 'MCM Comic Con',
    ];

    $imported = 0; $skipped = 0; $i = 0;
    foreach ($topics as $topic) {
        $page = fh_wikipedia_summary($topic);
        if (!$page || empty($page['extract'])) { $skipped++; $i++; continue; }

        $ok = fh_upsert_content($pdo, [
            'category_id' => $categoryId,
            'title' => $page['title'],
            'type' => 'image',
            'description' => mb_substr($page['extract'], 0, 600),
            'image_url' => $page['thumbnail']['source'] ?? ('https://picsum.photos/seed/' . urlencode($topic) . '/600/400'),
            'genre' => 'Convention',
            'popularity_score' => fh_score($i),
            'source_api' => 'wikipedia',
            'external_id' => (string) ($page['pageid'] ?? $topic),
        ]);
        $ok ? $imported++ : $skipped++;
        $i++;
        usleep(200000);
    }
    return ['imported' => $imported, 'skipped' => $skipped];
}
