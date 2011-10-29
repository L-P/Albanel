<?php

class HttpRequest {
	protected $cachePath	= 'cache';
	protected $server		= array();
	protected $time			= 0;
	protected $output		= null;
	protected $input		= null;

	/** This would have been a static var in getInput() to help
	 * memoize $this->input if PHP did not suck and did not treat such
	 * vars as static properties. I hate PHP.
	 * */
	protected $inputSet		= false;

	public function __construct($server) {
		if(!array_key_exists('REQUEST_URI', $server))
			throw new \InvalidArgumentException('Missing key `REQUEST_URI` in $server.');

		$this->server = $server;
		$this->time = empty($server['REQUEST_TIME']) ? time() : $server['REQUEST_TIME'];
	}

	public function getHeaders() {
		$headers = array();
		foreach($this->server as $k => $v) {
			if(substr($k, 0, 5) != 'HTTP_')
				continue;

			$name = explode('_', substr($k, 5));
			$name = array_map(function($v) { return ucfirst(strtolower($v)); }, $name);
			$name = implode('-', $name);

			if($name == 'Dnt')
				$name = 'DNT';

			$headers[$name] = $v;
		}

		return $headers;
	}


	public function getHost() {
		return $this->server['HTTP_HOST'];
	}

	public function getPath() {
		list($path) = explode('?', $this->server['REQUEST_URI']);
		return $path;
	}

	public function getQuery() {
		$query = explode('?', $this->server['REQUEST_URI']);
		array_shift($query);
		return implode('?', $query);
	}

	public function getInput() {
		if(!$this->inputSet) {
			$this->input = file_get_contents('php://input');
			$this->inputSet = true;
		}

		return $this->input;
	}


	protected function getFileName() {
		$path = explode('/', ltrim($this->getPath(), '/'));
		$name = array_pop($path);

		$finalPath = $this->cachePath.'/';
		foreach($path as $v) {
			$finalPath = "$finalPath$v/";
			if(!is_dir($finalPath))
				mkdir($finalPath);
		}

		return $finalPath.$name;
	}


	public function writeToFile() {
		$contents = array(
			'time'		=> date('c', $this->time),
			'host'		=> $this->getHost(),
			'path'		=> $this->getPath(),
			'query'		=> $this->getQuery(),
			'headers'	=> $this->getHeaders(),
			'input'		=> bin2hex($this->getInput()),
		);

		foreach(array('output', 'input') as $var) {
			if(!empty($this->$var))
				$contents[$var] = $this->$var;
		}

		$filename = sprintf('%s_%u', $this->getFileName(), $this->time);
		file_put_contents($filename, json_encode($contents));
		return $contents;
	}


	public function isHttps() {
		return !empty($this->server['HTTPS']);
	}


	protected function createReplayContext() {
		$headers = '';
		foreach($this->getHeaders() as $k => $v)
			$headers .= "$k: $v\r\n";

		$method = $this->getMethod();
		$options = array(
			'headers'		=> $headers,
			'method'		=> $method,
			'ignore_errors'	=> true,
		);

		if(in_array($method, array('PUT', 'POST')))
			$options['content'] = $this->getInput();

		return stream_context_create(array('http' => $options));
	}


	public function replay() {
		$context = $this->createReplayContext();
		$url = sprintf('%s://%s%s?%s',
			$this->isHttps() ? 'https' : 'http',
			$this->getHost(),
			$this->getPath(), $this->getQuery()
		);

		$this->output = file_get_contents($url, false, $context);
		return $this->output;
	}


	public function getMethod() {
		return strlen($this->getInput()) ? 'POST' : 'GET';
	}
}

