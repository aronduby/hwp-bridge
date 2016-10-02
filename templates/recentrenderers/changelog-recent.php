<section class="changelog" data-recent-id="<?php echo $this->recent_id ?>">
	
	<h2><?php echo $this->title ?></h2>
	<time datetime="<?php echo $this->inserted->format('c') ?>"><?php echo $this->inserted->format('F jS, Y @ g:ia') ?></time>

	<?php echo $this->content ?>

	<p><a href="<?php echo $this->url ?>" title="view changes">view all changes</a></p>

</section>