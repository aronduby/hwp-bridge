<section class="article" data-article-id="<?php echo $this->article_id ?>">
	
	<h3><a href="<?php echo $this->url ?>" title="<?php echo $this->title ?>" target="_blank"><?php echo $this->title ?></a></h3>
	
	<blockquote><?php echo $this->description ?></blockquote>

	<?php
	if(count($this->mentions)>0){
		$strs = [];
		foreach($this->mentions as $r)
			$strs[] = '<a href="players/'.$r['player']->name_key.'" title="'.$r['player']->name.'">'.$r['player']->name.'</a>';
		
		print '<p class="mentions">mentions: '.implode(', ', $strs).'</p>';
	}
	?>
</section>