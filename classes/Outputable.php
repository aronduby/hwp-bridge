<?php

trait Outputable{

	public function output($tpl, $extra = null){
		$path = TEMPLATE_PATH.str_replace('\\', '/', strtolower(get_class($this))).'-'.$tpl.'.php';
		if(file_exists($path)){
			ob_start();
			include $path;
			return ob_get_clean();
		} else {
			print_p( $path );
			throw new Exception('Template "'.strtolower(get_class($this)).'-'.$tpl.'.php" does not exist in proper location');
		}
	}

}

?>