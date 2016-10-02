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
		<h1>Choose Event Type</h1>
	</div><!-- /header -->

	<div data-role="content" data-theme="d">	
		<h2>Choose Event Type</h2>
		<a href="addedit-game.php" data-role="button" title="add game">Game</a>
		<a href="addedit-tournament.php" data-role="button" title="add tournament">Tournament</a>
	</div><!-- /content -->

</div><!-- /page -->

</body>
</html>