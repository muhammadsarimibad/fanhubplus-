<?php
// Run once in your browser: http://localhost/fanhub/scripts/fetch_events.php
// Seeds real, well-known fan conventions (with real coordinates + Wikipedia
// photos) so the Events map/calendar has genuine content to show.

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/fetch_helpers.php';
require_once __DIR__ . '/fetch_comics_cosplay.php'; // gives us fh_wikipedia_summary()

$events = [
    ['wiki' => 'San_Diego_Comic-Con', 'title' => 'San Diego Comic-Con', 'city' => 'San Diego', 'venue' => 'San Diego Convention Center', 'lat' => 32.7062, 'lng' => -117.1611, 'date' => '2027-07-22', 'link' => 'https://www.comic-con.org/', 'category' => 'comics'],
    ['wiki' => 'New_York_Comic_Con', 'title' => 'New York Comic Con', 'city' => 'New York', 'venue' => 'Javits Center', 'lat' => 40.7569, 'lng' => -74.0021, 'date' => '2026-10-08', 'link' => 'https://www.newyorkcomiccon.com/', 'category' => 'comics'],
    ['wiki' => 'Anime_Expo', 'title' => 'Anime Expo', 'city' => 'Los Angeles', 'venue' => 'LA Convention Center', 'lat' => 34.0407, 'lng' => -118.2668, 'date' => '2027-07-02', 'link' => 'https://www.anime-expo.org/', 'category' => 'anime'],
    ['wiki' => 'Gamescom', 'title' => 'Gamescom', 'city' => 'Cologne', 'venue' => 'Koelnmesse', 'lat' => 50.9473, 'lng' => 6.9830, 'date' => '2027-08-19', 'link' => 'https://www.gamescom.global/', 'category' => 'gaming'],
    ['wiki' => 'Japan_Expo', 'title' => 'Japan Expo', 'city' => 'Paris', 'venue' => 'Paris Nord Villepinte', 'lat' => 48.9615, 'lng' => 2.5158, 'date' => '2027-07-09', 'link' => 'https://www.japan-expo-paris.com/', 'category' => 'anime'],
    ['wiki' => 'WonderCon', 'title' => 'WonderCon', 'city' => 'Anaheim', 'venue' => 'Anaheim Convention Center', 'lat' => 33.8003, 'lng' => -117.9192, 'date' => '2027-03-26', 'link' => 'https://www.wondercon.org/', 'category' => 'comics'],
    ['wiki' => 'Dragon_Con', 'title' => 'Dragon Con', 'city' => 'Atlanta', 'venue' => 'Hyatt Regency Atlanta', 'lat' => 33.7607, 'lng' => -84.3927, 'date' => '2027-09-03', 'link' => 'https://www.dragoncon.org/', 'category' => 'cosplay'],
    ['wiki' => 'MCM_Comic_Con', 'title' => 'MCM Comic Con London', 'city' => 'London', 'venue' => 'ExCeL London', 'lat' => 51.5085, 'lng' => 0.0284, 'date' => '2026-10-24', 'link' => 'https://mcmcomiccon.com/', 'category' => 'comics'],
    ['wiki' => 'Fan_Expo_Canada', 'title' => 'Fan Expo Canada', 'city' => 'Toronto', 'venue' => 'Metro Toronto Convention Centre', 'lat' => 43.6435, 'lng' => -79.3791, 'date' => '2027-08-27', 'link' => 'https://fanexpocanada.com/', 'category' => 'comics'],
    ['wiki' => 'Comiket', 'title' => 'Comiket', 'city' => 'Tokyo', 'venue' => 'Tokyo Big Sight', 'lat' => 35.6300, 'lng' => 139.7940, 'date' => '2026-12-30', 'link' => 'https://www.comiket.co.jp/', 'category' => 'manga'],
];

set_time_limit(120);
$imported = 0; $skipped = 0;

foreach ($events as $e) {
    $exists = $pdo->prepare("SELECT event_id FROM events WHERE title = ? AND event_date = ?");
    $exists->execute([$e['title'], $e['date']]);
    if ($exists->fetch()) { $skipped++; continue; }

    $page = fh_wikipedia_summary($e['wiki']);
    $description = $page['extract'] ?? ($e['title'] . ' is a major fan convention held in ' . $e['city'] . '.');
    $image = $page['thumbnail']['source'] ?? ('https://picsum.photos/seed/' . urlencode($e['title']) . '/600/400');
    $categoryId = fh_category_id($pdo, $e['category']);

    $stmt = $pdo->prepare("
        INSERT INTO events (title, description, category_id, city, venue, lat, lng, event_date, ticket_link, image_url)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        $e['title'], mb_substr($description, 0, 500), $categoryId, $e['city'], $e['venue'],
        $e['lat'], $e['lng'], $e['date'], $e['link'], $image,
    ]);
    $imported++;
}

echo "Events imported: $imported, skipped (already existed): $skipped.";
