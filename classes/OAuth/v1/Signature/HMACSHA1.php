<?php

namespace OAuth\v1\Signature;

class HMACSHA1 extends SignatureAbstract {

	protected static $method = 'HMAC-SHA1';

	public static function sign(\OAuth\Request $request, $consumer_secret, $token_secret){
		$request->addParameter('oauth_signature_method', self::$method);
		$signature_base = self::generateSignatureBase($request);
		$signature_key = rawurlencode(utf8_encode($consumer_secret)) .'&'. rawurlencode(utf8_encode($token_secret));
		return base64_encode(hash_hmac("sha1", $signature_base, $signature_key, true));
	}
}

?>