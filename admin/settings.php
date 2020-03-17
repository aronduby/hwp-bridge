<?php
include '../common.php';

$settings = $site->getSettings();

require '_pre.php';
?>
<div data-role="page" data-theme="b">
    <?php
    include "_flash.php"
    ?>

    <div data-role="header" data-theme="b">
        <a href="index.php" title="back" data-icon="home" data-iconpos="notext" data-direction="reverse">back</a>
        <a href="addedit-badge.php" title="add badge" class="ui-btn-right" data-icon="plus" data-iconpos="notext">add</a>
        <h1>Site Settings</h1>
    </div><!-- /header -->

    <div data-role="content">
        <?php
        include "_form-errors.php";
        ?>

        <form action="settings.php.php" method="POST" data-ajax="false">
            <div data-role="header" data-theme="e">
                <h2>Services</h2>
            </div>

            <ul data-role="listview" data-theme="d" data-divider-theme="d" data-split-theme="d" data-split-icon="gear">
                <li>
                    <a href="/twitter/auth.php" data-ajax="false">
                        <?php
                        if ($settings->twitter->accessToken) {
                            ?>
                            <img src="<?= str_replace('_normal.', '.', $settings->twitter->profileImage) ?>" alt="profile image" />
                            <h3>@<?= $settings->twitter->screenName ?></h3>
	                        <p>click to re-authorize</p>
                            <?php
                        } else {
                            ?>Auth Twitter<?php
                        }
                        ?>
                    </a>
                </li>
            </ul>

            <div data-role="header" data-theme="e">
                <h2>Settings</h2>
            </div>
            <div data-role="content">
                <p>coming soon</p>
            </div>

        </form>
    </div><!-- /content -->

</div><!-- /page -->

<?php require '_post.php'; ?>
