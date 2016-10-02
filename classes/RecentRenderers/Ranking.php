<?php

namespace RecentRenderers;

class Ranking extends Renderer {

	public $ranking;

	public function setup(){
		$this->ranking = json_decode($this->content);
	}

}

?>