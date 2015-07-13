<?php
require('config.inc.php');
require($path_to_slim);
\Slim\Slim::registerAutoloader();

$app = new \Slim\Slim(array(
	'mode'		=> 'development'
));
$app->setName("CBW beer API");
$app->response->headers->set("Content-Type", "application/json");
$app->response->headers->set("Access-Control-Allow-Origin", "*");
$app->response->headers->set("Access-Control-Allow-Methods","GET,POST,PUT,OPTIONS");
$app->response->headers->set("Access-Control-Allow-Headers", "apikey, Content-Type");

// if it's development
$app->configureMode('development', function () use ($app)
	{
	$app->config(array(
		'log.enable'	=> false,
		'debug'		=> true
	));
	});

// or production
$app->configureMode('production', function () use ($app)
	{
	$app->config(array(
		'log.enable'	=> true,
		'debug'		=> false
	));
	});

// catch-all OPTIONS
$app->options('/(:x+)', function() use ($app)
	{
	//$app->response->headers->set('Access-Control-Allow-Origin', '*');
	//$app->response->headers->set("Access-Control-Allow-Methods: GET, POST, PUT, OPTIONS");
	//$app->response->headers->set('Access-Control-Allow-Headers', 'apikey');
	$app->response->setStatus(200);
	});
//
// how do you want it returned?
if ($app->request->get('format') == "object") $format = JSON_FORCE_OBJECT;
else $format = NULL;

/*** individual groups ***/
require 'beers.php';
require 'taps.php';

$app->run();

?>
