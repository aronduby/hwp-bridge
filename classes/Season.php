<?php

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

	private $dbh;

	public static function getAllSeasons($order='ASC', PDO $dbh){
		if($order !== 'ASC' && $order !== 'DESC')
			throw new Exception('Order must be ASC or DESC');

		$seasons = [];
		foreach($dbh->query("
			SELECT 
				id 
			FROM 
				seasons 
			ORDER BY 
				id ".$order
		)->fetchAll(PDO::FETCH_COLUMN) as $sid){
			$seasons[] = new Season($sid, $dbh);
		}
		return $seasons;
	}

	public function __construct($id = false, PDO $dbh){
		$this->dbh = $dbh;

		// id is false, grab the current one
		if($id === false){
			$sql = "
				SELECT 
					id, title, short_title, current, ranking, ranking_updated, ranking_tie 
				FROM 
					seasons
				WHERE 
					current=1
			";
		} else {
			$sql = "
				SELECT 
					id, title, short_title, current, ranking, ranking_updated, ranking_tie 
				FROM 
					seasons 
				WHERE 
					id=".$this->dbh->quote($id);
		}

		$stmt = $dbh->query($sql);
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
			AND (".implode(' OR ', $team_where).")
		ORDER BY ".$order_by;
		
		$teams = [];
		foreach(explode(',',$team) as $t)
			$teams[$t] = [];
		
		$players = $this->dbh->query($sql)->fetchAll(PDO::FETCH_ASSOC);
		foreach($players as $r){
			foreach(explode(',', $r['team']) as $t)
				$teams[$t][] = new Player($r['id'], $this->dbh);
		}

		return $teams;
	}

	public function getPlayers(){
		$sql = "
			SELECT 
				p.* 
			FROM 
				player_season pts 
				LEFT JOIN players p ON(pts.player_id = p.id) 
			WHERE 
				pts.season_id = :season_id 
			ORDER BY 
				p.first_name
		";
		$stmt = $this->dbh->prepare($sql);
		$stmt->bindValue(':season_id', $this->id);
		$stmt->setFetchMode(PDO::FETCH_CLASS, 'Player', [null, $this->dbh]);
		$stmt->execute();

		return $stmt->fetchAll();
	}

	public function getBadges(){
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
		$stmt->setFetchMode(PDO::FETCH_CLASS, 'Badge', [null, $this->dbh]);

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
			ORDER BY 
				published DESC
		");
		$stmt->setFetchMode(PDO::FETCH_CLASS, 'Article', [null, $this->dbh]);

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
				$this->stats = Stats::getTotalsForSeason($this->id, $this->dbh);
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
			ORDER BY 
				updated_at DESC
		");
		$stmt->setFetchMode(PDO::FETCH_CLASS, 'PhotoAlbum', [null, $this->dbh]);

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
			ORDER BY 
				RAND()
			LIMIT 
				".intval($limit)."
		";
		foreach($this->dbh->query($sql)->fetchAll(PDO::FETCH_COLUMN) as $photo_id)
			$photos[] = new Photo($photo_id, $this->dbh);

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
			ORDER BY 
				RAND()
			LIMIT 
				".intval($limit)."
		";
		foreach($this->dbh->query($sql)->fetchAll(PDO::FETCH_COLUMN) as $photo_id)
			$photos[] = new Photo($photo_id, $this->dbh);

		return $photos;	
	}

}

?>