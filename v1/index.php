<?php
require('config.inc.php');
require($path_to_slim);
\Slim\Slim::registerAutoloader();

$app = new \Slim\Slim(array(
			'mode'		=> 'development'
			));
$app->setName("CBW beer API");

// if it's development
$app->configureMode('development', function () use ($app) {
		$app->config(array(
				'log.enable'	=> false,
				'debug'		=> true
				));
		});

// or production
$app->configureMode('production', function () use ($app) {
		$app->config(array(
				'log.enable'	=> true,
				'debug'		=> false
				));
		});

/* 
   API functions
   */

// dealing with list of beers we have made
$app->group('/beer', function() use ($app) {
		// all active and non-archived beers
		$app->get('', function() {
			echo "List of all beers";
			});

		// all beers, including archived
		$app->get('/all', function() {
			echo "old beer too";
			});

		// detail of specific beer
		$app->get('/:id', function($id) {
			echo $id;
			});
		});

// what we have on tap
$app->group('/ontap', function() use ($app) {
		// what's currently on tap
		$app->get('', function() {
			echo "1-10 (and cask??)";
			});

		// what's on cask
		$app->get('/cask', function() {
			echo "what's on the cask";
			});

		// what's on a specific tap
		$app->get('/:id', function($id) {
			echo "what's on tap " . $id;
			});

		});


$app->run();

?>
