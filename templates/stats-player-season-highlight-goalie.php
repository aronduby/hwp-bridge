<?php
/**
 * @var $this Stats
 */
?>

<section class="stat stat--saves">

	<div class="stat-chart-wrapper">
		<div class="stat-chart-sizer">
			<div class="stat-chart"></div>
		</div>
	</div>

	<div class="stat-header">
		<h1 class="percent"><?= $this->save_percent ?></h1>
		<p><?= $this->saves ?>/<?= $this->goals_allowed ?></p>
	</div>

	<h2>Saves</h2>

	<pre class="json"><?php

		// they have some saves or goals allowed
		if($this->saves > 0 || $this->goals_allowed > 0){
			$json = [
				'data' => [
					['Stat', 'Value'],
					['Saves', $this->saves],
					['Goals Allowed', $this->goals_allowed]
				],
			];

		// done nothing, grey
		} else {
			$json = [
				'options' => [
					'negative' => true
				],
				'data' => [
					['Stat', 'Value'],
					['Saves/Goals Allowed', [
						'v' => 1,
						'f' => 0
					]]
				],
			];
		}

		print json_encode($json, JSON_PRETTY_PRINT);
	?></pre>
</section>

<section class="stat stat--five-meter-saves">

	<div class="stat-chart-wrapper">
		<div class="stat-chart-sizer">
			<div class="stat-chart"></div>
		</div>
	</div>

	<div class="stat-header">
		<h1 class="percent"><?= $this->five_meters_save_percent ?></h1>
		<p><?= $this->five_meters_blocked ?>/<?= $this->five_meters_missed ?>/<?= $this->five_meters_allowed ?></p>
	</div>

	<h2>Five Meters</h2>
	<h3>Blocked/Missed/Allowed</h3>

	<pre class="json"><?php

		// have had 5 meters taken on them
		if($this->five_meters_taken_on > 0){
			$json = [
				'options' => [
					'multiple' => true
				],
				'data' => [
					['Stat', 'Value'],
					['Blocked', $this->five_meters_blocked],
					['Missed', $this->five_meters_missed],
					['Allowed', $this->five_meters_allowed]
				],
			];

		// no 5 meters taken on
		} else {
			$json = [
				'options' => [
					'negative' => true
				],
				'data' => [
					['Stat', 'Value'],
					['Blocked/Missed/Allowed', [
						'v' => 1,
						'f' => 0
					]]
				]
			];
		}

		print json_encode($json, JSON_PRETTY_PRINT);
		?></pre>
</section>

<section class="stat stat--shoot-out-saves">

	<div class="stat-chart-wrapper">
		<div class="stat-chart-sizer">
			<div class="stat-chart"></div>
		</div>
	</div>

	<div class="stat-header">
		<h1 class="percent"><?= $this->shoot_out_save_percent ?></h1>
		<p><?= $this->shoot_out_blocked ?>/<?= $this->shoot_out_missed ?>/<?= $this->shoot_out_allowed ?></p>
	</div>

	<h2>Shoot Outs</h2>
	<h3>Blocked/Missed/Allowed</h3>

	<pre class="json"><?php

		if($this->shoot_out_taken_on){
			$json = [
				'options' => [
					'multiple' => true
				],
				'data' => [
					['Stat', 'Value'],
					['Blocked', $this->shoot_out_blocked],
					['Missed', $this->shoot_out_missed],
					['Allowed', $this->shoot_out_allowed]
				]
			];
		} else {
			$json = [
				'options' => [
					'negative' => true
				],
				'data' => [
					['Stat', 'Value'],
					['Blocked/Missed/Allowed', [
						'v' => 1,
						'f' => 0
					]]
				]
			];
		}

		print json_encode($json, JSON_PRETTY_PRINT);
		?></pre>
</section>