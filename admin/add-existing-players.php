<?php /** @noinspection SqlResolve */
require '../common.php';

use Cloudinary\Cloudinary;

function sortByName(&$arr) {
	return usort($arr, function ($a, $b) {
		return strnatcmp ( strtoupper($a->name_key), strtoupper($b->name_key) );
	});
}

function groupBy($arr, $fnc) {
	$return = [];

	foreach ($arr as $v) {
		$k = $fnc($v);
		if (!array_key_exists($k, $return)) {
			$return[$k] = [];
		}
		$return[$k][] = $v;
	}

	return $return;
}

function indexBy($arr, $fnc) {
	$return = [];

	foreach($arr as $v) {
		$k = $fnc($v);
		$return[$k] = $v;
	}

	return $return;
}

$allPlayers = $site->getAllPlayers();
sortByName($allPlayers);
$allPlayers = groupBy($allPlayers, function($p) { return strtoupper($p->first_name[0]); });
$seasonPlayersNoIndex = $season->getPlayerSeasons();
$seasonPlayers = indexBy($seasonPlayersNoIndex, function($ps) { return $ps->player_id; });
$importData = $season->getPreviousSeasonImport();

if (!empty($_POST)) {
    try{
        /**
         * @var $_POST
         * player array<int> array of player ids
         * playerSeasonId array<int> array of player season ids, indexed by the player id
         * number array<string> array of player cap numbers, indexed by player id
         * team array<array<V|JV|STAFF>> array indexed by player id where values are an array of the teams they are on, [[80] => ['V','JV']]
         * position array<FIELD|GOALIE> array of player positions, indexed by player id
        */

        $write = [];
        foreach($_POST['player'] as $pid) {
            $psid = intval($_POST['playerSeasonId'][$pid]);

        	// team and position could not exists
            $team = array_key_exists($pid, $_POST['team']) ? implode(',', $_POST['team'][$pid]) : '';
            $position = array_key_exists($pid, $_POST['position']) ? $_POST['position'][$pid] : '';

        	$write[] = [
        	    ':id' => $psid ?: null,
		        ':player_id' => $pid,
		        ':team' => $team,
		        ':position' => $position,
		        ':number' => $_POST['number'][$pid],
		        // can't combine value and param with array to execute so add the fields here for easy processing
		        ':site_id' => $site->id,
		        ':season_id' => $season->id
	        ];
        }

	    // remove any that are in the original list, but not in the posted one
	    $allSpIds = array_keys(indexBy($seasonPlayersNoIndex, function($sp) { return $sp->id; }));
        $postedSpIds = array_keys(indexBy($write, function($w) { return $w[':id']; }));
        $delete = array_diff($allSpIds, $postedSpIds);

        $dbh = PDODB::getInstance();

		$savedIds = [];
        if (count($write)) {
        	$sql = "
        	    INSERT INTO player_season SET
					id = :id,
				    site_id = :site_id,
					player_id = :player_id,
				    season_id = :season_id,
					team = :team,
					position = :position,
					number = :number,
					created_at = NOW()
				ON DUPLICATE KEY UPDATE 
					site_id = VALUES(site_id),
					player_id = VALUES(player_id),
					season_id = VALUES(season_id),
					team = VALUES(team),
					position = VALUES(position),
					number = VALUES(number),
					updated_at = NOW(),
                    id = LAST_INSERT_ID(id)
        	";

        	$writeStmt = $dbh->prepare($sql);
        	$writeStmt->bindParam(':site_id', $siteId, PDO::PARAM_INT);
        	$writeStmt->bindParam(':season_id', $seasonId, PDO::PARAM_INT);
            $writeStmt->bindParam(':id', $id, PDO::PARAM_INT);
			$writeStmt->bindParam(':player_id', $player_id, PDO::PARAM_INT);
			$writeStmt->bindParam(':team', $team, PDO::PARAM_STR);
			$writeStmt->bindParam(':position', $position, PDO::PARAM_STR);
			$writeStmt->bindParam(':number', $number, PDO::PARAM_STR);

			foreach($write as $v) {
				$writeStmt->execute($v);
				$savedIds[] = $dbh->lastInsertId();
			}
        }

        if (count($delete)) {
            $sql = "DELETE FROM player_season WHERE id IN (".str_repeat("?,", count($delete) - 1)."?)";
            $stmt = $dbh->prepare($sql);
            $stmt->execute(array_values($delete));
        }

        // clear the artisan cache so playerlist is regened
        // also regen the JS player list
        exec('php '.ARTISAN_PATH.' cache:clear');
        exec('php '.ARTISAN_PATH.' generate:js-player-list');

		// if it's cloudinary make sure to sync any changes to the player field
		if ($season->media_service === MEDIA_SOURCE_CLOUDINARY) {
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

                $playersField = $cloudinary->adminApi()->metadataFieldByFieldId('players');
				$cloudinaryTags = array_column($playersField['datasource']['values'], 'state', 'external_id');
				$cloudinaryTagKeys = array_keys($cloudinaryTags);

				$tagsToAdd = [];
				$tagsToDelete = [];
				$tagsToRestore = [];

				$stmt = $dbh->prepare("
					SELECT 
						IF(
							ps.media_tag IS NULL OR ps.media_tag = '',
							LOWER(CONCAT(p.first_name, '_', p.last_name)),
							ps.media_tag
						) AS external_id,
						CONCAT(p.first_name, ' ', p.last_name) AS value
					FROM 
						player_season ps 
						JOIN players p ON ps.player_id = p.id 
					WHERE 
						ps.season_id = :season_id
				");
				$stmt->bindValue(':season_id', $season->id, PDO::PARAM_INT);
				$stmt->setFetchMode(PDO::FETCH_ASSOC);
				$stmt->execute();
                $localTags = $stmt->fetchAll();
				$localTags = array_column($localTags, null, 'external_id');
				$localTagKeys = array_keys($localTags);

				// delete is anything in cloudinary with active state that isn't in local - just external_id
	            $deleteIds = array_diff($cloudinaryTagKeys, $localTagKeys);
				foreach ($deleteIds as $id) {
                    if ($cloudinaryTags[$id] === 'active') {
                        $tagsToDelete[] = $id;
                    }
				}
				if (count($tagsToDelete)) {
					$cloudinary->adminApi()->deleteDatasourceEntries('players', $tagsToDelete);
				}

                // restore is anything in local that's in cloudinary with inactive state - just external id
	            $intersection = array_intersect($localTagKeys, $cloudinaryTagKeys);
				foreach ($intersection as $id) {
					if ($cloudinaryTags[$id] === 'inactive') {
						$tagsToRestore[] = $id;
					}
				}
				if (count($tagsToRestore)) {
					$cloudinary->adminApi()->restoreMetadataFieldDatasource('players', $tagsToRestore);
				}

                // add is anything in local that isn't in cloudinary - whole array
	            $addIds = array_diff($localTagKeys, $cloudinaryTagKeys);
				$tagsToAdd = array_map(function($k) use($localTags) {
					return $localTags[$k];
				}, $addIds);
				if (count($tagsToAdd)) {
					$cloudinary->adminApi()->updateMetadataFieldDatasource('players', $tagsToAdd);
				}

            } else {
                // flash message about adding manually
                $_SESSION['flashMsg'] = 'Players added successfully, but Cloudinary tags unable to be updated. Please manually handle tags';
            }
		}

        header("Location: players.php");
        die();

    } catch(Exception $e){
        $form_errors = $e->getMessage();

        // update season player data with our changes so we aren't start over
	    if ($write && count($write)) {
	    	print_p($write);
	    	print_p(array_keys($seasonPlayers));
	    	foreach($write as $psid => $ps) {
	    		// if we are making a new season this won't exist in season players
                if (array_key_exists($ps[':player_id'], $seasonPlayers)) {
                    $update = $seasonPlayers[$ps[':player_id']];
                } else {
					$update = new PlayerSeason(null, null, $register);
					$update->player_id = $ps[':player_id'];
					$update->season_id = $season->id;
					$seasonPlayers[$update->player_id] = $update;
                }

	    		$update->team = $ps[':team'];
	    		$update->position = $ps[':position'];
	    		$update->number = $ps[':number'];
		    }
	    }

	    if ($delete && count($delete)) {
	    	$spsBySpId = indexBy($seasonPlayersNoIndex, function($sp) { return $sp->id; });
	    	foreach($delete as $spId) {
	    		unset($seasonPlayers[$spsBySpId[$spId]->player_id]);
		    }
	    }
    }

}

require '_pre.php';
?>
<div data-role="page" data-theme="b" class="add-existing-players" id="page--add-existing-players">
    <link rel="stylesheet" href="css/jquery-mobile-overrides.css" />
    <link rel="stylesheet" href="css/add-existing-players.css" />

	<script>
        $('#page--add-existing-players').live('pageinit',function(event){
            $("input.player-selected-cb").bind("change", function(event, ui) {
                $(this).parents('li.player').toggleClass('player--checked', this.checked);
            });

            $('#import').bind('click', function() {
                $.mobile.showPageLoadingMsg();

                try {
                    const script = document.getElementById('importData');
                    const data = JSON.parse(script.textContent);

                    data.forEach((d) => {
                        const pid = d.player_id;

                        $(`li.player[data-player-id="${pid}"]`).addClass('player--checked');
                        $(`#player-${pid}`).prop('checked', true).checkboxradio('refresh');
                        $(`#cap-${pid}`).val(d.number);
                        $(`#team-${d.team.toLowerCase()}-${pid}`).prop('checked', true).checkboxradio('refresh');
                        if (d.position !== '') {
                            $(`#position-${d.position.toLowerCase()}-${pid}`).prop('checked', true).checkboxradio('refresh');
                        }
                    });
                } finally {
                    $.mobile.hidePageLoadingMsg();
                }
            })
        });
        //# sourceURL=add-existing-players.js
	</script>
	<script id="importData" type="application/json"><?= json_encode($importData) ?></script>

	<form action="add-existing-players.php" method="POST" data-ajax="false" autocomplete="off" enctype="multipart/form-data">

	    <div data-role="header" data-theme="b">
	        <a href="index.php" data-rel="back" title="back" data-icon="back" data-iconpos="notext" data-direction="reverse">back</a>
	        <h1>Batch Existing Players</h1>
		    <a id="import" href="#" data-role="button" data-icon="forward" data-iconpos="notext" style="transform: rotate(90deg)">import</a>
	    </div><!-- /header -->

	    <div data-role="content">
	        <?php
	        include '_form-errors.php';
	        ?>
	        <ul data-role="listview" data-filter="true" data-filter-placeholder="Search players..." data-filter-theme="d" data-theme="d" data-divider-theme="d">
		        <?php
		        $alpha = range('A', 'Z');
		        foreach($alpha as $letter) {
		            print '<li data-role="list-divider">'.$letter.'</li>';
		            if (array_key_exists($letter, $allPlayers)) {
	                    foreach($allPlayers[$letter] as $p) {
	                    	$ps = array_key_exists($p->id, $seasonPlayers) ? $seasonPlayers[$p->id] : false;
	                    	if ($ps) {
	                    		$ps->team = explode(',', $ps->team);
		                    }
	                        ?>
					        <li class="player <?= $ps ? 'player--checked' : '' ?>" data-player-id="<?= $p->id ?>">
						        <input type="hidden" name="playerSeasonId[<?=$p->id?>]" value="<?= $ps ? $ps->id : '' ?>" />
						        <input
							        type="checkbox"
							        name="player[]"
							        id="player-<?= $p->id ?>"
							        class="player-selected-cb"
							        value="<?= $p->id ?>"
							        data-theme="d"
							        <?= $ps ? 'checked' : '' ?>
						        />
						        <label for="player-<?= $p->id ?>" data-theme="d"><?= $p->name ?></label>
						        <div data-role="content" class="subfields">
							        <fieldset data-role="controlgroup" class="subfields-cap">
								        <label for="cap-<?= $p->id ?>">#</label>
								        <input type="text" id="cap-<?= $p->id ?>" name="number[<?= $p->id ?>]" value="<?= $ps ? $ps->number : '' ?>" data-mini="true"/>
							        </fieldset>
							        <fieldset data-role="controlgroup" data-type="horizontal" class="subfields-team">
								        <legend>Team:</legend>
								        <label for="team-v-<?=$p->id?>">Varsity</label>
								        <input type="checkbox" name="team[<?=$p->id?>][]" id="team-v-<?=$p->id?>" value="V" <?= $ps && in_array('V', $ps->team) ? 'checked' : '' ?> data-theme="d" data-mini="true" />
								        <label for="team-jv-<?=$p->id?>">JV</label>
								        <input type="checkbox" name="team[<?=$p->id?>][]" id="team-jv-<?=$p->id?>" value="JV" <?= $ps && in_array('JV', $ps->team) ? 'checked' : '' ?> data-theme="d" data-mini="true" />
								        <label for="team-staff-<?=$p->id?>">Staff</label>
								        <input type="checkbox" name="team[<?=$p->id?>][]" id="team-staff-<?=$p->id?>" value="STAFF" <?= $ps && in_array('STAFF', $ps->team) ? 'checked' : '' ?> data-theme="d" data-mini="true" />
							        </fieldset>
							        <fieldset data-role="controlgroup" data-type="horizontal" class="subfields-position">
								        <legend>Position:</legend>
								        <label for="position-field-<?=$p->id?>">Field</label>
								        <input type="radio" name="position[<?=$p->id?>]" id="position-field-<?=$p->id?>" value="FIELD" <?= $ps && $ps->position === 'FIELD' ? 'checked' : '' ?> data-theme="d" data-mini="true" />
								        <label for="position-goalie-<?=$p->id?>">Goalie</label>
								        <input type="radio" name="position[<?=$p->id?>]" id="position-goalie-<?=$p->id?>" value="GOALIE" <?= $ps && $ps->position === 'GOALIE' ? 'checked' : '' ?> data-theme="d" data-mini="true" />
							        </fieldset>
						        </div>
					        </li>
	                        <?php
	                    }
			        }
		        }
		        ?>

		        <li data-role="list-divider" data-theme="a"></li>
		        <li data-role="fieldcontain" data-theme="a">
			        <button type="submit" data-theme="b">Save</button>
		        </li>
	        </ul>
	    </div><!-- /content -->

	</form>
</div><!-- /page -->

<?php require '_post.php'; ?>