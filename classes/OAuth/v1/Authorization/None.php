<?php

namespace OAuth\v1\Authorization;

class None {

	public static function auth(\OAuth\Request $request, $signature){
		$parameters = $request->getParameters();
		foreach($parameters as $k=>$v){
			if( strpos($k, 'oauth_')!== false ){
				$request->removeParameter($k);
			}
		}

		return $request;
	}

}

?>