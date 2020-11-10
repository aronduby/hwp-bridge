<?php /** @noinspection SqlResolve */

class Player{

	use Outputable;

	public $id;
	public $first_name;
	public $last_name;
	public $pronouns;
	public $name_key;
	public $last_update;
		
	public $name;
	public $seasons;
	public $number; // copy it over from the most recent season
	public $title; // copy it over from the most recent season

	public $alex = false;

	private $register;
	private $dbh;
	private $site;

	// Static Controller Function
	public static function createFromNameKey($key, Register $register){
		$sql = "SELECT id FROM players WHERE name_key=".$register->dbh->quote($key)." AND site_id = ".intval($register->site->id);
		$player_id = $register->dbh->query($sql)->fetch(PDO::FETCH_COLUMN);
		if($player_id === false){
			throw new Exception('Could not find a player with that name.');
		} else {
			return new Player($player_id, $register);
		}
	}


	public function __construct($player_id = null, Register $register){
	    $this->register = $register;
		$this->dbh = $register->dbh;
		$this->site = $register->site;

		if(!isset($this->id) && $player_id != null){
			$sql = "SELECT * FROM players WHERE id=".intval($player_id)." AND site_id = ".intval($this->site->id);
			$stmt = $this->dbh->query($sql);
			$stmt->setFetchMode(PDO::FETCH_INTO, $this);
			$stmt->fetch();
		}
		
		$this->name = $this->first_name .' '. $this->last_name;
		$this->last_update = DateTime::createFromFormat(MYSQL_DATETIME_FORMAT, $this->last_update);

		$copy_from_season = $this->getValuesFromSeason($register->season->id);
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
				AND ptp.site_id = ".intval($this->site->id)."
			ORDER BY 
				RAND() 
			LIMIT 1
		");
		$photo_id = $stmt->fetch(PDO::FETCH_COLUMN);
		if($photo_id === false){
			return new Photo(0, $this->register);
		} else {
			return new Photo($photo_id, $this->register);
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
					AND site_id = ".intval($this->site->id)."
				ORDER BY 
					season_id DESC";
			$stmt = $this->dbh->query($sql);
			
			$this->seasons = [];
			while($season_id = $stmt->fetch(PDO::FETCH_COLUMN)){
				$this->seasons[$season_id] = new PlayerSeason($this, $season_id, $this->register);
			}
		}

		return $this->seasons;
	}

	private function addSiteAndSeason($seasonId = null) {
	    $parts = ['AND site_id = '.intVal($this->site->id)];
	    if ($seasonId) {
	        $parts[] = 'AND season_id = '.intval($seasonId);
        }

	    return implode(' ', $parts);
    }

	public function countPhotos($season_id = null){
		$sql = "SELECT COUNT(*) AS total FROM photo_player WHERE player_id=".$this->addSiteAndSeason($season_id);
		return $this->dbh->query($sql)->fetch(PDO::FETCH_COLUMN);
	}

	public function countArticles($season_id = null){
		$sql = "SELECT COUNT(*) AS total FROM article_player WHERE player_id=".$this->addSiteAndSeason($season_id);
		return $this->dbh->query($sql)->fetch(PDO::FETCH_COLUMN);
	}

	public function countBadges($season_id = null){
		$sql = "SELECT COUNT(*) AS total FROM badge_player WHERE player_id=".$this->addSiteAndSeason($season_id);
		return $this->dbh->query($sql)->fetch(PDO::FETCH_COLUMN);
	}

	public function getCareer($full = false){
		$career = new PlayerCareer($this);

		if($full === true){
			foreach($this->getActiveSeasons() as $s){
				$career->addSeason($s);
				$career->setStats(Stats::getPlayerForCareer($this->id, $this->register));
			}
		}

		return $career;
	}

	private function getValuesFromSeason($seasonId){
		$sql = "SELECT 
            title, number 
        FROM 
            player_season 
        WHERE 
              player_id=".intval($this->id)." 
              AND site_id = ".intval($this->site->id)."
              AND season_id = ".intval($seasonId)."
        ORDER BY 
            season_id DESC 
        LIMIT 1";
		return $this->dbh->query($sql)->fetch(PDO::FETCH_ASSOC);
	}

}

?>