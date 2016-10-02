<?php

namespace RecentRenderers;

class Game extends Renderer {

	public $games = [];

	public function setup(){
		$ids = json_decode($this->content);
		foreach($ids as $id)
			$this->games[] = new \Game($id, $this->dbh);		
	}

}

?>