// Fan Hub Plus — shared front-end behaviour (navbar, menu, reveal animations, theme)

/* Minimal neon sparkle trail that follows the mouse cursor */
(function () {
    const colors = ['#FF3D9A', '#7A5CFF', '#22E4FF', '#FF9A3D'];
    let lastTime = 0;

    document.addEventListener('mousemove', function (e) {
        const now = performance.now();
        if (now - lastTime < 40) return; // throttle so it stays light, not a heavy trail
        lastTime = now;

        const sparkle = document.createElement('span');
        sparkle.className = 'cursor-sparkle';
        const size = 3 + Math.random() * 4;
        const color = colors[Math.floor(Math.random() * colors.length)];
        sparkle.style.width = size + 'px';
        sparkle.style.height = size + 'px';
        sparkle.style.left = (e.clientX + (Math.random() * 12 - 6)) + 'px';
        sparkle.style.top = (e.clientY + (Math.random() * 12 - 6)) + 'px';
        sparkle.style.background = color;
        sparkle.style.boxShadow = '0 0 6px ' + color + ', 0 0 12px ' + color;
        document.body.appendChild(sparkle);
        setTimeout(() => sparkle.remove(), 750);
    }, { passive: true });
})();

document.addEventListener('DOMContentLoaded', function () {
    /* Navbar shrink + shadow on scroll */
    const navbar = document.querySelector('.navbar');
    if (navbar) {
        const onScroll = () => navbar.classList.toggle('scrolled', window.scrollY > 12);
        onScroll();
        window.addEventListener('scroll', onScroll, { passive: true });
    }

    /* Mobile menu toggle */
    const toggle = document.querySelector('.nav-toggle');
    const links = document.querySelector('.nav-links');
    if (toggle && links) {
        toggle.addEventListener('click', () => links.classList.toggle('open'));
        links.querySelectorAll('a').forEach(a => a.addEventListener('click', () => links.classList.remove('open')));
    }

    /* Scroll reveal animations */
    const revealEls = document.querySelectorAll('.reveal');
    if ('IntersectionObserver' in window && revealEls.length) {
        const io = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('in-view');
                    io.unobserve(entry.target);
                }
            });
        }, { threshold: 0.12 });
        revealEls.forEach(el => io.observe(el));
    } else {
        revealEls.forEach(el => el.classList.add('in-view'));
    }

    /* Bookmark heart buttons (event delegation so it works for dynamically rendered cards too) */
    document.addEventListener('click', function (e) {
        const btn = e.target.closest('.bookmark-btn');
        if (!btn) return;
        e.preventDefault();
        e.stopPropagation();

        const contentId = btn.dataset.contentId;
        const itemType = btn.dataset.itemType || 'content';
        fetch('/fanhub/bookmark.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'content_id=' + encodeURIComponent(contentId) + '&item_type=' + encodeURIComponent(itemType),
        })
            .then(res => {
                if (res.status === 401) {
                    window.location.href = '/fanhub/login.php';
                    return null;
                }
                return res.json();
            })
            .then(data => {
                if (!data) return;
                const list = btn.closest('.bookmarks-list');
                if (list && !data.bookmarked) {
                    const card = btn.closest('.content-card');
                    card.style.transition = 'opacity .3s ease, transform .3s ease';
                    card.style.opacity = '0';
                    card.style.transform = 'scale(.9)';
                    setTimeout(() => card.remove(), 300);
                    return;
                }
                btn.classList.toggle('active', data.bookmarked);
                btn.textContent = data.bookmarked ? '♥' : '♡';
                btn.classList.remove('pop');
                void btn.offsetWidth; // restart animation
                btn.classList.add('pop');
            })
            .catch(() => {});
    });

    /* Dark / light mode toggle (default is dark).
       Logged-in users: the server already rendered the right theme from their
       saved preference, and clicks are saved back to their account.
       Guests: preference just lives in localStorage for this browser. */
    const themeBtn = document.querySelector('[data-theme-toggle]');
    const isLoggedIn = document.body.dataset.loggedIn === '1';

    if (!isLoggedIn) {
        const savedTheme = localStorage.getItem('fanhub-theme');
        if (savedTheme === 'light') {
            document.body.classList.add('light-mode');
            if (themeBtn) themeBtn.textContent = '🌙';
        }
    }

    if (themeBtn) {
        themeBtn.addEventListener('click', () => {
            const isLight = document.body.classList.toggle('light-mode');
            themeBtn.textContent = isLight ? '🌙' : '☀️';

            if (isLoggedIn) {
                fetch('/fanhub/theme.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'dark_mode=' + (isLight ? '0' : '1'),
                }).catch(() => {});
            } else {
                localStorage.setItem('fanhub-theme', isLight ? 'light' : 'dark');
            }
        });
    }

    /* FAQ Chatbot widget */
    const fab = document.getElementById('chatbotFab');
    const panel = document.getElementById('chatbotPanel');
    const closeBtn = document.getElementById('chatbotClose');
    const messages = document.getElementById('chatbotMessages');
    const suggestions = document.getElementById('chatSuggestions');
    const chatForm = document.getElementById('chatbotForm');
    const chatInput = document.getElementById('chatbotInput');

    if (fab && panel && chatForm) {
        const togglePanel = () => panel.classList.toggle('open');
        fab.addEventListener('click', togglePanel);
        closeBtn.addEventListener('click', togglePanel);

        const chatHistory = [];

        function addBubble(text, who) {
            const bubble = document.createElement('div');
            bubble.className = 'chat-bubble ' + who;
            bubble.textContent = text;
            messages.appendChild(bubble);
            messages.scrollTop = messages.scrollHeight;
        }

        function askBot(text) {
            if (!text.trim()) return;
            addBubble(text, 'user');
            chatInput.value = '';
            suggestions.style.display = 'none';

            const typing = document.createElement('div');
            typing.className = 'chat-typing';
            typing.innerHTML = '<span></span><span></span><span></span>';
            messages.appendChild(typing);
            messages.scrollTop = messages.scrollHeight;

            const body = 'message=' + encodeURIComponent(text) + '&history=' + encodeURIComponent(JSON.stringify(chatHistory));
            chatHistory.push({ role: 'user', text });

            fetch('/fanhub/chatbot.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: body,
            })
                .then(res => res.json())
                .then(data => {
                    typing.remove();
                    const reply = data.response || 'Sorry, something went wrong.';
                    addBubble(reply, 'bot');
                    chatHistory.push({ role: 'bot', text: reply });
                })
                .catch(() => {
                    typing.remove();
                    addBubble("Sorry, I couldn't reach the server. Please try again.", 'bot');
                });
        }

        chatForm.addEventListener('submit', function (e) {
            e.preventDefault();
            askBot(chatInput.value);
        });

        suggestions.querySelectorAll('button').forEach(btn => {
            btn.addEventListener('click', () => askBot(btn.textContent));
        });
    }
});
