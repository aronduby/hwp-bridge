<?php
/** @noinspection SqlResolve */
/**
 * @var Register $register
 * @var Season $season
 * @var Site $site
 */

require '../common.php';

if (!empty($_POST)) {

	try{
		$dbh = PDODB::getInstance();

		$sql = "
			INSERT INTO seasons SET
				id = :season_id,
                site_id = :site_id,
				title = :title,
				short_title = :short_title,
				current = :current,
				ranking = :ranking,
				ranking_tie = :ranking_tie,
				ranking_title = :ranking_title,			                        
                ranking_updated = NOW(),
				created_at = NOW()
			ON DUPLICATE KEY UPDATE
                site_id = VALUES(site_id),
				title = VALUES(title),
				short_title = VALUES(short_title),
				current = VALUES(current),
				ranking = VALUES(ranking),
				ranking_tie = VALUES(ranking_tie),
                ranking_title = VALUES(ranking_title),
                ranking_updated = NOW(),
				updated_at = NOW(),
				id = LAST_INSERT_ID(id)
		";

		$insert_update_stmt = $dbh->prepare($sql);
        $insert_update_stmt->bindValue(':season_id', $_POST['season_id'], PDO::PARAM_INT);
        $insert_update_stmt->bindValue(':site_id', $site->id, PDO::PARAM_INT);
        $insert_update_stmt->bindValue(':title', $_POST['title'], PDO::PARAM_STR);
        $insert_update_stmt->bindValue(':short_title', $_POST['short_title'], PDO::PARAM_STR);
        $insert_update_stmt->bindValue(':current', $_POST['current'], PDO::PARAM_BOOL);
        $insert_update_stmt->bindValue(':ranking', $_POST['ranking'], PDO::PARAM_INT);
        $insert_update_stmt->bindValue(':ranking_tie', $_POST['ranking_tie'], PDO::PARAM_BOOL);
        $insert_update_stmt->bindValue(':ranking_title', empty($_POST['ranking_title']) ? null : $_POST['ranking_title']);
		$inserted = $insert_update_stmt->execute();
		$season_id = $dbh->lastInsertId();

		if($inserted){
			// if it's current, unset all of the other current seasons for this site
			if ($_POST['current'] == true) {
				$clearSql = "UPDATE seasons SET current = 0 WHERE current = 1 AND site_id = :site_id AND id != :season_id";

				$clearStmt = $dbh->prepare($clearSql);
				$clearStmt->bindValue(':site_id', $site->id, PDO::PARAM_INT);
				$clearStmt->bindValue(':season_id', $season_id, PDO::PARAM_INT);

				if (!$clearStmt->execute()) {
					throw new Exception('Season was saved, but other seasons are still marked as current. Please manually edit those seasons');
				}
			}

			header("Location: seasons.php");
			die();
		} else {
			throw new Exception('Could not save season in database');
		}	
	
	} catch(Exception $e){
		$form_errors = $e->getMessage();
		$editSeason = new Season(isset($_POST['season_id']) ? $_POST['season_id'] : null, $register);
	}

} else {
    $editSeason = new Season(isset($_GET['season_id']) ? $_GET['season_id'] : null, $register);
}

require '_pre.php';
?>

<div data-role="page" data-theme="b">
	<link rel="stylesheet" href="css/jquery-mobile-overrides.css" />

	<div data-role="header" data-theme="b">
		<a href="index.php" data-rel="back" title="back" data-icon="back" data-iconpos="notext" data-direction="reverse">back</a>
		<h1>Edit <?php echo $editSeason->title ?></h1>
	</div><!-- /header -->

	<div data-role="content">	
		<?php
		include '_form-errors.php';
		?>
		<form action="addedit-season.php<?= isset($_GET['season_id']) ? '?season_id='.$_GET['season_id'] : ''?>" method="POST" data-ajax="false" autocomplete="off" enctype="multipart/form-data">
			<input type="hidden" name="season_id" value="<?php echo $editSeason->id?>" />

			<ul data-role="listview">
				<li data-role="fieldcontain">
					<label for="title">Title:</label>
					<input type="text" name="title" id="title" placeholder="title" value="<?= $editSeason->title ?>" />
					<p class="helper-text ui-li-desc">usually full school year, ie 2019/2020</p>
				</li>
				<li data-role="fieldcontain">
					<label for="short_title">Short Title:</label>
					<input type="text" name="short_title" id="short_title" placeholder="short title" value="<?= $editSeason->short_title ?>" />
					<p class="helper-text ui-li-desc">usually the last two of the year of the start of season, ie 19</p>
				</li>
				<li data-role="fieldcontain">
					<label for="current">Current Season:</label>
					<select name="current" id="current" data-role="slider" data-theme="d" data-track-theme="d">
						<option value="0" <?php echo $editSeason->current == false ? 'selected="selected"' : '' ?>>No</option>
						<option value="1" <?php echo $editSeason->current == true ? 'selected="selected"' : '' ?>>Yes</option>
					</select>
				</li>

				<li role="list-divider" data-theme="c">Ranking</li>

				<li data-role="fieldcontain">
					<label for="ranking">Ranking:</label>
					<input type="number" name="ranking" id="ranking" placeholder="ranking" value="<?= $editSeason->ranking ?>" />
					<p class="helper-text ui-li-desc">auto-updated by ranking parser</p>
				</li>

				<li data-role="fieldcontain">
					<label for="ranking_tie">Ranking Is Tied:</label>
					<select name="ranking_tie" id="ranking_tie" data-role="slider" data-theme="d" data-track-theme="d">
						<option value="0" <?php echo $editSeason->ranking_tie === 0 ? 'selected="selected"' : '' ?>>No</option>
						<option value="1" <?php echo $editSeason->ranking_tie === 1 ? 'selected="selected"' : '' ?>>Yes</option>
					</select>
					<p class="helper-text ui-li-desc">auto-updated by ranking parser</p>
				</li>

				<li role="list-divider" data-theme="c">Ranking Override</li>

				<li data-role="fieldcontain">
					<label for="ranking_title">Title:</label>
					<input type="text" name="ranking_title" id="ranking_title" placeholder="ranking title" value="<?= $editSeason->ranking_title ?>" />
					<p class="helper-text ui-li-desc">manually set the ranking title, overriding the ranking parser values above</p>
				</li>


				<li data-role="fieldcontain">
					<button type="submit">Save</button>
				</li>
			</ul>

		</form>
	</div><!-- /content -->

</div><!-- /page -->

<?php require '_post.php'; ?>