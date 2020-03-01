<?php

namespace OAuth\Store;

class HardCoded extends \OAuth\Store {

	private $token = TWITTER_TOKEN;
	private $token_secret = TWITTER_TOKEN_SECRET;

	public function saveToken($token, $secret, $type, $ttl = false, $additional = array()){
		return $this->createToken($this->token, $this->token_secret, $type, $ttl, $additional);
	}

	public function getTokens($type = null){
		return $this->createToken($this->token, $this->token_secret);
	}

	public function removeTokens($type = null){}

	public function saveSerialized($obj){
		$serial_obj = base64_encode(serialize($obj));
		$_SESSION['serialized'] = $serial_obj;
		return $id;
	}

	public function restoreFromSerialized($id){
		$serial_obj = $_SESSION['serialized'];
		return unserialize(base64_decode($serial_obj));
	}


	private function createToken($token = false, $secret = false, $type = false, $ttl = false, $additional = array()){
		return new \OAuth\Token($token, $secret, $type, $ttl, $additional);
	}

}

?>