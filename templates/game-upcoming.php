<section class="upcoming">

	<h3><?php echo $this->team == 'V' ? 'Varsity' : $this->team ?></h3>

	<h4>
		<?php echo $this->title ?> at
		<a class="location" 
			href="<?php echo $this->location->googleDirectionsLink() ?>" 
			title="Get Directions" 
			data-static_map="<?php echo $this->location->googleStaticMap(250,120) ?>"
			data-full_address="<?php echo $this->location->full_address ?>"
			>
			<?php echo $this->location->title ?>
		</a>
	</h4>

	<p class="date">
		<?php echo str_replace(' @ 12:00am', '', $this->start->format('n/j @ g:ia')) ?>
	</p>

	<?php
	// live scoring possibilities
	if(!is_null($this->score_us) && !is_null($this->score_them)){
		?>
		<section class="live_scoring">
			<div class="us">
				<h5>Eagles</h5>
				<h6><?php echo $this->score_us ?></h6>
			</div>
			<div class="them">
				<h6><?php echo $this->opponent ?></h6>
				<h6><?php echo $this->score_them ?></h6>
			</div>
			<?php echo $this->has_live_scoring ? '<a class="live_scoring_archive" href="livescoringarchive/'.$this->game_id.'" title="view live scoring archive">view live scoring archive</a>' : '' ?>
		</section>
		<?php
	}
	?>

</section>