<?php

namespace ScheduleFilter;

class Opponent extends \FilterIterator{

	private $opponent = [];
	
	public function __construct(\Iterator $itr, $opponent){
		parent::__construct($itr);
		$this->opponent = (array)$opponent;
	}

	public function accept(){
		return in_array(parent::current()->opponent, $this->opponent);
	}


}

?>