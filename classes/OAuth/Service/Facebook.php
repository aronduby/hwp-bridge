<?php

namespace OAuth\Service;

class Facebook extends \OAuth\v2\Service{
	
	protected $consumer_key = FACEBOOK_CONSUMER_KEY;
	protected $consumer_secret = FACEBOOK_CONSUMER_SECRET;

	protected $api_url = 'https://graph.facebook.com/';
	protected $authorize_url = 'https://www.facebook.com/dialog/oauth';
	protected $access_token_url = 'https://graph.facebook.com/oauth/access_token';

	protected $redirect_url = 'http://client.grcmc.org/wip/cfacc/norewrite/auth_callback.php';

	protected $scope = "user_status,user_photos,user_checkins,publish_checkins";

	protected $debug = true;


	public $methods = array(
		'me' => array(
			'method' => 'GET',
			'url' => 'me'
		),
		'statuses_get' => array(
			'method' => 'GET',
			'url' => 'me/statuses'
		),
		'statuses_post' => array(
			'method' => 'POST',
			'url' => 'me/statuses'
		)
	);

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

	public function getIdentifiers(){
		$rsp = $this->me();
		return ['id' => $rsp->id, 'name' => $rsp->name];
	}

}

?>