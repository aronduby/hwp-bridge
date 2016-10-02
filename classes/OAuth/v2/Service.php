<?php

namespace OAuth\v2;

class Service extends \OAuth\ServiceAbstract {

	const VERSION = '2.0';

	protected $scope;
	protected $grant_type;

	public function __construct(\OAuth\Store $store, $auth_type = \OAuth\AuthorizationFactory::AUTH_HEADER, $grant_type = GrantFactory::TYPE_AUTH_CODE){
		$this->setStore($store);
		$this->setAuthType($auth_type);
		$this->setGrantType($grant_type);
		
		$this->debugger = new \OAuth\Debugger();
		$this->debugger->debug = $this->debug;

		$this->areWeAuthorized();
	}

	
	public function authorize(){
		$id = $this->store->saveSerialized($this);
		$this->setState($id);
		GrantFactory::authorize($this->getGrantType(), $this);
	}

	public function getAccessToken(array $arg){
		GrantFactory::getAccessToken($this->getGrantType(), $this, $arg);
	}		

	public function refreshAccessToken(){
		$token = $this->store->getTokens(\OAuth\Token::TYPE_REFRESH);
		if($token->getToken() === null){
			$this->authorize();
		} else {
			$parameters = array(
				'grant_type' => 'refresh_token',
				'refresh_token' => $token->getRefreshToken(),
				'client_id' => $this->_client->getClientKey(),
				'client_secret' => $this->_client->getClientSecret()
			);

			$scope = $service->getScope();
			if($scope!=null)
				$parameters['scope'] = $scope;

			$request = new Request($service->access_token_url, 'GET', $parameters);
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
					case 'expires_in':
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

			$service->store->saveToken(
				$access_token, 
				null, // token_secret is only v1
				\OAuth\Token::TYPE_ACCESS,
				$expires,
				$additional
			);

			if($refresh_token !== false){
				$service->store->saveToken($refresh_token, null, \OAuth\Token::TYPE_REFRESH);
			}
		}	
	}

	public function fetch(\OAuth\Request $request){
		$token = $this->store->getTokens(\OAuth\Token::TYPE_ACCESS);
		$request = $this->prepareRequest($request, $token);

		if($this->debugger->debug === true)
			$this->debugger[] = $request;
		
		$http = new \OAuth\HTTPClient($request, $this->debugger);
		$http->execute();
		return $http->getResponse();

	}
	
	public function prepareRequest(\OAuth\Request $request, \OAuth\Token $token){
		if(is_array($this->global_parameters) && count($this->global_parameters)>0){
			foreach($this->global_parameters as $k=>$v)
				$request->addParameter($k, $v);
		}

		$request = \OAuth\AuthorizationFactory::auth(intval(self::VERSION), $this->auth_type, $request, $token->getToken());
		return $request;
	}


	public function setGrantType($type){
		if(GrantFactory::isValidType($type)){
			$this->grant_type = $type;
		} else {
			throw new \OAuth\Exception('Grant type "'.$type.'" is not a valid Grant type.', null, null, $this->debugger);
		}
	}

	public function getGrantType(){
		return $this->grant_type;
	}

	public function setScope($scope){
		$this->scope = $scope;
	}
	
	public function getScope(){
		return $this->scope;
	}


}
?>