<?php

class Change {

	use Outputable;

	public ?int $change_id = null;
	public array|null $changes = null;
	public ?string $ts = null;
	public array $suggestions = [];

	private ?PDO $dbh = null;

	public function __construct(?int $change_id = null, PDO $dbh)
	{
		$this->dbh = $dbh;

		if($change_id !== null){
			$stmt = $this->dbh->query("SELECT change_id, changes, ts FROM changelog WHERE change_id=".intval($change_id));
			$stmt->setFetchMode(PDO::FETCH_INTO, $this);
			if(!$stmt->fetch()){
				throw new Exception('Change Not Found');
			}
		}

		$this->ts = DateTime::createFromFormat(MYSQL_DATETIME_FORMAT, $this->ts);

		// $this->suggestions = $this->getSuggestions();
	}

	public function getSuggestions(): array{
		$stmt = $this->dbh->query("SELECT s.* FROM changelog_to_suggestion cts LEFT JOIN suggestion s USING(suggestion_id) WHERE cts.change_id=".intval($this->change_id)." ORDER BY s.submitted");
		$stmt->setFetchMode(PDO::FETCH_CLASS, 'Suggestion', [null, $this->dbh]);

		return $stmt->fetchAll();
	}

}
