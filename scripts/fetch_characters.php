<?php
// Pulls real character profiles (name, bio, image) from AniList for every
// anime/manga title already in our database — reuses the same free,
// no-key GraphQL API. Run once: http://localhost/fanhub/scripts/fetch_characters.php

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/fetch_helpers.php';

set_time_limit(180);

$stmt = $pdo->query("SELECT content_id, category_id, external_id, title FROM content WHERE source_api = 'anilist'");
$rows = $stmt->fetchAll();

$imported = 0; $skipped = 0;

foreach ($rows as $row) {
    $query = 'query ($id: Int) {
        Media(id: $id) {
            characters(sort: FAVOURITES_DESC, perPage: 3) {
                nodes { id name { full } image { large } description(asHtml: false) }
            }
        }
    }';
    $data = fh_post_json('https://graphql.anilist.co', ['query' => $query, 'variables' => ['id' => (int) $row['external_id']]]);
    $characters = $data['data']['Media']['characters']['nodes'] ?? [];

    foreach ($characters as $char) {
        $externalId = (string) $char['id'];

        $exists = $pdo->prepare("SELECT character_id FROM character_profiles WHERE source_api = 'anilist' AND external_id = ?");
        $exists->execute([$externalId]);
        if ($exists->fetch()) { $skipped++; continue; }

        $bio = $char['description']
            ? trim(preg_replace('/\s+/', ' ', strip_tags(str_replace(['~!', '!~', '__', '**'], '', $char['description']))))
            : "A character from {$row['title']}.";

        $pdo->prepare("INSERT INTO character_profiles (category_id, name, bio, image_url, source_api, external_id) VALUES (?, ?, ?, ?, 'anilist', ?)")
            ->execute([$row['category_id'], $char['name']['full'], mb_substr($bio, 0, 500), $char['image']['large'] ?? null, $externalId]);
        $imported++;
    }
    usleep(200000);
}

echo "Characters imported: $imported, skipped (already existed): $skipped.";
