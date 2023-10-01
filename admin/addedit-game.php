<?php /** @noinspection SqlResolve */
/**
 * @var Register $register
 * @var Season $season
 * @var Site $site
 */
require '../common.php';

if (!empty($_POST)) {

	$dbh = PDODB::getInstance();

	$start = strtotime(str_replace('-', '/', $_POST['start']));
	$end = strtotime(str_replace('-', '/', $_POST['end']));

	if($start !== false && $end !== false && isset($_POST['team']) && isset($_POST['location_id']) && isset($_POST['opponent'])){

		$start = date('Y-m-d G:i:s', $start);
		$end = date('Y-m-d G:i:s', $end);

		$score_us = $_POST['score_us']!=='' ? intval($_POST['score_us']) : 'null';
		$score_them = $_POST['score_them']!=='' ? intval($_POST['score_them']) : 'null';

		$sql = "INSERT INTO games SET
				id = ".($_POST['game_id'] ? $dbh->quote($_POST['game_id']) : 'null').",
				site_id = ".intval($site->id).", 
				season_id = ".$dbh->quote($_POST['season_id']).",
				tournament_id = ".(intval($_POST['tournament_id']) ? $dbh->quote($_POST['tournament_id']) : "NULL").",
				location_id = ".intval($_POST['location_id']).",
				album_id = ".(intval($_POST['album_id']) ? $dbh->quote($_POST['album_id']) : "NULL").",
				team = ".$dbh->quote($_POST['team']).",
				title_append = ".$dbh->quote($_POST['title_append']).",
				start = ".$dbh->quote($start).",
				end = ".$dbh->quote($end).",
				district = ".intval($_POST['district']).",
				opponent = ".$dbh->quote($_POST['opponent']).",
				score_us = ".$score_us.",
				score_them = ".$score_them.",
				created_at = NOW()
			ON DUPLICATE KEY UPDATE
				id = VALUES(id),
				site_id = VALUES(site_id),
				season_id = VALUES(season_id),
				tournament_id = VALUES(tournament_id),
				location_id = VALUES(location_id),
				album_id = VALUES(album_id),
				team = VALUES(team),
				title_append = VALUES(title_append),
				start = VALUES(start),
				end = VALUES(end),
				district = VALUES(district),
				opponent = VALUES(opponent),
				score_us = VALUES(score_us),
				score_them = VALUES(score_them),
				updated_at = NOW(),
				id = LAST_INSERT_ID(id)";

		$inserted  = $dbh->exec($sql);
		$game_id = $dbh->lastInsertId();

		if ($inserted !== false) {
			$redirect = true;

			// also add to the recent listing
			if ($_POST['action'] === 'saveAndPost') {
				$json = json_encode([intval($game_id)]);
				$recentStmt = $dbh->prepare('INSERT INTO recent (site_id, season_id, renderer, content, created_at, updated_at) VALUES (:site_id, :season_id, "game", :content, NOW(), NOW())');
				$recentStmt->bindValue(':site_id', $site->id, PDO::PARAM_INT);
				$recentStmt->bindValue(':season_id', $_POST['season_id'], PDO::PARAM_INT);
				$recentStmt->bindValue(':content', $json);

				$posted = $recentStmt->execute();
				if (!$posted) {
					$redirect = false;
					$form_errors = 'Game was saved, but we were unable to post it to the recent listing';
                    if(isset($_POST['tournament_id']))
                        $tournament = new Tournament($_POST['tournament_id'], $register);
				}
			}

			if ($redirect) {
				if ($_POST['action'] === 'saveAndNew') {
					$location = 'addedit-game.php';
					if (intval($_POST['tournament_id'])) {
						$location .= '?tournament_id='.$_POST['tournament_id'];
					}
					header("Location: ".$location);

				} elseif (intval($_POST['tournament_id'])) {
                    header("Location: tournament.php?tournament_id=".$_POST['tournament_id']);

                } elseif (strtotime($end) < time()) {
                    header("Location: pastevents.php");

                } else {
                    header("Location: events.php");
                }

                die();
			}

		} else {
			$form_errors = 'Could not save the form. Please try again later';
			if(isset($_POST['tournament_id']))
				$tournament = new Tournament($_POST['tournament_id'], $register);
		}

	} else {
		$form_errors = 'You are missing some required fields, please try again.';
		$tournament = new Tournament($_POST['tournament_id'], $register);
	}

}
else {
	$game = new Game(isset($_GET['game_id']) ? $_GET['game_id'] : null, $register);
	if(isset($_GET['tournament_id'])) {
        $game->tournament_id = $_GET['tournament_id'];
        $tournament = new Tournament($_GET['tournament_id'], $register);
    } else {
	    if ($game->tournament_id) {
            $tournament = new Tournament($game->tournament_id, $register);
        } else {
            $tournament = false;
        }
    }
}

require '_pre.php';
?>

<div data-role="page" data-theme="b">

	<div data-role="header" data-theme="b">
		<a href="index.php" data-rel="back" title="back" data-icon="back" data-iconpos="notext" data-direction="reverse">back</a>
		<h1>Edit <?php echo $game->title ?></h1>
	</div><!-- /header -->

	<div data-role="content">	
		<?php
        include '_form-errors.php';
		?>
		<form action="addedit-game.php" method="POST" data-ajax="false">
			<input type="hidden" name="game_id" value="<?php echo $game->id ?>" />
			<input type="hidden" name="season_id" value="<?php echo isset($game->season_id) ? $game->season_id : $season->id?>" />

			<ul data-role="listview">
				<li role="list-divider" data-theme="c">Details</li>

				<li data-role="fieldcontain">
					<label for="g-title">Title Append:</label>
		        	<input type="text" name="title_append" id="g-title" placeholder="title append" value="<?php echo $game->title_append ?>" />
				</li>

				<li data-role="fieldcontain">
					<label for="g-start">Start:</label>
		        	<input type="datetime-local" name="start" id="g-start" placeholder="start" value="<?php echo $game->start->format(INPUT_DATETIME_FORMAT) ?>" />
				</li>

				<li data-role="fieldcontain">
					<label for="g-end">End:</label>
		        	<input type="datetime-local" name="end" id="g-end" placeholder="end" value="<?php echo $game->end->format(INPUT_DATETIME_FORMAT) ?>" />
				</li>

				<li data-role="fieldcontain">
					<label for="g-opponent">Opponent:</label>
		        	<input type="text" name="opponent" id="g-opponent" placeholder="opponent" value="<?php echo $game->opponent ?>" />
				</li>

				<li data-role="fieldcontain" >
					<fieldset data-role="controlgroup" data-type="horizontal">
				     	<legend>Team:</legend>
			     		<label for="g-team-v">Varsity</label>
			     		<input type="radio" name="team" id="g-team-v" value="V" data-theme="d" <?php echo $game->team=='V' ? 'checked="checked"' : '' ?> />
			     		<label for="g-team-jv">JV</label>
			         	<input type="radio" name="team" id="g-team-jv" value="JV" data-theme="d" <?php echo $game->team=='JV' ? 'checked="checked"' : '' ?> />
				    </fieldset>
				</li>

				<li data-role="fieldcontain">
					<label for="g-tournament">Tournament:</label>
					<select name="tournament_id" id="g-tournament" data-theme="d">
						<option value=""></option>
			        	<?php
				        $tournaments = Tournament::getOptionsForSelect($register);
			        	foreach($tournaments as $t) {
                            print '<option value="' . $t->id . '" ' . ($t->id == $game->tournament_id ? 'selected="selected"' : '') . '>' . $t->title . '</option>';
                        }
			        	?>
		        	</select>
				</li>

				<li data-role="fieldcontain">
					<label for="g-location">Location:</label>
					<select name="location_id" id="g-location" data-theme="d">
			        	<?php
				        $locations = Location::getOptionsForSelect($register);
			        	foreach($locations as $l) {
                            print '<option value="' . $l->id . '" ' . ($l->id == $game->location_id || ($tournament && $tournament->location_id === $l->id) ? 'selected="selected"' : '') . '>' . $l->title . '</option>';
                        }
			        	?>
		        	</select>
				</li>

				<li data-role="fieldcontain">
					<label for="g-district">District Game:</label>
			        <select name="district" id="g-district" data-role="slider" data-theme="d" data-track-theme="d">
						<option value="0" <?php echo $game->district === 0 ? 'selected="selected"' : '' ?>>No</option>
						<option value="1" <?php echo $game->district === 1 ? 'selected="selected"' : '' ?>>Yes</option>
			        </select>
				</li>

				<li role="list-divider" data-theme="c">Final Score</li>

				<li data-role="fieldcontain">
					<label for="g-score_us">Us:</label>
		        	<input type="number" name="score_us" id="g-score_us" placeholder="score_us" value="<?php echo $game->score_us ?>" />
				</li>

				<li data-role="fieldcontain">
					<label for="g-score_them">Them:</label>
		        	<input type="number" name="score_them" id="g-score_them" placeholder="score_them" value="<?php echo $game->score_them ?>" />
				</li>

				<li role="list-divider" data-theme="c">Photo Album</li>
				<li data-role="fieldcontain">
					<label for="g-album_id">Photo Album:</label>
					<select name="album_id" id="g-album_id" data-theme="d">
						<option value=""></option>
			        	<?php
				        $albums = PhotoAlbum::getOptionsForSelect($register);
			        	foreach($albums as $id=>$title){
			        		print '<option value="'.$id.'" '.($id==$game->album_id?'selected="selected"':'').'>'.$title.'</option>';
			        	}
			        	?>
		        	</select>
                    <?php
                    if ($tournament && $tournament->album_id) {
                        $album = $albums[$tournament->album_id];
                        print '<p class="helper-text ui-li-desc">tournament album: <strong>'.$album.'</strong></p>';
                    }
                    ?>
				</li>

				<li role="list-divider" data-theme="c">Actions</li>

				<li>
					<?php
					$saveBtns = '<button type="submit" name="action" value="save">Save</button><button type="submit" name="action" value="saveAndNew">Save &amp; New</button>';
					$saveAndPostBtn = '<button type="submit" name="action" value="saveAndPost" data-theme="d">Save And Post</button>';

					if (!$game->is_posted) {
						?>
						<div class="ui-grid-a">
							<div class="ui-block-a">
								<?= $saveBtns ?>
							</div>
							<div class="ui-block-b">
                                <?= $saveAndPostBtn ?>
							</div>
						</div>
						<?php
					} else {
						print $saveBtns;
					}
					?>
				</li>

			</ul>
		</form>
	</div><!-- /content -->

</div><!-- /page -->

<?php require '_post.php'; ?>