<?php
require '../common.php';

if (Auth::authenticated()) {
    header('Location: index.php');
    die();
}

if (!empty($_POST)) {
    try {
        if (Auth::login($_POST['username'], $_POST['password'])) {
            header('Location: index.php');
            die();
        }
    } catch (\GuzzleHttp\Exception\ClientException $e) {
        $form_errors = "Supplied credentials were incorrect";

    } catch (Exception $e) {
        $form_errors = 'An unexpected error happened. Please try again.';
    }
}

require '_pre.php';
?>

    <div data-role="page" data-theme="b">

        <div data-role="header" data-theme="b">
            <h1>Please Login</h1>
        </div><!-- /header -->

        <div data-role="content">

            <p>Please login to manage games. If you don't have a log-in and think you should contact <a href="mailto:aron.duby@gmail.com" title="duby">Duby</a>.</p>

            <?php
            if(isset($form_errors)){
                print '<div data-role="content" data-theme="e">';
                print $form_errors;
                print '</div>';
            }
            ?>

            <form action="login.php" method="POST">
                <ul data-role="listview" data-inset="true">
                    <li data-role="fieldcontain" class="ui-hide-label">
                        <label for="username">Username:</label>
                        <input type="text" name="username" id="username" placeholder="username" required autocomplete="username" />
                    </li>
                    <li data-role="fieldcontain" class="ui-hide-label">
                        <label for="password">Password:</label>
                        <input type="password" name="password" id="password" placeholder="password" required autocomplete="current-password" />
                    </li>
                    <li data-role="fieldcontain" class="ui-hide-label">
                        <button type="submit">Login</button>
                    </li>

                </ul>
            </form>
        </div><!-- /content -->

    </div><!-- /page -->

<?php require '_post.php'; ?>