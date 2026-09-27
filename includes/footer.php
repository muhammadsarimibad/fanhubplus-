<footer>
    <div class="container">
        <div class="footer-grid">
            <div>
                <div class="brand" style="margin-bottom:14px;">
                    <svg class="brand-logo" viewBox="0 0 64 64" xmlns="http://www.w3.org/2000/svg" width="34" height="34">
                        <path d="M32,8 L55,24 L9,24 Z" fill="#FF9A3D"/>
                        <rect x="13" y="22" width="38" height="38" rx="8" fill="#7A5CFF"/>
                        <path d="M30,0 H34 V4 H38 V8 H34 V12 H30 V8 H26 V4 H30 Z" fill="#ffffff"/>
                        <g transform="translate(32,41)">
                            <g class="fan-blades" fill="#07050c">
                                <path d="M-2,-5 L-5,-16 Q0,-19 5,-16 L2,-5 Z" transform="rotate(45)"/>
                                <path d="M-2,-5 L-5,-16 Q0,-19 5,-16 L2,-5 Z" transform="rotate(135)"/>
                                <path d="M-2,-5 L-5,-16 Q0,-19 5,-16 L2,-5 Z" transform="rotate(225)"/>
                                <path d="M-2,-5 L-5,-16 Q0,-19 5,-16 L2,-5 Z" transform="rotate(315)"/>
                            </g>
                            <circle r="5" fill="#07050c"/>
                        </g>
                    </svg>
                    <span class="brand-name">FAN HUB<span class="plus">+</span></span>
                </div>
                <p style="color:var(--text-dim); font-size:13.5px; max-width:260px;">
                    A single, visually rich home for every fandom — Anime, Gaming, Movies, TV, K-Pop, Comics, Manga and Cosplay.
                </p>
            </div>
            <div>
                <h5>Categories</h5>
                <ul>
                    <li><a href="/fanhub/category.php?slug=anime">Anime</a></li>
                    <li><a href="/fanhub/category.php?slug=gaming">Gaming</a></li>
                    <li><a href="/fanhub/category.php?slug=movies">Movies</a></li>
                    <li><a href="/fanhub/category.php?slug=tv-shows">TV Shows</a></li>
                </ul>
            </div>
            <div>
                <h5>More Fandoms</h5>
                <ul>
                    <li><a href="/fanhub/category.php?slug=kpop">K-Pop</a></li>
                    <li><a href="/fanhub/category.php?slug=comics">Comics</a></li>
                    <li><a href="/fanhub/category.php?slug=manga">Manga</a></li>
                    <li><a href="/fanhub/category.php?slug=cosplay">Cosplay</a></li>
                </ul>
            </div>
            <div>
                <h5>Explore</h5>
                <ul>
                    <li><a href="/fanhub/explore.php">Browse All</a></li>
                    <li><a href="/fanhub/characters.php">Characters</a></li>
                    <li><a href="/fanhub/merchandise.php">Merchandise</a></li>
                    <li><a href="/fanhub/events.php">Events &amp; Map</a></li>
                    <li><a href="/fanhub/feedback.php">Feedback</a></li>
                    <li><a href="/fanhub/index.php#sitemap">Sitemap</a></li>
                </ul>
            </div>
            <div>
                <h5>Account</h5>
                <ul>
                    <li><a href="/fanhub/login.php">Login</a></li>
                    <li><a href="/fanhub/register.php">Sign Up</a></li>
                    <li><a href="/fanhub/dashboard.php">Dashboard</a></li>
                </ul>
            </div>
        </div>
        <div class="footer-bottom">
            <span>&copy; <?= date('Y') ?> Fan Hub Plus. Built for TechWiz — Fandom Universe.</span>
            <span>Content sourced from AniList, TVMaze, FreeToGame, Jamendo, Pexels &amp; Wikipedia APIs.</span>
        </div>
    </div>
</footer>

<button class="chatbot-fab" id="chatbotFab" title="Ask Sarim Kuro">
    <img src="/fanhub/assets/img/chatbot-avatar.png" alt="Sarim Kuro">
    <span class="chatbot-fab-badge">💬</span>
</button>

<div class="chatbot-panel glass" id="chatbotPanel">
    <div class="chatbot-header">
        <img src="/fanhub/assets/img/chatbot-avatar.png" alt="" class="chatbot-header-avatar">
        <strong>Sarim Kuro</strong>
        <button id="chatbotClose" title="Close">✕</button>
    </div>
    <div class="chatbot-messages" id="chatbotMessages">
        <div class="chat-bubble bot">Hi! I'm Sarim Kuro, your Fan Hub assistant. Ask me about categories, search, bookmarks, events or your account 👋</div>
    </div>
    <div class="chat-suggestions" id="chatSuggestions">
        <button type="button">What categories are available?</button>
        <button type="button">How do I bookmark something?</button>
        <button type="button">Where can I find events?</button>
        <button type="button">I forgot my password</button>
    </div>
    <form class="chatbot-input" id="chatbotForm">
        <input type="text" id="chatbotInput" placeholder="Type your question..." autocomplete="off">
        <button type="submit">➤</button>
    </form>
</div>

<script src="/fanhub/assets/js/main.js?v=<?= filemtime(__DIR__ . '/../assets/js/main.js') ?>"></script>
</body>
</html>
