<?php

error_reporting(-1);
if(version_compare(PHP_VERSION, '5.3.0') == -1)
	exit('PHP >= 5.3.0 required.');

function __autoload($class) {
	require_once "includes/$class.class.php";
}

header('Content-type: text/plain');
$request = new HttpRequest($_SERVER);
$request->replay();
print_r($request->writeToFile());

