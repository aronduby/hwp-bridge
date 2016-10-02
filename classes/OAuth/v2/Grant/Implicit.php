<?php

namespace OAuth\v2\Grant;

class Implicit {

	public static function authorize(\OAuth\v2\Service $service){
		$parameters = array(
			'response_type' => 'token',
			'client_id' => $service->getConsumerKey(),
			'redirect_uri' => $service->getRedirectUrl(),
			'state' => $service->getState()
		);
		$scope = $service->getScope();
		if($scope!=null)
			$parameters['scope'] = $scope;

		$request = new \OAuth\Request($service->getAuthorizeUrl(), 'GET', $parameters);

		if($service->debugger->debug === true)
			$service->debugger[] = $request;

		header("Location: ".$request->formatAsUrl(), true, 307);
		die();
	}

	public static function getAccessToken(\OAuth\v2\Service $service, array $arg){
		$additional = array();
		$ignore = array('access_token', 'expires_in', 'token_type');
		foreach($arg as $k=>$v){
			if(!in_array($k, $ignore))
				$additional[$k] = $v;
		}

		$service->getStore()->saveToken(
			$arg['access_token'], 
			null, // token_secret is only v1
			\OAuth\Token::TYPE_ACCESS,
			isset($arg['expires']) ? $arg['expires'] : null,
			$additional
		);
	}

}

?>