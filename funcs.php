<?php

class HttpRequest {
	protected $cachePath = 'cache';
	protected $server = array();

	public function __construct($server) {
		$this->server = $server;
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
		$contents = json_encode(array(
			'path'		=> $this->getPath(),
			'query'		=> $this->getQuery(),
			'headers'	=> $this->getHeaders(),
			'input'		=> $this->getInput(),
		));

		file_put_contents($this->getFileName().'_'.time(), $contents);
		return $contents;
	}
}

