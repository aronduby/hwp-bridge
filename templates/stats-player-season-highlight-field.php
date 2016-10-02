<?php
/**
 * @var $this Stats
 */
?>
<section class="stat stat--shooting">

	<div class="stat-chart-wrapper">
		<div class="stat-chart-sizer">
			<div class="stat-chart"></div>
		</div>
	</div>

	<h2>Shooting</h2>

	<div class="stat-header">
		<h1 class="percent"><?= $this->shooting_percent ?></h1>

		<p><?= $this->goals ?>&thinsp;/&thinsp;<?= $this->shots ?></p>
	</div>

	<pre class="json"><?php
		// they have taken some shots
		if($this->shots > 0){
			$json = [
				'data' => [
					['Stat', 'Value'],
					['Made', $this->goals],
					['Missed/Blocked', $this->shots - $this->goals]
				],
			];

		// no shots taken, just do grey
		} else {
			$json = [
				'options' => [
					'negative' => true
				],
				'data' => [
					['Stat', 'Value'],
					['Shots', [
						'v' => 1,
						'f' => 0
					]],
				],
			];
		}

		print json_encode($json, JSON_PRETTY_PRINT);
	?></pre>
</section>

<section class="stat stat--steals-turnovers">

	<div class="stat-chart-wrapper">
		<div class="stat-chart-sizer">
			<div class="stat-chart"></div>
		</div>
	</div>

	<h2>Steals&thinsp;/&thinsp;Turnovers</h2>

	<div class="stat-header">
		<h1 class="<?= $this->steals_to_turn_overs > 0 ? 'positive' : ($this->steals_to_turn_overs < 0 ? 'negative' : '') ?>"><?= str_replace('-', '', $this->steals_to_turn_overs) ?></h1>

		<p><?= $this->steals ?>&thinsp;/&thinsp;<?= $this->turn_overs ?></p>
	</div>

	<pre class="json"><?php

		// they have at least 1 steal or turn over
		if($this->steals > 0 || $this->turn_overs > 0){

			// if they have more steals than turnovers
			if ($this->steals > $this->turn_overs) {
				$json =[
					'data' => [
						['Stat', 'Value'],
						['Steals', $this->steals],
						['Turn Overs', $this->turn_overs]
					],
				];

			// more turnovers, draw it negative
			} else {
				$json = [
					'options' => [
						'negative' => true
					],
					'data'    => [
						['Stat', 'Value'],
						['Turn Overs', $this->turn_overs],
						['Steals', $this->steals]
					],
				];
			}

		// no steals and no turnovers, just grey
		} else {
			$json = [
				'options' => [
					'negative' => true
				],
				'data' => [
					['Stat', 'Value'],
					['Steals/Turnovers', [
						'v' => 1,
						'f' => 0
					]]
				]
			];
		}

		print json_encode($json, JSON_PRETTY_PRINT);
	?></pre>
</section>

<section class="stat stat--kickouts">

	<div class="stat-chart-wrapper">
		<div class="stat-chart-sizer">
			<div class="stat-chart"></div>
		</div>
	</div>

	<h2>Kickouts</h2>
	<h3>Drawn&thinsp;/&thinsp;Called</h3>

	<div class="stat-header">
		<h1 class="<?= $this->kickouts_drawn_to_called > 0 ? 'positive' : ($this->kickouts_drawn_to_called < 0 ? 'negative' : '') ?>"><?= str_replace('-', '', $this->kickouts_drawn_to_called) ?></h1>

		<p><?= $this->kickouts_drawn ?>&thinsp;/&thinsp;<?= $this->kickouts ?></p>
	</div>

	<pre class="json"><?php
		// they have at least one kickout or kickout drawn
		if($this->kickouts_drawn > 0 || $this->kickouts > 0){

			// they have drawn more kickouts than they've been called, it's positive
			if ($this->kickouts_drawn > $this->kickouts) {
				$json = [
					'data' => [
						['Stat', 'Value'],
						['Drawn', $this->kickouts_drawn],
						['Called', $this->kickouts]
					],
				];

			// been kicked out more than drawn, negative
			} else {
				$json = [
					'options' => [
						'negative' => true
					],
					'data'    => [
						['Stat', 'Value'],
						['Called', $this->kickouts],
						['Drawn', $this->kickouts_drawn]
					],
				];
			}

		// no kickouts either way, just grey
		} else {
			$json = [
				'options' => [
					'negative' => true
				],
				'data' => [
					['Stat', 'Value'],
					['Kickouts Drawn/Called', [
						'v' => 1,
						'f' => 0
					]]
				]
			];
		}

		print json_encode($json, JSON_PRETTY_PRINT);
	?></pre>
</section>

<?php
// Do either sprints or assists/goals
// But only do sprints if they've taken more than 1
if ($this->sprints_taken > 2) {
	?>
	<section class="stat stat--sprints">

		<div class="stat-chart-wrapper">
			<div class="stat-chart-sizer">
				<div class="stat-chart"></div>
			</div>
		</div>

		<h2>Sprints</h2>

		<div class="stat-header">
			<h1 class="percent"><?= $this->sprints_percent ?></h1>

			<p><?= $this->sprints_won ?>&thinsp;/&thinsp;<?= $this->sprints_taken ?></p>
		</div>

		<pre class="json"><?php
			print json_encode([
				'data' => [
					['Stat', 'Value'],
					['Won', $this->sprints_won],
					['Lost', $this->sprints_taken - $this->sprints_won]
				],
			], JSON_PRETTY_PRINT);
		?></pre>
	</section>
	<?php
}
else {
	?>
	<section class="stat stat--assists">

		<div class="stat-chart-wrapper">
			<div class="stat-chart-sizer">
				<div class="stat-chart"></div>
			</div>
		</div>

		<h2>Goals&thinsp;/&thinsp;Assists</h2>

		<div class="stat-header">
			<h1><?= $this->goals ?>&thinsp;/&thinsp;<?= $this->assists ?></h1>
		</div>

		<pre class="json"><?php

			// if they have some goals or some assists
			if($this->goals > 0 || $this->assists > 0){
				$json = [
					'options' => [
						'multiple' => true
					],
					'data'    => [
						['Stat', 'Value'],
						['Goals', $this->goals],
						['Assists', $this->assists]
					],
				];

			// no goals or assists, just show grey
			} else {
				$json = [
					'options' => [
						'negative' => true
					],
					'data' => [
						['Stat', 'Value'],
						['Goals/Assists', [
							'v' => 1,
							'f' => 0
						]]
					]
				];
			}

			print json_encode($json, JSON_PRETTY_PRINT);
		?></pre>
	</section>
	<?php
}
?>