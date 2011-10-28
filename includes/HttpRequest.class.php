<?php

class HttpRequest {
	protected $cachePath = 'cache';
	protected $server = array();
	protected $time = 0;

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
		return bin2hex(file_get_contents('php://input'));
	}


	protected function getFileName() {
		$path = explode('/', $this->getPath());
		if(strlen($path[0]) === 0)
			array_shift($path);
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
			'path'		=> $this->getPath(),
			'query'		=> $this->getQuery(),
			'headers'	=> $this->getHeaders(),
			'input'		=> $this->getInput(),
		);

		$filename = sprintf('%s_%u', $this->getFileName(), $this->time);
		file_put_contents($filename, json_encode($contents));
		return $contents;
	}
}

