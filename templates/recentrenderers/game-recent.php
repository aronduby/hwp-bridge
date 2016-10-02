<section class="games" data-recent-id="<?= $this->recent_id ?>">

	<h2>Game Results</h2>
	<time datetime="<?php echo $this->inserted->format('c') ?>"><?php echo $this->inserted->format('F jS, Y @ g:ia') ?></time>
	
	<div class="tiny_games">
		<?php
		foreach($this->games as $g){
			echo $g->output('results');
		}
		?>
	</div>

</section>