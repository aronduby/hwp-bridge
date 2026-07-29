<?php /** @noinspection SqlResolve */

class Game {

	use Outputable;

	public ?int $id = null;
	public int $season_id;
	public int $tournament_id;
	public string $title_append;
	public ?string $start = null;
	public ?string $end = null;
	public int $location_id;
	public string $team;
	public string $district;
	public string $opponent;
	public int|null $score_us = null;
	public int|null $score_them = null;
	public string $json_dump;
	public ?int $album_id = null;
	public string $title;
	public Location|null $location = null;
	public ?string $result = null;
	public bool $has_stats;
	public bool $has_live_scoring;
	public bool $has_recap;
	public bool $has_photo_album;
    public bool $is_posted;

	private ?Register $register = null;
	private ?PDODB $dbh = null;
	private ?Site $site = null;

	public function __construct(?int $id = null, Register $register)
    {
	    $this->register = $register;
		$this->dbh = $register->dbh;
		$this->site = $register->site;

		if($id !== null){
			$stmt = $this->dbh->query("SELECT * FROM games WHERE id = ".intval($id)." AND site_id = ".intval($this->site->id));
			$stmt->setFetchMode(PDO::FETCH_INTO, $this);
			$stmt->fetch();
		}

		if ($this->start !== null) {
			$this->start = new DateTime($this->start);
		}
		if ($this->end !== null) {
			$this->end = new DateTime($this->end);
		}

		$this->location = new Location($this->location_id, $this->register);

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

        $postedSql = "SELECT COUNT(*) FROM recent WHERE renderer = 'game' AND content = '".intval($this->id)."'";
        $this->is_posted = (bool)$this->dbh->query($postedSql)->fetch(PDO::FETCH_COLUMN);;

        // for the new live scoring, probably be replaced with stats later
		$this->has_recap = (bool)strlen($this->json_dump);
	}

	public function getPhotoAlbum(): PhotoAlbum|false{
		if(isset($this->album_id))
			return new PhotoAlbum((int)$this->album_id, $this->register);
		else
			return false;
	}
}
