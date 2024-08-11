<?php
require '../common.php';

$player_list = $season->getPlayersByTeam('V,JV,STAFF', "sort IS NOT NULL DESC,	sort, CAST(pts.number as INT)");

require '_pre.php';
?>

<div data-role="page" data-theme="b">
    <?php
    include "_flash.php";
    ?>

	<div data-role="header" data-theme="b">
		<a href="index.php" title="back" data-icon="home" data-iconpos="notext" data-direction="reverse">back</a>
		<a href="addedit-player.php" title="add player" class="ui-btn-right" data-icon="plus" data-iconpos="notext" data-rel="dialog">add</a>
		<h1>Players List</h1>
	</div><!-- /header -->

	<div data-role="content">	
		<?php
        include '_alerts.php';

        ?>
		<ul data-role="listview" data-theme="d" data-divider-theme="d" data-split-theme="d" data-split-icon="gear">
			<li data-role="list-divider">Batch</li>
			<li>
				<div data-role="content">
					<a href="add-existing-players.php" data-role="button" data-theme="b">Batch Existing Players</a>
				</div>
			</li>

            <?php
            if ($player_list !== false) {

                foreach($player_list as $team => $players){
                    print '<li data-role="list-divider">'.($team=='V' ? 'Varsity' : $team).'</li>';
                    foreach($players as $p){
                        print '<li><a href="addedit-player.php?player_id='.$p->id.'" title="edit player">'.(strlen($p->number) ? '#'.$p->number : '').' '.$p->name.'</a></li>';
                    }
                }

            } else {
                print '<p>No players found for this season</p>';
            }
            ?>
		</ul>
	</div><!-- /content -->

</div><!-- /page -->

<?php require '_post.php'; ?>