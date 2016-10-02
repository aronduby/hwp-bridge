<?php

class Page{

	public static $title = '<span class="cap_number">Hudsonville</span>&nbsp;Water&nbsp;Polo';
	public static $include_like = true;
	public static $container_class = '';

	public static $css = array();
	public static $js = array();
	public static $meta = array();

	public static function addCSS($files){
		self::$css = array_merge(self::$css, $files);
	}

	public static function addJS($files){
		self::$js = array_merge(self::$js, $files);
	}

	public static function addMeta($meta){
		if(is_array($meta))
			self::$meta[] = $meta;
	}

}

?>