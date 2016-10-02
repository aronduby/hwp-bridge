<?php

namespace RecentRenderers;

class Tournament extends Renderer {

	public $tournaments = [];

	public function setup(){
		$ids = json_decode($this->content);
		foreach($ids as $id)
			$this->tournaments[] = new \Tournament($id, $this->dbh);		
	}

}

?>