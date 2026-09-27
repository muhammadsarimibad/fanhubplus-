<?php
// Run this once in your browser: http://localhost/fanhub/scripts/fetch_all.php
// It pulls real content from free public APIs and stores it in your database.
// Safe to re-run — it skips items that were already imported.

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/fetch_helpers.php';
require_once __DIR__ . '/fetch_anime_manga.php';
require_once __DIR__ . '/fetch_tv.php';
require_once __DIR__ . '/fetch_movies.php';
require_once __DIR__ . '/fetch_games.php';
require_once __DIR__ . '/fetch_kpop.php';
require_once __DIR__ . '/fetch_comics_cosplay.php';

set_time_limit(180);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Fan Hub Plus — Importing Content</title>
<style>
    body { background:#07050c; color:#f4f2fb; font-family: 'DM Sans', Arial, sans-serif; max-width:760px; margin:40px auto; padding:0 20px; line-height:1.6; }
    h1 { background: linear-gradient(135deg,#FF3D9A,#7A5CFF,#22E4FF); -webkit-background-clip:text; background-clip:text; color:transparent; }
    .row { display:flex; justify-content:space-between; padding:12px 16px; border:1px solid rgba(255,255,255,.12); border-radius:10px; margin-bottom:10px; background:rgba(255,255,255,.05); }
    .ok { color:#7CFFB2; } .warn { color:#FF9A3D; }
    a.btn { display:inline-block; margin-top:24px; padding:12px 26px; border-radius:999px; background: linear-gradient(135deg,#FF3D9A,#7A5CFF); color:#07050c; font-weight:700; text-decoration:none; }
</style>
</head>
<body>
<h1>Importing real fandom content…</h1>
<p>Pulling from AniList, TVMaze, iTunes, FreeToGame, Deezer and Wikipedia. Please wait, this can take about a minute.</p>
<?php flush(); ob_flush(); ?>

<?php
function report($label, $result) {
    echo "<div class='row'><span>$label</span><span class='ok'>+{$result['imported']} added</span> <span class='warn'>{$result['skipped']} skipped</span></div>";
    @ob_flush(); @flush();
}

report('🎬 Anime (AniList)', fetch_anilist_media($pdo, 'ANIME', 'anime'));
report('📚 Manga (AniList)', fetch_anilist_media($pdo, 'MANGA', 'manga'));
report('📺 TV Shows (TVMaze)', fetch_tv_shows($pdo));
report('🎥 Movies (Wikipedia)', fetch_movies($pdo));
report('🎮 Gaming (FreeToGame)', fetch_games($pdo));
report('🎤 K-Pop (Deezer)', fetch_kpop($pdo));
report('📖 Comics (Wikipedia)', fetch_comics($pdo));
report('🎭 Cosplay (Wikipedia)', fetch_cosplay($pdo));
?>

<p style="margin-top:24px;">Done! Your database now has real content in every category.</p>
<a class="btn" href="/fanhub/index.php">Go to Homepage →</a>
</body>
</html>
