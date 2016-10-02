<?php

class ChangeLog implements IteratorAggregate {

	use Outputable;

	private $changes = [];
	private $dbh;

	public function __construct(PDO $dbh){
		$this->dbh = $dbh;

		$stmt = $this->dbh->query("SELECT * FROM changelog ORDER BY ts DESC");
		$stmt->setFetchMode(PDO::FETCH_CLASS, 'Change', [null, $this->dbh]);
		$this->changes = $stmt->fetchAll();
		
	}

	public function getIterator(){
		return new ArrayIterator($this->changes);
	}

}

?>