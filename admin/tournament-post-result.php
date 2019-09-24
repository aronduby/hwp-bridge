<?php
require '../common.php';

if(!empty($_POST) && array_key_exists('tournament_id', $_POST)){

	$dbh = PDODB::getInstance();

	$sql = "
		INSERT INTO recent SET
			site_id = 1,
			season_id = :season_id,
			renderer = 'tournament',
			content = :content,
			sticky = 0,
			created_at = NOW(),
			updated_at = NOW()
	";

	$stmt = $dbh->prepare($sql);
	$stmt->execute([
		'season_id' => intval($_POST['season_id']),
		'content' => '['.intval($_POST['tournament_id']).']'
	]);

	header("Location: tournament.php?tournament_id=".$_POST['tournament_id']);
	die();
}
else {
	$tournament = new Tournament($_GET['tournament_id'], PDODB::getInstance());
}

require '_pre.php';
?>

<div data-role="page" data-theme="b">

	<div data-role="header" data-theme="b">
		<a href="index.php" data-rel="back" title="back" data-icon="back" data-iconpos="notext" data-direction="reverse">back</a>
		<h1>Post Tournament Results?</h1>
	</div><!-- /header -->

	<div data-role="content">
		<form action="tournament-post-result.php" method="POST" data-ajax="false">
			<input type="hidden" name="tournament_id" value="<?php echo $tournament->id ?>" />
			<input type="hidden" name="season_id" value="<?php echo $tournament->season_id?>" />

			<h2>Are you sure the tournament is over and you want to post the results?</h2>

			<div class="ui-body ui-body-b">
				<fieldset class="ui-grid-a">
					<div class="ui-block-a">
						<a href="tournament.php?tournament_id=<?= $tournament->id ?>" data-rel="back" data-role="button" data-theme="d">Cancel</a>
					</div>
					<div class="ui-block-b">
						<button type="submit">Submit</button>
					</div>
				</fieldset>
			</div>
		</form>
	</div><!-- /content -->

</div><!-- /page -->

<?php require '_post.php'; ?>