<?php
// Free stock-photo helpers (Unsplash primary, Pexels fallback) — used only
// by import/seed scripts to populate real image URLs in the database.
// Templates never call these directly at page-render time.

require_once __DIR__ . '/../config/secrets.php';

function fh_unsplash_photo($query, $offset = 0) {
    $data = fh_get_json(
        'https://api.unsplash.com/search/photos?query=' . urlencode($query) . '&per_page=' . (5 + $offset),
        ['Authorization: Client-ID ' . UNSPLASH_ACCESS_KEY]
    );
    $results = $data['results'] ?? [];
    if (!$results) return null;
    $pick = $results[min($offset, count($results) - 1)];
    return $pick['urls']['regular'] ?? null;
}

function fh_pexels_photo($query, $offset = 0) {
    $data = fh_get_json(
        'https://api.pexels.com/v1/search?query=' . urlencode($query) . '&per_page=' . (5 + $offset),
        ['Authorization: ' . PEXELS_API_KEY]
    );
    $results = $data['photos'] ?? [];
    if (!$results) return null;
    $pick = $results[min($offset, count($results) - 1)];
    return $pick['src']['large'] ?? null;
}

// Tries Unsplash first, falls back to Pexels if nothing found.
function fh_stock_photo($query, $offset = 0) {
    return fh_unsplash_photo($query, $offset) ?: fh_pexels_photo($query, $offset);
}
