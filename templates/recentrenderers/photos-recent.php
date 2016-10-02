<section class="photos" data-recent-id="<?php echo $this->recent_id ?>">

	<h2><?php echo $this->title ?></h2>
	<time datetime="<?php echo $this->inserted->format('c') ?>"><?php echo $this->inserted->format('F jS, Y @ g:ia') ?></time>
	
	<div class="tiny_gallery">
		<?php
		$i = 0;
		foreach($this->photos as $p){
			if($i < $this->max_thumbnails)
				echo $p->output('tiny', $this->inserted->getTimestamp());
			else
				echo $p->output('hidden', $this->inserted->getTimestamp());
			
			$i++;
		}

		if($i > $this->max_thumbnails)
			print '<p class="note">and '.($i - $this->max_thumbnails - 1).' others</p>';
		?>
	</div>

</section>