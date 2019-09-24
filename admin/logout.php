<?php

use Auth0\SDK\Auth0;

require __DIR__ . '/../define.php';
require __DIR__ . '/../vendor/autoload.php';

$auth0 = new Auth0([
    'domain' => AUTH0_DOMAIN,
    'client_id' => AUTH0_CLIENT_ID,
    'client_secret' => AUTH0_CLIENT_SECRET,
    'redirect_uri' => AUTH0_REDIRECT_URI,
    'persist_id_token' => true,
    'persist_access_token' => true,
    'persist_refresh_token' => true,
    'scope' => 'openid profile email',
]);

$userInfo = $auth0->getUser();

if ($userInfo) {
    $auth0->logout();
    $logout_url = sprintf('https://%s/v2/logout?client_id=%s&returnTo=%s', AUTH0_DOMAIN, AUTH0_CLIENT_ID, AUTH0_LOGOUT_URI);
    header('Location: ' . $logout_url);
    die();
}

require '_pre.php';
?>

    <div data-role="page" data-theme="b">

        <div data-role="header" data-theme="b">
            <h1>Logged Out</h1>
        </div><!-- /header -->

        <div data-role="content">
            <h3>Logged Out</h3>

            <p>You have been logged out. Click the button below to log back in.</p>

            <p><a href="login.php" data-ajax="false" data-role="button" data-inline="true" data-theme="b">Login</a></p>
        </div><!-- /content -->

    </div><!-- /page -->

<?php require '_post.php'; ?>