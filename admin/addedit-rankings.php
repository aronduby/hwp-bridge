<?php
/**
 * @var Register $register
 * @var Site $site
 * @var Season $season
 *
 * @noinspection SqlResolve
 */
require '../common.php';

$loadedPrevious = false;
$titlePrefix = 'Edit';
$options = [
	'current' => false,
	'notify' => false
];

if (!empty($_POST)) {
	// print_p($_POST, true);

	// figure out ties
	$lastRank = 0;
	foreach ($_POST['rank'] as $i => $rank) {
		if ($rank['rank'] == $lastRank) {
            $_POST['rank'][$i - 1]['tied'] = true;
            $_POST['rank'][$i]['tied'] = true;
		}

		$lastRank = $_POST['rank'][$i]['rank'];
	}

	$dbh = PDODB::getInstance();

	try {
		$dbh->beginTransaction();

		// insert the rankings
		$rankingInsertSql = <<<SQL
INSERT INTO rankings SET
	id = :id,
	site_id = :site_id,
	season_id = :season_id,
	week = :week,
	start = :start,
	end = :end,
	created_at = NOW(),
	updated_at = NOW()
ON DUPLICATE KEY UPDATE
	id = VALUES(id),
	site_id = VALUES(site_id),
	season_id = VALUES(season_id),
	week = VALUES(week),
	start = VALUES(start),
	end = VALUES(end),
	updated_at = NOW()
SQL;

		$rankingInsertStmt = $dbh->prepare($rankingInsertSql);
		$rankingInsertStmt->bindValue(':id', $_POST['ranking']['id'] ?: null, PDO::PARAM_INT);
        $rankingInsertStmt->bindValue(':site_id', $site->id, PDO::PARAM_INT);
        $rankingInsertStmt->bindValue(':season_id', $season->id, PDO::PARAM_INT);
        $rankingInsertStmt->bindValue(':week', $_POST['ranking']['week'], PDO::PARAM_INT);
        $rankingInsertStmt->bindValue(':start', $_POST['ranking']['start']);
        $rankingInsertStmt->bindValue(':end', $_POST['ranking']['end']);

        $rankingInsertStmt->execute();
        $rankingId = $dbh->lastInsertId();

		// delete any existing ranks for the give id
        $ranksDeleteSql = "DELETE FROM ranks WHERE ranking_id = ".$rankingId;
        $dbh->exec($ranksDeleteSql);

		// insert the new ranks
		$rankInsertSql = <<<SQL
INSERT INTO ranks SET
	site_id = :site_id,
	season_id = :season_id,
	ranking_id = :ranking_id,
	rank = :rank,
	team = :team,
	tied = :tied,
	self = :self,
	points = :points
ON DUPLICATE KEY UPDATE
	site_id = VALUES(site_id),
	season_id = VALUES(season_id),
	ranking_id = VALUES(ranking_id),
	rank = VALUES(rank),
	team = VALUES(team),
	tied = VALUES(tied),
	self = VALUES(self),
	points = VALUES(points)
SQL;
		$rankInsertStmt = $dbh->prepare($rankInsertSql);
        $rankInsertStmt->bindValue(':site_id', $site->id, PDO::PARAM_INT);
        $rankInsertStmt->bindValue(':season_id', $season->id, PDO::PARAM_INT);
        $rankInsertStmt->bindValue(':ranking_id', $rankingId, PDO::PARAM_INT);
        $rankInsertStmt->bindParam(':rank', $rank, PDO::PARAM_INT);
        $rankInsertStmt->bindParam(':team', $team);
        $rankInsertStmt->bindParam(':tied', $tied, PDO::PARAM_BOOL);
        $rankInsertStmt->bindParam(':self', $self, PDO::PARAM_BOOL);
        $rankInsertStmt->bindParam(':points', $points, PDO::PARAM_INT);

        foreach ($_POST['rank'] as $rankData) {
            $rank = $rankData['rank'];
			$team = $rankData['team'];
			$tied = array_key_exists('tied', $rankData) ? $rankData['tied'] : false;
			$self = array_key_exists('self', $rankData) ? $rankData['self'] : false;
			$points = $rankData['points'];
        	$rankInsertStmt->execute();
        }

        // set as current?
        if (isset($_POST['options']['current'])) {
            $self = false;
            foreach ($_POST['rank'] as $r) {
                if (array_key_exists('self', $r)) {
                    $self = $r;
                    break;
                }
            }

            $currentRank = $self ? $self['rank'] : null;
            $currentTie = $self ? array_key_exists('tied', $self) : false;

            $currentStmt = $dbh->prepare("UPDATE seasons SET ranking = :ranking, ranking_tie = :ranking_tie, ranking_updated = NOW() WHERE id = :season_id");
            $currentStmt->execute([
                ':ranking' => $currentRank,
                ':ranking_tie' => $currentTie ? 1 : 0,
                ':season_id' => $season->id
            ]);
        }

        $dbh->commit();

        // send notification?
        if (isset($_POST['options']) && array_key_exists('notify', $_POST['options'])) {
            // trigger article event/notification
            $path = 'php '.ARTISAN_PATH.' events:manual-ranking-notification '. $rankingId;
            exec($path);
        }

		header("Location: rankings.php");
		die();
	}
	catch(Exception $e) {
		$dbh->rollBack();
		$form_errors = $e->getMessage();

		// send it all back as objects, using json encode/decode to go from $_POST arrays to objects
		$ranking = json_decode(json_encode($_POST['ranking']));
        $ranking->start = $ranking->start ? DateTime::createFromFormat(MYSQL_DATETIME_FORMAT, $ranking->start.' 00:00:00') : new DateTime();
        $ranking->end = $ranking->end ? DateTime::createFromFormat(MYSQL_DATETIME_FORMAT, $ranking->end.' 00:00:00') : (new DateTime())->add(new DateInterval('P7D'));

		$ranking->ranks = array_values(array_map(function($rank) {
			return json_decode(json_encode($rank));
		}, $_POST['rank']));

		$options['current'] = isset($_POST['options']['current']);
		$options['notify'] = isset($_POST['options']['notify']);
	}

} else {

	if (isset($_GET['ranking_id'])) {
		$ranking = new Ranking($_GET['ranking_id'], $register);

	} else {
		$titlePrefix = 'Create';
		$options['current'] = true;
		$options['notify'] = true;

		$ranking = Ranking::getLatest($register);
		if ($ranking) {
			$loadedPrevious = true;
			// bump the info that we can guess at
			$ranking->id = null;
			$ranking->week++;
			$ranking->start = new DateTime();
			$ranking->end = (new DateTime())->add(new DateInterval('P7D'));
		} else {
			$titlePrefix .= ' New';
			$ranking = new Ranking(false, $register);
			$ranking->week = 0;
		}
	}
}

require '_pre.php';
?>

<div data-role="page" data-theme="b" id="page--addedit-rankings" class="addedit-rankings">
	<link rel="stylesheet" href="css/jquery-mobile-overrides.css" />

	<div data-role="header" data-theme="b">
		<a href="index.php" data-rel="back" title="back" data-icon="back" data-iconpos="notext" data-direction="reverse">back</a>
		<h1><?= $titlePrefix ?> Week <?php echo $ranking->week ?></h1>
	</div><!-- /header -->

	<div data-role="content">	
		<?php
        include '_form-errors.php';

        if ($loadedPrevious) {
            ?>
			<div data-role="content" data-theme="e" class="ui-shadow ui-alert">
				Pre-populated based on data from the latest entry.
			</div>
            <?php
        }
		?>
		<form action="addedit-rankings.php<?= isset($_GET['ranking_id']) ? '?ranking_id='.$_GET['ranking_id'] : ''?>" method="POST" data-ajax="false" autocomplete="off" enctype="multipart/form-data">
			<input type="hidden" name="ranking[id]" value="<?php echo $ranking->id ?>" />

			<div data-role="header" data-theme="e"> 
				<h2>Ranking Info</h2>
			</div>

			<ul data-role="listview">
				<li data-role="fieldcontain">
					<label for="week">Week:</label>
					<input type="number" name="ranking[week]" id="week" placeholder="title" value="<?= $ranking->week ?>" />
				</li>
				<li data-role="fieldcontain">
					<label for="start">Start:</label>
					<input type="date" id="start" name="ranking[start]" value="<?= $ranking->start->format(INPUT_DATE_FORMAT) ?>" />
				</li>
				<li data-role="fieldcontain">
					<label for="end">End:</label>
					<input type="date" id="end" name="ranking[end]" value="<?= $ranking->end->format(INPUT_DATE_FORMAT) ?>" />
				</li>
				<li data-role="fieldcontain">
					<fieldset data-role="controlgroup">
						<legend>Options</legend>

						<input type="checkbox" id="options.current" name="options[current]" class="custom" data-theme="d" <?= $options['current'] ? 'checked' : '' ?> />
						<label for="options.current">Set as Current Rank</label>

						<input type="checkbox" id="options.notify" name="options[notify]" class="custom" data-theme="d" <?= $options['notify'] ? 'checked' : '' ?> />
						<label for="options.notify">Send Notification</label>
					</fieldset>
				</li>
			</ul>

			<div data-role="header" data-theme="e"> 
				<h2>Ranks</h2>
			</div>
			<div data-role="header" data-theme="d" class="rank-header rankRow">
				<h3 class="rankRow-rank">Rank</h3>
				<h3 class="rankRow-team">Team</h3>
				<h3 class="rankRow-points">Points</h3>
				<h3 class="rankRow-self">Self</h3>
			</div>

			<ul data-role="listview" class="ranks">
				<?php
				$rankOptions = array_reduce(range(1,10), function($acc, $rank) {
					return $acc .= '<option value="'.$rank.'">'.$rank.'</option>';
				}, '');

				$rankOptionsSelected = function($i) use ($rankOptions) {
					$search = 'value="'.$i.'"';
					$replace = $search . ' selected';

					return str_replace($search, $replace, $rankOptions);
				};

				$selfName = false;
				try {
					$selfName = $site->getSettings()->ranking->parameters->name;
				} catch (Exception $e) {
					$selfName = false;
				}

				// if 10th place is a tie you can have more than 10 ranks... I think
				$max = max(10, count($ranking->ranks));
				for ($i = 1; $i <= $max; $i++) {
					$rank = isset($ranking->ranks[$i - 1]) ? $ranking->ranks[$i - 1] : new Rank(false, $register);
					$isSelf = isset($rank->self) ? $rank->self : $rank->team === $selfName;
					?>
					<li data-role="fieldContain" class="rankRow" data-i="<?= $i ?>">
						<fieldset data-role="controlgroup" class="rankRow-rank ui-hide-label">
							<label for="rank.<?= $i ?>.rank" class="select">Rank</label>
							<select name="rank[<?= $i ?>][rank]" id="rank.<?= $i ?>.rank" data-theme="d"><?= $rankOptionsSelected(isset($rank->rank) ? $rank->rank : $i) ?></select>
						</fieldset>
						<fieldset data-role="controlgroup" class="rankRow-team ui-hide-label">
							<label for="rank.<?= $i ?>.team">Team</label>
							<input type="text" name="rank[<?= $i ?>][team]" id="rank.<?= $i ?>.team" placeholder="team" value="<?= $rank->team ?>" />
						</fieldset>
						<fieldset data-role="controlgroup" class="rankRow-points ui-hide-label">
							<label for="rank.<?= $i ?>.points">Points</label>
							<input type="number" name="rank[<?= $i ?>][points]" id="rank.<?= $i ?>.points" max="1000" placeholder="points" value="<?= $rank->points ?>" />
						</fieldset>
						<fieldset data-role="controlgroup" class="rankRow-self ui-hide-label">
							<label for="rank.<?= $i ?>.self" data-theme="d">&nbsp;</label>
							<input type="checkbox" name="rank[<?= $i ?>][self]" id="rank.<?= $i ?>.self" data-theme="d" value="1" <?= $isSelf ? 'checked' : '' ?> />
						</fieldset>
					</li>
					<?php
				}
				?>

				<li data-role="fieldContain">
					<button type="button" data-theme="c" id="addAnotherRank">Add Another Rank</button>
				</li>
			</ul>

			<ul data-role="listview" data-theme="a">
				<li data-role="fieldcontain">
					<button type="submit" data-theme="b">Save</button>
				</li>
			</ul>
		</form>

		<!-- make sure this is after the form otherwise styles break -->
		<link rel="stylesheet" href="css/addedit-rankings.css" />
		<script>
            $('#page--addedit-rankings').live( 'pageinit',function(event){
                $('#addAnotherRank').bind('click', function(event, ui) {
                   const lastRow = $(this).parents('.ranks').find('li.rankRow').last();
                   const newRow = lastRow.clone();

                   const lastI = parseInt(lastRow.data('i'), 10);
                   const newI = lastI + 1;

                   newRow.attr('data-i', newI);

                   newRow.find('label[for]').each(function() {
                       $(this).attr('for', $(this).attr('for').replace('.'+lastI+'.', '.'+newI+'.'));
                   });

                   newRow.find('[id][name]').each(function() {
                       $(this).attr('id', $(this).attr('id').replace('.'+lastI+'.', '.'+newI+'.'));
                       $(this).attr('name', $(this).attr('name').replace('['+lastI+']', '['+newI+']'));
                   });

                   newRow.find('input#rank\\.'+newI+'\\.team').val('');

                   newRow.insertAfter(lastRow);
                });
            });
		</script>
	</div><!-- /content -->

</div><!-- /page -->

<?php require '_post.php'; ?>