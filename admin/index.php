<?php
require '../common.php';

require '_pre.php';
?>

<div data-role="page" data-theme="b">
	<?php
    include "_flash.php";
	?>

	<div data-role="header" data-theme="b">
		<a href="settings.php" title="settings" data-icon="gear" data-iconpos="notext" data-direction="reverse">settings</a>
		<h1>Index</h1>
        <a href="logout.php" data-icon="alert" data-ajax="false" class="ui-btn-right">Logout</a>
	</div><!-- /header -->

	<div data-role="content">

        <?php
        include '_alerts.php';
        ?>

		<ul data-role="listview" data-inset="true" data-theme="d" data-divider-theme="d">
			<li><a href="events.php" title="events">Events</a></li>
			<li><a href="players.php" title="players">Players</a></li>
			<li data-role="list-divider"></li>
			<li><a href="pastevents.php" title="past events">Past Events</a></li>
			<li data-role="list-divider"></li>
			<li><a href="articles.php" title="articles">Articles</a></li>
			<li><a href="badges.php" title="badges">Badges</a></li>
			<li><a href="rankings.php" title="rankings">Rankings</a></li>
		</ul>

		<ul data-role="listview" data-inset="true" data-theme="d" data-divider-theme="d">
			<li><a href="seasons.php" title="seasons">Seasons</a></li>
		</ul>

        <ul data-role="listview" data-inset="true" data-theme="d" data-divider-theme="d">
          <li><a href="<?= PUBLIC_HREF ?>" title="public site">Public Site</a></li>
        </ul>
	</div><!-- /content -->

</div><!-- /page -->

<?php require '_post.php'; ?>