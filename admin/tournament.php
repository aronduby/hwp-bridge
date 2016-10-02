<?php
require '../common.php';
if(!isset($_COOKIE['in'])){
	header("Location: login.php");;
	die();
}

$tournament = new Tournament($_GET['tournament_id'], PDODB::getInstance());
if($tournament != false){
	$games = $tournament->getGames();
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
		<a href="index.php" title="home" data-icon="home" data-iconpos="notext" data-direction="reverse">home</a>
		<h1><?php echo $tournament !== false ? $tournament->title : 'Error' ?></h1>
		<a href="addedit-tournament.php?tournament_id=<?php echo $tournament->id ?>" title="edit" class="ui-btn-right" data-icon="gear" data-iconpos="notext" data-direction="reverse">edit</a>
	</div><!-- /header -->

	<div data-role="content">	
		<?php
		if($tournament !== false){
			print '<ul data-role="listview" data-theme="d" data-split-theme="d" data-split-icon="gear">';
				print '<li data-role="list=divider" data-theme="e">Games</li>';
				
				foreach($games as $game)
					print $game->output('mobile-listing');

				// add additional links
				print '<li data-role="list=divider" data-theme="e">Options</li>';
				print '<li data-icon="plus"><a href="addedit-game.php?tournament_id='.$tournament->id.'" title="Add Game">Add New Game</a></li>';
				print '<li data-icon="gear"><a href="addedit-tournament.php?tournament_id='.$tournament->id.'" title="Edit Tournament">Edit Tournament</a></li>';
				print '<li data-icon="check"><a href="tournament-post-result.php?tournament_id='.$tournament->id.'" title="Post Tournament Results" data-rel="dialog">Post Results</a></li>';
			print '</ul>';
		} else {
			print '<p>No tournament found with that id. Please head back to <a href="index.php" title="home page">the homepage</a> and try again</p>.';
		}
		?>
	</div><!-- /content -->

</div><!-- /page -->

</body>
</html>