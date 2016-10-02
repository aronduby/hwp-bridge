<?php

class SuggestionLog implements IteratorAggregate {

	use Outputable;

	private $suggestions = [];
	private $dbh;

	public function __construct(PDO $dbh){
		$this->dbh = $dbh;		
	}

	public function getOpenSuggestions(){
		$stmt = $this->dbh->query("SELECT * FROM suggestion WHERE suggestion_id NOT IN (SELECT suggestion_id FROM changelog_to_suggestion) ORDER BY submitted DESC");
		$stmt->setFetchMode(PDO::FETCH_CLASS, 'Suggestion', [null, $this->dbh]);
		$this->suggestions = $stmt->fetchAll();
	}

	public function getClosedSuggestions(){
		$stmt = $this->dbh->query("SELECT * FROM suggestion WHERE suggestion_id IN (SELECT suggestion_id FROM changelog_to_suggestion) ORDER BY submitted DESC");
		$stmt->setFetchMode(PDO::FETCH_CLASS, 'Suggestion', [null, $this->dbh]);
		$this->suggestions = $stmt->fetchAll();
	}

	public function getAllSuggestions(){
		$stmt = $this->dbh->query("SELECT * FROM suggestion ORDER BY submitted DESC");
		$stmt->setFetchMode(PDO::FETCH_CLASS, 'Suggestion', [null, $this->dbh]);
		$this->suggestions = $stmt->fetchAll();
	}

	public function getIterator(){
		return new ArrayIterator($this->suggestions);
	}

}

?>