<?php /** @noinspection SqlResolve */

use Cloudinary\Cloudinary;

require '../common.php';

if(!empty($_POST)){

	$dbh = PDODB::getInstance();

	if (isset($_POST['first_name']) && isset($_POST['last_name'])) {

		$sql = "INSERT INTO players SET
				id = ".($_POST['player_id'] ? $dbh->quote($_POST['player_id']) : 'null').",
				site_id = ".intval($site->id).",
				first_name = ".$dbh->quote($_POST['first_name']).",
				last_name = ".$dbh->quote($_POST['last_name']).",
				name_key = ".$dbh->quote($_POST['name_key']).",
				pronouns = ".$dbh->quote($_POST['pronouns'])."
			ON DUPLICATE KEY UPDATE
				id = VALUES(id),
				site_id = VALUES(site_id),
				first_name = VALUES(first_name),
				last_name = VALUES(last_name),
				name_key = VALUES(name_key),
				pronouns = VALUES(pronouns),
				id = LAST_INSERT_ID(id)";

		$inserted  = $dbh->exec($sql);
		$player_id = $dbh->lastInsertId();

		if(!array_key_exists('position', $_POST)){
			$_POST['position'] = null;
		}

		$_POST['sort'] = intval($_POST['sort']);

		if($inserted !== false){
			// update the season info
			$sql = "INSERT INTO player_season SET
					site_id = ".intval($site->id).",
					player_id = ".$player_id.",
					season_id = ".$season->id.",
					title = ".$dbh->quote($_POST['title']).",
					team = ".$dbh->quote(implode(',',$_POST['team'])).",
					position = ".$dbh->quote($_POST['position']).",
					number = ".$dbh->quote($_POST['number']).",
					media_tag = ".$dbh->quote($_POST['media_tag']).",
					sort = ".(!empty($_POST['sort']) ? $dbh->quote($_POST['sort']) : 'null')."
				ON DUPLICATE KEY UPDATE
					site_id = VALUES(site_id),
					player_id = VALUES(player_id),
					season_id = VALUES(season_id),
					title = VALUES(title),
					team = VALUES(team),
					position = VALUES(position),
					number = VALUES(number),
					media_tag = VALUES(media_tag),
					sort = VALUES(sort)";

			$dbh->exec($sql);

            // clear the artisan cache so playerlist is regened
            // also regen the JS player list
            exec('php '.ARTISAN_PATH.' cache:clear');
            exec('php '.ARTISAN_PATH.' generate:js-player-list');

			// if we're using cloudinary as media service, add/update the players field for tagging
			if (!$_POST['player_id'] && $season->media_service === MEDIA_SOURCE_CLOUDINARY) {
                $seasonSettings = $season->getSettings()->cloudinary ?? null;
                $siteSettings = $site->getSettings()->cloudinary ?? null;
                $settings = $seasonSettings ?? $siteSettings ?? false;

                if ($settings) {
                    $cloudinary = new Cloudinary([
                        'cloud' => [
                            'cloud_name' => $settings->cloud_name,
                            'api_key' => $settings->api_key,
                            'api_secret' => $settings->api_secret,
                            'url' => [
                                'secure' => true
                            ]
                        ]
                    ]);

                    $tagId = !empty($_POST['media_tag']) ? $_POST['media_tag'] : strtolower($_POST['first_name'].'_'.$_POST['last_name']);
                    $tagValue = $_POST['first_name'].' '.$_POST['last_name'];
                    $cloudinary->adminApi()->updateMetadataFieldDatasource('players', [
                        'values' => [
                            'external_id' => $tagId,
                            'value' => $tagValue,
                        ]
                    ]);

                } else {
                    // flash message about adding manually
                    $_SESSION['flashMsg'] = 'Player added successfully, but Cloudinary tag unable to be added. Please add this players tag manually';
                }
			}

			header("Location: players.php");
			die();

		} else {
			$form_errors = 'Could not save the form. Please try again later';
			$player = new Player(isset($_GET['player_id']) ? $_GET['player_id'] : null, $register);
			$player_season = new PlayerSeason($player, $season->id, $register);
		}

	} else {
		$form_errors = 'You are missing some required fields, please try again.';
		$player = new Player(isset($_GET['player_id']) ? $_GET['player_id'] : null, $register);
		$player_season = new PlayerSeason($player, $season->id, $register);
	}

} else {
	$player = new Player(isset($_GET['player_id']) ? $_GET['player_id'] : null, $register);
	$player_season = new PlayerSeason($player, $season->id, $register);
	$player_season->team = explode(',', $player_season->team);

	if (!isset($player->pronouns)) {
		$settings = $site->getSettings();
		$player->pronouns = $settings->defaultPronouns;
	}
}

require '_pre.php';
?>

<div data-role="page" data-theme="b">

	<div data-role="header" data-theme="b">
		<a href="index.php" data-rel="back" title="back" data-icon="back" data-iconpos="notext" data-direction="reverse">back</a>
		<h1>Edit <?php echo $player->name ?></h1>
	</div><!-- /header -->

	<div data-role="content">	
		<?php
        include '_form-errors.php';
		// print '<pre>'. print_r($player_season, true).'</pre>';
		?>
		<form action="addedit-player.php" method="POST" data-ajax="false">
			<input type="hidden" name="player_id" value="<?php echo $player->id ?>" />
			<input type="hidden" name="season_id" value="<?php echo $season->id?>" />

			<ul data-role="listview">
				<li data-role="fieldcontain">
					<label for="p-first_name">First Name:</label>
		        	<input type="text" name="first_name" id="p-first_name" placeholder="first name" value="<?php echo $player->first_name ?>" required />
				</li>

				<li data-role="fieldcontain">
					<label for="p-last_name">Last Name:</label>
		        	<input type="text" name="last_name" id="p-last_name" placeholder="last_name" value="<?php echo $player->last_name ?>" required />
				</li>

				<li data-role="fieldcontain">
					<label for="p-name_key">Name Key:</label>
		        	<input type="text" name="name_key" id="p-name_key" placeholder="name key" value="<?php echo $player->name_key ?>" required pattern="^[a-zA-Z]+$" title="letters only - used for the url" />
				</li>

				<li data-role="fieldcontain">
					<fieldset data-role="controlgroup" data-type="horizontal">
						<legend>Pronouns:</legend>
						<label for="p-pronouns-he">He</label>
						<input type="radio" name="pronouns" id="p-pronouns-he" value="he" data-theme="d" <?php echo $player->pronouns === 'he' ? 'checked="checked"' : '' ?> />
						<label for="p-pronouns-she">She</label>
						<input type="radio" name="pronouns" id="p-pronouns-she" value="she" data-theme="d" <?php echo $player->pronouns === 'she' ? 'checked="checked"' : '' ?> />
						<label for="p-pronouns-they">They</label>
						<input type="radio" name="pronouns" id="p-pronouns-they" value="they" data-theme="d" <?php echo $player->pronouns === 'they' ? 'checked="checked"' : '' ?> />
					</fieldset>
				</li>

				<li data-role="fieldcontain">
					<label for="p-title">Title:</label>
					<input type="text" name="title" id="p-title" placeholder="title" value="<?php echo $player_season->title ?>" />
				</li>

				<li data-role="fieldcontain" >
					<fieldset data-role="controlgroup" data-type="horizontal">
				     	<legend>Team:</legend>
			     		<label for="p-team-v">Varsity</label>
			     		<input type="checkbox" name="team[]" id="p-team-v" value="V" data-theme="d" <?php echo in_array('V', $player_season->team) ? 'checked="checked"' : '' ?> />
			     		<label for="p-team-jv">JV</label>
			         	<input type="checkbox" name="team[]" id="p-team-jv" value="JV" data-theme="d" <?php echo in_array('JV', $player_season->team) ? 'checked="checked"' : '' ?> />
						<label for="p-team-staff">Staff</label>
						<input type="checkbox" name="team[]" id="p-team-staff" value="STAFF" data-theme="d" <?php echo in_array('STAFF', $player_season->team) ? 'checked="checked"' : '' ?> />
				    </fieldset>
				</li>

				<li data-role="fieldcontain" >
					<fieldset data-role="controlgroup" data-type="horizontal">
				     	<legend>Position:</legend>
			     		<label for="p-position-field">Field</label>
			     		<input type="radio" name="position" id="p-position-field" value="FIELD" data-theme="d" <?php echo $player_season->position=='FIELD' ? 'checked="checked"' : '' ?> />
			     		<label for="p-position-goalie">Goalie</label>
			         	<input type="radio" name="position" id="p-position-goalie" value="GOALIE" data-theme="d" <?php echo $player_season->position=='GOALIE' ? 'checked="checked"' : '' ?> />
				    </fieldset>
				</li>

				<li data-role="fieldcontain">
					<label for="p-number">Number:</label>
		        	<input type="text" name="number" id="p-number" placeholder="cap number" value="<?php echo $player_season->number ?>" />
				</li>

				<li data-role="fieldcontain">
					<label for="p-media_tag">Media Tag:</label>
		        	<input type="text" name="media_tag" id="p-media_tag" placeholder="media tag" value="<?php echo $player_season->media_tag ?>" />
					<?php
					if ($season->media_service === MEDIA_SOURCE_CLOUDINARY) {
                        ?><p class="helper-text">Leave blank to use players name. Only updates Cloudinary on initial creation, updates after have to be done manually</p><?php
					}
					?>
				</li>

				<li data-role="fieldcontain">
					<label for="p-sort">Sort:</label>
					<input type="number" name="sort" id="p-sort" placeholder="sort order" value="<?php echo $player_season->sort ?>" />
				</li>


				<li data-role="fieldcontain">
					<button type="submit">Save</button>
				</li>

			</ul>
		</form>
	</div><!-- /content -->

</div><!-- /page -->

<?php require '_post.php'; ?>