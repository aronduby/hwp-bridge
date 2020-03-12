<?php /** @noinspection SqlResolve */

class PhotoAlbum {
	
	use Outputable;

	public $album_id;
	public $season_id;
	public $title;
	public $modified;
	public $cover_photo_id;

	protected $photos;

	private $register;
	private $dbh;
	private $site;

    public static function getOptionsForSelect(Register $register)
    {
        return $register->dbh->query("SELECT id, title FROM albums WHERE site_id=".intval($register->site->id)." AND season_id=".intval($register->season->id)." ORDER BY title")
            ->fetchAll(PDO::FETCH_KEY_PAIR);
    }

	public function __construct($album_id = null, Register $register){
	    $this->register = $register;
		$this->dbh = $register->dbh;
		$this->site = $register->site;

		if($album_id !== null){
			$stmt = $this->dbh->query("SELECT * FROM albums WHERE id=".intval($album_id)." AND site_id = ".intval($this->site->id));
			$stmt->setFetchMode(PDO::FETCH_INTO, $this);
			if(!$stmt->fetch()){
				throw new Exception('Album Not Found');
			}	
		}

		$this->modified = new DateTime($this->modified);
	}

	public function getCoverPhoto(){
		return new Photo($this->cover_photo_id, $this->register);
	}

	public function getPhotos(){
		if(!isset($this->photos)){
			$stmt = $this->dbh->query('
				SELECT
					photo_id
				FROM 
					album_photo pta
					JOIN photos p ON(pta.photo_id = p.id)
				WHERE
					pta.album_id = '.$this->dbh->quote($this->album_id).'
					AND pta.site_id = '.intval($this->site->id).'
			');
			$this->photos = [];
			foreach($stmt->fetchAll(PDO::FETCH_COLUMN) as $photo_id){
				$this->photos[] = new Photo($photo_id, $this->register);
			}
		}
		return $this->photos;
	}

	public function getRandomPhotos($limit = 5){
		$stmt = $this->dbh->query('
			SELECT
				photo_id
			FROM 
				album_photo pta
				JOIN photos p ON(pta.photo_id = p.id)
			WHERE
				pta.album_id = '.$this->dbh->quote($this->album_id).'
				AND pta.site_id = '.intval($this->site->id).'
			ORDER BY
				RAND()
			LIMIT '.$limit.'
		');
		$photos = [];
		foreach($stmt->fetchAll(PDO::FETCH_COLUMN) as $photo_id){
			$photos[] = new Photo($photo_id, $this->register);
		}

		return $photos;
	}

	public function getTopPhotos($limit = 5){
		$stmt = $this->dbh->query('
			SELECT
				photo_id
			FROM 
				album_photo pta
				JOIN photos p ON(pta.photo_id = p.id)
			WHERE
				pta.album_id = '.$this->dbh->quote($this->album_id).'
				AND pta.site_id = '.intval($this->site->id).'
			ORDER BY
				RAND()
			LIMIT '.$limit.'
		');
		$photos = [];
		foreach($stmt->fetchAll(PDO::FETCH_COLUMN) as $photo_id){
			$photos[] = new Photo($photo_id, $this->register);
		}

		return $photos;
	}


	public function getGames(){
		$stmt = $this->dbh->query("SELECT * FROM games WHERE album_id=".intval($this->album_id)." AND site_id = ".intval($this->site->id));
		$stmt->setFetchMode(PDO::FETCH_CLASS, 'Game', [null, $this->register]);
		return $stmt->fetchAll();
	}


}

?>