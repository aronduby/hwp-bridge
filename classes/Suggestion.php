<?php

class Suggestion {

	use Outputable;

	public $suggestion_id;
	public $title;
	public $description;
	public $name;
	public $email;
	public $votes;
	public $user_agent;
	public $submitted;
	
	private $dbh;

	public function __construct($suggestion_id = null, PDO $dbh){
		$this->dbh = $dbh;

		if($suggestion_id !== null){
			$stmt = $this->dbh->query("SELECT * FROM suggestion WHERE suggestion_id=".intval($suggestion_id));
			$stmt->setFetchMode(PDO::FETCH_INTO, $this);
			if(!$stmt->fetch()){
				throw new Exception('Suggestion Not Found');
			}
		}

		$this->submitted = DateTime::createFromFormat(MYSQL_DATETIME_FORMAT, $this->submitted);
	}

}

?>