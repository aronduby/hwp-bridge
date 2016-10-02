<?php

namespace OAuth;

class Exception extends \Exception {

	protected $debugger;

	public function __construct($message = null, $code = 0, Exception $previous = null, Debugger $debugger = null){
		parent::__construct($message, $code,$previous);
		
		if($debugger !== null)
			$this->setDebugger($debugger);
	}

	private function setDebugger($debugger){
		$this->debugger = $debugger;
	}

	public function getDebugger(){
		return $this->debugger;
	}

}
?>