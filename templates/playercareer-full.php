<?php
/**
 * @var $this PlayerCareer
 */
?>

<div class="content player_season_full" data-player_id="<?php echo $this->player->id ?>" data-season_id="all">

	<?php
	if(strlen($this->number)){
		?>
		<hgroup class="season_identifier">
			<h2>#<?php echo $this->number ?></h2>
		</hgroup>
		<?php
	}
	?>

	<?php
	if( count($this->badges) > 0 || $this->stats !== false ){
		print '<aside id="top">';

			if(count($this->badges)){
				print '<section class="badges">';
				foreach($this->badges as $v){
					echo $v['badge']->output('badge', $v['count']);
				}
				print '</section>';
			}

			if($this->stats !== false){
				print '<section class="stats">';
					print $this->stats->output('player-season-highlight-'.strtolower($this->position));
				print '</section>';
			}

		print '</aside>';
	}

	?>

	<article id="main" class="gallery <?= count($this->articles) > 0 ? 'grid_9 alpha' : 'grid_12' ?>">
		<?php 
		foreach($this->photos as $photo){
			echo $photo->output('gallery', ['player'=>$this->player]);
		}
		?>
		<hr class="clear" />
	</article>

	<?php
	if(count($this->articles) > 0){
		?>
		<aside id="right_sidebar" class="grid_3 omega">

			<section class="articles">
				<h2>Articles</h2>
				<?php
				foreach($this->articles as $article){
					echo $article->output('player', $this->player->id);
				}
				?>
			</section>

		</aside>
		<?php
	}
	?>

	<hr class="clear" />
</div>