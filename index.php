<?php

error_reporting(-1);
if(version_compare(PHP_VERSION, '5.3.0') == -1)
	exit('PHP >= 5.3.0 required.');


/** Represents an HTTP request. This class allow the request to be
 * "replayed", PHP will then perform the original query on the real host
 * and return the contents.
 * */
class HttpRequest {
	protected $cachePath	= 'cache';	///< Where the files will be saved.
	protected $server		= array();	///< $_SERVER superglobal as given to __construct().
	protected $time			= 0;		///< Request time.
	protected $input		= null;		///< POST/PUT data.
	protected $output		= null;		///< Output from the real host.

	/** This would have been a static var in getInput() to help
	 * memoize $this->input if PHP did not suck and did not treat such
	 * vars as static properties. I hate PHP.
	 * */
	protected $inputSet		= false;

	/** Constructor.
	 * \param $server $_SERVER superglobal.
	 * */
	public function __construct($server) {
		if(!array_key_exists('REQUEST_URI', $server))
			throw new \InvalidArgumentException('Missing key `REQUEST_URI` in $server.');

		$this->server = $server;
		$this->time = empty($server['REQUEST_TIME']) ? time() : $server['REQUEST_TIME'];
	}


	/** Returns an array containing the headers as sent by the client.
	 * The headers are fetched from HTTP_* keys in $_SERVER.
	 * Some headers will be lost in the process because PHP sucks.
	 * */
	public function getHeaders() {
		$headers = array();
		foreach($this->server as $k => $v) {
			if(substr($k, 0, 5) != 'HTTP_')
				continue;

			$name = explode('_', substr($k, 5));
			$name = array_map(function($v) { return ucfirst(strtolower($v)); }, $name);
			$name = implode('-', $name);

			// TODO : This special case may not be that special, other all-caps headers have to be found.
			if($name == 'Dnt')
				$name = 'DNT';

			$headers[$name] = $v;
		}

		// Try to recreate the Content-Lenght header.
		if(!array_key_exists('Content-Lenght', $headers) AND strlen($this->getInput()))
			$headers['Content-Lenght'] = strlen($this->getInput());

		return $headers;
	}


	/** Returns the requested host.
	 * \return the host.
	 * */
	public function getHost() {
		return $this->server['HTTP_HOST'];
	}


	/** Returns the requested path.
	 * \return the path with the leading slash if there was one.
	 * */
	public function getPath() {
		list($path) = explode('?', $this->server['REQUEST_URI']);
		return $path;
	}


	/** Returns the 'query', the part after the '?' in an URL.
	 * \return the query.
	 * */
	public function getQuery() {
		$query = explode('?', $this->server['REQUEST_URI']);
		array_shift($query);
		return implode('?', $query);
	}


	/** Returns the PUT/POST input data. Memoized.
	 * \return raw input.
	 * */
	public function getInput() {
		if(!$this->inputSet) {
			$this->input = file_get_contents('php://input');
			$this->inputSet = true;
		}

		return $this->input;
	}


	/** Return the name of the file to write. Used only by writeToFile().
	 * This method will create the intermediate folders if they do not exist.
	 * \return file name.
	 * */
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


	/** Writes the contents of the request (including input/output) to a file.
	 * Input and output will pass through bin2hex() to prevent json_encode from failing.
	 * \return contents of the file before JSON-encoding.
	 * */
	public function writeToFile() {
		$contents = array(
			'time'		=> date('c', $this->time),
			'host'		=> $this->getHost(),
			'path'		=> $this->getPath(),
			'query'		=> $this->getQuery(),
			'headers'	=> $this->getHeaders(),
		);

		foreach(array('output', 'input') as $var) {
			if(!empty($this->$var))
				$contents[$var] = bin2hex($this->$var);
		}

		$filename = sprintf('%s_%u', $this->getFileName(), $this->time);
		file_put_contents($filename, json_encode($contents));
		return $contents;
	}


	/** Returns true if the request was done over HTTPS, false otherwise.
	 * \return true if the request was done over HTTPS, false otherwise.
	 * */
	public function isHttps() {
		return !empty($this->server['HTTPS']);
	}


	/** Creates the context for file_get_contents in order to copy the original query.
	 * \return stream_context_create() result.
	 * */
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


	/** "Replays" the request and returns the output.
	 * \return output of the request.
	 * */
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


	/** Returns the method.
	 * \return POST or GET.
	 * */
	public function getMethod() {
		return strlen($this->getInput()) ? 'POST' : 'GET';
	}
}


header('Content-type: text/plain');
$request = new HttpRequest($_SERVER);
$request->replay();
print_r($request->writeToFile());

