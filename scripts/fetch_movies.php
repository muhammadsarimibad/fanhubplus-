<?php
// Imports popular movies using Wikipedia's free REST API (no key needed).
// (Apple's iTunes movie search index no longer covers most theatrical films,
// so Wikipedia gives us reliable posters + real summaries instead.)

function fetch_movies(PDO $pdo) {
    $categoryId = fh_category_id($pdo, 'movies');
    if (!$categoryId) return ['imported' => 0, 'skipped' => 0];

    $topics = [
        'Inception_(film)' => 'Inception',
        'Interstellar_(film)' => 'Interstellar',
        'The_Dark_Knight' => 'The Dark Knight',
        'Avengers:_Endgame' => 'Avengers: Endgame',
        'Spider-Man:_No_Way_Home' => 'Spider-Man: No Way Home',
        'Parasite_(2019_film)' => 'Parasite',
        'The_Matrix' => 'The Matrix',
        'Joker_(2019_film)' => 'Joker',
        'Dune_(2021_film)' => 'Dune',
        'Oppenheimer_(film)' => 'Oppenheimer',
        'Barbie_(film)' => 'Barbie',
        'John_Wick' => 'John Wick',
        'Top_Gun:_Maverick' => 'Top Gun: Maverick',
        'La_La_Land_(film)' => 'La La Land',
        'Everything_Everywhere_All_at_Once' => 'Everything Everywhere All at Once',
    ];

    $imported = 0; $skipped = 0; $i = 0;
    foreach ($topics as $wikiSlug => $displayTitle) {
        $page = fh_get_json('https://en.wikipedia.org/api/rest_v1/page/summary/' . $wikiSlug);
        if (!$page || empty($page['extract'])) { $skipped++; $i++; continue; }

        $ok = fh_upsert_content($pdo, [
            'category_id' => $categoryId,
            'title' => $displayTitle,
            'type' => 'video',
            'description' => mb_substr($page['extract'], 0, 600),
            'image_url' => $page['thumbnail']['source'] ?? null,
            'genre' => 'Movie',
            'popularity_score' => fh_score($i),
            'source_api' => 'wikipedia',
            'external_id' => (string) ($page['pageid'] ?? $wikiSlug),
        ]);
        $ok ? $imported++ : $skipped++;
        $i++;
        usleep(200000);
    }
    return ['imported' => $imported, 'skipped' => $skipped];
}
