<?php

class PlayerCareer {

	use Outputable;

	public $player;
	public $number;
	public $position;
	public $badges;
	public $stats;
	public $photos;
	public $articles;

	public $seasons = [];

	public function __construct(Player $player){
		$this->player = $player;
		$this->number = $this->player->number;

		$this->badges = [];
		$this->photos = new AppendIterator();
		$this->articles = new AppendIterator();
	}

	public function addSeason(PlayerSeason $season){
		$this->position = $season->position;

		$this->addBadges((array)$season->getBadges());
		$this->addPhotos((array)$season->getPhotos());
		$this->addArticles((array)$season->getArticles());
	}

	public function addBadges(array $badges){
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

	public function addPhotos(array $photos){
		$this->photos->append(new ArrayIterator($photos));
	}

	public function addArticles(array $articles){
		$this->articles->append(new ArrayIterator($articles));
	}

	public function setStats(Stats $stats){
		$this->stats = $stats;
	}

}

?>