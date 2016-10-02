<section class="tournaments" data-recent-id="<?php echo $this->recent_id ?>">

	<h2>Tournament Results</h2>
	<time datetime="<?php echo $this->inserted->format('c') ?>"><?php echo $this->inserted->format('F jS, Y @ g:ia') ?></time>
	
	<div class="tiny_tournaments">
		<?php
		foreach($this->tournaments as $t){
			echo $t->output('results');
		}
		?>
	</div>

</section>