<?php
/**
 * @var Register $register
 * @var Season $season
 * @var Site $site
 */
require '../common.php';

if (!empty($_POST['message'])) {
    $success = exec('php '.ARTISAN_PATH.' events:shout "'.addcslashes($_POST['message'], '"').'"', $output, $resultCode);
	if ($success !== false) {
		header("Location: index.php");
		die();
	}
}

require '_pre.php';
?>

<div data-role="page" data-theme="b">

    <div data-role="header" data-theme="b">
        <h1>Shout</h1>
    </div>

    <div data-role="content" data-theme="d">

	    <?php
	    if (isset($output)) {
            ?>
		    <div>
			    <div data-role="content" data-theme="e" class="ui-alert">
                    <?= join("\n", $output) ?>
			    </div>
		    </div>
		    <?php
	    }
	    ?>

        <form action="shout.php" method="POST" data-ajax="false">
            <label for="textarea-a">Message:</label>
            <textarea name="message" id="textarea-a" placeholder="add your message"></textarea>

            <div>
                <button type="submit" data-theme="b" data-rel="back">Shout It</button></div>
            </div>
        </form>
    </div>

</div>

<?php require '_post.php'; ?>