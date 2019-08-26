<?php
require '../common.php';
if(!isset($_COOKIE['in'])){
	header("Location: login.php");;
	die();
}

require '_pre.php';
?>

<div data-role="page" data-theme="b">

	<div data-role="header" data-theme="b">
		<h1>Index</h1>
	</div><!-- /header -->

	<div data-role="content">
		<ul data-role="listview" data-inset="true" data-theme="d" data-divider-theme="d">
			<li><a href="events.php" title="events">Events</a></li>
			<li><a href="players.php" title="players">Players</a></li>
			<li data-role="list-divider"></li>
			<li><a href="pastevents.php" title="past events">Past Events</a></li>
			<li data-role="list-divider"></li>
			<li><a href="badges.php" title="badges">Badges</a></li>
		</ul>

    <ul data-role="listview" data-inset="true" data-theme="d" data-divider-theme="d">
      <li><a href="<?= PUBLIC_HREF ?>" title="public site">Public Site</a></li>
    </ul>
	</div><!-- /content -->

</div><!-- /page -->

<?php require '_post.php'; ?>