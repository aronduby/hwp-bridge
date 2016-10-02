<?php

namespace ScheduleFilter;

class District extends \FilterIterator{

	private $district = [];
	
	public function __construct(\Iterator $itr, $district){
		parent::__construct($itr);
		$this->district = (array)$district;
	}

	public function accept(){
		return in_array(parent::current()->district, $this->district);
	}


}

?>