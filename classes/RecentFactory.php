<?php

class RecentFactory {

	public int $season_id;
	public int $limit = 10;
	public int $offset = 0;

	private ?PDO $dbh = null;

	public function __construct(PDO $dbh, int $season_id)
	{
		$this->dbh = $dbh;
		$this->season_id = $season_id;
	}

	public function load(int $page): array{
		if($page !== null)
			$this->setPage((int)$page);

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
		
		$stmt->bindParam(':season_id', $this->season_id, PDO::PARAM_INT);
		$stmt->bindParam(':offset', $this->offset, PDO::PARAM_INT);
		$stmt->bindParam(':limit', $this->limit, PDO::PARAM_INT);
		$stmt->execute();

		$objs = [];
		foreach($stmt->fetchAll(PDO::FETCH_OBJ) as $r){
			$class = 'RecentRenderers\\'.$r->class;
			$objs[] = new $class((int)$r->recent_id, (string)$r->content, $r->inserted, $this->dbh);
		}
		return $objs;

	}

	public function setPage(int $page): void{
		$this->offset = $this->limit * $page;
	}

}