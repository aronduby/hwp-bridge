<?php

$say = 'On '.$this->start->format('n j').' ';

$say .= ($this->team=='V' ? 'Varsity' : 'JV').' ';

switch($this->result){
	case 'W':
		$say .= 'defeated ';
		break;
	case 'L':
		$say .= 'lost to ';
		break;
	case 'T':
		$say .= 'tied ';
}

$say .= $this->opponent.' '.$this->score_us.' to '.$this->score_them;
print $say;
?>