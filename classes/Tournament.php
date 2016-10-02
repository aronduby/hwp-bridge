<?php

class Tournament {

	use Outputable;

	public $id;
	public $season_id;
	public $title;
	public $location_id;
	public $team;
	public $start;
	public $end;
	public $result;
	public $note;

	public $location;
	public $games;

	private $dbh;

	public function __construct($id = null, PDO $dbh){
		$this->dbh = $dbh;

		if($id !== null){
			$stmt = $this->dbh->query("SELECT * FROM tournaments WHERE id=".intval($id));
			$stmt->setFetchMode(PDO::FETCH_INTO, $this);
			$stmt->fetch();
		}

		$this->start = new DateTime($this->start);
		$this->end = new DateTime($this->end);

		if(strlen($this->title) == 0)
			$this->title = 'Tournament';

		$this->location = new Location($this->location_id, $this->dbh);
	}

	public function getGames(){
		if(!isset($this->games)){
			$sql = "SELECT * FROM games WHERE tournament_id=".intval($this->id);
			$stmt = $this->dbh->query($sql);
			$stmt->setFetchMode(PDO::FETCH_CLASS, 'Game', [null, $this->dbh]);
			
			$this->games = $stmt->fetchAll();
		}

		return $this->games;
	}

	public function hasStats(){
		$sql = "SELECT 
			COUNT(*) 
		FROM 
			stats 
		WHERE 
			game_id IN (
				SELECT id FROM games WHERE tournament_id=".intval($this->id)."
			)";
		// print_p($sql);
		return (bool)$this->dbh->query($sql)->fetch(PDO::FETCH_COLUMN);
	}

}

?>