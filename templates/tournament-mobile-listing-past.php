<li data-theme="e" data-icon="arrow-r" data-iconshadow="false">
	<a href="tournament.php?tournament_id=<?php echo $this->id ?>" title="view tournament">
		<h3><?php echo $this->title ?></h3>
		<p><strong><?php echo str_replace(' @ 12:00am', '', $this->start->format('M, jS @ g:ia') .' - '. $this->end->format('M, jS @ g:ia')) ?></strong></p>
	</a>
	<a data-theme="e" href="addedit-tournament.php?tournament_id=<?php echo $this->id ?>" title="edit tournament">edit tournament</a>
</li>