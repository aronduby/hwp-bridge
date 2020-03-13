<?php /** @noinspection SqlResolve */

class Location {

	public $location_id;
	public $title;
	public $street;
	public $city;
	public $state;
	public $zipcode;
	public $notes;
	public $full_address;

	private $register;
	private $dbh;
	private $google_api_key;

    public static function getOptionsForSelect(Register $register)
    {
        $dbh = $register->dbh;
        $sql = "SELECT id, title FROM locations WHERE site_id = ".intval($register->site->id)." ORDER BY title";
        $stmt = $dbh->query($sql);
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

	public function __construct($id = null, Register $register){
	    $this->register = $register;
		$this->dbh = $register->dbh;

		if($id !== null){
			$stmt = $this->dbh->query("SELECT * FROM locations WHERE id=".intval($id)." AND site_id = ".intval($register->site->id));
			$stmt->setFetchMode(PDO::FETCH_INTO, $this);
			$stmt->fetch();
		}

		$this->full_address = $this->street.' '.$this->city.', '.$this->state.' '.$this->zipcode;
	}

	public function __sleep(){
		$arr = get_object_vars($this);
		unset( $arr['dbh'], $arr['google_api_key'] );

		$arr['static_map'] = $this->googleStaticMap();
		$arr['map_link'] = $this->googleMapLink();
		$arr['directions_link'] = $this->googleDirectionsLink();

		return $arr;
	}

	public function googleStaticMap($width=200, $height=200, $zoom = null){

		$url = 'http://maps.googleapis.com/maps/api/staticmap?';
		$url .= 'size='.$width.'x'.$height;
		$url .= '&amp;markers='.urlencode($this->full_address);
		if($zoom !== null)
			$url .= '&amp;zoom='.$zoom;
		$url .= '&amp;sensor=false';

		return $url;
	}

	public function googleMapLink(){
		return 'http://maps.google.com/?q='.urlencode($this->full_address);
	}

	public function googleDirectionsLink(){
		return 'http://maps.google.com/?daddr='.urlencode($this->full_address);
	}

}

?>