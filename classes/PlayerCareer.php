<?php

class PlayerCareer {

	use Outputable;

	public Player $player;
	public int|null $number = null;
	public ?string $position = null;
	public array $badges = [];
	public ?Stats $stats = null;
	public AppendIterator $photos;
	public AppendIterator $articles;

	public array $seasons = [];

	public function __construct(Player $player)
	{
		$this->player = $player;
		$this->number = $this->player->number ?? null;

		$this->badges = [];
		$this->photos = new AppendIterator();
		$this->articles = new AppendIterator();
	}

	public function addSeason(PlayerSeason $season): void{
		$this->position = $season->position;

		$this->addBadges((array)$season->getBadges());
		$this->addPhotos((array)$season->getPhotos());
		$this->addArticles((array)$season->getArticles());
	}

	public function addBadges(array $badges): void{
		foreach($badges as $badge){
			if(isset($this->badges[$badge->badge_id])){
				$this->badges[$badge->badge_id]['count']++;
			} else {
				$this->badges[$badge->badge_id] = array(
					'badge' => $badge,
					'count' => 1
				);
			}
		}
	}

	public function addPhotos(array $photos): void{
		$this->photos->append(new ArrayIterator($photos));
	}

	public function addArticles(array $articles): void{
		$this->articles->append(new ArrayIterator($articles));
	}

	public function setStats(Stats $stats): void{
		$this->stats = $stats;
	}

}