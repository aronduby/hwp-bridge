<?php

class PlayerSeason {

	use Outputable;
	
	public $player_id;
	public $season_id;
	public $title;
	public $team;
	public $position;
	public $number;
	public $shutterfly_tag;
	public $sort;
	
	public $season_title;
	public $season_short_title;
	public $player;


	protected $photos;
	protected $badges;
	protected $articles;
	protected $stats;

	private $dbh;

	public function __construct(Player $player, $season_id, PDO $dbh){
		$this->player = $player;
		$this->dbh = $dbh;

		$sql = "SELECT * FROM player_season WHERE player_id=".intval($this->player->id)." AND season_id=".intval($season_id);
		$stmt = $this->dbh->query($sql);
		$stmt->setFetchMode(PDO::FETCH_INTO, $this);
		$stmt->fetch();

		$s = $this->dbh->query("SELECT title, short_title FROM seasons WHERE id=".intval($this->season_id))->fetch(PDO::FETCH_OBJ);
		$this->season_title = $s->title;
		$this->season_short_title = $s->short_title;
	}

	public function getPhotos(){
		if(!isset($this->photos)){
			$sql = "
				SELECT 
					ptp.photo_id 
				FROM 
					photo_player ptp 
					LEFT JOIN photos p ON(ptp.player_id = p.id) 
				WHERE 
					ptp.player_id=".intval($this->player->id)." 
					AND ptp.season_id=".intval($this->season_id)." 
				ORDER BY 
					p.created_at DESC";
			$stmt = $this->dbh->query($sql);

			while($photo_id = $stmt->fetch(PDO::FETCH_COLUMN))
				$this->photos[] = !$this->player->alex ? new Photo($photo_id, $this->dbh) : new AlexPhoto($photo_id, $this->dbh);
			
		}

		return $this->photos;
	}

	public function getBadges(){
		if(!isset($this->badges)){
			$stmt = $this->dbh->query("
				SELECT 
					b.* 
				FROM 
					badge_player ptb 
					LEFT JOIN badges b ON(ptb.badge_id = b.id) 
				WHERE 
					ptb.player_id=".intval($this->player->id)." 
					AND ptb.season_id=".intval($this->season_id)." 
				ORDER BY 
					ptb.created_at DESC, 
					ptb.badge_id DESC
			");
			$stmt->setFetchMode(PDO::FETCH_CLASS, 'Badge', [null, $this->dbh]);

			$this->badges = $stmt->fetchAll();
		}

		return $this->badges;
	}

	public function getArticles(){
		if(!isset($this->articles)){
			$stmt = $this->dbh->query("
				SELECT 
					a.* 
				FROM 
					article_player pta 
					LEFT JOIN articles a ON(pta.article_id = a.id) 
				WHERE 
					pta.player_id=".intval($this->player->id)." 
					AND pta.season_id=".intval($this->season_id)." 
				ORDER BY 
					a.published DESC
				");
			$stmt->setFetchMode(PDO::FETCH_CLASS, 'Article', [null, $this->dbh]);

			$this->articles = $stmt->fetchAll();
		}

		return $this->articles;
	}

	public function getStats(){
		if(!isset($this->stats)){
			try{
				$this->stats = Stats::getPlayerForSeason($this->player->id, $this->season_id, $this->dbh);
			} catch(Exception $e){
				$this->stats = false;
			}
		}

		return $this->stats;
	}

}

?>