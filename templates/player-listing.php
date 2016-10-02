<?php
/* @var $this Player */
?>
<div class="player">
	<div class="img_holder">
		<a href="players/<?php echo $this->name_key ?>" title="view profile">
			<img src="<?php echo $this->getRandomPhoto()->thumb ?>" alt="<?php echo $this->name ?> photo" />
		</a>
	</div>
	<h2><a href="players/<?php echo $this->name_key ?>" title="view profile"><?php echo $this->name ?></a></h2>
	<?php
	if(isset($this->title)){
		print '<h3 class="title">'.$this->title.'</h3>';
	}
	?>
	<p>
		<?php
		$photo_count = $this->countPhotos($extra);
		$badge_count = $this->countBadges($extra);
		$article_count = $this->countArticles($extra);

		if($photo_count) echo '<span class="count">'.$photo_count.'</span> photos ';
		if($badge_count) echo '<span class="count">'.$badge_count.'</span> badges ';
		if($article_count) echo '<span class="count">'.$article_count.'</span> articles ';
		?>
	</p>
	<p><a href="players/<?php echo $this->name_key ?>" title="view profile">view profile</a></p>
	
	<hr class="clear" />
</div>