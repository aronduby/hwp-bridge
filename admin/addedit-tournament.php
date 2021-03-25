<?php
require '../common.php';

if(!empty($_POST)){

	$dbh = PDODB::getInstance();

	$start = strtotime(str_replace('-', '/', $_POST['start']));
	$end = strtotime(str_replace('-', '/', $_POST['end']));

	if($start !== false && $end !== false && isset($_POST['team']) && isset($_POST['title']) && isset($_POST['location_id'])){
		$start = date('Y-m-d', $start);
		$end = date('Y-m-d', $end);

		$sql = "INSERT INTO tournaments SET
				id = ".($_POST['tournament_id'] ? $dbh->quote($_POST['tournament_id']) : 'null').",
				site_id = ".intval($site->id).",
				season_id = ".$dbh->quote($_POST['season_id']).",
				location_id = ".intval($_POST['location_id']).",
				team = ".$dbh->quote($_POST['team']).",
				title = ".$dbh->quote($_POST['title']).",
				start = ".$dbh->quote($start).",
				end = ".$dbh->quote($end).",
				result = ".$dbh->quote($_POST['result']).",
				album_id = ".(intval($_POST['album_id']) ? $dbh->quote($_POST['album_id']) : "NULL").",
				created_at = NOW()
			ON DUPLICATE KEY UPDATE
				id = VALUES(id),
				site_id = VALUES(site_id),
				season_id = VALUES(season_id),
				location_id = VALUES(location_id),
				team = VALUES(team),
				title = VALUES(title),
				start = VALUES(start),
				end = VALUES(end),
				result = VALUES(result),
				album_id = VALUES(album_id),
				updated_at = NOW(),
				id=LAST_INSERT_ID(id)";

		$inserted  = $dbh->exec($sql);

		if($inserted !== false){
			header("Location: tournament.php?tournament_id=".$dbh->lastInsertId());
			die();
		} else {
			$form_errors = 'Could not save the form. Please try again later';
			if(isset($_POST['tournament_id']))
				$tournament = new Tournament($_POST['tournament_id'], $register);
		}

	} else {
		$form_errors = 'You are missing some required fields, please try again.';
		$tournament = new Tournament($_POST['tournament_id'], $register);
	}

} else {
	$tournament = new Tournament(isset($_GET['tournament_id']) ? $_GET['tournament_id'] : null, $register);
}

require '_pre.php';
?>

<div data-role="page" data-theme="b">

	<div data-role="header" data-theme="b">
		<a href="index.php" data-rel="back" title="back" data-icon="back" data-iconpos="notext" data-direction="reverse">back</a>
		<h1>Edit <?php echo $tournament->title ?></h1>
	</div><!-- /header -->

	<div data-role="content">	
		<?php
        include '_form-errors.php';
		?>
		<form action="addedit-tournament.php" method="POST" data-ajax="false">
			<input type="hidden" name="tournament_id" value="<?php echo $tournament->id ?>" />
			<input type="hidden" name="season_id" value="<?php echo isset($tournament->season_id) ? $tournament->season_id : $season->id?>" />

			<ul data-role="listview">
				<li data-role="fieldcontain">
					<label for="t-title">Title:</label>
		        	<input type="text" name="title" id="t-title" placeholder="title" value="<?php echo $tournament->title ?>" />
				</li>
				<li data-role="fieldcontain">
					<label for="t-location">Location:</label>
					<select name="location_id" id="t-location" data-theme="d">
			        	<?php
						$locations = Location::getOptionsForSelect($register);
						foreach($locations as $l) {
                            print '<option value="' . $l->id . '" ' . ($l->id == $tournament->location_id ? 'selected="selected"' : '') . '>' . $l->title . '</option>';
                        }
			        	?>
		        	</select>
				</li>
				<li data-role="fieldcontain" >
					<fieldset data-role="controlgroup" data-type="horizontal">
				     	<legend>Team:</legend>
			     		<label for="t-team-v">Varsity</label>
			     		<input type="radio" name="team" id="t-team-v" value="V" data-theme="d" <?php echo $tournament->team=='V' ? 'checked="checked"' : '' ?> />
			     		<label for="t-team-jv">JV</label>
			         	<input type="radio" name="team" id="t-team-jv" value="JV" data-theme="d" <?php echo $tournament->team=='JV' ? 'checked="checked"' : '' ?> />
				    </fieldset>
				</li>

				<li data-role="fieldcontain">
					<label for="t-start">Start:</label>
		        	<input type="date" name="start" id="t-start" placeholder="start" value="<?php echo $tournament->start->format(INPUT_DATE_FORMAT) ?>" />
				</li>

				<li data-role="fieldcontain">
					<label for="t-end">End:</label>
		        	<input type="date" name="end" id="t-end" placeholder="end" value="<?php echo $tournament->end->format(INPUT_DATE_FORMAT) ?>" />
				</li>

				<li data-role="fieldcontain">
					<label for="t-result">Result:</label>
		        	<input type="text" name="result" id="t-result" placeholder="result" value="<?php echo $tournament->result ?>" />
				</li>

                <li data-role="fieldcontain">
                    <label for="g-album_id">Photo Album:</label>
                    <select name="album_id" id="g-album_id" data-theme="d">
                        <option value=""></option>
                        <?php
                        foreach(PhotoAlbum::getOptionsForSelect($register) as $id=>$title){
                            print '<option value="'.$id.'" '.($id==$tournament->album_id?'selected="selected"':'').'>'.$title.'</option>';
                        }
                        ?>
                    </select>
                </li>


				<li data-role="fieldcontain">
					<button type="submit">Save</button>
				</li>

			</ul>
		</form>
	</div><!-- /content -->

</div><!-- /page -->

<?php require '_post.php'; ?>