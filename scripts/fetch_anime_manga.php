<?php
// Imports top popular Anime and Manga from the free AniList GraphQL API (no key needed).

function fetch_anilist_media(PDO $pdo, $type, $categorySlug) {
    $categoryId = fh_category_id($pdo, $categorySlug);
    if (!$categoryId) return ['imported' => 0, 'skipped' => 0];

    $query = 'query ($type: MediaType) {
        Page(page: 1, perPage: 18) {
            media(type: $type, sort: POPULARITY_DESC) {
                id
                title { romaji english }
                description(asHtml: false)
                genres
                averageScore
                coverImage { large }
                startDate { year month day }
            }
        }
    }';
    $data = fh_post_json('https://graphql.anilist.co', ['query' => $query, 'variables' => ['type' => $type]]);
    $items = $data['data']['Page']['media'] ?? [];

    $imported = 0; $skipped = 0;
    $contentType = $type === 'ANIME' ? 'video' : 'image';

    foreach ($items as $m) {
        $title = $m['title']['english'] ?: $m['title']['romaji'];
        $desc = $m['description'] ? trim(strip_tags(str_replace(['<br>', '<br/>', '<br />'], ' ', $m['description']))) : null;
        $desc = $desc ? mb_substr($desc, 0, 600) : null;
        $release = null;
        if (!empty($m['startDate']['year'])) {
            $release = sprintf('%04d-%02d-%02d', $m['startDate']['year'], $m['startDate']['month'] ?: 1, $m['startDate']['day'] ?: 1);
        }
        $ok = fh_upsert_content($pdo, [
            'category_id' => $categoryId,
            'title' => $title,
            'type' => $contentType,
            'description' => $desc,
            'image_url' => $m['coverImage']['large'] ?? null,
            'genre' => !empty($m['genres']) ? implode(', ', array_slice($m['genres'], 0, 3)) : null,
            'release_date' => $release,
            'popularity_score' => $m['averageScore'] ?? 60,
            'source_api' => 'anilist',
            'external_id' => (string) $m['id'],
        ]);
        $ok ? $imported++ : $skipped++;
    }
    return ['imported' => $imported, 'skipped' => $skipped];
}
