<tr class="tournament <?php echo $this->end->getTimestamp() < time() ? 'past' : '' ?>">
	<td class="date" data-title="date">
		<?php 
		echo $this->start->format('D n/j');
		$time = $this->start->format('g:ia');
		if($time != '12:00am'){
			echo ' at '.$time;
		} else {
			echo ' all day';
		}
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
	<td class="result <?php echo strlen($this->result)==0 ? 'empty' : '' ?>" data-title="result" colspan="2"><?php echo strlen($this->result)>0 ? $this->result : '&ndash;'; ?></td>
	<td class="stats empty" data-title="stats">
		<?php
		// echo $this->hasStats() ? '<a href="norewrite/stats.php?tournament_id='.$this->tournament_id.'" title="view stats for this tournament">view stats</a>' : ''
		?>
	</td>
</tr>