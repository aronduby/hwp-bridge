<?php

namespace ScheduleFilter;

class Result extends \FilterIterator{

	private $result = [];
	
	public function __construct(\Iterator $itr, $result){
		parent::__construct($itr);
		$this->result = array_map(function($v){ return strtoupper($v); }, (array)$result);
	}

	public function accept(){
		$cur = parent::current();

		if(is_null($cur->score_us)) return false;
		
		if($cur->score_us > $cur->score_them)
			$k = 'W';
		elseif($cur->score_us < $cur->score_them)
			$k = 'L';
		elseif($cur->score_us == $cur->score_them)
			$k = 'T';
		else
			return false;
		
		return in_array($k, $this->result);
	}


}

?>