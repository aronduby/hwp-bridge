<section class="results" data-tournament-id="<?= $this->tournament_id ?>">

	<h3><?php echo $this->team == 'V' ? 'Varsity' : $this->team ?> finished <?php echo $this->result ?></h3>

	<h4>
		<?php echo $this->title ?> at
		<a class="location" 
			href="<?php echo $this->location->googleDirectionsLink() ?>" 
			title="Get Directions" 
			data-static_map="<?php echo $this->location->googleStaticMap(250, 120) ?>"
			data-full_address="<?php echo $this->location->full_address ?>"
			>
			<?php echo $this->location->title ?>
		</a>
	</h4>

	<?php
	$games = $this->getGames();
	if(count($games) > 0){
		print '<ul class="games">';
			foreach($games as $g){
				print '<li data-game-id="'.$g->game_id.'">';
					switch($g->result){
						case 'W':
							print '<span class="result W">Win</span> over ';
							break;
						case 'L':
							print '<span class="result L">Lose</span> to ';
							break;
						case 'T':
							print '<span class="result T">Tied</span> with ';
							break;
					}
					print $g->opponent;

					if(!is_null($g->score_us) && !is_null($g->score_them))
						print ' - '.$g->score_us.' to '.$g->score_them;
				print '</li>';
			}
		print '</ul>';
	}
	?>

</section>