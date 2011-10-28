<?php

error_reporting(-1);
require_once 'funcs.php';
header('Content-type: text/plain');

$request = new HttpRequest($_SERVER);
print_r($request->writeToFile());

