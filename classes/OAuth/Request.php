<?php

namespace OAuth;

class Request{

	private $url;
	private $method = 'GET';
	private $parameters = array();
	private $headers = array();
	private $ssl_checks = false;
	private $ca_info;
	private $ca_path;
	private $multipart_flag = false;

	// http 1.1 methods - http://www.w3.org/Protocols/rfc2616/rfc2616-sec9.html
	// this is used as a check in setMethod, doesn't change any behavior
	private $available_methods = array('OPTIONS', 'GET', 'HEAD', 'POST', 'PUT', 'DELETE', 'TRACE', 'CONNECT');

	public function __construct($url, $method='GET', $parameters=array(), $headers=array()){		
		$this->setUrl($url);
		$this->setMethod($method);
		$this->setParameters($parameters);
		$this->setHeaders($headers);
	}

	public function formatAsUrl(){
		$url = $this->url;
		if(count($this->parameters)){
			$qmpos = strpos($url, '?');
			
			// no ? for query string
			if($qmpos===false){
				$url .= '?';			
			// ? isn't the last character, but does exist, assume there's already a query string in the url
			} elseif($qmpos <= strlen($url)-1) {
				$url .= '&';
			}

			$url .= $this->buildQuery();
		}

		return $url;
	}

	public function buildQuery($numeric_prefix = '', $arg_seperator = false, $enc_type = '+'){
		if($numeric_prefix == '' && $arg_seperator === false && $enc_type === '+'){
			return http_build_query($this->parameters);
		} else {
			if($arg_seperator === false)
				$arg_seperator = ini_get('arg_separator.output');
			
			if($enc_type == '+'){
				$enc_type = PHP_QUERY_RFC1738;
			} elseif($enc_type == '%'){
				$enc_type = PHP_QUERY_RFC3986;
			} else{
				throw new Exception('Enc_type for OAuth\Request::buildQuery must be either + for RFC1738 encoding (spaces = +) or % for RFC3986 encoding (spaces = %20)');
			}

			return http_build_query($this->parameters, $numeric_prefix, $arg_seperator, $enc_type);
		}
	}
	
	public function encodeAndSortParameters(){
		$encoded_parameters = array();
		foreach($this->parameters as $k=>$v)
			$encoded_parameters[rawurlencode(utf8_encode($k))] = rawurlencode(utf8_encode($v));
		ksort($encoded_parameters);

		return $encoded_parameters;
	}

	public function addParameter($k, $v){
		$this->parameters[$k] = $v;
	}

	public function addHeader($header){
		$this->headers[] = $header;
	}

	public function removeParameter($k){
		if(array_key_exists($k, $this->parameters)){
			unset($this->parameters[$k]);
		} else {
			throw new Exception('Parameter "'.$k.'" does not exist in OAuth\Request::parameters');
		}
	}

	public function removeHeader($header){
		if(in_array($header, $this->headers)){
			$this->headers = array_diff($this->headers, array($header));
		} else {
			throw new Exception('Header value "'.$header.'" does not exist in OAuth\Request::headers');
		}
	}

	public function countParameters(){
		return count($this->parameters);
	}

	public function countHeaders(){
		return count($this->headers);
	}



	public function getCAInfo(){
		return $this->ca_info;
	}

	public function getCAPath(){
		return $this->ca_path;
	}

	public function getHeaders(){
		return $this->headers;
	}

	public function getMethod(){ 
		return $this->method; 
	}

	public function getParameters($key = false){ 
		if($key === false){
			ksort($this->parameters);
			return $this->parameters;
		} else {
			if(array_key_exists($key, $this->parameters)){
				return $this->parameters[$key];
			} else {
				throw new Exception('Parameter "'.$key.'" doesn\'t exist in OAuth\Request parameters');
			}
		}
	}

	public function getSSLChecks(){
		return $this->ssl_checks;
	}
	
	public function getUrl(){
		return $this->url; 
	}

	public function getMultipartFlag(){
		return $this->multipart_flag;
	}


	public function setCAInfo($info){
		$this->ca_info = $info;
	}

	public function setCAPath($path){
		$this->ca_path = $path;
	}

	public function setHeaders($headers){
		if(is_array($headers)){
			$this->headers = $headers;
		} elseif(is_string($headers)){
			$this->headers = array($headers);
		} else {
			throw new Exception('Supplied argument for OAuth\Request::setHeaders() must be an array or a string');
		}		 
	}

	public function setMethod($method){
		$method = strtoupper($method);
		if(in_array($method, $this->available_methods)){
			$this->method = $method;
		} else {
			throw new Exception('Method "'.$method.'" doesn\'t exist in OAuth\Request. Available methods are: '.implode(', ', $this->available_methods).'.');
		}
	}

	public function setParameters($parameters){
		if(is_array($parameters)){
			$this->parameters = $parameters;
		} else {
			throw new Exception('Supplied argument for OAuth\Request::setParameters() must be an array');
		}
	}

	public function setSSLChecks($bool){
		$this->ssl_checks = (bool)$bool;
	}

	public function setUrl($url){
		$url_parts = parse_url($url);
		if($url_parts !== false && $url_parts !== array('path'=>'') ){
			$url = $url_parts['scheme'].'://' . strtolower($url_parts['host']);
			if(isset($url_parts['port'])){
				if($url_parts['scheme'] == 'http' && $url_parts['port'] != '80')
					$url .= ':'.$url_parts['port'];
				elseif($url_parts['scheme'] == 'https' && $url_parts['port'] != '443')
					$url .= ':'.$url_parts['port'];
			}
			$url .= isset($url_parts['path']) ? $url_parts['path'] : '';
			$url .= isset($url_parts['query']) ? '?'.$url_parts['query'] : '';

			$this->url = $url;
		} else {
			throw new Exception('Could not parse url "'.$url.'" supplied to OAuth\Request, was it a relative path?');
		}
	}

	public function setMultipartFlag($bool){
		$this->multipart_flag = $bool;
	}






}
?>