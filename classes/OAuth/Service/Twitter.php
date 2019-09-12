<?php

namespace OAuth\Service;

class Twitter extends \OAuth\v1\Service{
	
	// Hudsonville Water Polo App
	protected $consumer_key = TWITTER_CONSUMER_KEY;
	protected $consumer_secret = TWITTER_CONSUMER_SECRET;

	protected $api_url = 'https://api.twitter.com/1.1/';
	protected $request_token_url = 'https://api.twitter.com/oauth/request_token';
	protected $authorize_url = 'https://api.twitter.com/oauth/authenticate';
	protected $access_token_url = 'https://api.twitter.com/oauth/access_token';

	protected $redirect_url = 'http://www.hudsonvillewaterpolo.com/norewrite/auth_callback.php';


	// protected $token_key = 'token';
	// protected $token_secret_key = 'token_secret';

	public $methods = array(
		'account_verify_credentials' => array(
			'method' => 'GET',
			'url' => 'account/verify_credentials.json'
		),
		'statuses_update' => array(
			'method' => 'POST',
			'url' => 'statuses/update.json'
		),
		'friends_ids' => array(
			'method' => 'GET',
			'url' => 'friends/ids.json'
		)
	);

	public function statuses_update_with_media($status, $media){
		// if it starts with the @ symbol php/curl will think it's a file upload/
		// to get around this add a space, which twitter will trim off
		// it's a terrible solution, but I can't find another way around it
		if($status[0] == '@')
			$status = ' '.$status;
		
		$request = new \OAuth\Request(
			$this->api_url.'statuses/update_with_media.json',
			'POST',
			[
				'status' => $status,
				'media[]' => '@'.$media
			]
		);
		$request->setMultipartFlag(true);
		return $this->fetch($request);
	}

	public function post_user_lookup($user_ids, $include_entities = false){
		$request = new \OAuth\Request(
			$this->api_url.'users/lookup.json',
			'POST',
			[
				'user_id' => $user_ids,
				'include_entities' => ($include_entities ? 'true' : 'false')
			]
		);
		return $this->fetch($request);
	}


	public function __call($name, $arguments){
		if(array_key_exists($name, $this->methods)){
			$method = $this->methods[$name]['method'];
			$url = $this->methods[$name]['url'];
			if(isset($this->methods[$name]['required'])){
				foreach($this->methods[$name]['required'] as $k){
					if(!isset($arguments[$k]))
						throw new \Exception('You are missing required parameter"'.$k.'" for calling "'.$name.'"');
				}
			}
			$request = new \OAuth\Request($this->api_url.$url, $method, count($arguments)>0 ? $arguments[0] : array());
			return $this->fetch($request);

		} else {
			throw new \OAuth\Exception('Method "'.$name.'" does not exist in defined methods');
		}
	}

}

?>