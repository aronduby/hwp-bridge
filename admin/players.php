<?php
require '../common.php';
if(!isset($_COOKIE['in'])){
	header("Location: login.php");;
	die();
}

$player_list = $season->getPlayersByTeam();

require '_pre.php';
?>

<div data-role="page" data-theme="b">

	<div data-role="header" data-theme="b">
		<a href="index.php" title="back" data-icon="home" data-iconpos="notext" data-direction="reverse">back</a>
		<a href="addedit-player.php" title="add player" class="ui-btn-right" data-icon="plus" data-iconpos="notext" data-rel="dialog">add</a>
		<h1>Players List</h1>
	</div><!-- /header -->

	<div data-role="content">	
		<?php
		if($player_list !==false){

			print '<ul data-role="listview" data-theme="d" data-divider-theme="d" data-split-theme="d" data-split-icon="gear">';
			foreach($player_list as $team => $players){
				print '<li data-role="list-divider">'.($team=='V' ? 'Varsity' : $team).'</li>';
				foreach($players as $p){
					print '<li><a href="addedit-player.php?player_id='.$p->id.'" title="edit player">'.(strlen($p->number) ? '#'.$p->number : '').' '.$p->name.'</a></li>';
				}
			}
			print '</ul>';

		} else {
			print '<p>No players found for this season</p>';
		}
		?>
	</div><!-- /content -->

</div><!-- /page -->

<?php require '_post.php'; ?>