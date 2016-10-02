<?php

namespace OAuth\v2;

class GrantFactory{

	const TYPE_AUTH_CODE = 'AuthorizationCode';
	const TYPE_IMPLICIT = 'Implicit';
	const TYPE_OWNER_CREDENTIALS = 'OwnerCredentials';
	const TYPE_CLIENT_CREDENTIALS = 'ClientCredentials';
	// maybe add the extension type later

	static public function authorize($type, Service $service){
		$obj = '\OAuth\v2\Grant\\'.$type;
		$obj::authorize($service);
	}

	static public function getAccessToken($type, Service $service, array $arg){
		$obj = '\OAuth\v2\Grant\\'.$type;
		$obj::getAccessToken($service, $arg);
	}

	static public function isValidType($type){
		$r = new \ReflectionClass('\OAuth\v2\GrantFactory');
        $consts = $r->getConstants();
        return in_array ($type, $consts);
	}

}

?>