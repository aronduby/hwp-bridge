<tr class="game <?php echo $this->end->getTimestamp() < time() ? 'past' : '' ?>">
	<td class="date" data-title="date">
		<?php 
		$date = $this->start->format('D n/j @ g:ia');
		echo str_replace(' at 12:00am', '', $date);
		?>
	</td>
	<td class="team" data-title="team">
		<abbv><?php echo $this->team ?></abbv><span><?php echo $this->team=='V' ? 'Varsity' : $this->team ?></span>
	</td>
	<td class="title" data-title="title">
		<?php echo $this->title ?>
	</td>
	<td class="location" data-title="location">
		<a class="location" 
			href="<?php echo $this->location->googleDirectionsLink() ?>" 
			title="Get Directions" 
			data-static_map="<?php echo $this->location->googleStaticMap(250,120) ?>"
			data-full_address="<?php echo $this->location->full_address ?>"
			>
			<?php echo $this->location->title ?>
		</a>
	</td>
	<td class="result <?php echo is_null($this->result) ? 'empty' : $this->result ?>" data-title="result">
		<abbv><?php echo is_null($this->result) ? '&ndash;' : $this->result ?></abbv>
		<span><?php
			switch($this->result){
				case 'W':
					print 'Won';
					break;
				case 'L':
					print 'Lost';
					break;
				case 'T':
					print 'Tied';
					break;
				default:
					print '&ndash;';
					break;
			}
		?></span>
	</td>
	<td class="score <?php echo is_null($this->score_us) ? 'empty' : '' ?>" data-title="score">
		<?php echo $this->score_us .' &ndash; '.$this->score_them ?>
	</td>
	<td class="stats <?php echo !$this->has_stats && !$this->has_live_scoring && !$this->has_recap && !$this->has_photo_album ? 'empty' : '' ?>">
		<?php
		// echo $this->has_stats ? '<a class="stats" href="norewrite/stats.php?game_id='.$this->game_id.'" title="view stats for this game">view stats</a>' : '';
		echo $this->has_live_scoring ? '<a class="live_scoring_archive" href="livescoringarchive/'.$this->game_id.'" title="view live scoring archive">view live scoring archive</a>' : '';
		echo $this->has_recap ? '<a class="recap" href="gamerecap/'.$this->game_id.'" title="view recap">view recap of this game</a>' : '';
		echo $this->has_photo_album ? '<a class="photoalbum" href="photoalbums/'.$this->album_id.'" title="view photos">view photos</a>' : '';
		?>
	</td>
</tr>