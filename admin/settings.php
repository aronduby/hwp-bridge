<?php
include '../common.php';

$settings = $site->getSettings();

require '_pre.php';
?>
<div data-role="page" data-theme="b">
    <?php
    include "_flash.php";
    ?>

    <div data-role="header" data-theme="b">
        <a href="index.php" title="back" data-icon="home" data-iconpos="notext" data-direction="reverse">back</a>
        <h1>Site Settings</h1>
    </div><!-- /header -->

    <div data-role="content">
        <?php
        include "_form-errors.php";
        ?>

        <form action="settings.php" method="POST" data-ajax="false">
            <div data-role="header" data-theme="e">
                <h2>Services</h2>
            </div>

            <ul data-role="listview" data-theme="d" data-divider-theme="d" data-split-theme="d" data-split-icon="gear">
                <li>
                    <a href="/twitter/auth.php" data-ajax="false">
                        <?php
                        if ($settings->twitter->accessToken) {
							if (isset($settings->twitter->profileImage)) {
								?><img src="<?= str_replace('_normal.', '.', $settings->twitter->profileImage) ?>" alt="profile image" /><?php
							}
                            ?>
                            <h3>@<?= $settings->twitter->screenName ?></h3>
	                        <p>click to re-authorize</p>
                            <?php
                        } else {
	                        ?>
	                        <h3>Auth Twitter</h3>
	                        <p>click to authorize your twitter account</p>
	                        <?php
                        }
                        ?>
                    </a>
                </li>
            </ul>

            <div data-role="header" data-theme="e">
                <h2>Settings</h2>
            </div>
            <div data-role="content">
                <p>coming soon&trade;</p>
            </div>

        </form>
    </div><!-- /content -->

</div><!-- /page -->

<?php require '_post.php'; ?>
