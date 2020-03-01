<?php

namespace OAuth\Service;

class FourSquare extends \OAuth\v2\Service{
	
	protected $consumer_key = FOURSQUARE_CONSUMER_KEY;
	protected $consumer_secret = FOURSQUARE_CONSUMER_SECRET;

	protected $api_url = 'https://api.foursquare.com/v2/';
	protected $authorize_url = 'https://foursquare.com/oauth2/authenticate';
	protected $access_token_url = 'https://foursquare.com/oauth2/access_token';

	protected $redirect_url = 'http://client.grcmc.org/wip/cfacc/norewrite/auth_callback.php';

	protected $debug = true;
	protected $global_parameters = ['v'=>'20120816'];


	public $methods = array(
		'users_self' => array(
			'method' => 'GET',
			'url' => 'users/self'
		),
		'checkins_add' => array(
			'method' => 'POST',
			'url' => 'checkins/add',
			'required' => ['venue_id']
		)
	);

	public function venues(array $args){
		if(!isset($args['venue_id']))
			throw new \Exception('You are missing required parameter "venue_id" for calling "venues"');

		$request = new \OAuth\Request($this->api_url.'venues/'.$args['venue_id'], 'GET');
		return $this->fetch($request);
	}

	public function __call($name, $arguments){
		if(array_key_exists($name, $this->methods)){
			$method = $this->methods[$name]['method'];
			$url = $this->methods[$name]['url'];
			$arguments = array_pop($arguments);
			if(isset($this->methods[$name]['required'])){
				foreach($this->methods[$name]['required'] as $k){
					if(!isset($arguments[$k]))
						throw new \Exception('You are missing required parameter"'.$k.'" for calling "'.$name.'"');
				}
			}
			$request = new \OAuth\Request($this->api_url.$url, $method, count($arguments)>0 ? $arguments : array());
			return $this->fetch($request);

		} else {
			throw new \OAuth\Exception('Method "'.$name.'" does not exist in defined methods');
		}
	}

	public function getIdentifiers(){
		$rsp = $this->users_self();
		return ['id' => $rsp->response->user->id, 'name' => $rsp->response->user->firstName.' '.$rsp->response->user->lastName ];
	}

}

?>