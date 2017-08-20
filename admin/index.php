<?php
require '../common.php';
if(!isset($_COOKIE['in'])){
	header("Location: login.php");;
	die();
}
?>
<!DOCTYPE html> 
<html> 
	<head> 
	<title>Admin - Hudsonville Water Polo</title> 
	<meta name="viewport" content="width=device-width, initial-scale=1"> 
	<link rel="stylesheet" href="http://code.jquery.com/mobile/1.1.1/jquery.mobile-1.1.1.min.css" />
	<script src="http://code.jquery.com/jquery-1.7.1.min.js"></script>
	<script src="http://code.jquery.com/mobile/1.1.1/jquery.mobile-1.1.1.min.js"></script>
	
</head> 
<body> 

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

</body>
</html>