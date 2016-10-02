<?php

namespace ScheduleFilter;

class Season extends \FilterIterator{

	private $season_id = [];
	
	public function __construct(\Iterator $itr, $season_id){
		parent::__construct($itr);
		$this->season_id = (array)$season_id;
	}

	public function accept(){
		return in_array(parent::current()->season_id, $this->season_id);
	}


}

?>