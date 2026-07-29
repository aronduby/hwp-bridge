<?php

use Traits\HasOtherNumbers;

class PlayerSeason {

	use Outputable;
    use HasOtherNumbers;

	public ?int $id = null;
	public int $player_id;
	public int $season_id;
	public string $title;
	public string $team;
	public ?string $position = null;
	public ?int $number = null;
    public ?array $other_numbers = null;
	public ?string $media_tag = null;
	public ?int $sort = null;
	
	public ?string $season_title = null;
	public ?string $season_short_title = null;
	public Player|null $player = null;


	protected array|null $photos = null;
	protected array|null $badges = null;
	protected array|null $articles = null;
	protected ?Stats $stats = null;

	private ?Register $register = null;
	private ?PDODB $dbh = null;
	private ?Site $site = null;

	public function __construct(?Player $player = null, ?int $season_id = null, Register $register)
	{
		$this->player = $player;

		$this->register = $register;
		$this->dbh = $register->dbh;
		$this->site = $register->site;

		if ($player && $season_id) {
            $sql = "SELECT * FROM player_season WHERE player_id=".intval($this->player->id)." AND season_id=".intval($season_id)." AND site_id = ".intval($this->site->id);
            $stmt = $this->dbh->query($sql);
            $stmt->setFetchMode(PDO::FETCH_INTO, $this);
            $stmt->fetch();
        }

		if ($season_id) {
            $s = $this->dbh->query("SELECT title, short_title FROM seasons WHERE id=".intval($season_id)." AND site_id = ".intval($this->site->id))->fetch(PDO::FETCH_OBJ);
            if ($s) {
                $this->season_title = $s->title;
                $this->season_short_title = $s->short_title;
            }
        }

        if ($this->other_numbers !== null) {
            $this->other_numbers = json_decode($this->other_numbers, true);
        }
	}

	public function getPhotos(): array{
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
					AND ptp.site_id=".intval($this->site->id)."
				ORDER BY 
					p.created_at DESC";
			$stmt = $this->dbh->query($sql);

			while($photo_id = $stmt->fetch(PDO::FETCH_COLUMN)){
				if ($photo_id !== false) {
					if ($this->player && $this->player->alex) {
						$this->photos[] = new AlexPhoto((int)$photo_id, $this->register);
					} else {
						$this->photos[] = new Photo((int)$photo_id, $this->register);
					}
				}
			}
			
		}

		return $this->photos ?? [];
	}

	public function getBadges(): array{
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
					AND ptb.site_id=".intval($this->site->id)."
				ORDER BY 
					ptb.created_at DESC, 
					ptb.badge_id DESC
			");
			$stmt->setFetchMode(PDO::FETCH_CLASS, 'Badge', [null, $this->register]);

			$this->badges = $stmt->fetchAll();
		}

		return $this->badges ?? [];
	}

	public function getArticles(): array{
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
					AND pta.site_id=".intval($this->site->id)."
				ORDER BY 
					a.published DESC
				");
			$stmt->setFetchMode(PDO::FETCH_CLASS, 'Article', [null, $this->register]);

			$this->articles = $stmt->fetchAll();
		}

		return $this->articles ?? [];
	}

	public function getStats(): ?Stats{
		if(!isset($this->stats)){
			try{
				$this->stats = Stats::getPlayerForSeason($this->player->id, $this->season_id, $this->register);
			} catch(Exception $e){
				$this->stats = null;
			}
		}

		return $this->stats;
	}

}