<li<?php echo $this->end->getTimestamp() < time() ? ' data-theme="f"' :'' ?>>
	<a href="scoring.php#/game/<?php echo $this->id ?>/start" title="view game" data-ajax="false">
		<h3><?php echo $this->title ?></h3>	
		<p><strong><?php echo $this->start->format('M, j @ g:ia') ?></strong></p>
	</a>
	<a href="addedit-game.php?game_id=<?php echo $this->id ?>" title="edit game">edit game</a>
</li>