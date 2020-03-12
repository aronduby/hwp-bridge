<?php

class Schedule implements IteratorAggregate {

	use Outputable;

	public $season_id;
	public $start;
	public $end;

	private $schedule;

	private $register;
	private $dbh;

	public function __construct($season_id = null, Register $register){
		$this->season_id = $season_id;

		$this->register = $register;
		$this->dbh = $register->dbh;

		$sql = "SELECT * FROM schedule WHERE site_id = ".intval($register->site->id).( !is_null($season_id) ? " AND season_id=".intval($this->season_id)." " : "" )." ORDER BY start";
		$stmt = $this->dbh->query($sql);
		$this->schedule = $stmt->fetchAll(PDO::FETCH_OBJ);
	}

	public function reverse(){
		$this->schedule = array_reverse($this->schedule);
	}

	public function getIterator(){
		return new ArrayIterator($this->schedule);
	}

	public function count(){
		return count($this->schedule);
	}



}

?>