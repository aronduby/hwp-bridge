<?php

namespace OAuth\v2\Grant;

class AuthorizationCode {

	public static function authorize(\OAuth\v2\Service $service){
		$parameters = array(
			'response_type' => 'code',
			'client_id' => $service->getConsumerKey(),
			'redirect_uri' => $service->getRedirectUrl(),
			'state' => $service->getState()
		);
		$scope = $service->getScope();
		if($scope!=null)
			$parameters['scope'] = $scope;

		$request = new \OAuth\Request($service->getAuthorizeUrl(), 'GET', $parameters);
		
		if($service->getDebugger()->debug === true)
			$service->getDebugger()[] = $request;

		header("Location: ".$request->formatAsUrl(), true, 307);
		die();
	}

	public static function getAccessToken(\OAuth\v2\Service $service, array $arg){
		$parameters = array(
			'grant_type' => 'authorization_code',
			'code' => $arg['code'],
			'redirect_uri' => $service->getRedirectUrl(),
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