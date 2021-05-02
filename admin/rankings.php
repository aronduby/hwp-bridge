<?php
/**
 * @var Register $register
 */
require '../common.php';

$rankings = Ranking::getAll($register);

require '_pre.php';
?>

<div data-role="page" data-theme="b">

	<div data-role="header" data-theme="b">
		<a href="index.php" title="back" data-icon="home" data-iconpos="notext" data-direction="reverse">back</a>
		<a href="addedit-rankings.php" title="add badge" class="ui-btn-right" data-icon="plus" data-iconpos="notext">add</a>
		<h1>Rankings</h1>
	</div><!-- /header -->

	<div data-role="content">	
		<?php
        include '_alerts.php';

		if($rankings !==false && count($rankings) > 0){
			?>
			<ul data-role="listview" data-theme="d">
				<?php
				foreach($rankings as $r){
					?>
					<li><a href="addedit-rankings.php?ranking_id=<?=$r->id ?>" title="edit ranking">
						<h3>Week <?= $r->week ?></h3>
						<p><?= $r->start->format(USER_DATE_FORMAT) ?> - <?= $r->end->format(USER_DATE_FORMAT) ?></p>
					</a></li>
					<?php
				}
				?>
			</ul>
			<?php
		} else {
			print '<p>No rankings found</p>';
		}
		?>
	</div><!-- /content -->

</div><!-- /page -->

<?php require '_post.php'; ?>