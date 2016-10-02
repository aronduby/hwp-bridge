<?php

namespace OAuth\v1\Signature;

class RSASHA1 extends SignatureAbstract {

	protected static $method = 'RSA-SHA1';

	public static function sign(\OAuth\Request $request, $consumer_secret, $token_secret){
		throw new \OAuth\Exception('RSA-SHA1 signature method not yet supported');
	}
}

?>