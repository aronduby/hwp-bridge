<tr>
	<td class="when">
		<p class="date"><?php echo $this->ts->format('M. jS, Y') ?></p>
		<p class="time"><?php echo $this->ts->format('g:ia') ?></p>
	</td>
	<td class="changes">
		<?php
		echo $this->changes;
		
		if(count($this->suggestions)>0){
			$titles = [];
			foreach($this->suggestions as $s)
				$titles[] = $s->title;

			echo '<p class="implements"><label>Implements the following suggestions:</label>'.implode(', ', $titles).'</p>';
		}
		?>
	</td>
</tr>