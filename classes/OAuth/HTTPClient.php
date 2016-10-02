<?php

namespace OAuth;

class HTTPClient{

	/**
	 *	Static Methods
	*/
	public static function parseStringToArray($string, $first_delimiter = '&', $second_delimiter = '=') {
		$results = array();
		$parts = explode($first_delimiter, $string);
		foreach($parts as $part){
			$second_part = explode($second_delimiter, $part);
			$results[urldecode($second_part[0])] = isset($second_part[1]) ? trim(urldecode($second_part[1])) : null;
		}
		return $results;
	}

	/**
	 *	Normal class
	*/
	private $debugger;
	
	private $request;
	private $raw_response;
	private $response;
	private $response_headers;
	private $info;
	private $ch;

	public function __construct(Request $request, Debugger $debugger){
		$this->request = $request;
		$this->debugger = $debugger;
	}

	public function execute(){
		$this->ch = curl_init();		

		$http_method = $this->request->getMethod();		
		switch($http_method){
			case 'HEAD':
			case 'DELETE':
			case 'GET':
				$url = $this->request->formatAsUrl();
				
				switch($http_method){
					case 'HEAD':
						curl_setopt($this->ch, CURLOPT_NOBODY, true);
						break;
					case 'DELETE':
						curl_setopt($this->ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
						break;
				}
				break;

			case 'POST':
				curl_setopt($this->ch, CURLOPT_POST, true);
				if($this->request->getMultipartFlag() === false){
					curl_setopt($this->ch, CURLOPT_POSTFIELDS, $this->request->buildQuery());
				} else {
					curl_setopt($this->ch, CURLOPT_POSTFIELDS, $this->request->getParameters());
				}
				break;

			case 'PUT':
				$put_file = @tmpfile();
				if(!$put_file){
					throw new Exception('Could not create tmpfile for PUT operation', null, null, $this->debugger);
				}
				fwrite($put_file, $parameters['file']);
				fseek($put_file, 0);

				curl_setopt($this->ch, CURLOPT_PUT, true);
  				curl_setopt($this->ch, CURLOPT_INFILE, $put_file);
  				curl_setopt($this->ch, CURLOPT_INFILESIZE, filesize($put_file));
				break;
		}

		curl_setopt($this->ch, CURLOPT_URL, isset($url) ? $url : $this->request->getUrl());
		curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($this->ch, CURLOPT_HTTPHEADER, $this->request->getHeaders());
		curl_setopt($this->ch, CURLOPT_TIMEOUT, 30);
		curl_setopt($this->ch, CURLOPT_VERBOSE, 1);
		curl_setopt($this->ch, CURLOPT_HEADER, 1);
		curl_setopt($this->ch, CURLINFO_HEADER_OUT, true);
		curl_setopt($this->ch, CURLOPT_HEADER, true);
		curl_setopt($this->ch, CURLOPT_SSL_VERIFYPEER, true);
		curl_setopt($this->ch, CURLOPT_SSL_VERIFYHOST, 2);
		
		if($this->request->getSSLChecks() === false){
			curl_setopt($this->ch, CURLOPT_SSL_VERIFYPEER, false);
		} else {
			$ca_path = $this->request->getCAPath();
			$ca_info = $this->request->getCAInfo();

			if($ca_path !== null)
				curl_setopt($this->ch, CURLOPT_CAPATH, $ca_path);
			if($ca_info !== null)
				curl_setopt($this->ch, CURLOPT_CAINFO, $ca_info);
		}

		$rsp = curl_exec($this->ch);
		
		if($rsp === false){
			$error = curl_error($this->ch);
			$errno = curl_errno($this->ch);
			$this->raw_response = false;
			$this->response = false;
			$this->response_headers = false;
			$this->info = curl_getinfo($this->ch);
			curl_close($this->ch);

			if($this->debugger->debug===true){
				$this->debugger['raw_response'] = $this->raw_response;
				$this->debugger['response_headers'] = $this->response_headers;
				$this->debugger['info'] = $this->info;
			}

			throw new Exception($error, $errno, null, $this->debugger);

		} else {
			// split body and content
			list($headers, $content) = explode("\r\n\r\n", $rsp, 2);
			$this->raw_response = $content;
			$this->response_headers = $headers;
			$this->info = curl_getinfo($this->ch);
			curl_close($this->ch);

			$this->response_headers = self::parseStringToArray($this->response_headers, PHP_EOL, ':');
			$this->response = $this->parseRawResponse();

			if($this->debugger->debug===true){
				$this->debugger['raw_response'] = $this->raw_response;
				$this->debugger['response_headers'] = $this->response_headers;
				$this->debugger['info'] = $this->info;
			}

			if($this->info['http_code'] != 200 && $this->info['http_code'] != 304){
				$e = new HTTPException('Request returned non 200 header, see code for response', $this->info['http_code'], null, $this->debugger);
				throw $e;
			}

			return true;
		}
	}

	public function getRawResponse(){
		return $this->raw_response;
	}

	public function getResponse(){
		return $this->response;
	}

	public function getResponseHeaders(){
		return $this->response_headers;
	}

	public function getInfo(){
		return $this->info;
	}


	private function parseRawResponse(){
		$type = 'text';
		if (
			isset($this->response_headers['content-type']) 
			&& (
				strpos($this->response_headers['content-type'], 'application/json') !== false // correct way
				|| strpos($this->response_headers['content-type'], 'text/javascript') !== false // facebook
			)
		){
			$type = 'json';
		}

		switch ($type) {
			case 'json':
				return json_decode($this->raw_response, false, 512, JSON_BIGINT_AS_STRING);
				if($this->response === null){
					throw new Exception('Response sent as JSON but failed to decode: '.json_last_error(), null, null, $this->debugger);
				}			
		case 'text':
		default:
			return self::parseStringToArray($this->raw_response);
		}
	}

}

?>