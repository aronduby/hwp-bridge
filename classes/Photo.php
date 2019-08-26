<?php

class Photo{

	use Outputable;

	public $photo_id;
	public $thumb;
	public $photo;
	public $width;
	public $height;
	public $viewed;

	public $players;	

	protected $prepend = '';
	protected $append = '.jpg';
	protected $dbh;

	public function __construct($photo_id, PDO $dbh){
		$this->photo_id = $photo_id;
		$this->dbh = $dbh;
		
		$this->photo = PHOTO_BASE_HREF . $this->prepend . $this->photo_id . $this->append;
		$this->thumb = THUMB_BASE_HREF . $this->prepend . $this->photo_id . $this->append;

		$this->players = $this->getPlayersInPhoto();

		// TODO might have to change id to something else
		$stmt = $this->dbh->query("SELECT width, height FROM photos WHERE id=".$this->dbh->quote($this->photo_id));
		$stmt->setFetchMode(PDO::FETCH_INTO, $this);
		$stmt->fetch();
	}

	// magic toString function
	// this accounts for places where we were just giving and array with the actual value
	public function __toString(){
		return $this->photo_id;
	}

	// what gets output here will be formatted by photo popup to display links to other tagged players
	public function getJSONTitle(Player $player = null){
		$json = ['main'=>null, 'also'=>[]];
		$players_temp = $this->players;

		if($player != null){
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

	private function getPlayersInPhoto(){
		$sql = "SELECT player_id FROM photo_player WHERE photo_id=".$this->dbh->quote(($this->photo_id));
		$stmt = $this->dbh->query($sql);

		$players = [];
		while($player_id = $stmt->fetch(PDO::FETCH_COLUMN))
			$players[$player_id] = new Player($player_id, $this->dbh);

		return $players;
	}





}
?>