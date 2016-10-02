<?php

class Player{

	use Outputable;

	public $id;
	public $first_name;
	public $last_name;
	public $name_key;
	public $last_update;
		
	public $name;
	public $seasons;
	public $number; // copy it over from the most recent season
	public $title; // copy it over from the most recent season

	public $alex = false;

	private $dbh;

	// Static Controller Function
	public static function createFromNameKey($key, PDO $dbh){
		$sql = "SELECT id FROM players WHERE name_key=".$dbh->quote($key);
		$player_id = $dbh->query($sql)->fetch(PDO::FETCH_COLUMN);
		if($player_id === false){
			throw new Exception('Could not find a player with that name.');
		} else {
			return new Player($player_id, $dbh);
		}
	}


	public function __construct($player_id = null, PDO $dbh){
		$this->dbh = $dbh;

		if(!isset($this->id) && $player_id != null){
			$sql = "SELECT * FROM players WHERE id=".intval($player_id);
			$stmt = $this->dbh->query($sql);
			$stmt->setFetchMode(PDO::FETCH_INTO, $this);
			$stmt->fetch();
		}
		
		$this->name = $this->first_name .' '. $this->last_name;
		$this->last_update = DateTime::createFromFormat(MYSQL_DATETIME_FORMAT, $this->last_update);

		$copy_from_season = $this->getValuesFromSeason();
		$this->number = $copy_from_season['number'];
		$this->title = $copy_from_season['title'];
	}

	public function getRandomPhoto(){
		$stmt = $this->dbh->query("
			SELECT 
				ptp.photo_id 
			FROM 
				photo_player ptp 
				LEFT JOIN photos p ON(ptp.photo_id = p.id) 
			WHERE 
				ptp.player_id=".intval($this->id)." 
			ORDER BY 
				RAND() 
			LIMIT 1
		");
		$photo_id = $stmt->fetch(PDO::FETCH_COLUMN);
		if($photo_id === false){
			return new Photo(0, $this->dbh);
		} else {
			return new Photo($photo_id, $this->dbh);
		}
	}

	public function getActiveSeasons(){
		if($this->seasons === null){
			$sql = "
				SELECT 
					season_id 
				FROM 
					player_season 
				WHERE 
					player_id=".intval($this->id)." 
				ORDER BY 
					season_id DESC";
			$stmt = $this->dbh->query($sql);
			
			$this->seasons = [];
			while($season_id = $stmt->fetch(PDO::FETCH_COLUMN)){
				$this->seasons[$season_id] = new PlayerSeason($this, $season_id, $this->dbh);
			}
		}

		return $this->seasons;
	}

	public function countPhotos($season_id = null){
		$sql = "SELECT COUNT(*) AS total FROM photo_player WHERE player_id=".intval($this->id).($season_id!=null ? " AND season_id=".intval($season_id) : '' );
		return $this->dbh->query($sql)->fetch(PDO::FETCH_COLUMN);
	}

	public function countArticles($season_id = null){
		$sql = "SELECT COUNT(*) AS total FROM article_player WHERE player_id=".intval($this->id).($season_id!=null ? " AND season_id=".intval($season_id) : '' );
		return $this->dbh->query($sql)->fetch(PDO::FETCH_COLUMN);
	}

	public function countBadges($season_id = null){
		$sql = "SELECT COUNT(*) AS total FROM badge_player WHERE player_id=".intval($this->id).($season_id!=null ? " AND season_id=".intval($season_id) : '' );
		return $this->dbh->query($sql)->fetch(PDO::FETCH_COLUMN);
	}


	public function getCareer($full = false){
		$career = new PlayerCareer($this);

		if($full === true){
			foreach($this->getActiveSeasons() as $s){
				$career->addSeason($s);
				$career->setStats(Stats::getPlayerForCareer($this->id, $this->dbh));
			}
		}

		return $career;
	}

	private function getValuesFromSeason(){
		$sql = "SELECT title, number FROM player_season WHERE player_id=".intval($this->id)." ORDER BY season_id DESC LIMIT 1";
		return $this->dbh->query($sql)->fetch(PDO::FETCH_ASSOC);
	}

}

?>