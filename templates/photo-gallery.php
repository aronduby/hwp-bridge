<?php
$lazyload = true;
if(is_array($extra) && array_key_exists('lazyload', $extra) && $extra['lazyload']===false)
	$lazyload = false;

$original = $this->thumb;
$src = $lazyload ? 'layout_imgs/thumbnail-loading.png' : $original;
?>
<a class="gallery_pic" id="pic-<?php echo $this->photo_id ?>" href="<?php echo $this->photo ?>" title="" data-photo_id="<?php echo $this->photo_id ?>" >
	<!--<img <?php echo $lazyload?'data-original':'src' ?>="<?php echo $this->thumb ?>" alt="thumb" />-->
	<img data-original="<?= $original ?>" src="<?= $src ?>" alt="thumb" />
	<span class="code"><?php echo $this->getJSONTitle($extra['player']) ?></span>
</a>