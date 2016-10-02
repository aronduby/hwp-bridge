<section class="article" data-title="Article">
	
	<h2><a href="<?php echo $this->url ?>" title="<?php echo $this->title ?>" target="_blank"><?php echo $this->title ?></a></h2>
	
	<p class="published"><?php echo $this->published->format('F jS, Y') ?></p>
	
	<blockquote><?php echo $this->mentions[$extra]['highlight'] ?></blockquote>

	<?php
	if(count($this->mentions)>0){
		$strs = [];
		foreach($this->mentions as $r)
			$strs[] = '<a href="players/'.$r['player']->name_key.'" title="'.$r['player']->name.'">'.$r['player']->name.'</a>';
		
		print '<p class="mentions">mentions: '.implode(', ', $strs).'</p>';
	}
	?>
</section>