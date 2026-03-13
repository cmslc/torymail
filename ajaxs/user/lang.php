<?php
require_once __DIR__ . "/../bootstrap.php";

$lang = isset($_GET['lang']) ? preg_replace('/[^a-z]/', '', $_GET['lang']) : '';

if (!in_array($lang, ['en', 'vi'])) {
    error_response('Invalid language', 400);
}

$_SESSION['lang'] = $lang;

success_response('Language changed');
