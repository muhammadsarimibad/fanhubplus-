<?php
session_start();
$_SESSION = [];
session_destroy();
header('Location: /fanhub/index.php');
exit;
