<?php
// Every admin page includes this first (after config/db.php + functions.php).
// Blocks anyone who isn't logged in as an admin.
if (session_status() === PHP_SESSION_NONE) session_start();

if (!is_logged_in()) {
    redirect('/fanhub/login.php');
}
if (!is_admin()) {
    redirect('/fanhub/index.php?denied=1');
}
