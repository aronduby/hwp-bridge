<section class="upcoming">

	<h3><?php echo $this->team == 'V' ? 'Varsity' : $this->team ?></h3>

	<h4>
		<?php echo $this->title ?> at
		<a class="location" 
			href="<?php echo $this->location->googleDirectionsLink() ?>" 
			title="Get Directions" 
			data-static_map="<?php echo $this->location->googleStaticMap(250, 120) ?>"
			data-full_address="<?php echo $this->location->full_address ?>"
			>
			<?php echo $this->location->title ?>
		</a>
	</h4>

	<p class="date">
		<?php 
		echo $this->start->format('n/j');
		
		$time = $this->start->format('@ g:ia');
		if($time != '@ 12:00am'){
			echo ' '.$time;
		} else {
			echo ' all day';
		}		
		?>
	</p>

	<?php
	if(isset($this->note))
		print $this->note;
	?>

</section>