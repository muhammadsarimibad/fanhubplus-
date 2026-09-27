<?php
// Seeds realistic merchandise items per category using free Unsplash/Pexels
// stock photos (no official-brand merch API exists, so this is curated
// demo content — matches the SRS's fallback guidance for Cosplay/K-Pop-style
// categories where no free structured API is available).
// Run: http://localhost/fanhub/scripts/seed_merchandise.php

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/fetch_helpers.php';
require_once __DIR__ . '/image_helpers.php';

set_time_limit(120);

$items = [
    ['cat' => 'anime', 'name' => 'Anime Figure Collectible Set', 'tag' => 'Collectible', 'q' => 'anime figure collectible'],
    ['cat' => 'anime', 'name' => 'Anime Character Keychain Pack', 'tag' => 'New', 'q' => 'anime keychain'],
    ['cat' => 'gaming', 'name' => 'Retro Gaming Controller', 'tag' => 'Collectible', 'q' => 'retro game controller'],
    ['cat' => 'gaming', 'name' => 'Gaming Headset Pro', 'tag' => 'Pre-Order', 'q' => 'gaming headset'],
    ['cat' => 'movies', 'name' => 'Movie Poster Art Print', 'tag' => 'Limited Edition', 'q' => 'movie poster print'],
    ['cat' => 'movies', 'name' => 'Cinema Popcorn Bucket Set', 'tag' => 'New', 'q' => 'popcorn cinema'],
    ['cat' => 'tv-shows', 'name' => 'TV Series Mug Collection', 'tag' => 'New', 'q' => 'coffee mug collection'],
    ['cat' => 'tv-shows', 'name' => 'Show-Themed Hoodie', 'tag' => 'Pre-Order', 'q' => 'hoodie apparel'],
    ['cat' => 'kpop', 'name' => 'K-Pop Idol Photocard Album', 'tag' => 'Limited Edition', 'q' => 'photocard album kpop'],
    ['cat' => 'kpop', 'name' => 'K-Pop Lightstick', 'tag' => 'Collectible', 'q' => 'concert lightstick'],
    ['cat' => 'comics', 'name' => 'Comic Book Variant Cover', 'tag' => 'Limited Edition', 'q' => 'comic book cover'],
    ['cat' => 'comics', 'name' => 'Superhero Action Figure', 'tag' => 'Collectible', 'q' => 'superhero action figure'],
    ['cat' => 'manga', 'name' => 'Manga Box Set Volume Collection', 'tag' => 'New', 'q' => 'manga books collection'],
    ['cat' => 'manga', 'name' => 'Manga Character Acrylic Stand', 'tag' => 'Pre-Order', 'q' => 'acrylic figure stand'],
    ['cat' => 'cosplay', 'name' => 'Cosplay Wig Styling Kit', 'tag' => 'New', 'q' => 'colorful wig'],
    ['cat' => 'cosplay', 'name' => 'Cosplay Prop Armor Set', 'tag' => 'Collectible', 'q' => 'cosplay armor prop'],
];

$imported = 0; $failed = 0;
foreach ($items as $i => $item) {
    $categoryId = fh_category_id($pdo, $item['cat']);
    if (!$categoryId) { $failed++; continue; }

    $exists = $pdo->prepare("SELECT item_id FROM merchandise_items WHERE name = ? AND category_id = ?");
    $exists->execute([$item['name'], $categoryId]);
    if ($exists->fetch()) { $failed++; continue; }

    $image = fh_stock_photo($item['q'], $i % 5);
    $pdo->prepare("INSERT INTO merchandise_items (category_id, name, description, image_url, tag, is_upcoming) VALUES (?, ?, ?, ?, ?, ?)")
        ->execute([
            $categoryId, $item['name'],
            'A ' . strtolower($item['tag']) . ' item for fans of this fandom.',
            $image, $item['tag'], $item['tag'] === 'Pre-Order' ? 1 : 0,
        ]);
    $imported++;
    usleep(250000);
}

echo "Merchandise imported: $imported, skipped/failed: $failed.";
