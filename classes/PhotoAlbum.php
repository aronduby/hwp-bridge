<?php

class PhotoAlbum {
	
	use Outputable;

	public $album_id;
	public $season_id;
	public $title;
	public $modified;
	public $cover_photo_id;

	protected $photos;

	private $dbh;

	public function __construct($album_id = null, $dbh){
		$this->dbh = $dbh;

		if($album_id !== null){
			$stmt = $this->dbh->query("SELECT * FROM albums WHERE id=".intval($album_id));
			$stmt->setFetchMode(PDO::FETCH_INTO, $this);
			if(!$stmt->fetch()){
				throw new Exception('Album Not Found');
			}	
		}

		$this->modified = new DateTime($this->modified);
	}

	public function getCoverPhoto(){
		return new Photo($this->cover_photo_id, $this->dbh);
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
			');
			$this->photos = [];
			foreach($stmt->fetchAll(PDO::FETCH_COLUMN) as $photo_id){
				$this->photos[] = new Photo($photo_id, $this->dbh);
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
			ORDER BY
				RAND()
			LIMIT '.$limit.'
		');
		$photos = [];
		foreach($stmt->fetchAll(PDO::FETCH_COLUMN) as $photo_id){
			$photos[] = new Photo($photo_id, $this->dbh);
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
			ORDER BY
				RAND()
			LIMIT '.$limit.'
		');
		$photos = [];
		foreach($stmt->fetchAll(PDO::FETCH_COLUMN) as $photo_id){
			$photos[] = new Photo($photo_id, $this->dbh);
		}

		return $photos;
	}


	public function getGames(){
		$stmt = $this->dbh->query("SELECT * FROM games WHERE album_id=".intval($this->album_id));
		$stmt->setFetchMode(PDO::FETCH_CLASS, 'Game', [null, $this->dbh]);
		return $stmt->fetchAll();
	}


}

?>