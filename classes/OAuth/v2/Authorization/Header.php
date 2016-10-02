<?php

namespace OAuth\v2\Authorization;

class Header {

	public static function auth(\OAuth\Request $request, $token){
		$parameters = $request->getParameters();
		$auth_headers = 'Authorization: OAuth '.$token;
		$request->addHeader($auth_headers);

		return $request;
	}

}

?>