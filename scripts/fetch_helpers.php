<?php
// Shared helpers used by every scripts/fetch_*.php importer.

function fh_http_get($url, $headers = []) {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 20,
        CURLOPT_HTTPHEADER => array_merge(['User-Agent: FanHubPlus/1.0'], $headers),
        CURLOPT_SSL_VERIFYPEER => true,
    ]);
    $result = curl_exec($ch);
    $error = curl_error($ch);
    curl_close($ch);
    if ($result === false) {
        echo "<div style='color:#ff6b9d'>Request failed: " . htmlspecialchars($url) . " — $error</div>";
        return null;
    }
    return $result;
}

function fh_http_post_json($url, $payload, $headers = []) {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 20,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($payload),
        CURLOPT_HTTPHEADER => array_merge(['Content-Type: application/json', 'User-Agent: FanHubPlus/1.0'], $headers),
    ]);
    $result = curl_exec($ch);
    $error = curl_error($ch);
    curl_close($ch);
    if ($result === false) {
        echo "<div style='color:#ff6b9d'>Request failed: " . htmlspecialchars($url) . " — $error</div>";
        return null;
    }
    return $result;
}

function fh_get_json($url, $headers = []) {
    $raw = fh_http_get($url, $headers);
    return $raw ? json_decode($raw, true) : null;
}

function fh_post_json($url, $payload, $headers = []) {
    $raw = fh_http_post_json($url, $payload, $headers);
    return $raw ? json_decode($raw, true) : null;
}

function fh_category_id(PDO $pdo, $slug) {
    static $cache = [];
    if (isset($cache[$slug])) return $cache[$slug];
    $stmt = $pdo->prepare("SELECT category_id FROM categories WHERE slug = ?");
    $stmt->execute([$slug]);
    $row = $stmt->fetch();
    $cache[$slug] = $row ? (int) $row['category_id'] : null;
    return $cache[$slug];
}

// Inserts a content row if it doesn't already exist (matched by category + source_api + external_id).
function fh_upsert_content(PDO $pdo, array $data) {
    $stmt = $pdo->prepare("SELECT content_id FROM content WHERE category_id = ? AND source_api = ? AND external_id = ?");
    $stmt->execute([$data['category_id'], $data['source_api'], $data['external_id']]);
    if ($stmt->fetch()) {
        return false; // already imported, skip
    }

    $sql = "INSERT INTO content
        (category_id, title, type, description, image_url, video_url, audio_url, genre, release_date, popularity_score, source_api, external_id, status)
        VALUES (:category_id, :title, :type, :description, :image_url, :video_url, :audio_url, :genre, :release_date, :popularity_score, :source_api, :external_id, 'approved')";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':category_id' => $data['category_id'],
        ':title' => mb_substr($data['title'], 0, 255),
        ':type' => $data['type'],
        ':description' => $data['description'] ?? null,
        ':image_url' => $data['image_url'] ?? null,
        ':video_url' => $data['video_url'] ?? null,
        ':audio_url' => $data['audio_url'] ?? null,
        ':genre' => $data['genre'] ?? null,
        ':release_date' => $data['release_date'] ?? null,
        ':popularity_score' => $data['popularity_score'] ?? 50,
        ':source_api' => $data['source_api'],
        ':external_id' => $data['external_id'],
    ]);
    return true;
}

function fh_score($index, $base = 98, $step = 4, $floor = 42) {
    return max($floor, $base - ($index * $step));
}
