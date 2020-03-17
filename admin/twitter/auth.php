<?php
require '../../common.php';

try{
    $store = new OAuth\Store\Session();

    $twitter = new OAuth\Service\Twitter($store);
    if (site::$ngrok) {
        $twitter->redirect_url = 'https://'.site::$ngrok.'/twitter/auth-callback.php';
    } else {
        $twitter->redirect_url = BASE_HREF.'/twitter/auth-callback.php';
    }

    $twitter->authorize();

} catch(Exception $e){
    $_SESSION['flashMsg'] = 'Could not redirect to twitter for auth';
    header("Location: ".BASE_HREF);
    die();
}