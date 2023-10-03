<?php

/**
 * Used for listing the available folders in cloudinary
 * Uses post values for the creds, so it can be used before the season is fully saved
 */

use Cloudinary\Cloudinary;

require_once '../../common.php';

header('Content-Type: application/json; charset=utf-8');

$keys = ['cloud_name', 'api_key', 'api_secret'];
$missing = [];
foreach ($keys as $k) {
    if (empty($_REQUEST[$k])) {
        $missing[] = $k;
    }
}

if (count($missing)) {
    http_response_code(400);
    print json_encode([
        'error' => 'Missing required post fields: ' . implode(', ', $missing)
    ]);
    die();
}

$cloudinary = new Cloudinary([
    'cloud' => [
        'cloud_name' => $_REQUEST['cloud_name'],
        'api_key' => $_REQUEST['api_key'],
        'api_secret' => $_REQUEST['api_secret'],
        'url' => [
            'secure' => true
        ]
    ]
]);

$folders = $cloudinary->adminApi()->rootFolders();
$folders = $folders['folders'];

// do a second level for flexibility
foreach ($folders as &$folder) {
    $subs = $cloudinary->adminApi()->subFolders($folder['path']);
    $folder['subs'] = $subs['folders'];
}

print json_encode($folders);