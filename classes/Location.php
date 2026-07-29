<?php /** @noinspection SqlResolve */

class Location {

	public ?int $location_id = null;
	public string $title;
	public string $street;
	public string $city;
	public string $state;
	public string $zipcode;
	public ?string $notes = null;
	public string $full_address;

	private ?Register $register = null;
	private ?PDODB $dbh = null;
	public ?string $google_api_key = null;

    public static function getOptionsForSelect(Register $register): array
    {
        $dbh = $register->dbh;
        $sql = "SELECT id, title FROM locations WHERE site_id = ".intval($register->site->id)." ORDER BY title";
        $stmt = $dbh->query($sql);
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

	public function __construct(?int $id = null, Register $register)
	{
	    $this->register = $register;
		$this->dbh = $register->dbh;

		if($id !== null){
			$stmt = $this->dbh->query("SELECT * FROM locations WHERE id=".intval($id)." AND site_id = ".intval($register->site->id));
			$stmt->setFetchMode(PDO::FETCH_INTO, $this);
			$stmt->fetch();
		}

		$this->full_address = $this->street.' '.$this->city.', '.$this->state.' '.$this->zipcode;
	}

	public function __sleep(): array{
		$arr = get_object_vars($this);
		unset( $arr['dbh'], $arr['google_api_key'] );

		$arr['static_map'] = $this->googleStaticMap();
		$arr['map_link'] = $this->googleMapLink();
		$arr['directions_link'] = $this->googleDirectionsLink();

		return $arr;
	}

	public function googleStaticMap(int $width=200, int $height=200, ?int $zoom = null): string{

		$url = 'http://maps.googleapis.com/maps/api/staticmap?';
		$url .= 'size='.$width.'x'.$height;
		$url .= '&amp;markers='.urlencode($this->full_address);
		if($zoom !== null)
			$url .= '&amp;zoom='.$zoom;
		$url .= '&amp;sensor=false';

		return $url;
	}

	public function googleMapLink(): string{
		return 'http://maps.google.com/?q='.urlencode($this->full_address);
	}

	public function googleDirectionsLink(): string{
		return 'http://maps.google.com/?daddr='.urlencode($this->full_address);
	}

}