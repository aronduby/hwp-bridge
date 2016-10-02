<section class="articles" data-recent-id="<?php echo $this->recent_id ?>">

	<h2><?php echo $this->title ?></h2>
	<time datetime="<?php echo $this->inserted->format('c') ?>"><?php echo $this->inserted->format('F jS, Y @ g:ia') ?></time>
	
	<div class="tiny_articles">
		<?php
		foreach($this->articles as $a){
			echo $a->output('tiny');
		}
		?>
	</div>

</section>