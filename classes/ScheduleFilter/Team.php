<?php

namespace ScheduleFilter;

class Team extends \FilterIterator{

	private $team = [];
	
	public function __construct(\Iterator $itr, $team){
		parent::__construct($itr);
		$this->team = (array)$team;
	}

	public function accept(){
		return in_array(parent::current()->team, $this->team);
	}


}

?>