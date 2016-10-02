<?php
$lazyload = true;
if(is_array($extra) && array_key_exists('lazyload', $extra) && $extra['lazyload']===false)
	$lazyload = false;
?>
<a class="gallery_pic" rel="gallery_<?php echo $extra['rel'] ?>" id="pic-<?php echo $this->photo_id ?>" href="<?php echo $this->photo ?>" title="" data-photo_id="<?php echo $this->photo_id ?>" >
	<img <?=$lazyload?'data-original':'src'?>="<?php echo $this->thumb ?>" alt="thumb" />
	<span class="code"><?php echo $this->getJSONTitle() ?></span>
</a>