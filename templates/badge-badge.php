<?php
/**
 * @var $this Badge
 */
?>
<div class="badge <?= $this->double ? 'badge--double' : '' ?>">
	<img src="layout_imgs/badges/<?php echo $this->image ?>" alt="<?php echo $this->description ?>" data-badge_id="<?php echo $this->badge_id ?>" />
	<div class="pop" data-badge_id="<?php echo $this->badge_id ?>">
		<p class="title"><?php echo $this->title ?></p>
		<p class="description"><?php echo $this->description ?></p>
		<?php
		if(isset($extra) && $extra!= 1)
			echo '<p class="multiple">x'.$extra.'</p>';
		?>
	</div>
</div>