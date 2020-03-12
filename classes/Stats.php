<?php

/**
 * Class Stats
 *
 * @property int $goals
 * @property int $shots
 * @property int $shooting_percent
 * @property int $assists
 * @property int $steals
 * @property int $turn_overs
 * @property int $steals_to_turn_overs
 * @property int $blocks
 * @property int $kickouts_drawn
 * @property int $kickouts
 * @property int $kickouts_drawn_to_called
 * @property int $saves
 * @property int $goals_allowed
 * @property int $save_percent
 * @property int $sprints_taken
 * @property int $sprints_won
 * @property int $sprint_percent
 * @property int $five_meters_drawn
 * @property int $five_meters_taken
 * @property int $five_meters_made
 * @property int $five_meters_percent
 * @property int $five_meters_called
 * @property int $five_meters_taken_on
 * @property int $five_meters_blocked
 * @property int $five_meters_allowed
 * @property int $five_meters_missed
 * @property int $five_meters_save_percent
 * @property int $shoot_out_taken
 * @property int $shoot_out_made
 * @property int $shoot_out_percent
 * @property int $shoot_out_taken_on
 * @property int $shoot_out_blocked
 * @property int $shoot_out_allowed
 * @property int $shoot_out_missed
 * @property int $shoot_out_save_percent
 */
class Stats implements Iterator{

	use Outputable;

	public static $fields = array(
		'goals' => array(
			'label' => 'Goals',
			'order' => 'high'
		),
		'shots' => array(
			'label' => 'Shots',
			'order' => 'high'
		),
		'shooting_percent' => array(
			'label' => 'Shooting Percentage',
			'order' => 'high',
			'calculated' => true
		),
		'assists' => array(
			'label' => 'Assists',
			'order' => 'high'
		),
		'steals' => array(
			'label' => 'Steals',
			'order' => 'high'
		),
		'turn_overs' => array(
			'label' => 'Turn Overs',
			'order' => 'low'
		),
		'steals_to_turn_overs' => array(
			'label' => 'Steals to Turn Overs',
			'order' => 'high',
			'calculated' => true
		),
		'blocks' => array(
			'label' => 'Blocks',
			'order' => 'high'
		),
		'kickouts_drawn' => array(
			'label' => 'Kick Outs Drawn',
			'order' => 'high'
		),
		'kickouts' => array(
			'label' => 'Kick Outs',
			'order' => 'low'
		),
		'kickouts_drawn_to_called' => [
			'labels' => 'Kick Outs Drawn to Called',
			'order' => 'high',
			'calculated' => true
		],
		'saves' => array(
			'label' => 'Saves',
			'order' => 'high'
		),
		'goals_allowed' => array(
			'label' => 'Goals Allowed',
			'order' => 'low'
		),
		'save_percent' => array(
			'label' => 'Save Percentage',
			'order' => 'high',
			'calculated' => true
		),
		'sprints_taken' => [
			'label' => 'Sprints Taken',
			'order' => 'high'
		],
		'sprints_won' => [
			'label' => 'Sprints Won',
			'order' => 'high'
		],
		'sprints_percent' => [
			'label' => 'Sprint Percentage',
			'order' => 'high',
			'calculated' => true
		],
		'five_meters_drawn' => [
			'label' => '5 Meters Drawn',
			'order' => 'high'
		],
		'five_meters_taken' => [
			'label' => '5 Meters Taken',
			'order' => 'high'
		],
		'five_meters_made' => [
			'label' => '5 Meters Made',
			'order' => 'high'
		],
		'five_meters_percent' => [
			'label' => '5 Meters Percentage',
			'order' => 'high',
			'calculated' => true
		],
		'five_meters_called' => [
			'label' => '5 Meters Called',
			'order' => 'low'
		],
		'five_meters_taken_on' => [
			'label' => '5 Meters Taken On',
			'order' => 'high'
		],
		'five_meters_blocked' => [
			'label' => '5 Meters Blocked',
			'order' => 'high'
		],
		'five_meters_allowed' => [
			'label' => '5 Meters Allowed',
			'order' => 'low'
		],
		'five_meters_missed' => [
			'label' => '5 Meters That Missed',
			'order' => 'high',
			'calculated' => true
		],
		'five_meters_save_percent' => [
			'label' => '5 Meters Save Percentage',
			'order' => 'high',
			'calculated' => true
		],
		'shoot_out_taken' => [
			'label' => 'Shoot Out Taken',
			'order' => 'high'
		],
		'shoot_out_made' => [
			'label' => 'Shoot Out Made',
			'order' => 'high'
		],
		'shoot_out_percent' => [
			'label' => 'Shoot Out Percentage',
			'order' => 'high',
			'calculated' => true
		],
		'shoot_out_taken_on' => [
			'label' => 'Shoot Out Taken On',
			'order' => 'low'
		],
		'shoot_out_blocked' => [
			'label' => 'Shoot Out Blocked',
			'order' => 'high'
		],
		'shoot_out_allowed' => [
			'label' => 'Shoot Out Allowed',
			'order' => 'low'
		],
		'shoot_out_missed' => [
			'label' => 'Shoot Out That Missed',
			'order' => 'high',
			'calculated' => true
		],
		'shoot_out_save_percent' => [
			'label' => 'Shoot Out Save Percentage',
			'order' => 'high',
			'calculated' => true
		]
	);

	public static $goalie_only = [
		'saves',
		'goals_allowed',
		'save_percent',
		'five_meters_taken_on',
		'five_meters_blocked',
		'five_meters_allowed',
		'five_meters_missed',
		'five_meters_save_percent',
		'shoot_out_taken_on',
		'shoot_out_blocked',
		'shoot_out_allowed',
		'shoot_out_missed',
		'shoot_out_save_percent'
	];

	public $averages;

	protected $stats = array();


    /**
     *    STATIC CONTROLLER FUNCTIONS
     * @param $player_id
     * @param $season_id
     * @param Register $register
     * @return bool|Stats
     */
	public static function getPlayerForSeason($player_id, $season_id, Register $register){
	    $dbh = $register->dbh;

		$stat_fields = [];
		foreach(self::$fields as $k=>$v){
			if(array_key_exists('calculated', $v) && $v['calculated'] === true)
				continue;

			$stat_fields[] = "SUM(".$k.") AS ".$k;
		}
		$sql = "SELECT ".implode(', ', $stat_fields)." FROM stats WHERE player_id=".intval($player_id)." AND site_id=".intval($register->site->id)." AND season_id=".intval($season_id)." GROUP BY season_id";
		$stats = $dbh->query($sql)->fetch(PDO::FETCH_ASSOC);

		$averages = self::getAverageForSeason($season_id, $register);
		
		if($stats === false)
			return false;
		else
			return new Stats($stats, $averages);
	}

	public static function getPlayerForCareer($player_id, Register $register){
	    $dbh = $register->dbh;
	    $site = $register->site;

		$stat_fields = [];
		foreach(self::$fields as $k=>$v){
			if(array_key_exists('calculated', $v))
				continue;
			$stat_fields[] = "SUM(".$k.") AS ".$k;
		}
		$sql = "SELECT ".implode(', ', $stat_fields)." FROM stats WHERE player_id=".intval($player_id)." AND site_id = ".intval($site->id);
		$stats = $dbh->query($sql)->fetch(PDO::FETCH_ASSOC);

		$averages = self::getAverageForCareer($dbh);

		if($stats === false)
			return false;
		else
			return new Stats($stats, $averages);
	}

	public static function getPlayerForGame($player_id, $game_id, Register $register) {
	    $dbh = $register->dbh;
	    $site = $register->site;

		$stat_fields = [];
		foreach(self::$fields as $k=>$v){
			if(array_key_exists('calculated', $v))
				continue;
			$stat_fields[] = $k;
		}
		$sql = "SELECT ".implode(', ', $stat_fields)." FROM stats WHERE player_id=".intval($player_id)." AND site_id = ".intval($site->id)." AND game_id=".intval($game_id);
		$stats = $dbh->query($sql)->fetch(PDO::FETCH_ASSOC);

		$averages = self::getAverageForGame($game_id, $register);

		if($stats === false)
			return false;
		else
			return new Stats($stats, $averages);
	}

	public static function getAllPlayersForGame($game_id, Register $register){
	    $dbh = $register->dbh;
	    $site = $register->site;

		$stat_fields = [];
		foreach(self::$fields as $k=>$v){
			if(array_key_exists('calculated', $v))
				continue;
			$stat_fields[] = $k;
		}
		$sql = "SELECT player_id, ".implode(', ', $stat_fields)." FROM stats WHERE site_id = ".intval($site->id)." AND game_id=".intval($game_id);
		$stmt = $dbh->query($sql);

		$return = [];
		while($r = $stmt->fetch(PDO::FETCH_ASSOC)){
			$temp = ['player' => new Player($r['player_id'], $register), 'stats'=>[]];
			unset($r['player_id']);
			$temp['stats'] = new Stats($r);
			$return[] = $temp;
		}

		return $return;
	}


	# AVERAGES

	public static function getAverageForSeason($season_id, Register $register){
	    $dbh = $register->dbh;
	    $site = $register->site;

		$avg_fields = [];
		foreach(self::$fields as $k=>$v){
			if(array_key_exists('calculated', $v))
				continue;
			$avg_fields[] = "ROUND(AVG(".$k."), 2) AS ".$k;
		}
		$sql = "SELECT ".implode(', ', $avg_fields)." FROM stats WHERE site_id = ".intval($site->id)." AND season_id=".intval($season_id)." GROUP BY season_id";
		$stats = $dbh->query($sql)->fetch(PDO::FETCH_ASSOC);
		if($stats !== false)
			return new Stats($stats);
		else
			return false;
	}

	public static function getAverageForCareer(Register $register){
	    $dbh = $register->dbh;
	    $site = $register->site;

		$avg_fields = [];
		foreach(self::$fields as $k=>$v){
			if(array_key_exists('calculated', $v))
				continue;
			$avg_fields[] = "ROUND(AVG(".$k."), 2) AS ".$k;
		}
		$sql = "SELECT ".implode(', ', $avg_fields)." FROM stats WHERE site_id = ".intval($site->id)." GROUP BY player_id";
		$stats = $dbh->query($sql)->fetch(PDO::FETCH_ASSOC);
		if($stats !== false)
			return new Stats($stats);
		else
			return false;
	}

	public static function getAverageForGame($game_id, Register $register){
	    $dbh = $register->dbh;
	    $site = $register->site;

		$avg_fields = [];
		foreach(self::$fields as $k=>$v){
			if(array_key_exists('calculated', $v))
				continue;
			$avg_fields[] = "ROUND(AVG(".$k."), 2) AS ".$k;
		}
		$sql = "SELECT ".implode(', ', $avg_fields)." FROM stats WHERE site_id = ".intval($site->id)." AND game_id=".intval($game_id)." GROUP BY game_id";
		$stats = $dbh->query($sql)->fetch(PDO::FETCH_ASSOC);
		if($stats !== false)
			return new Stats($stats);
		else
			return false;
	}

	
	# TOTALS
	public static function getTotalsForSeason($season_id, Register $register){
        $dbh = $register->dbh;
        $site = $register->site;

        $stat_fields = [];
		foreach(self::$fields as $k=>$v){
			if(array_key_exists('calculated', $v))
				continue;
			$stat_fields[] = "SUM(".$k.") AS ".$k;
		}
		$sql = "SELECT ".implode(', ', $stat_fields)." FROM stats WHERE site_id = ".intval($site->id)." AND season_id=".intval($season_id)." GROUP BY season_id";
		$stats = $dbh->query($sql)->fetch(PDO::FETCH_ASSOC);
		
		if($stats === false)
			return false;
		else
			return new Stats($stats);
	}





	private function __construct(Array $stats, Stats $averages = null){
		$this->stats = $stats;

		$this->stats['steals_to_turn_overs'] = $this->stats['steals'] - $this->stats['turn_overs'];
		$this->stats['kickouts_drawn_to_called'] = $this->stats['kickouts_drawn'] - $this->stats['kickouts'];
		$this->stats['shooting_percent'] = $this->stats['shots']==0 ? null : number_format(($this->stats['goals'] / $this->stats['shots']) * 100, 1);

		if($this->stats['saves'] != 0 || $this->stats['goals_allowed'] != 0){
			$this->stats['save_percent'] = number_format(($this->stats['saves'] / ($this->stats['saves'] + $this->stats['goals_allowed'])) * 100, 1);
		} else {
			$this->stats['save_percent'] = null;
		}

		if($this->stats['sprints_taken'] != 0){
			$this->stats['sprints_percent'] = number_format( ($this->stats['sprints_won'] / $this->stats['sprints_taken']) * 100, 1);
		} else {
			$this->stats['sprints_percent'] = null;
		}

		$this->stats['five_meters_percent'] = $this->stats['five_meters_taken']==0 ? null : number_format(($this->stats['five_meters_made'] / $this->stats['five_meters_taken']) * 100, 1);

		// this counts shots that were missed in the save percentage
		// an argument could be made that they should be removed from the equation completely for a better save picture
		// but since the charts show it as a save it's done this way
		// plus the goalie probably could have stopped the misses if the wanted to
		if($this->stats['five_meters_taken_on'] != 0){
			$missed = $this->stats['five_meters_taken_on'] - $this->stats['five_meters_blocked'] - $this->stats['five_meters_allowed'];
			$total_not_missed = $missed + $this->stats['five_meters_blocked'];
			$this->stats['five_meters_missed'] = $missed;
			$this->stats['five_meters_save_percent'] = number_format( ($total_not_missed / $this->stats['five_meters_taken_on']) * 100, 1);
		} else {
			$this->stats['five_meters_missed'] = null;
			$this->stats['five_meters_save_percent'] = null;
		}

		$this->stats['shoot_out_percent'] = $this->stats['shoot_out_taken']==0 ? null : number_format(($this->stats['shoot_out_made'] / $this->stats['shoot_out_taken']) * 100, 1);

		// see note about five meters above
		if($this->stats['shoot_out_taken_on'] != 0){
			$missed = $this->stats['shoot_out_taken_on'] - $this->stats['shoot_out_blocked'] - $this->stats['shoot_out_allowed'];
			$total_not_missed = $missed + $this->stats['shoot_out_blocked'];
			$this->stats['shoot_out_missed'] = $missed;
			$this->stats['shoot_out_save_percent'] = number_format( ($total_not_missed / $this->stats['shoot_out_taken_on']) * 100, 1);
		} else {
			$this->stats['shoot_out_missed'] = null;
			$this->stats['shoot_out_save_percent'] = null;
		}


		if($averages != null)
			$this->averages = $averages;
	}

	/*
	 *	Attempts to calculate the players best stats as compared to the average of the team
	 *	does this by calculating the difference between their stat and the average for the rest of the team
	 *	stats that should be low are made negative to allow for sorting
	*/
	public function getBestStats($limit = 2, $position){
		$best = array();
		$difs = array();
		foreach(self::$fields as $k=>$v){
			// if we don't check all field players end up with goals allowed
			if($position != 'GOALIE' && in_array($k, self::$goalie_only))
				continue;

			if($v['order'] == 'high')
				$difs[$k] = $this->stats[$k] - $this->averages->$k;
			else
				$difs[$k] = -($this->stats[$k] - $this->averages->$k);
		}
		arsort($difs);

		foreach(array_slice($difs, 0, $limit, true) as $k=>$v){
			$best[self::$fields[$k]['label']] = $this->stats[$k];
		}
		
		return $best;
	}


	public function __get($k){
		if($k == 'stats'){
			return $this->stats;
		} elseif(array_key_exists($k, $this->stats)){
			return (int) $this->stats[$k];
		} else {
			throw new exception('Could not find '.$k.' in Stats class');
		}		
	}	


	/*
	 *	Iterator Functions
	 *	extending the iterator interface to control the order of fields in our loops
	*/
	private $position = 0;
	public function current(){
		return $this->stats[ $this->getIteratorKey($this->position) ];
	}
	public function key(){
		return $this->getIteratorKey($this->position);
	}
	public function next(){
		++$this->position;
	}
	public function rewind(){
		$this->position = 0;
	}
	public function valid(){
		return $this->getIteratorKey($this->position) !== false;
	}

	private function getIteratorKey($i){
		$keys = array_keys(self::$fields);
		if(array_key_exists($i, $keys))
			return $keys[$i];
		else
			return false;
	}


}

?>