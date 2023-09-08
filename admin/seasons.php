<?php
require '../common.php';

$seasons = Season::getAllSeasons('DESC', $register);

require '_pre.php';
?>

<div data-role="page" data-theme="b">

	<div data-role="header" data-theme="b">
		<a href="index.php" title="back" data-icon="home" data-iconpos="notext" data-direction="reverse">back</a>
		<a href="addedit-season.php" title="add season" class="ui-btn-right" data-icon="plus" data-iconpos="notext" data-rel="dialog">add</a>
		<h1>Season List</h1>
	</div><!-- /header -->

	<div data-role="content">	
		<?php
        include '_alerts.php';

		if($seasons !==false){
			print '<ul data-role="listview" data-theme="d" data-divider-theme="d" data-split-theme="d" data-split-icon="check">';

            foreach($seasons as $s){
            	?>
	            <li>
		            <a href="addedit-season.php?season_id=<?= $s->id ?>" title="edit season" data-ajax="false">
			            <?= $s->title ?>
			            <?= $s->current ? ' <span class="ui-icon-inline ui-icon ui-icon-star" title="current season">&nbsp;</span>' : '' ?>
			            <?= $s->id === $season->id ? ' <span class="ui-icon-inline ui-icon ui-icon-check" title="editing season">&nbsp;</span>' : '' ?>
		            </a>
		            <a href="season-make-active.php?season_id=<?= $s->id ?>" title="make active" data-ajax="false">make active</a>
	            </li>
	            <?php
            }

		} else {
			print '<p>No seasons found</p>';
		}
		?>
	</div><!-- /content -->

</div><!-- /page -->

<?php require '_post.php'; ?>