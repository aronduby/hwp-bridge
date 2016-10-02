<?php

namespace OAuth\v2\Authorization;

class None {

	public static function auth(\OAuth\Request $request, $signature){
		return $request;
	}

}

?>