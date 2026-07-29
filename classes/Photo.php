<?php

class Photo{

	use Outputable;

	public ?int $photo_id = null;
	public string $thumb;
	public string $photo;
	public int|null $width = null;
	public int|null $height = null;
	public bool $viewed = false;

	public array|null $players = null;	

	protected string $prepend = '';
	protected string $append = '.jpg';

	protected ?Register $register = null;
	protected ?PDODB $dbh = null;
	protected ?Site $site = null;

	public function __construct(int $photo_id, Register $register) {
        $this->register = $register;
        $this->dbh = $register->dbh;
        $this->site = $register->site;

        $this->photo_id = $photo_id;
        $this->photo = PHOTO_BASE_HREF . $this->prepend . (string)$this->photo_id . $this->append;
		$this->thumb = THUMB_BASE_HREF . $this->prepend . (string)$this->photo_id . $this->append;

		$this->players = $this->getPlayersInPhoto();

		// TODO might have to change id to something else
		$stmt = $this->dbh->query("SELECT width, height FROM photos WHERE id=".$this->dbh->quote((string)$this->photo_id)." AND site_id = ".intval($this->site->id));
		$stmt->setFetchMode(PDO::FETCH_INTO, $this);
		$stmt->fetch();
	}

	// magic toString function
	// this accounts for places where we were just giving and array with the actual value
	public function __toString(): string{
		return (string)$this->photo_id;
	}

	// what gets output here will be formatted by photo popup to display links to other tagged players
	public function getJSONTitle(Player $player = null): string{
		$json = ['main'=>null, 'also'=>[]];
		$players_temp = $this->players ?? [];

		if($player !== null){
			unset($players_temp[$player->id]);
			$json['main'] = array('name_key'=>$player->name_key, 'name'=>$player->name);			
		}
		
		if(count($players_temp)){
			$temp = array();
			foreach($players_temp as $id=>$player){
				$temp[] = array('name_key'=>$player->name_key, 'name'=>$player->name);
			}
			$json['also'] = $temp;
		}

		return json_encode($json);
	}

	private function getPlayersInPhoto(): array{
		if ($this->photo_id === null) return [];
		
		$sql = "SELECT player_id FROM photo_player WHERE photo_id=".$this->dbh->quote((string)$this->photo_id)." AND site_id = ".intval($this->site->id);
		$stmt = $this->dbh->query($sql);

		$players = [];
		while($player_id = $stmt->fetch(PDO::FETCH_COLUMN)){
			if($player_id !== false){
				$players[(int)$player_id] = new Player((int)$player_id, $this->register);
			}
		}

		return $players;
	}

}