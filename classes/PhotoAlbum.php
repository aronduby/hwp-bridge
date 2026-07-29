<?php /** @noinspection SqlResolve */

class PhotoAlbum {
	
	use Outputable;

	public ?int $album_id = null;
	public int $season_id;
	public string $title;
	public ?string $modified = null;
	public ?int $cover_photo_id = null;

	protected array|null $photos = null;

	private ?Register $register = null;
	private ?PDODB $dbh = null;
	private ?Site $site = null;

    public static function getOptionsForSelect(Register $register): array
    {
        return $register->dbh->query("SELECT id, title FROM albums WHERE site_id=".intval($register->site->id)." AND season_id=".intval($register->season->id)." ORDER BY title")
            ->fetchAll(PDO::FETCH_KEY_PAIR);
    }

	public function __construct(?int $album_id = null, Register $register) {
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

		if ($this->modified !== null) {
		    $this->modified = new DateTime($this->modified);
		}
	}

	public function getCoverPhoto(): ?Photo{
		if($this->cover_photo_id === null) return null;
		return new Photo((int)$this->cover_photo_id, $this->register);
	}

	public function getPhotos(): array{
		if(!isset($this->photos)){
			$stmt = $this->dbh->query('
				SELECT
					photo_id
				FROM 
					album_photo pta
					JOIN photos p ON(pta.photo_id = p.id)
				WHERE
					pta.album_id = '.$this->dbh->quote((string)$this->album_id).'
					AND pta.site_id = '.intval($this->site->id).'
			');
			$this->photos = [];
			foreach($stmt->fetchAll(PDO::FETCH_COLUMN) as $photo_id){
				if ($photo_id !== false) {
					$this->photos[] = new Photo((int)$photo_id, $this->register);
				}
			}
		}
		return $this->photos ?? [];
	}

	public function getRandomPhotos(int $limit = 5): array{
		$stmt = $this->dbh->query('
			SELECT
				photo_id
			FROM 
				album_photo pta
				JOIN photos p ON(pta.photo_id = p.id)
			WHERE
				pta.album_id = '.$this->dbh->quote((string)$this->album_id).'
				AND pta.site_id = '.intval($this->site->id).'
			ORDER BY
				RAND()
			LIMIT '.$limit.'
		');
		$photos = [];
		foreach($stmt->fetchAll(PDO::FETCH_COLUMN) as $photo_id){
			if ($photo_id !== false) {
				$photos[] = new Photo((int)$photo_id, $this->register);
			}
		}

		return $photos;
	}

	public function getTopPhotos(int $limit = 5): array{
		$stmt = $this->dbh->query('
			SELECT
				photo_id
			FROM 
				album_photo pta
				JOIN photos p ON(pta.photo_id = p.id)
			WHERE
				pta.album_id = '.$this->dbh->quote((string)$this->album_id).'
				AND pta.site_id = '.intval($this->site->id).'
			ORDER BY
				RAND()
			LIMIT '.$limit.'
		');

		$photos = [];
		foreach($stmt->fetchAll(PDO::FETCH_COLUMN) as $photo_id){
			if ($photo_id !== false) {
				$photos[] = new Photo((int)$photo_id, $this->register);
			}
		}

		return $photos;
	}


	public function getGames(): array{
		$stmt = $this->dbh->query("SELECT * FROM games WHERE album_id=".intval($this->album_id)." AND site_id = ".intval($this->site->id));
		$stmt->setFetchMode(PDO::FETCH_CLASS, 'Game', [null, $this->register]);
		return $stmt->fetchAll();
	}


}