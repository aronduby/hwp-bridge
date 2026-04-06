<li data-icon="grid">
	<a href="addedit-game.php?game_id=<?php echo $this->id ?>" title="edit game" >
		<h3><?php echo $this->title ?></h3>	
		<p><strong><?php echo $this->start->format('M, j @ g:ia') ?></strong></p>
	</a>
	<!--<a href="addedit-game.php?game_id=<?php echo $this->id ?>" title="edit game">edit game</a>-->
	<a href="<?= PUBLIC_HREF ?>/game/<?= $this->id ?>/stats/edit" title="edit stats" target="_blank">edit stats</a>
</li>