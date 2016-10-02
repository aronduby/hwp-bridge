<?php

namespace OAuth\v1\Signature;

abstract class SignatureAbstract {

	protected static $method;

	// abstract public static function sign(\OAuth\Request $request, $consumer_secret, $token_secret);

	public static function generateSignatureBase(\OAuth\Request $request){
		$encoded_parameters = $request->encodeAndSortParameters();

		// apparently you don't use the multi-part params in auth, just the oauth params
		if($request->getMultipartFlag() === true){
			$new_params = [];
			foreach($encoded_parameters as $k=>$v){
				if(strpos($k, 'oauth') !== false)
					$new_params[$k] = $v;
			}
			$encoded_parameters = $new_params;
		}
		
		// start out signature_base with the parameters
		$signature_base = array();
		foreach($encoded_parameters as $k=>$v){
			$signature_base[] = $k.'='.$v;
		}
		$signature_base = implode('&', $signature_base);

		return $request->getMethod().'&'.rawurlencode($request->getUrl()).'&'.rawurlencode($signature_base);
	}
}

?>