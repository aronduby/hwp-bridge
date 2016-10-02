<li>
	<input type="checkbox" id="game_id_<?php echo $this->game_id ?>" name="game_id[]" value="<?php echo $this->game_id ?>" />
	<label for="game_id_<?php echo $this->game_id ?>">
		<time><?php echo $this->start->format('M, jS').'</time> '.$this->title ?>
	</label>
</li>