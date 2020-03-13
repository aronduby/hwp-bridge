<?php /** @noinspection SqlResolve */

class Badge {

	use Outputable;

	public $id;
	public $title;
	public $image;
	public $description;
	public $display_order;

	private $register;
	private $dbh;
	private $site;

	public static function getAll(Register $register){
	    $dbh = $register->dbh;

		$stmt = $dbh->query("SELECT id, title, image, description, display_order FROM badges ORDER BY display_order IS NULL, display_order, title");
		$stmt->setFetchMode(PDO::FETCH_CLASS, 'Badge', [null, $register]);
		return $stmt->fetchAll();
	}


	public function __construct($badge_id = null, Register $register){
	    $this->register = $register;
		$this->dbh = $register->dbh;
		$this->site = $register->site;

		if(!isset($this->id) && $badge_id != null){
			$stmt = $this->dbh->query("SELECT id, title, image, description, display_order FROM badges WHERE id=".intval($badge_id));
			$stmt->setFetchMode(PDO::FETCH_INTO, $this);
			if(!$stmt->fetch()){
				throw new Exception('Badge Not Found');
			}
		}
	}

	public function checkSeason($season_id){
		$stmt = $this->dbh->prepare("SELECT COUNT(*) FROM badge_season WHERE badge_id = :badge_id AND season_id = :season_id");
		$stmt->bindValue(':badge_id', $this->id, PDO::PARAM_INT);
		$stmt->bindValue(':season_id', $season_id, PDO::PARAM_INT);
		$stmt->execute();

		return (bool)$stmt->fetchColumn();
	}

	public function getPlayers($season_id){
		$stmt = $this->dbh->prepare("
			SELECT 
				p.* 
			FROM 
				badge_player ptb 
				LEFT JOIN players p ON(ptb.player_id = p.id) 
			WHERE 
				ptb.badge_id = :badge_id 
			    AND ptb.site_id = :site_id
				AND season_id = :season_id
		");
		$stmt->bindValue(':badge_id', $this->id, PDO::PARAM_INT);
		$stmt->bindValue(':site_id', $this->site->id, PDO::PARAM_INT);
		$stmt->bindValue(':season_id', $season_id, PDO::PARAM_INT);
		$stmt->setFetchMode(PDO::FETCH_CLASS, 'Player', [null, $this->register]);

		$stmt->execute();
		return $stmt->fetchAll();
	}
}

?>