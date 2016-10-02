<?php

namespace RecentRenderers;

class Renderer{
	
	use \Outputable;

	public $recent_id;
	public $content;
	public $inserted;

	public $title;

	protected $dbh;

	public function __construct($recent_id, $content, $inserted, \PDO $dbh){
		$this->recent_id = $recent_id;
		$this->content = $content;
		$this->inserted = new \DateTime($inserted);

		$this->dbh = $dbh;

		if(method_exists($this, 'setup')){
			$this->setup();
		}
	}

}

?>