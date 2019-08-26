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
		<h1>Choose Event Type</h1>
	</div><!-- /header -->

	<div data-role="content" data-theme="d">	
		<h2>Choose Event Type</h2>
		<a href="addedit-game.php" data-role="button" title="add game">Game</a>
		<a href="addedit-tournament.php" data-role="button" title="add tournament">Tournament</a>
	</div><!-- /content -->

</div><!-- /page -->

<?php require '_post.php'; ?>