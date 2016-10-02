<?php

namespace ScheduleFilter;

class TimeFrame extends DateRange{

	public function __construct(\Iterator $itr, $type = null, $other = null){
		$start = null;
		$end = null;

		foreach($type as $t){
			$t = strtoupper($t);
			switch ($t) {
				case 'PAST':
					$end = 'today';
					break;
				case 'FUTURE':
					$start = 'today';
					break;				
				default:
					throw new \Exception('Must supply either past or future type');
			}
		}

		parent::__construct($itr, $start, $end);
	}

}

?>