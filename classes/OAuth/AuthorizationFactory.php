<?php

namespace OAuth;

class AuthorizationFactory{

	const AUTH_HEADER = 'Header';
	const AUTH_ALTERNATIVE = 'Alternative';
	const AUTH_URI = 'Uri';
	const AUTH_FORM = 'Form';
	const AUTH_NONE = 'None';

	private static $type_by_version = array(
		1 => array(self::AUTH_HEADER, self::AUTH_URI, self::AUTH_FORM, self::AUTH_NONE),
		2 => array(self::AUTH_HEADER, self::AUTH_ALTERNATIVE)
	);

	static public function auth($version, $type, \OAuth\Request $request, $signature){
		if(!in_array($type, self::$type_by_version[$version])){
			throw new Exception('Authorization type "'.$type.'" not valid for version "'.$version.'".');
		}
		$obj = 'OAuth\v'.$version.'\Authorization\\'.$type;
		return $obj::auth($request, $signature);
	}

	static public function isValidType($type){
		$r = new \ReflectionClass('\OAuth\AuthorizationFactory');
        $consts = $r->getConstants();
        return in_array ($type, $consts);
	}

}

?>