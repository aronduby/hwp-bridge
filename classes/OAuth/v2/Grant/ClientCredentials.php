<?php

namespace OAuth\v2\Grant;

class ClientCredentials {

	public static function authorize(\OAuth\v2\Service $service){
		self::getAccessToken($service, array());
	}

	public static function getAccessToken(\OAuth\v2\Service $service, array $arg){
		$parameters = array(
			'grant_type' => 'client_credentials'
		);

		$request = new \OAuth\Request($service->getAccesstokenUrl(), 'POST', $parameters);
		$rsp = $service->fetch($request);

		$access_token = '';
		$expires = null;
		$refresh_token = false;
		$additional = [];
		foreach($rsp as $k=>$v){
			switch($k){
				case 'access_token':
					$access_token = $v;
					break;
				case 'expires':
					$expires = $v;
					break;
				case 'refresh_token':
					$refresh_token = $v;
					break;
				default:
					$additional[$k] = $v;
					break;
			}
		}

		$service->getStore()->saveToken(
			$access_token, 
			null, // token_secret is only v1
			\OAuth\Token::TYPE_ACCESS,
			$expires,
			$additional
		);

		if($refresh_token !== false){
			$service->getStore()->saveToken($refresh_token, null, \OAuth\Token::TYPE_REFRESH);
		}
	}

}

?>