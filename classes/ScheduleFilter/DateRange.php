<?php

namespace ScheduleFilter;

class DateRange extends \FilterIterator{

	private $start;
	private $end;
	
	public function __construct(\Iterator $itr, $start = null, $end = null){
		if($start == null && $end == null){
			throw new \Exception('Must supply either a start date and/or end date');
		}
		
		if($start !== null)
			$this->start = strtotime($start);
		if($end !== null)
			$this->end = strtotime($end);

		if($this->start === false || $this->end === false){
			$incorrect = [];
			if($this->start === false) $incorrect[] = 'start';
			if($this->end === false) $incorrect[] = 'end';
			throw new \Exception(implode(' and ', $incorrect).' could not be converted to a timestamp, please check the format and try again');
		}

		parent::__construct($itr);
		
	}

	public function accept(){
		$start = strtotime(parent::current()->start);
		$end = strtotime(parent::current()->end);
		
		if(isset($this->start) && isset($this->end)){
			return $end >= $this->start && $start <= $this->end;
		
		} elseif(isset($this->start)){
			return $end >= $this->start;
		
		} else {
			return $start <= $this->end;
		}
	}


}

?>