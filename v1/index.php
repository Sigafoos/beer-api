<?php
require('config.inc.php');
require($path_to_slim);
\Slim\Slim::registerAutoloader();

$app = new \Slim\Slim(array(
			'mode'		=> 'development'
			));
$app->setName("CBW beer API");
$app->response->headers->set('Content-Type', 'application/json');
$app->response->headers->set('Access-Control-Allow-Origin', '*');
$app->response->headers->set("Access-Control-Allow-Methods: GET");

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
function get_beers($where = NULL) {
	global $db, $dbprefix;
	$query = "SELECT " . $dbprefix . "beers.id, beer, abv, " . $dbprefix . "beer_styles.style, description FROM " . $dbprefix . "beers INNER JOIN " . $dbprefix . "beer_styles ON " . $dbprefix . "beers.style=" . $dbprefix . "beer_styles.id " . $where;
	if (!($result = $db->query($query))) {
		echo "error";
		echo $query;
		// some form of error
	}
	$beers = array();
	while ($row = $result->fetch_assoc()) $beers[] = $row;
	return $beers;
}

function get_taps($where = NULL) {
	global $db, $dbprefix;
	$query = "SELECT " . $dbprefix . "taps.id AS tapid, " . $dbprefix . "taps.tap, " . $dbprefix . "taps.description AS tapdescription, " . $dbprefix . "beers.id, " . $dbprefix . "beers.beer, abv, " . $dbprefix . "beer_styles.style, " . $dbprefix . "beers.description FROM " . $dbprefix . "taps INNER JOIN " . $dbprefix . "beers ON " . $dbprefix . "taps.beer=" . $dbprefix . "beers.id INNER JOIN " . $dbprefix . "beer_styles ON " . $dbprefix . "beers.style=" . $dbprefix . "beer_styles.id " . $where . " ORDER BY " . $dbprefix . "taps.id";
	if (!($result = $db->query($query))) {
		echo "error";
		echo $query;
		// some form of error
	}
	$taps = array();
	while ($tap = $result->fetch_assoc()) {
		$taps[] = array(
				"id"		=> $tap['tapid'],
				"tap"		=> $tap['tap'],
				"description"	=> $tap['tapdescription'],
				"beer"		=> array(
					"id"		=> $tap['id'],
					"beer"		=> $tap['beer'],
					"abv"		=> $tap['abv'],
					"style"		=> $tap['style'],
					"description"	=> $tap['description']
					)
			       );
	}
	return $taps;
}

// dealing with list of beers we have made
$app->group('/beer', function() use ($app) {
		// all active and non-archived beers
		$app->get('', function() {
			$beers = get_beers("WHERE active=1");
			echo json_encode($beers);
			});

		// all beers, including archived
		$app->get('/all', function() {
			$beers = get_beers();
			echo json_encode($beers);
			});

		// detail of specific beer
		$app->get('/:id', function($id) {
			if (!is_numeric($id)) {
				$app->status(400);
				$app->stop();
				}
			global $dbprefix;
			$beers = get_beers("WHERE " . $dbprefix . "beers.id=" . $id);
			echo json_encode($beers);
			});

		$app->put('/:id', function($id) use($app) {
			global $db, $dbprefix;
			$key = $app->request->headers->get('apikey');
			if (!$key) {
				$app->status(400);
				$app->stop();
			}
			$query = "SELECT user FROM " . $dbprefix . "apikeys WHERE apikey='" . addslashes($key) . "'";
			if (!($result = $db->query($query))) {
				$app->status(500);
				$app->stop();
			} else if (!$result->fetch_assoc()) {
				$app->status(400);
				$app->stop();
			}

			// this doesn't support changing the style
			if ($app->request->params("beer")) $changes[] = "beer='" . addslashes($app->request->params("beer")) . "'";
			if ($app->request->params("abv")) $changes[] = "abv='" . addslashes($app->request->params("abv")) . "'";
			if ($app->request->params("description")) $changes[] = "description='" . addslashes($app->request->params("description")) . "'";
			$query = "UPDATE " . $dbprefix . "beers SET " . implode(",",$changes) . " WHERE id=" . $id;
			if (!($result = $db->query($query))) {
				$app->status(500);
				$app->stop();
			}
			echo json_encode(array("status"=>"success"));

			});
		});

// what we have on tap
$app->group('/ontap', function() use ($app) {
		// what's currently on tap
		$app->get('', function() {
			$taps = get_taps();
			echo json_encode($taps);
			});

		// what's on a specific tap
		$app->get('/:id', function($id) {
			global $dbprefix;
			$taps = get_taps("WHERE " . $dbprefix . "taps.id=" . $id);
			echo json_encode($taps);
			});

		});

// style-specific information
$app->group('/style', function() use ($app) {
		// style list
		$app->get('', function() {
			global $db, $dbprefix;
			$query = "SELECT id, style FROM " . $dbprefix . "beer_styles ORDER BY id";
			if (!($result = $db->query($query))) {
			echo "error";
			echo $query;
			// some form of error
			}
			$styles = array();
			while ($row = $result->fetch_assoc()) $styles[] = $row;
			echo json_encode($styles);
			});

		// all beers with a style
		$app->get('/:id', function($id) {
			global $dbprefix;
			$beers = get_beers("WHERE " . $dbprefix . "beer_styles.id=" . $id);
			echo json_encode($beers);
			});
		});

$app->run();

?>
