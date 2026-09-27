<?php
// Loads the real API keys from config/secrets.php (gitignored — see
// config/secrets.example.php for the template). GEMINI_API_KEY powers the
// chatbot's general-purpose answers; JAMENDO_CLIENT_ID streams K-Pop audio
// directly on our own site with no redirect and no region lock.
require_once __DIR__ . '/secrets.php';
