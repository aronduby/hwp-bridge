<?php

namespace OAuth;

abstract class ServiceAbstract{

	public $authorized = false;
	public $state;

	protected $consumer_key;
	protected $consumer_secret;

	protected $api_url;
	protected $authorize_url;
	protected $access_token_url;
	protected $redirect_url;

	protected $global_parameters;

	protected $store;
	protected $http_client_factory;
	protected $auth_type;
	protected $debug = false;
	protected $debugger;

	abstract public function authorize();
	abstract public function getAccessToken(array $arg);
	abstract public function refreshAccessToken();
	abstract public function fetch(Request $request);
	abstract public function prepareRequest(\OAuth\Request $request, \OAuth\Token $token);


	public function areWeAuthorized(){
		$token = $this->store->getTokens(\OAuth\Token::TYPE_ACCESS);
		$token_str = $token->getToken();
		if(!isset($token_str)){
			$this->authorized = false;
			return false;
		} else {
			$this->authorized = true;
			return true;
		}
	}

	public function __clone(){
		$this->store = clone $this->store;
	}

	public function setDebug($bool){
		$this->debugger->debug = (bool)$bool;
	}

	public function getDebugger(){
		return $this->debugger;
	}


	public function setAccessTokenUrl($url){
		$this->accessTokenUrl = $url;
	}

	public function setApiUrl($url){
		$this->api_url = $url;
	}

	public function setAuthorizeUrl($url){
		$this->authorize_url = $url;
		return true;
	}

	public function setAuthType($type){
		if(AuthorizationFactory::isValidType($type)){
			$this->auth_type = $type;
		} else {
			throw new \OAuth\Exception('AuthType "'.$type.'" is not a valid authorization method.', null, null, $this->debugger);
		}
	}

	public function setConsumerKey($key){
		$this->consumer_key = $key;
	}

	public function setConsumerSecret($secret){
		$this->consumer_secret = $secret;
	}

	public function setRedirectUrl($url){
		$this->redirect_url = $url;
	}

	public function setState($state){
		$this->state = $state;
		$_SESSION['state'] = $state;
	}

	public function setStore(\OAuth\Store $store){
		$this->store = $store;
	}


	
	public function getAccessTokenUrl(){
		return $this->access_token_url;		
	}

	public function getApiUrl(){
		return $this->api_url;		
	}

	public function getAuthorizeUrl(){
		return $this->authorize_url;		
	}

	public function getAuthType(){
		return $this->auth_type;
	}

	public function getConsumerKey(){
		return $this->consumer_key;		
	}

	public function getConsumerSecret(){
		return $this->consumer_secret;		
	}

	public function getRedirectUrl(){
		return $this->redirect_url;
	}

	public function getState(){
		if($this->state === null){
			$this->setState(uniqid());
			return $this->state;

		} else {
			return $this->state;
		}
	}

	public function getStore(){
		return $this->store;
	}

	

}
?>