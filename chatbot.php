<?php
// AJAX endpoint for the floating chatbot widget ("Sarim Kuro").
// First checks the site's own FAQ knowledge base (fast, free, exact for
// common site questions). If nothing matches well, it falls back to the
// free Gemini API so it can hold a real, multilingual, ChatGPT-style
// conversation about anything — not just the site.
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/ai.php';
require_once __DIR__ . '/includes/functions.php';
if (session_status() === PHP_SESSION_NONE) session_start();

header('Content-Type: application/json');

$message = trim($_POST['message'] ?? '');
if ($message === '') {
    http_response_code(400);
    echo json_encode(['error' => 'empty_message']);
    exit;
}

// Recent turns from the browser, e.g. [{"role":"user","text":"..."},{"role":"bot","text":"..."}]
$history = [];
if (!empty($_POST['history'])) {
    $decoded = json_decode($_POST['history'], true);
    if (is_array($decoded)) $history = array_slice($decoded, -8);
}

$faqs = $pdo->query("SELECT question, answer, keywords FROM chatbot_faqs")->fetchAll();

function fanhub_match_faq(string $message, array $faqs): ?array {
    $msg = strtolower($message);
    $msgWords = preg_split('/[^a-z0-9]+/', $msg, -1, PREG_SPLIT_NO_EMPTY);
    $msgWordSet = array_flip($msgWords);

    $best = null;
    $bestScore = 0;

    foreach ($faqs as $faq) {
        $score = 0;

        // A keyword phrase only counts if EVERY one of its words appears in the
        // message (not just a raw substring) — longer phrases score higher,
        // so a full multi-word match beats a single generic word.
        foreach (array_filter(array_map('trim', explode(',', strtolower($faq['keywords'] ?? '')))) as $keyword) {
            if ($keyword === '') continue;
            $keywordWords = preg_split('/[^a-z0-9]+/', $keyword, -1, PREG_SPLIT_NO_EMPTY);
            if (!$keywordWords) continue;
            $matched = 0;
            foreach ($keywordWords as $kw) {
                if (isset($msgWordSet[$kw])) $matched++;
            }
            if ($matched === count($keywordWords)) $score += 2 + $matched;
        }

        $questionWords = preg_split('/[^a-z0-9]+/', strtolower($faq['question']), -1, PREG_SPLIT_NO_EMPTY);
        $score += count(array_intersect($msgWords, $questionWords));

        if ($score > $bestScore) {
            $bestScore = $score;
            $best = $faq;
        }
    }

    // Require a confident match so genuinely unrelated questions fall through to the AI.
    return $bestScore >= 5 ? $best : null;
}

function fanhub_ask_gemini(string $message, array $history): ?string {
    $systemInstruction = "You are Sarim Kuro, the friendly assistant for Fan Hub Plus, a fandom website "
        . "covering Anime, Gaming, Movies, TV Shows, K-Pop, Comics, Manga and Cosplay. "
        . "Always reply in the same language the user writes in. Keep answers conversational and fairly short "
        . "(a few sentences) unless the user asks for detail. You can chat about absolutely anything, not just the site.";

    $contents = [];
    foreach ($history as $turn) {
        if (!isset($turn['role'], $turn['text'])) continue;
        $contents[] = [
            'role' => $turn['role'] === 'bot' ? 'model' : 'user',
            'parts' => [['text' => (string) $turn['text']]],
        ];
    }
    $contents[] = ['role' => 'user', 'parts' => [['text' => $message]]];

    $payload = [
        'system_instruction' => ['parts' => [['text' => $systemInstruction]]],
        'contents' => $contents,
    ];

    $url = 'https://generativelanguage.googleapis.com/v1beta/models/' . GEMINI_MODEL . ':generateContent?key=' . GEMINI_API_KEY;

    for ($attempt = 0; $attempt < 2; $attempt++) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_TIMEOUT => 20,
        ]);
        $raw = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($raw) {
            $data = json_decode($raw, true);
            $text = $data['candidates'][0]['content']['parts'][0]['text'] ?? null;
            if ($text) return trim($text);
        }
        if ($httpCode !== 503) break; // only retry on "model overloaded"
        usleep(600000);
    }
    return null;
}

$match = fanhub_match_faq($message, $faqs);

if ($match) {
    $response = $match['answer'];
} else {
    $response = fanhub_ask_gemini($message, $history)
        ?? "Sorry, I'm having trouble reaching my brain right now 😅 — please try again in a moment.";
}

$pdo->prepare("INSERT INTO chatbot_queries (user_id, message, response) VALUES (?, ?, ?)")
    ->execute([current_user_id(), $message, $response]);

echo json_encode(['response' => $response]);
