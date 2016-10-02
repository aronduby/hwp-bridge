<?php

namespace ScheduleFilter;

class Type extends \FilterIterator{

	private $type = [];
	
	public function __construct(\Iterator $itr, $type){
		parent::__construct($itr);
		$this->type = (array)$type;
	}

	public function accept(){
		return in_array(parent::current()->type, $this->type);
	}


}

?>