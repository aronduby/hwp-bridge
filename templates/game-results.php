<section class="results" data-game-id="<?php echo $this->game_id ?>">

	<h3>
		<?php 
		print $this->team == 'V' ? 'Varsity' : $this->team;
		switch($this->result){
			case 'W':
				print ' defeated ';
				break;
			case 'L':
				print ' lost to ';
				break;
			case 'T':
				print ' tied with ';
				break;
		}

		print $this->opponent;
		?>
	</h3>

	<?php
	// live scoring possibilities
	if(!is_null($this->score_us) && !is_null($this->score_them)){
		?>
		<section class="score">
			<div class="us">
				<h4>Eagles</h4>
				<h5><?php echo $this->score_us ?></h5>
			</div>
			<div class="them">
				<h4><?php echo $this->opponent ?></h4>
				<h5><?php echo $this->score_them ?></h5>
			</div>
		</section>
		<?php
	}
	?>

	<p class="links">
		<?php 
		// echo $this->has_stats ? '<a class="stats" href="norewrite/stats.php?game_id='.$this->game_id.'" title="view stats for this game">view stats</a>' : '';
		echo $this->has_live_scoring ? '<a class="live_scoring_archive" href="livescoringarchive/'.$this->game_id.'" title="view live scoring archive">view live scoring archive</a>' : '';
		echo $this->has_recap ? '<a class="recap" href="gamerecap/'.$this->game_id.'" title="view recap">view recap of this game</a>' : '';
		echo $this->has_photo_album ? '<a class="photoalbum" href="photoalbums/'.$this->album_id.'" title="view photos">view photos</a>' : '';
		?>
	</p>

</section>