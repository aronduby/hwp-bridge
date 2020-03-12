<?php

class Game {

	use Outputable;

	public $id;
	public $season_id;
	public $tournament_id;
	public $title_append;
	public $bus_time;
	public $start;
	public $end;
	public $location_id;
	public $team;
	public $district;
	public $opponent;
	public $score_us;
	public $score_them;
	public $json_dump;
	public $album_id;
	public $title;
	public $location;
	public $result;
	public $has_stats;
	public $has_live_scoring;
	public $has_recap;
	public $has_photo_album;
	public $dump_version;

	private $register;
	private $dbh;
	private $site;

	public function __construct($id = null, Register $register){
	    $this->register = $register;
		$this->dbh = $register->dbh;
		$this->site = $register->site;

		if($id !== null){
			$stmt = $this->dbh->query("SELECT * FROM games WHERE id = ".intval($id)." AND site_id = ".intval($this->site->id));
			$stmt->setFetchMode(PDO::FETCH_INTO, $this);
			$stmt->fetch();
		}

		$this->start = new DateTime($this->start);
		$this->end = new DateTime($this->end);

		$this->location = new Location($this->location_id, $this->dbh);

		$this->title = (strlen($this->title_append)>0 ? $this->title_append : 'Game').' against '.$this->opponent;

		// make sure to account for ties
		if(isset($this->score_us) && isset($this->score_them)){
			if($this->score_us > $this->score_them)
				$this->result = 'W';
			elseif($this->score_us == $this->score_them)
				$this->result = 'T';
			else
				$this->result = 'L';
		}

		$this->has_stats = (bool)$this->dbh->query("SELECT COUNT(*) FROM stats WHERE game_id=".intval($this->id)." AND site_id = ".intval($this->site->id))->fetch(PDO::FETCH_COLUMN);
		$this->has_live_scoring = (bool)$this->dbh->query("SELECT COUNT(*) FROM game_update_dumps WHERE game_id=".intval($this->id)." AND site_id = ".intval($this->site->id))->fetch(PDO::FETCH_COLUMN);
		$this->has_photo_album = isset($this->album_id);

		// for the new live scoring, probably be replaced with stats later
		$this->has_recap = (bool)strlen($this->json_dump);

	}

	public function getPhotoAlbum(){
		if(isset($this->album_id))
			return new PhotoAlbum($this->album_id, $this->register);
		else
			return false;
	}
	

}