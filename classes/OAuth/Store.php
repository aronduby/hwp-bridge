<?php

namespace OAuth;

abstract class Store {

	protected $user;
	protected $service;
	protected $cache = array();

	abstract public function saveToken($token, $secret, $type, $ttl = null, $additional = null);
	abstract public function getTokens($type = null);
	abstract public function removeTokens($type = null);
	abstract public function saveSerialized($obj);
	abstract public function restoreFromSerialized($id);

	public function storeInCache($k, $v){
		if(!array_key_exists($k, $this->cache))
			$this->cache[$k] = null;

		$this->cache[$k] = $v;
	}

	public function getFromCache($k){
		if(array_key_exists($k, $this->cache)){
			return $this->cache[$k];
		} else {
			throw new Exception('Key "'.$k.'" does not exist in stores cache');
		}
	}

	public function removeFromCache($k){
		if(array_key_exists($k, $this->cache))
			unset($this->cache[$k]);
	}

	public function getUser(){
		return $this->user;
	}

	public function setUser($user){
		$this->user = $user;
	}

	public function getService(){
		return $this->service;
	}

	public function setService($service){
		$this->service = $service;
	}


}

?>