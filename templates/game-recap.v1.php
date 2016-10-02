<?php
$stats = json_decode($this->json_dump);

// re-order by cap number 
// needs to be actual array first
$stats->stats = (array)$stats->stats;
$numbers = [];
$players_by_key = [];
foreach ($stats->stats as $p) {
    $numbers[$p->name_key]  = $p->number;
	$players_by_key[$p->name_key] = $p;
}
array_multisort($numbers, SORT_ASC, $stats->stats);

// used for totals
function label2key($label){
	return strtolower(str_replace(
		[' ', '%', '5m'],
		['_', 'percent', 'five_meters'],
		$label
	));
}


$quarter_labels = ['1st','2nd','3rd','4th','OT','OT','SO'];
?>
<div class="content recap">
	<article id="main">

		<p class="note">All stats are <em>very</em> unofficial and some may be (and probably are) <em>very</em> wrong.</p>

		<section class="scores split">
			<header><h2>Final Score</h2></header>
			<section>
				<section>
					<header><h3>Hudsonville</h3></header>
					<section>
						<h4><?php echo $stats->score[0] ?></h4>
						<table class="box-score">
							<?php
							$ths = [];
							$tds = [];
							foreach($stats->boxscore[0] as $k=>$v){
								$ths[] = $quarter_labels[$k];
								$tds[] = array_sum((array)$v);
							}
							print '<thead><tr><th>'.implode('</th><th>', $ths).'</th></tr></thead>';
							print '<tbody><tr><td>'.implode('</td><td>', $tds).'</td></tr></tbody>';
							?>
						</table>
					</section>
				</section>
				<section>
					<header><h3><?php echo $stats->opponent ?></h3></header>
					<section>
						<h4><?php echo $stats->score[1] ?></h4>
						<table class="box-score">
							<?php
							$ths = [];
							$tds = [];
							foreach($stats->boxscore[1] as $k=>$v){
								$ths[] = $quarter_labels[$k];
								$tds[] = $v;
							}
							print '<thead><tr><th>'.implode('</th><th>', $ths).'</th></tr></thead>';
							print '<tbody><tr><td>'.implode('</td><td>', $tds).'</td></tr></tbody>';
							?>
						</table>
					</section>
				</section>
			</section>
		</section>

		<section class="goalie">
			<header>
				<h2>Goalie Stats</h2>
			</header>
			<section class="stat-wrapper">
				<?php
				$count = 0;
				$totals = [];
				$fields = [
					'' => function($p){
						return '<a href="/players/'.$p->name_key.'">#'.$p->number.' '.$p->first_name.' '.$p->last_name.'</a>';
					},
					'Saves' => 'saves',
					'Goals Allowed' => 'goals_allowed',
					'Save %' => function($p){
						try{
							$total_shots = $p->saves + $p->goals_allowed;

							return str_replace('.0','',number_format(($p->saves / $total_shots) * 100, 1).'%');
						} catch(Exception $e) {
							return $p->saves;
						}
					},
					'5m Taken On' => 'five_meters_taken_on',
					'5m Blocked' => 'five_meters_blocked',
					'5m Allowed' => 'five_meters_allowed',
					'Shoot Out Taken On' => 'shoot_out_taken_on',
					'Shoot Out Blocked' => 'shoot_out_blocked',
					'Shoot Out Allowed' => 'shoot_out_allowed'
				];
				$totals_override = [
					'' => function(){
						return 'Totals';
					}
				];
				?>
				<table class="stats collapse-table--tablet">
					<thead>
						<tr>
							<?php
							foreach(array_keys($fields) as $label){
								print '<th>'.$label.'</th>';
								$key = label2key($label);
								$totals[$key] = 0;
							}
							?>
						</tr>
					</thead>
					<tbody>
						<?php
						foreach($stats->stats as $player){
							if($player->saves != 0 || $player->goals_allowed!=0){
								$count++;
								print '<tr>';
									foreach($fields as $label=>$fld){
										if(is_callable($fld)){
											$val = $fld($player);
										} else {
											$val = $player->$fld;
										}

										print '<td data-title="'.($label==''?'Name':$label).'" '.($val === 0 ? 'class="empty"' : '').'>';
											print $val;
										print '</td>';

										$key = label2key($label);
										$totals[$key] += $val;
									}
								print '</tr>';
							}
						}
						?>
					</tbody>
					<?php
					// Don't do totals if there's only been one goalie
					if($count > 1){
						?>
						<tfoot>
							<tr class="totals">
								<?php
								$totals = json_decode(json_encode($totals));
								foreach($fields as $label=>$fld) {
									$overriden = array_key_exists($label, $totals_override);
									if($overriden && is_callable($totals_override[$label])){
										$val = $totals_override[$label]($totals);

									} elseif($overriden){
										$val = $totals->$totals_override[$label];

									} elseif (is_callable($fld)) {
										$val = $fld($totals);
									} else {
										$val = $totals->$fld;
									}

									print '<td data-title="'.($label==''?'Totals':$label).'" '.($val === 0 ? 'class="empty"' : '').'>';
										print $val;
									print '</td>';
								}
								?>
							</tr>
						</tfoot>
						<?php
					}
					?>
				</table>
			</section>
		</section>

		<section class="field">
			<header>
				<h2>Field Stats</h2>
			</header>
			<section class="stat-wrapper">
				<?php
				$totals = [];
				$fields = [
					'' => function($p){
						return '<a href="/players/'.$p->name_key.'">#'.$p->number.' '.$p->first_name.' '.$p->last_name.'</a>';
					},
					'Goals' => 'goals',
					'Shots' => 'shots',
					'Shooting %' => function($p){
						try{
							return str_replace('.0','',number_format(($p->goals / $p->shots) * 100, 1).'%');
						} catch (Exception $e) {
							return $p->goals;
						}
					},
					'Assists' => 'assists',
					'Steals' => 'steals',
					'Turn Overs' => 'turn_overs',
					'Steals to Turnovers Ratio' => function($p){
						try{
							return number_format($p->steals / $p->turn_overs, 2);
						} catch (Exception $e) {
							return $p->steals;
						}
					},
					'Blocks' => 'blocks',
					'Kickouts' => 'kickouts',
					'Kickouts Drawn' => 'kickouts_drawn',
					'5m Called' => 'five_meters_called',
					'5m Drawn' => 'five_meters_drawn',
					'5m Taken' => 'five_meters_taken',
					'5m Made' => 'five_meters_made',
					'5m %' => function($p){
						try{
							return str_replace('.0','',number_format(($p->five_meters_made / $p->five_meters_taken) * 100, 1).'%');
						} catch (Exception $e) {
							return $p->five_meters_made;
						}
					},
					'Sprints Won' => 'sprints_won',
					'Sprints Taken' => 'sprints_taken',
					'Shoot Out Taken' => 'shoot_out_taken',
					'Shoot Out Made' => 'shoot_out_made'
				];

				// override any of the above fields for the totals field
				$totals_override = [
					'' => function($p){
						return 'Totals';
					}
				];
				?>
				<table class="stats collapse-table--tablet">
					<thead>
						<tr>
							<?php
							foreach(array_keys($fields) as $label){
								print '<th>'.$label.'</th>';
								$key = label2key($label);
								$totals[$key] = 0;
							}
							?>
						</tr>
					</thead>
					<tbody>
						<?php
						foreach($stats->stats as $player){
							print '<tr>';
								foreach($fields as $label=>$fld){
									if(is_callable($fld)){
										$val = $fld($player);
									} else {
										$val = $player->$fld;
									}

									print '<td data-title="'.($label==''?'Name':$label).'" '.($val === 0 ? 'class="empty"' : '').'>';
										print $val;
									print '</td>';

									$key = label2key($label);
									$totals[$key] += $val;
								}
							print '</tr>';
						}
						?>
					</tbody>
					<tfoot>
						<tr class="totals">
							<?php
							$totals = json_decode(json_encode($totals));
							foreach($fields as $label=>$fld) {
								$overriden = array_key_exists($label, $totals_override);
								if($overriden && is_callable($totals_override[$label])){
									$val = $totals_override[$label]($totals);

								} elseif($overriden){
									$val = $totals->$totals_override[$label];

								} elseif (is_callable($fld)) {
									$val = $fld($totals);
								} else {
									$val = $totals->$fld;
								}

								print '<td data-title="'.($label==''?'Totals':$label).'" '.($val === 0 ? 'class="empty"' : '').'>';
								print $val;
								print '</td>';
							}
							?>
						</tr>
					</tfoot>
				</table>
			</section>
		</section>

		<section class="goals-per-quarter">
			<header><h2>Goals per Quarter</h2></header>
			<table class="fake-headings">
				<thead>
				<tr>
					<th>Hudsonvile</th>
					<th><?= $stats->opponent ?></th>
				</tr>
				</thead>
				<tbody>
				<?php
				$i = 0;
				foreach($stats->boxscore[0] as $quarter){
					?>
					<tr class="quarter-divider">
						<th ><?= $quarter_labels[$i] ?></th>
						<th><?= $quarter_labels[$i] ?></th>
					</tr>
					<tr>
						<td class="us">
							<table class="player-goals">
								<?php
								if(count(get_object_vars($stats->boxscore[0][$i]))){
									foreach($stats->boxscore[0][$i] as $name_key => $goals){
										?>
										<tr>
											<td class="name">
												<?php
												$p = $players_by_key[$name_key];
												print '#'.$p->number.' '.$p->first_name.' '.$p->last_name;
												?>
											</td>
											<td class="goals"><?= $goals ?></td>
										</tr>
										<?php
									}
								} else {
									print '<tr><td class="empty goals" colspan="2">0</td></tr>';
								}
								?>
							</table>
						</td>
						<td class="them"><?= $stats->boxscore[1][$i] ?></td>
					</tr>
					<?php
					$i++;
				}
				?>
				</tbody>
			</table>
		</section>


		<section class="advantages-converted split">
			<header><h2>Advantages Converted</h2></header>
			<section>
				<section>
					<header><h3>Hudsonville</h3></header>
					<section>
						<?php echo $stats->advantage_conversion[0]->converted ?> / <?php echo $stats->advantage_conversion[0]->drawn ?>
					</section>
				</section>

				<section>
					<header><h3><?php echo $stats->opponent ?></h3></header>
					<section>
						<?php echo $stats->advantage_conversion[1]->converted ?> / <?php echo $stats->advantage_conversion[1]->drawn ?>
					</section>
				</section>
			</section>
		</section>


	</article>
	<hr class="clear" />
</div>

<p class="note">v<?= $this->dump_version ?></p>