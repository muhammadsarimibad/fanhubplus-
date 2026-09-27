<?php
// Run once in your browser: http://localhost/fanhub/scripts/seed_chatbot_faqs.php
// Fills the chatbot's knowledge base with FAQ entries about the site itself.

require_once __DIR__ . '/../config/db.php';

$faqs = [
    ['q' => 'What is Fan Hub Plus?', 'k' => 'what is,about,fan hub,who are you,purpose', 'a' => "Fan Hub Plus is a Fandom Universe portal that brings Anime, Gaming, Movies, TV Shows, K-Pop, Comics, Manga and Cosplay together in one place, with search, filters, bookmarks and event listings."],
    ['q' => 'What categories are available?', 'k' => 'categories,category,fandoms,which categories,anime,gaming,movies,tv,kpop,comics,manga,cosplay', 'a' => "We cover 8 categories: Anime, Gaming, Movies, TV Shows, K-Pop, Comics, Manga and Cosplay. You can browse each from the homepage or the Explore page."],
    ['q' => 'How do I create an account?', 'k' => 'register,sign up,signup,create account,new account,join', 'a' => 'Click "Sign Up" in the top navigation, fill in your name, email and password, and you\'re instantly logged in!'],
    ['q' => 'How do I log in?', 'k' => 'login,log in,sign in,signin', 'a' => 'Click "Login" in the navigation bar and enter your email and password.'],
    ['q' => 'I forgot my password, what do I do?', 'k' => 'forgot password,reset password,forgot,reset,cant login,lost password', 'a' => 'Go to the Login page and click "Forgot password?" — you\'ll get a secure reset link to set a new password.'],
    ['q' => 'How do I bookmark something?', 'k' => 'bookmark,save,heart,favorite,favourites,wishlist', 'a' => 'Click the ♡ heart icon on any content card to save it. You need to be logged in — your saved items appear on your Dashboard.'],
    ['q' => 'How do I search for content?', 'k' => 'search,find,explore,filter,sort,browse', 'a' => 'Head to the Explore page — you can search by keyword and filter by category, genre, type or release year, then sort by latest, popularity or A-Z.'],
    ['q' => 'Where can I find fan events?', 'k' => 'events,convention,comic con,meetup,calendar,con', 'a' => 'Check out the Events page for a map and calendar of upcoming fan conventions, cosplay meetups and screenings around the world.'],
    ['q' => 'How does the events map work?', 'k' => 'map,location,near me,gps,nearby', 'a' => 'On the Events page, click "Find events near me" and allow location access — the map will center on you and events will be sorted by distance.'],
    ['q' => 'How do I switch between dark and light mode?', 'k' => 'dark mode,light mode,theme,switch theme,bright,night mode', 'a' => 'Click the sun/moon icon in the top navigation bar to toggle between dark and light mode. If you\'re logged in, your choice is saved to your account.'],
    ['q' => 'Can I edit my profile?', 'k' => 'profile,edit profile,avatar,picture,favorite fandoms,bio,account settings', 'a' => 'Yes! Go to your Dashboard to update your name, bio, profile picture and favorite fandoms.'],
    ['q' => 'Is Fan Hub Plus free to use?', 'k' => 'free,cost,price,paid,subscription', 'a' => "Yes, Fan Hub Plus is completely free — it's a student project built for the TechWiz competition, not a commercial product."],
    ['q' => 'Who built Fan Hub Plus?', 'k' => 'who made,developer,built,creator,techwiz,made this', 'a' => 'Fan Hub Plus was built as a student project for the TechWiz "Fandom Universe" competition category.'],
    ['q' => 'Does this work on mobile phones?', 'k' => 'mobile,phone,responsive,tablet,ipad', 'a' => 'Yes, Fan Hub Plus is fully responsive and works great on phones, tablets and desktops.'],
    ['q' => 'How is popularity score calculated?', 'k' => 'popularity,score,star rating,rank,ranking', 'a' => 'Popularity scores (out of 100) come from the real source APIs where available (like AniList and TVMaze ratings) or are curated for well-known titles.'],
];

$inserted = 0; $skipped = 0;
foreach ($faqs as $f) {
    $exists = $pdo->prepare("SELECT faq_id FROM chatbot_faqs WHERE question = ?");
    $exists->execute([$f['q']]);
    if ($exists->fetch()) { $skipped++; continue; }

    $pdo->prepare("INSERT INTO chatbot_faqs (question, answer, keywords) VALUES (?, ?, ?)")
        ->execute([$f['q'], $f['a'], $f['k']]);
    $inserted++;
}

echo "FAQs inserted: $inserted, skipped (already existed): $skipped.";
