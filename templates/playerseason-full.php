<?php
/**
 * @var $this PlayerSeason
 * @var $badges	Badge[]
 * @var $photos Photo[]
 * @var $articles Article[]
 * @var $stats Stats
 */
$badges = $this->getBadges();
$articles = $this->getArticles();
$photos = $this->getPhotos();
$stats = $this->getStats()
?>
<div class="content player_season_full" data-player_id="<?php echo $this->player_id ?>" data-season_id="<?php echo $this->season_id ?>">

	<hgroup class="season_identifier">
		<?php
		if(strlen($this->number)){
			print '<h2><sup>#</sup>'.$this->number.'</h2>';
		}
		?>
		<h3><?php echo $this->season_title ?></h3>
	</hgroup>

	<?php
	if(count($badges) || $stats !== false){
		print '<aside id="top">';

			if(count($badges)){
				print '<section class="badges">';
					foreach($this->getBadges() as $badge){
						echo $badge->output('badge');
					}
				print '</section>';
			}

			if($stats !== false){
				print '<section class="stats">';
					print $stats->output('player-season-highlight-'.strtolower($this->position));
				print '</section>';
			}


		print '</aside>';
	}
	?>

	<article id="main" class="gallery <?= count($articles) > 0 ? 'grid_9' : 'grid_12' ?>">
		<?php
		if($photos != null){
			foreach($this->getPhotos() as $photo){
				echo $photo->output('gallery', ['player'=>$this->player]);
			}
		} else {
			$photo = new Photo(0, $this->dbh);
			echo $photo->output('zero');
		}
		?>
		<hr class="clear" />
	</article>

	<?php
	if(count($articles) > 0){
		?>
		<aside id="right_sidebar" class="grid_3">

			<section class="articles">
				<h2>Articles</h2>
				<?php
				foreach($this->getArticles() as $article){
					echo $article->output('player', $this->player_id);
				}
				?>
			</section>

		</aside>
		<?php
	}
	?>

	<hr class="clear" />
</div>