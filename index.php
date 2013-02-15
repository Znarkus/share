<?php

error_reporting(E_ALL | E_STRICT);
$config = require(getenv('CONFIG_FILE'));
date_default_timezone_set($config['timezone']);
header('Content-type: text/html; charset=utf-8');

require 'lib/klein/klein.php';
require 'lib/php_http_auth/lib/digest.php';
require 'lib/database/lib/database.php';
require 'lib/prowlphp_wrapper/lib/prowl.php';
require 'models/file.php';
require 'models/files.php';

// Init
respond(function (_Request $request, _Response $response, _App $app) use ($config) {
	$app->config = $config;
	$app->db = new Lib\Database($config['db']);
	$app->prowl = new Prowl\Wrapper($config['prowl']['keys'], $config['prowl']['app']);
});

// List
respond('/', function (_Request $request, _Response $response, _App $app) {
	$digest = new Php_Http_Auth\Digest($app->config['users']);

	if (!$digest->login()) {
		$response->code(401);
		echo 'Failed to login.';
		exit;
	}
	
	$files = new Files($app->db, $app->config['dir']['files']);
	$response->render('pages/index.phtml', array('files' => $files->all()));
});

// File
respond('/[*:file]', function (_Request $request, _Response $response, _App $app) {
	$filename = basename($request->file);
	
	if ($filename[0] === '.') {
		$request->cookie(403);
		echo 'Invalid file.';
		exit;
	}
	
	$path = $app->config['dir']['files'] . $filename;
	
	if (!is_file($path)) {
		$response->code(404);
		echo "Sorry, can't find that file.";
		exit;
	}
	
	if (!is_readable($path)) {
		$response->code(500);
		echo "Failed to read this file.";
		exit;
	}
	
	$file = new File($path, array('db' => $app->db, 'prowl' => $app->prowl));
	$file->register_hit(array('ip_address' => $request->ip(), 'date' => time()));
	
	$response->header('X-Accel-Redirect', '/f/' . $filename);
	//$response->header('X-Accel-Limit-Rate', 1024 * 50);	// 50kB/s
	$response->header('Content-type', '');
});

respond('404', function () {
	echo '404, sorry.';
});

dispatch();