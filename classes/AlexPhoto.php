<?php

class AlexPhoto extends Photo{
	
	public function __construct($photo_id, PDO $dbh){
		parent::__construct($photo_id, $dbh);

		$this->photo = $this->photo_path . $this->prepend . 'alex' . $this->append;
		$this->thumb = $this->thumb_path . $this->prepend . 'alex' . $this->append;

		$this->photo_id = 'alex';
	}

	// override the trait to still use photo templates
	public function output($tpl, $extra = null){
		$path = TEMPLATE_PATH.str_replace('\\', '/', 'photo').'-'.$tpl.'.php';
		if(file_exists($path)){
			ob_start();
			include $path;
			return ob_get_clean();
		} else {
			print_p( $path );
			throw new Exception('Template "'.'photo'.'-'.$tpl.'.php" does not exist in proper location');
		}
	}
}

?>