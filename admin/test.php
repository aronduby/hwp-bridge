<?php
require '../common.php';
$dbh = PDODB::getInstance();
Config::setDbh($dbh);
Config::setSite($site);

$shutterfly_site = Config::get('SHUTTERFLY_SITE');

header("Access-Control-Allow-Origin: https://$shutterfly_site.shutterfly.com");
// header("Access-Control-Allow-Origin: *");

print 1;