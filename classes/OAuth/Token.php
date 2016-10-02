<?php

namespace OAuth;

class Token{

	const TYPE_ACCESS = 'access';
	const TYPE_REFRESH = 'refresh';
	const TYPE_REQUEST = 'request';

	private $token;
	private $secret;
	private $type;
	private $expires = false;
	private $additional;

	public $expired = false;

	public function __construct($token = false, $secret = false, $type = false, $expires = false, $additional = array()){
		if($token != false)
			$this->setToken($token);
		if($secret != false)
			$this->setSecret($secret);
		if($type != false)
			$this->setType($type);
		if($expires != false)
			$this->setExpires($expires);
		
		$this->setAdditional($additional);
	}

	public function setToken($token){
		$this->token = $token;
		return true;
	}

	public function setSecret($secret){
		$this->secret = $secret;
		return true;
	}

	public function setType($type){
		if(
			$type === self::TYPE_ACCESS
			|| $type === self::TYPE_REFRESH
			|| $type === self::TYPE_REQUEST
		){
			$this->type = $type;
			return true;
		} else {
			throw new OAuthException('Supplied token type is not a known type');
		}
	}

	public function setExpires($expires){
		$this->expires = $expires;
		if($this->expires <= time())
			$this->expired = true;
	}

	public function setAdditional($additional){
		$this->additional = $additional;
	}


	public function getToken(){
		return $this->token;
	}

	public function getSecret(){
		return $this->secret;
	}

	public function getType(){
		return $this->type;
	}

	public function getExpires(){
		return $this->expires;
	}

	public function getAdditional($k = false){
		if($k === false){
			return $this->additional;
		
		} elseif( array_key_exists($k, $this->additional) ){
			return $this->additional[$k];

		} else {
			throw new OAuthException('Key "'.$k.'" doesn\'t exist in additional token data');
		}
	}

}
?>