<?php
require '../../common.php';

function clear() {
    // clear the store data
    unset($_SESSION['tokens']);
    unset($_SESSION['serialized']);
}


if (array_key_exists('denied', $_GET)) {
    clear();
    header("Location: ".BASE_HREF.'/settings.php');
    die();
}

try{
    $store = new OAuth\Store\Session();

    $service = $store->restoreFromSerialized($_SESSION['serialized']);
    if (!$service->areWeAuthorized()) {
        $service->getAccessToken($_GET);
    }
} catch(Exception $e){
    $_SESSION['flashMsg'] = 'Error -- I think you cancelled?';
    clear();
    header("Location: ".BASE_HREF.'/settings.php');
    die();
}

if ($service->areWeAuthorized()) {

    $store = $service->getStore();
    $token = $store->getTokens(\OAuth\Token::TYPE_ACCESS);
    $rsp = $service->account_verify_credentials();

    clear();

    $twitterData = [
        'accessToken' => $token->getToken(),
        'accessTokenSecret' => $token->getSecret(),
        'screenName' => $rsp->screen_name,
        'profileImage' => $rsp->profile_image_url_https
    ];

    $siteSettings = $site->getSettings();
    $siteSettings->twitter = $twitterData;

    $saved = $site->saveSettings($siteSettings);
    if ($saved === false) {
        $_SESSION['flashMsg'] = 'Failed to save twitter settings. Probably a permissions issue.';
    }

} else {
    $_SESSION['flashMsg'] = 'Auth error with Twitter.';
}

header("Location: ".BASE_HREF.'/settings.php');