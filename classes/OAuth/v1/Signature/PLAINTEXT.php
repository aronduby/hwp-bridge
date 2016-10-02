<?php

namespace OAuth\v1\Signature;

class PLAINTEXT extends SignatureAbstract {

	protected static $method = 'PLAINTEXT';

	public static function sign(\OAuth\Request $request, $consumer_secret, $token_secret){
		$signature_base = self::generateSignatureBase($request);
		$signature_key = rawurlencode(utf8_encode($consumer_secret)) .'&'. rawurlencode(utf8_encode($token_secret));
		return $signature_key;
	}
}

?>