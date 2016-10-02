<?php

namespace OAuth\v2\Grant;

class OwnerCredentials {

	public static function authorize(\OAuth\v2\Service $service){
		self::getAccessToken($service, array());
	}

	public static function getAccessToken(\OAuth\v2\Service $service, array $arg){
		try{
			$username = $this->service->store->getFromCache('username');
			$password = $this->service->store->getFromCache('password');
		} catch(Exception $e){
			throw new \OAuth\Exception('Username and Password must be cached in the store');
		}

		$parameters = array(
			'grant_type' => 'password',
			'username' => $username,
			'password' => $password,
			'client_id' => $service->getConsumerKey(),
			'client_secret' => $service->getConsumerSecret()
		);

		$request = new \OAuth\Request($service->getAccessTokenUrl(), 'POST', $parameters);
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