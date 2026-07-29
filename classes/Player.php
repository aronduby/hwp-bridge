<?php /** @noinspection SqlResolve */

use Traits\HasOtherNumbers;

class Player{

	use Outputable;
    use HasOtherNumbers;

	public ?int $id = null;
	public string $first_name;
	public string $last_name;
	public ?string $pronouns = null;
	public string $name_key;
	public ?string $last_update = null;
		
	public string $name;
	public ?array $seasons = null;
	public ?int $number = null; // copy it over from the most recent season
    public ?array $other_numbers = null; // copy it over from the most recent season
	public ?string $title = null; // copy it over from the most recent season

	public bool $alex = false;

	private ?Register $register = null;
	private ?PDODB $dbh = null;
	private ?Site $site = null;

	// Static Controller Function
	public static function createFromNameKey(string $key, Register $register): Player{
		$sql = "SELECT id FROM players WHERE name_key=".$register->dbh->quote($key)." AND site_id = ".intval($register->site->id);
		$player_id_result = $register->dbh->query($sql)->fetch(PDO::FETCH_COLUMN);
		if($player_id_result === false){
			throw new Exception('Could not find a player with that name.');
		} else {
			return new Player((int)$player_id_result, $register);
		}
	}


	public function __construct(?int $player_id = null, Register $register)
	{
	    $this->register = $register;
		$this->dbh = $register->dbh;
		$this->site = $register->site;

		if(!isset($this->id) && $player_id !== null){
			$sql = "SELECT * FROM players WHERE id=".intval($player_id)." AND site_id = ".intval($this->site->id);
			$stmt = $this->dbh->query($sql);
			$stmt->setFetchMode(PDO::FETCH_INTO, $this);
			$stmt->fetch();
		}
		
		$this->name = $this->first_name .' '. $this->last_name;
		$this->last_update = DateTime::createFromFormat(MYSQL_DATETIME_FORMAT, $this->last_update);

		$copy_from_season = $this->getValuesFromSeason($register->season->id);
		$this->number = $copy_from_season['number'];
        $this->other_numbers = json_decode($copy_from_season['other_numbers'], true);
		$this->title = $copy_from_season['title'];
	}

	public function getRandomPhoto(): Photo{
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
			return new Photo((int)$photo_id, $this->register);
		}
	}

	public function getActiveSeasons(): array{
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
				if ($season_id !== false) {
					$this->seasons[(int)$season_id] = new PlayerSeason($this, (int)$season_id, $this->register);
				}
			}
		}

		return array_values($this->seasons ?? []);
	}

	private function addSiteAndSeason(string|int|null $seasonId = null): string {
	    $parts = ['AND site_id = '.intval($this->site->id)];
	    if ($seasonId !== null) {
	        $parts[] = 'AND season_id = '.intval((string)$seasonId);
        }

	    return implode(' ', $parts);
    }

	public function countPhotos(string|int|null $season_id = null): int|false{
		$sql = "SELECT COUNT(*) AS total FROM photo_player WHERE player_id=".$this->addSiteAndSeason($season_id);
		return (int)$this->dbh->query($sql)->fetch(PDO::FETCH_COLUMN);
	}

	public function countArticles(string|int|null $season_id = null): int|false{
		$sql = "SELECT COUNT(*) AS total FROM article_player WHERE player_id=".$this->addSiteAndSeason($season_id);
		return (int)$this->dbh->query($sql)->fetch(PDO::FETCH_COLUMN);
	}

	public function countBadges(string|int|null $season_id = null): int|false{
		$sql = "SELECT COUNT(*) AS total FROM badge_player WHERE player_id=".$this->addSiteAndSeason($season_id);
		return (int)$this->dbh->query($sql)->fetch(PDO::FETCH_COLUMN);
	}

	public function getCareer(PlayerCareer|null $full = null): PlayerCareer{
		$career = new PlayerCareer($this);

		if($full === true){
			foreach($this->getActiveSeasons() as $s){
				$career->addSeason($s);
				$career->setStats(Stats::getPlayerForCareer($this->id, $this->register));
			}
		}

		return $career;
	}

	private function getValuesFromSeason(int $seasonId): array{
		$sql = "SELECT 
            title, number, other_numbers
        FROM 
            player_season 
        WHERE 
              player_id=".intval($this->id)." 
              AND site_id = ".intval($this->site->id)."
              AND season_id = ".intval($seasonId)."
        ORDER BY 
            season_id DESC 
        LIMIT 1";
		return $this->dbh->query($sql)->fetch(PDO::FETCH_ASSOC) ?? [];
	}

}