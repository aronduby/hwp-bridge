<?php
require '../common.php';

$sid = array_key_exists('season_id', $_GET) ? $_GET['season_id'] : false;
$newSeason = new Season($sid, $register);

if (!$newSeason || $newSeason->current == true || array_key_exists('clear', $_GET)) {
    unset($_SESSION['season_id']);
} else {
    $_SESSION['season_id'] = $newSeason->id;
}

header("Location: index.php");
die();
