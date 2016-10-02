<?php

namespace OAuth\Store;

class Session extends \OAuth\Store {

	public function __construct(){
		if(!session_id())
			session_start();
		
		if(!isset($_SESSION['tokens'])){
			$_SESSION['tokens'] = array();
			$_SESSION['serialized'] = '';
		}
	}

	public function saveToken($token, $secret, $type, $ttl = false, $additional = array()){
		$arr = array(
			'token' => $token,
			'secret' => $secret,
			'type' => $type,
		);

		if(isset($ttl)) $arr['ttl'] = $ttl;
		if(isset($additional)) $arr['additional'] = json_encode($additional);

		$_SESSION['tokens'][] = $arr;

		return $this->createToken($token, $secret, $type, $ttl, $additional);
	}

	public function getTokens($type = null){
		if($type === null){
			$return = array();
			foreach($_SESSION['tokens'] as $arr){
				$return[] = $this->createToken($arr['token'], $arr['secret'], $arr['type'], isset($arr['ttl']) ? $arr['ttl'] : null, isset($arr['additional']) ? $arr['additional'] : null);
			}
			return $return;
		
		} elseif(is_string($type)){
			foreach($_SESSION['tokens'] as $arr){
				if($arr['type'] == $type)
					return $this->createToken($arr['token'], $arr['secret'], $arr['type'], isset($arr['ttl']) ? $arr['ttl'] : null, isset($arr['additional']) ? $arr['additional'] : null);
			}
			return $this->createToken();

		} elseif(is_array($type)){

			foreach($type as $t){
				foreach($_SESSION['tokens'] as $arr){
					if($arr['type'] == $t)
						return $this->createToken($arr['token'], $arr['secret'], $arr['type'], isset($arr['ttl']) ? $arr['ttl'] : null, isset($arr['additional']) ? $arr['additional'] : null);
				}
			}

			return $this->createToken();

		} else {
			throw new \OAuth\Exception('Argument to getTokens needs to be either null, a string, or an array');
		}
	}

	public function saveSerialized($obj){
		$serial_obj = base64_encode(serialize($obj));
		$_SESSION['serialized'] = $serial_obj;
		return microtime();
	}

	public function restoreFromSerialized($id){
		$serial_obj = $_SESSION['serialized'];
		return unserialize(base64_decode($serial_obj));
	}

	public function removeTokens($type = null){}


	private function createToken($token = false, $secret = false, $type = false, $ttl = false, $additional = array()){
		return new \OAuth\Token($token, $secret, $type, $ttl, $additional);
	}

}

?>