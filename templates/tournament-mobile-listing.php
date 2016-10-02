<li data-theme="e">
	<a href="tournament.php?tournament_id=<?php echo $this->id ?>" title="view tournament">
		<h3><?php echo $this->title ?></h3>
		<p><strong><?php echo str_replace(' @ 12:00am', '', $this->start->format('M, jS @ g:ia') .' - '. $this->end->format('M, jS @ g:ia')) ?></strong></p>
	</a>
	<a href="addedit-tournament.php?tournament_id=<?php echo $this->id ?>" title="edit tournament">edit tournament</a>
</li>