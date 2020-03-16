<?php /** @noinspection SqlResolve */

class Season {
	
	public $id;
	public $title;
	public $short_title;
	public $current;
	public $team_image_map;
	public $ranking;
	public $ranking_updated;
	public $ranking_tie;

	protected $stats;

	private $register;
	private $dbh;
	private $site;

	public static function getAllSeasons($order='ASC', Register $register){
	    $dbh = $register->dbh;
	    $site = $register->site;

		if($order !== 'ASC' && $order !== 'DESC')
			throw new Exception('Order must be ASC or DESC');

		$seasons = [];
		foreach($dbh->query("
			SELECT 
				id 
			FROM 
				seasons 
			WHERE
			    site_id = ".intval($site->id)."
			ORDER BY 
				id ".$order
		)->fetchAll(PDO::FETCH_COLUMN) as $sid){
			$seasons[] = new Season($sid, $register);
		}
		return $seasons;
	}

	public function __construct($id = false, Register $register){
	    $this->register = $register;
		$this->dbh = $register->dbh;
		$this->site = $register->site;

		if ($id === null) {
		    $this->current = false;
		    return $this;
        }

		// id is false, grab the current one
		if($id === false){
			$sql = "
				SELECT 
					id, title, short_title, current, ranking, ranking_updated, ranking_tie 
				FROM 
					seasons
				WHERE 
					current=1
                    AND site_id = ".intval($this->site->id);
		} else {
			$sql = "
				SELECT 
					id, title, short_title, current, ranking, ranking_updated, ranking_tie 
				FROM 
					seasons 
				WHERE 
					id=".$this->dbh->quote($id)."
					AND site_id = ".intval($this->site->id);
		}

		$stmt = $this->dbh->query($sql);
		$stmt->setFetchMode(PDO::FETCH_INTO, $this);
		if(!$stmt->fetch()){
			throw new Exception('No Season Found');
		}

		$this->current = (bool)$this->current;
	}

	public function getPlayersByTeam($team = 'V,JV,STAFF', $order_by = "sort IS NOT NULL DESC, sort, p.first_name"){
		$parts = explode(',', $team);
		if(
			count($parts) === 0 // no teams supplied
			|| count($parts) > 3 // more than the available teams supplied
			|| count( array_diff($parts, ['JV', 'V', 'STAFF']) ) > 0 // invalid teams supplied
		){
			throw new Exception('Team argument must be a combination of V, JV, and/or STAFF');
		}


		$team_where = [];
		foreach(explode(',', $team) as $t){
			$team_where[] = "FIND_IN_SET('".$t."', pts.team)"; 
		}

		$sql = "SELECT 
			p.id, pts.team
		FROM 
			player_season pts 
			LEFT JOIN players p ON(pts.player_id = p.id) 
		WHERE 
			pts.season_id=".$this->id."
			AND pts.site_id=".intval($this->site->id)."
			AND (".implode(' OR ', $team_where).")
		ORDER BY ".$order_by;
		
		$teams = [];
		foreach(explode(',',$team) as $t)
			$teams[$t] = [];
		
		$players = $this->dbh->query($sql)->fetchAll(PDO::FETCH_ASSOC);
		foreach($players as $r){
			foreach(explode(',', $r['team']) as $t)
				$teams[$t][] = new Player($r['id'], $this->register);
		}

		return $teams;
	}

	public function getPlayers() {
		$sql = "
			SELECT 
				p.*
			FROM 
				player_season pts 
				LEFT JOIN players p ON(pts.player_id = p.id) 
			WHERE 
				pts.season_id = :season_id 
			    AND pts.site_id = :site_id
			ORDER BY 
				p.first_name
		";
		$stmt = $this->dbh->prepare($sql);
		$stmt->bindValue(':season_id', $this->id);
		$stmt->bindValue(':site_id', $this->site->id);
		$stmt->setFetchMode(PDO::FETCH_CLASS, 'Player', [null, $this->register]);
		$stmt->execute();

		return $stmt->fetchAll();
	}

	public function getBadges(){
	    // not tenanted to site
		$stmt = $this->dbh->query("
			SELECT 
				b.* 
			FROM 
				badge_season bs 
				LEFT JOIN badges b ON(bs.badge_id = b.id) 
			WHERE 
				bs.season_id = ".$this->id." 
			ORDER BY 
				created_at DESC
		");
		$stmt->setFetchMode(PDO::FETCH_CLASS, 'Badge', [null, $this->register]);

		return $stmt->fetchAll();
	}

	public function getArticles(){
		$stmt = $this->dbh->query("
			SELECT 
				a.* 
			FROM 
				articles a 
			WHERE 
				season_id = ".$this->id." 
				AND site_id = ".intval($this->site->id)."
			ORDER BY 
				published DESC
		");
		$stmt->setFetchMode(PDO::FETCH_CLASS, 'Article', [null, $this->register]);

		return $stmt->fetchAll();
	}

	public function getRecord($team){
		$sql = "
			SELECT 
				CASE
					WHEN score_us > score_them THEN 'W'
					WHEN score_us < score_them THEN 'L'
					ELSE 'T'
				END AS result, 
				COUNT(*) AS total
			FROM 
				games 
			WHERE 
				season_id=".intval($this->id)."
				AND site_id=".intval($this->site->id)."
				AND team=".$this->dbh->quote($team)."
				AND score_us IS NOT NULL
			GROUP BY result 
			ORDER BY 
				result='W' DESC, 
				result='L' DESC
		";

		$stmt = $this->dbh->query($sql);
		$return = ['W'=>0, 'L'=>0, 'T'=>0];
		while($r = $stmt->fetch(PDO::FETCH_ASSOC))
			$return[$r['result']] = $r['total'];

		return $return;
	}

	public function getStats(){
		if(!isset($this->stats)){
			try{
				$this->stats = Stats::getTotalsForSeason($this->id, $this->register);
			} catch(Exception $e){
				$this->stats = false;
			}
		}

		return $this->stats;
	}

	public function getPhotoAlbums(){
		$stmt = $this->dbh->query("
			SELECT 
				* 
			FROM 
				albums
			 WHERE 
			 	season_id = ".$this->id." 
			 	AND site_id = ".intval($this->site->id)."
			ORDER BY 
				updated_at DESC
		");
		$stmt->setFetchMode(PDO::FETCH_CLASS, 'PhotoAlbum', [null, $this->register]);

		return $stmt->fetchAll();
	}

	public function getTopPhotos($limit = 10){
		$photos = [];
		$sql = "
			SELECT 
				id 
			FROM 
				photos 
			WHERE 
				season_id = ".$this->id." 
				AND site_id = ".intval($this->site->id)."
			ORDER BY 
				RAND()
			LIMIT 
				".intval($limit)."
		";
		foreach($this->dbh->query($sql)->fetchAll(PDO::FETCH_COLUMN) as $photo_id)
			$photos[] = new Photo($photo_id, $this->register);

		return $photos;	
	}

	public function getRandomPhotos($limit = 10){
		$photos = [];
		$sql = "
			SELECT 
				id 
			FROM 
				photos 
			WHERE 
				season_id = ".$this->id." 
				AND site_id = ".intval($this->site->id)."
			ORDER BY 
				RAND()
			LIMIT 
				".intval($limit)."
		";
		foreach($this->dbh->query($sql)->fetchAll(PDO::FETCH_COLUMN) as $photo_id)
			$photos[] = new Photo($photo_id, $this->register);

		return $photos;	
	}

	public function getPlayerSeasons() {
        $stmt = $this->dbh->prepare("
			SELECT 
				ps.* 
			FROM 
				player_season ps 
			WHERE 
				ps.site_id = :site_id
                AND ps.season_id = :season_id
		");
        $stmt->bindValue(':site_id', $this->site->id, PDO::PARAM_INT);
        $stmt->bindValue(':season_id', $this->id, PDO::PARAM_INT);
        $stmt->setFetchMode(PDO::FETCH_CLASS, 'PlayerSeason', [null, $this->id, $this->register]);

        $stmt->execute();
        return $stmt->fetchAll();
    }

}
