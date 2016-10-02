<?php

namespace OAuth;

class MultiPartHTTPClient extends HTTPClient {

	public function __construct(Request $request, Debugger $debugger){
		$this->request = $request;
		$this->debugger = $debugger;

		if($this->request->getMethod() != 'POST')
			throw new Exception('Request method must be post to use MultiPartHTTPClient');
	}

	public function execute(){

		$mime_boundary=md5(microtime());

		$data = '';
		foreach($this->request->getParameters() as $k=>$v){
			$data .= '--' . $mime_boundary . PHP_EOL;
			$data .= 'Content-Disposition: form-data; name="'.$k.'"' . PHP_EOL;
			$data .= $v . PHP_EOL;
		}
		$data .= PHP_EOL;

		$this->request->addHeader('Content-Length: '.strlen($data));
		$this->request->addHeader('Content-Type: multipart/form-data;boundary='.$mime_boundary);
		$this->request->addHeader('Accept-Encoding: gzip');

		// print_p($this->request->getHeaders());
		// print_p($data, true);

		$params = array('http' => array(
			'method' => 'POST',
			'header' => $this->request->getHeaders(),
			'content' => $data
		));

		$ctx = stream_context_create($params);
		$response = file_get_contents($this->request->getUrl(), FILE_TEXT, $ctx);

		print_p($response);
		print_p($params);

	}
}

?>