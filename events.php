<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';
if (session_status() === PHP_SESSION_NONE) session_start();

$pageTitle = 'Events';
$activeNav = 'events';

$city = $_GET['city'] ?? '';
$cities = array_column($pdo->query("SELECT DISTINCT city FROM events ORDER BY city ASC")->fetchAll(), 'city');

$where = ['event_date >= CURDATE()'];
$params = [];
if ($city !== '') { $where[] = 'events.city = ?'; $params[] = $city; }
$whereSql = 'WHERE ' . implode(' AND ', $where);

$stmt = $pdo->prepare("
    SELECT events.*, categories.name AS category_name, categories.icon AS category_icon, categories.color AS category_color
    FROM events
    LEFT JOIN categories ON categories.category_id = events.category_id
    $whereSql
    ORDER BY event_date ASC
");
$stmt->execute($params);
$events = $stmt->fetchAll();

$mapPoints = array_map(fn($e) => [
    'id' => (int) $e['event_id'],
    'title' => $e['title'],
    'lat' => (float) $e['lat'],
    'lng' => (float) $e['lng'],
    'city' => $e['city'],
    'venue' => $e['venue'],
    'date' => date('M j, Y', strtotime($e['event_date'])),
    'image' => $e['image_url'],
    'link' => $e['ticket_link'],
], $events);

require_once __DIR__ . '/includes/header.php';
?>
<section class="section" style="padding-top:150px;">
    <div class="container">
        <div class="section-head reveal">
            <div>
                <span class="section-tag">Fan Meetups</span>
                <h2>Events &amp; Conventions</h2>
                <p>Discover upcoming fan conventions, cosplay meetups and screenings around the world.</p>
            </div>
            <button id="findNearMe" class="btn btn-gradient btn-sm">📍 Find events near me</button>
        </div>

        <div id="map" class="glass reveal in-view" style="height:440px; margin-bottom:32px; overflow:hidden;"></div>

        <form method="GET" action="/fanhub/events.php" class="filter-bar glass reveal in-view" style="padding:18px 20px;">
            <div class="form-group">
                <label>Filter by City</label>
                <select name="city" onchange="this.form.submit()">
                    <option value="">All Cities</option>
                    <?php foreach ($cities as $c): ?>
                        <option value="<?= h($c) ?>" <?= $city === $c ? 'selected' : '' ?>><?= h($c) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php if ($city !== ''): ?><a href="/fanhub/events.php" class="btn btn-ghost btn-sm">Reset</a><?php endif; ?>
        </form>

        <div id="eventGrid" class="category-grid reveal-stagger" style="grid-template-columns: repeat(3, 1fr);">
            <?php foreach ($events as $i => $e): ?>
                <div class="content-card glass reveal" data-event-id="<?= $e['event_id'] ?>" data-lat="<?= h($e['lat']) ?>" data-lng="<?= h($e['lng']) ?>" style="--i:<?= $i % 8 ?>;">
                    <div class="thumb" style="background-image:url('<?= h($e['image_url'] ?: 'https://picsum.photos/seed/' . $e['event_id'] . '/400/300') ?>');">
                        <?php if ($e['category_name']): ?><span class="badge"><?= icon_img($e['category_icon'], 13) ?> <?= h($e['category_name']) ?></span><?php endif; ?>
                        <span class="distance-badge" style="position:absolute; bottom:10px; right:10px; font-size:10.5px; font-weight:700; padding:4px 10px; border-radius:999px; background:rgba(7,5,12,.65); backdrop-filter:blur(6px); border:1px solid var(--border); display:none;"></span>
                    </div>
                    <div class="body">
                        <h4><?= h($e['title']) ?></h4>
                        <p style="font-size:12.5px; color:var(--text-dim); margin:0 0 10px;">📍 <?= h($e['venue']) ?>, <?= h($e['city']) ?></p>
                        <div class="meta" style="margin-bottom:14px;">
                            <span>📅 <?= date('M j, Y', strtotime($e['event_date'])) ?></span>
                        </div>
                        <a href="<?= h($e['ticket_link']) ?>" target="_blank" rel="noopener" class="btn btn-gradient btn-sm btn-block">Get Tickets</a>
                    </div>
                </div>
            <?php endforeach; ?>
            <?php if (!$events): ?>
                <p class="empty-note">No upcoming events found<?= $city ? ' in ' . h($city) : '' ?>.</p>
            <?php endif; ?>
        </div>
    </div>
</section>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.js"></script>
<script>
const eventPoints = <?= json_encode($mapPoints, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;

const map = L.map('map', { scrollWheelZoom: false }).setView([20, 10], 2);
L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '&copy; OpenStreetMap contributors',
    maxZoom: 18,
}).addTo(map);

const markers = {};
eventPoints.forEach(function (e) {
    const marker = L.marker([e.lat, e.lng]).addTo(map);
    marker.bindPopup(
        '<div style="font-family:DM Sans,sans-serif; min-width:180px;">' +
        '<strong>' + e.title + '</strong><br>' +
        '<span style="font-size:12px;">' + e.venue + ', ' + e.city + '</span><br>' +
        '<span style="font-size:12px;">📅 ' + e.date + '</span><br>' +
        '<a href="' + e.link + '" target="_blank" rel="noopener" style="font-size:12px; font-weight:700;">Get Tickets →</a>' +
        '</div>'
    );
    markers[e.id] = marker;
});

function haversineKm(lat1, lon1, lat2, lon2) {
    const R = 6371;
    const dLat = (lat2 - lat1) * Math.PI / 180;
    const dLon = (lon2 - lon1) * Math.PI / 180;
    const a = Math.sin(dLat / 2) ** 2 + Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) * Math.sin(dLon / 2) ** 2;
    return R * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
}

document.getElementById('findNearMe').addEventListener('click', function () {
    if (!navigator.geolocation) {
        alert('Geolocation is not supported by your browser.');
        return;
    }
    const btn = this;
    btn.textContent = 'Locating...';

    navigator.geolocation.getCurrentPosition(function (pos) {
        const userLat = pos.coords.latitude;
        const userLng = pos.coords.longitude;

        L.marker([userLat, userLng], {
            icon: L.divIcon({ className: '', html: '<div style="width:16px;height:16px;border-radius:50%;background:#22E4FF;border:3px solid #fff;box-shadow:0 0 12px #22E4FF;"></div>' })
        }).addTo(map).bindPopup('You are here').openPopup();

        map.setView([userLat, userLng], 4);

        const grid = document.getElementById('eventGrid');
        const cards = Array.from(grid.querySelectorAll('.content-card'));

        cards.forEach(function (card) {
            const lat = parseFloat(card.dataset.lat);
            const lng = parseFloat(card.dataset.lng);
            const dist = Math.round(haversineKm(userLat, userLng, lat, lng));
            card.dataset.distance = dist;
            const badge = card.querySelector('.distance-badge');
            badge.textContent = dist + ' km away';
            badge.style.display = 'block';
        });

        cards.sort((a, b) => parseFloat(a.dataset.distance) - parseFloat(b.dataset.distance));
        cards.forEach(card => grid.appendChild(card));

        btn.textContent = '📍 Sorted by distance';
    }, function () {
        alert('Could not get your location. Please allow location access and try again.');
        btn.textContent = '📍 Find events near me';
    });
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
