<?php

namespace OAuth\v1;

class Service extends \OAuth\ServiceAbstract {

	const VERSION = '1.0';

	protected $request_token_url;
	protected $signature_type;

	protected $token_key = 'oauth_token';
	protected $token_secret_key = 'oauth_token_secret';

	private $nonce;
	private $past_nonces = array();
	private $timestamp;

	public function __construct(\OAuth\Store $store, $auth_type = \OAuth\AuthorizationFactory::AUTH_HEADER, $signature_type = SignatureFactory::TYPE_HMACSHA1){
		$this->setStore($store);
		$this->setAuthType($auth_type);
		$this->setSignatureType($signature_type);

		$this->debugger = new \OAuth\Debugger();
		$this->setDebug($this->debug);
		
		$this->areWeAuthorized();
	}

	
	public function authorize(){
		$request_token = $this->store->getTokens(\OAuth\Token::TYPE_REQUEST);
		
		if($request_token->getToken() === null){
			$request_token = $this->getRequestToken();
		}

		$id = $this->store->saveSerialized($this);
		$this->setState($id);
		
		$parameters = array(
			'oauth_token' => $request_token->getToken()
		);
		$request = new \OAuth\Request($this->authorize_url, 'GET', $parameters);
		
		header("Location: ".$request->formatAsUrl(), true, 307);
		die();

	}

	public function getAccessToken(array $arg){
		// tokens are sometime passed as token and sometimes oauth_token
		if(!isset($arg['token']) && isset($arg['oauth_token'])){
			$arg['token'] = $arg['oauth_token'];
		} else {
			throw new \Exception("Could not find token in passed in arguments");
		}
		
		// verifier isn't a required field
		if(!isset($arg['verifier']) && isset($arg['oauth_verifier']))
			$arg['verifier'] = $arg['oauth_verifier'];

		$request = new \OAuth\Request($this->access_token_url, 'POST', array('oauth_token' => $arg['token'], 'oauth_verifier'=>$arg['verifier']));
		$rsp = $this->fetch($request);
		
		$this->store->saveToken($rsp[$this->token_key], $rsp[$this->token_secret_key], \OAuth\Token::TYPE_ACCESS, isset($rsp['expires']) ? $rsp['expires'] : null);
	}

	public function refreshAccessToken(){
		// we need to remove any current access tokens
		$this->store->removeTokens(\OAuth\Token::TYPE_ACCESS);
		$this->authorize();
	}

	public function fetch(\OAuth\Request $request){
		$token = $this->store->getTokens(array(\OAuth\Token::TYPE_ACCESS, \OAuth\Token::TYPE_REQUEST));
		$request = $this->prepareRequest($request, $token);

		if($this->debugger->debug === true)
			$this->debugger[] = $request;

		$http = new \OAuth\HTTPClient($request, $this->debugger);

		$http->execute();
		return $http->getResponse();

	}
	
	public function prepareRequest(\OAuth\Request $request, \OAuth\Token $token){
		$request->addParameter('oauth_consumer_key', $this->getConsumerKey());
		$request->addParameter('oauth_nonce', $this->getNonce());
		$request->addParameter('oauth_timestamp', $this->getTimestamp());
		$request->addParameter('oauth_version', self::VERSION);
		
		if($token->getToken() !== null)
			$request->addParameter('oauth_token', $token->getToken());

		if(is_array($this->global_parameters) && count($this->global_parameters)>0){
			foreach($this->global_parameters as $k=>$v)
				$request->addParameter($k, $v);
		}

		$signature = SignatureFactory::sign($this->signature_type, $request, $this->getConsumerSecret(), $token->getSecret());
		$request = \OAuth\AuthorizationFactory::auth(intval(self::VERSION), $this->auth_type, $request, $signature);

		return $request;
	}

	private function getRequestToken(){
		$request = new \OAuth\Request($this->request_token_url, 'POST', array('oauth_callback'=>isset($this->redirect_url) ? $this->redirect_url : 'oob'));
		$rsp = $this->fetch($request);

		return $this->store->saveToken($rsp[$this->token_key], $rsp[$this->token_secret_key], \OAuth\Token::TYPE_REQUEST, time() + 30);
	}




	public function setNonce($nonce){
		if(!in_array($nonce, $this->past_nonces)){
			$this->nonce = $nonce;
			$this->past_nonces[] = $nonce;
		} else {
			throw new \OAuth\Exception('Nonce "'.$nonce.'" has already been used', null, null, $this->debugger);
		}
	}

	public function setRequestTokenUrl($url){
		$this->request_token_url = $url;
	}

	public function setSignatureType($type){
		if(SignatureFactory::isValidType($type)){
			$this->signature_type = $type;
		} else {
			throw new \OAuth\Exception('Signature type "'.$type.'" is not a valid Signature type.', null, null, $this->debugger);
		}
	}

	public function setTimestamp($timestamp){
		$timestamp = (string)$timestamp;
		// taken from http://stackoverflow.com/questions/2524680/check-whether-the-string-is-a-unix-timestamp
		$valid =  ((string) (int) $timestamp === $timestamp) 
	        && ($timestamp <= PHP_INT_MAX)
		    && ($timestamp >= ~PHP_INT_MAX);

		if($valid === true){
			$this->timestamp = (int)$timestamp;
		} else {
			throw new \OAuth\Exception('Timestamp "'.$timestamp.'" does not appear to be a valid timestamp', null, null, $this->debugger);
		}
	}

	public function getNonce(){
		if(isset($this->nonce)){
			return $this->nonce;
		} else {
			return uniqid();
		}
	}

	public function getRequestTokenUrl(){
		return $this->request_token_url;
	}

	public function getSignatureType(){
		return $this->signature_type;
	}

	public function getTimestamp(){
		if(isset($this->timestamp)){
			return $this->timestamp;
		} else {
			return time();
		}
	}

}
?>