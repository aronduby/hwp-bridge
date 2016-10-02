<?php
$cover = $extra['cover'];
$additional = $extra['additional'];
// $game = $extra['game'];
?>
<section class="photo-album" data-album_id="<?php echo $this->album_id?>">

	<div class="gallery"><?php echo $cover->output('gallery') ?></div>
	
	<h2><a href="photoalbums/<?php echo $this->album_id ?>" title="view album"><?php echo $this->title ?></a></h2>

	<time datetime="<?php echo $this->modified->format('c') ?>"><?php echo $this->modified->format('F jS, Y @ g:ia') ?></time>

	<div class="additional tiny-gallery">
		<?php
		foreach($additional as $p)
			print $p->output('tiny');
		?>	
	</div>

	<p><a href="photoalbums/<?php echo $this->album_id ?>" title="view album">view full album</a></p>	

</section>