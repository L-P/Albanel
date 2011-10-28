<?php

error_reporting(-1);
function __autoload($class) {
	require_once "includes/$class.class.php";
}

$request = new HttpRequest($_SERVER);
header('Content-type: text/plain');
print_r($request->writeToFile());
print_r($_SERVER);


