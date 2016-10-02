<?php

namespace OAuth;

class Debugger implements \IteratorAggregate, \ArrayAccess {

	public $debug = false;

	private $files = [];
	private $lines = [];
	private $keys = [];
	private $logs = [];

	private function push($key, $log){
		$debug = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS,2);

		$this->files[] = $debug[1]['file'];
		$this->lines[] = $debug[1]['line'];
		$this->keys[] = $key;
		$this->logs[] = $log;
	}

	public function getIterator(){
		$mi = new \MultipleIterator(\MultipleIterator::MIT_NEED_ANY|\MultipleIterator::MIT_KEYS_ASSOC);
		$mi->attachIterator(new \ArrayIterator($this->files), 'FILE');
		$mi->attachIterator(new \ArrayIterator($this->lines), 'LINE');
		$mi->attachIterator(new \ArrayIterator($this->keys), 'KEY');
		$mi->attachIterator(new \ArrayIterator($this->logs), 'LOG');

		return $mi;
	}

	// $debugger[$offset] = $value
	public function offsetSet($offset, $value){
        $this->push( $offset, $value );
    }

	// isset($debugger[$offset])
	public function offsetExists($offset){
		if(in_array($offset, $this->types))
			return count($this->$offset) > 0;
		else
			throw new Exception('Offset must be one of '.implode(', ', $this->types));
	}
	
	// $debugger[$offset]
	public function offsetGet($offset){
		if(in_array($offset, $this->types))
			return $this->$offset;
		else
			throw new Exception('Offset must be one of '.implode(', ', $this->types));
	}	
	
	// unset($debugger[$offset])
    public function offsetUnset($offset) { throw new Exception('Can not unset an offset'); }
}

?>