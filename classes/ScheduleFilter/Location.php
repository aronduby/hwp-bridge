<?php

namespace ScheduleFilter;

class Location extends \FilterIterator{

	private $location_id = [];
	
	public function __construct(\Iterator $itr, $location_id){
		parent::__construct($itr);
		$this->location_id = (array)$location_id;
	}

	public function accept(){
		return in_array(parent::current()->location_id, $this->location_id);
	}


}

?>