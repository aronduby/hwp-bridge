<?php

namespace \OAuth\v1\Authorization;

class Uri {

	public static function auth(\OAuth\Request $request, $signature){
		$request->addParameter('oauth_signature', $signature);
		return $request;
	}

}

?>