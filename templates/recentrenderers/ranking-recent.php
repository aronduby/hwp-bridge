<section class="rankings" data-recent-id="<?php echo $this->recent_id ?>">

	<h2>New Rankings Announced: <?php echo ($this->ranking->tied == true ? 'tied for' : '').' '.date('jS', mktime(null,null,null,12, $this->ranking->ranking, 2012)) ?> place!</h2>
	<time datetime="<?php echo $this->inserted->format('c') ?>"><?php echo $this->inserted->format('F jS, Y @ g:ia') ?></time>

	<?php
	if(isset($this->ranking->url)){
		?>
		<p><em>View all of this weeks rankings and more at the <a href="<?php echo $this->ranking->url ?>" title="Michigan Water Polo" target="_blank">Michigan Water Polo Association</a>.</em></p>
		<?php
	}
	?>
	
	

</section>