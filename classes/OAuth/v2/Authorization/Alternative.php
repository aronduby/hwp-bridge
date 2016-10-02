<?php

namespace OAuth\v2\Authorization;

class Alternative {

	public static function auth(\OAuth\Request $request, $token){
		$request->addParameter('access_token', $token);
		return $request;
	}

}

?>