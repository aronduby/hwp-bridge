<li>
	<input type="checkbox" id="tournament_id_<?php echo $this->tournament_id ?>" name="tournament_id[]" value="<?php echo $this->tournament_id ?>" />
	<label for="tournament_id_<?php echo $this->tournament_id ?>">
		<time><?php echo $this->start->format('M, jS').'</time> '.$this->title ?>
	</label>
</li>