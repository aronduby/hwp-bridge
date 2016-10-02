<?php

namespace OAuth\v1;

class SignatureFactory{

	const TYPE_RSASHA1 = 'RSASHA1';
	const TYPE_HMACSHA1 = 'HMACSHA1';
	const TYPE_PLAINTEXT = 'PLAINTEXT';

	static public function sign($type, \OAuth\Request $request, $consumer_secret, $token_secret){
		$obj = 'OAuth\v1\Signature\\'.$type;
		return $obj::sign($request, $consumer_secret, $token_secret);
	}

	static public function isValidType($type){
		$r = new \ReflectionClass('\OAuth\v1\SignatureFactory');
        $consts = $r->getConstants();
        return in_array ($type, $consts);
	}

}

?>