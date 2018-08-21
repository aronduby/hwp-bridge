<?php
require '../common.php';

if (
    !empty($_POST)
    && array_key_exists('token', $_POST)
    && array_key_exists('tags', $_POST)
    && file_exists(TOKEN_PATH)
    && file_get_contents(TOKEN_PATH) == $_POST['token']
) {
    $json = new ServicesJSON();

    $data = new StdClass();
    $data->posted = time();
    $data->tags = [];
    $data->parsed = null;

    // $_POST['tags'] == array of strings
    foreach ($_POST['tags'] as $tagStr) {
        $data->tags[] = $json->decode($tagStr);
    }

    $data->parsed = time();

    print (file_put_contents(JSON_PATH, json_encode($data, JSON_PRETTY_PRINT)) !== false);
}

if (file_exists(TOKEN_PATH)) {
    unlink(TOKEN_PATH);
}