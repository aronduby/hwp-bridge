<?php

class RecentFactory {

	public $season_id;
	public $limit = 10;
	public $offset = 0;

	private $dbh;

	public function __construct(\PDO $dbh, $season_id){
		$this->dbh = $dbh;
		$this->season_id = $season_id;
	}

	public function load($page){
		if($page != null)
			$this->setPage($page);

		// Use mysql string functions to make the template into the offical (fully qualified) class name
		$sql = "
			SELECT 
				CONCAT(UPPER(LEFT(template,1)), SUBSTRING(template,2)) AS class,
				recent_id, content, inserted, sticky 
			FROM 
				recent 
			WHERE 
				season_id=:season_id
			ORDER BY 
				sticky DESC,
				inserted DESC
			LIMIT 
				:offset, :limit";

		$stmt = $this->dbh->prepare($sql);
		
		$stmt->bindParam(':season_id', $this->season_id, \PDO::PARAM_INT);
		$stmt->bindParam(':offset', $this->offset, \PDO::PARAM_INT);
		$stmt->bindParam(':limit', $this->limit, \PDO::PARAM_INT);
		$stmt->execute();

		$objs = [];
		foreach($stmt->fetchAll(\PDO::FETCH_OBJ) as $r){
			$class = 'RecentRenderers\\'.$r->class;
			$objs[] = new $class($r->recent_id, $r->content, $r->inserted, $this->dbh);
		}
		return $objs;

	}

	public function setPage($page){
		$this->offset = $this->limit * $page;
	}

}

?>