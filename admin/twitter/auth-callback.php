<?php
require '../../common.php';

$store = new OAuth\Store\Session();

try{
    $service = $store->restoreFromSerialized($_SESSION['serialized']);
    if (!$service->areWeAuthorized()) {
        $service->getAccessToken($_GET);
    }
} catch(Exception $e){}

if ($service->areWeAuthorized()) {

    $store = $service->getStore();
    $token = $store->getTokens(\OAuth\Token::TYPE_ACCESS);
    $rsp = $service->account_verify_credentials();

    // clear the store data
    unset($_SESSION['tokens']);
    unset($_SESSION['serialized']);

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

header("Location: ".BASE_HREF);