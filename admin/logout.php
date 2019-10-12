<?php
require '../common.php';

Auth::logout();

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