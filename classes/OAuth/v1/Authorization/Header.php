<?php

namespace OAuth\v1\Authorization;

class Header {

	public static function auth(\OAuth\Request $request, $signature){
		$parameters = $request->getParameters();
		$auth_headers = 'Authorization: OAuth realm="",';
		foreach($parameters as $k=>$v){
			if( strpos($k, 'oauth_')!== false ){
				$auth_headers .= rawurlencode(utf8_encode($k)).'="'.rawurlencode(utf8_encode($v)).'",';
				$request->removeParameter($k);
			}
		}
		$auth_headers .= 'oauth_signature="'.rawurlencode($signature).'"';
		$request->addHeader($auth_headers);

		return $request;
	}

}

?>