<div
	class="player_season_listing <?= ($this->season_id == $extra['current_season_id'] ? 'current' : '') ?>"
	data-season-id="<?php echo $this->season_id ?>"
	data-player-id="<?php echo $this->player_id ?>"
>
	<h3>
		<span class="season-title--short"><?php echo  $this->season_short_title ?></span>
		<span class="season-title--full"><?= $this->season_title ?> Season</span>
	</h3>
</div>