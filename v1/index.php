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

// catch-all OPTIONS 
$app->options('/(:x+)', function() use ($app) {
		//$app->response->headers->set('Access-Control-Allow-Origin', '*');
		//$app->response->headers->set("Access-Control-Allow-Methods: GET, POST, PUT, OPTIONS");
		//$app->response->headers->set('Access-Control-Allow-Headers', 'apikey');
		$app->response->setStatus(200);
		});
/* 
   API functions
   */
function get_beers($where = NULL) {
	global $db, $dbprefix;
	$query = "SELECT " . $dbprefix . "beers.id, beer, abv, " . $dbprefix . "beer_styles.id AS styleid, " . $dbprefix . "beer_styles.style, description, " . $dbprefix . "beers.active FROM " . $dbprefix . "beers INNER JOIN " . $dbprefix . "beer_styles ON " . $dbprefix . "beers.style=" . $dbprefix . "beer_styles.id " . $where . " ORDER BY " . $dbprefix . "beers.id";
	if (!($result = $db->query($query))) {
		echo "error";
		echo $query;
		// some form of error
	}
	$beers = array();
	while ($beer = $result->fetch_assoc()) {
		$beers[] = array(
				"id"		=> $beer['id'],
				"beer"		=> $beer['beer'],
				"abv"		=> $beer['abv'],
				"style"		=> array(
					"id"		=> $beer['styleid'],
					"style"		=> $beer['style']
					),
				"description"	=> $beer['description'],
				"active"	=> $beer['active']
				);
	}
	return $beers;
}

function get_taps($where = NULL) {
	global $db, $dbprefix;
	$query = "SELECT " . $dbprefix . "taps.id AS tapid, " . $dbprefix . "taps.tap, " . $dbprefix . "taps.description AS tapdescription, " . $dbprefix . "beers.id, " . $dbprefix . "beers.beer, abv, " . $dbprefix . "beer_styles.id AS styleid, " . $dbprefix . "beer_styles.style, " . $dbprefix . "beers.description, " . $dbprefix . "beers.active FROM " . $dbprefix . "taps LEFT OUTER JOIN " . $dbprefix . "beers ON " . $dbprefix . "taps.beer=" . $dbprefix . "beers.id LEFT OUTER JOIN " . $dbprefix . "beer_styles ON " . $dbprefix . "beers.style=" . $dbprefix . "beer_styles.id " . $where . " ORDER BY " . $dbprefix . "taps.id";
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
					"style"		=> array(
						"id"		=> $tap['styleid'],
						"style"		=> $tap['style']
						),
					"description"	=> $tap['description'],
					"active"	=> $tap['active']
					)
			       );
	}
	return $taps;
}

// dealing with list of beers we have made
$app->group('/beers', function() use ($app) {
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

			$params = json_decode($app->request->getBody());

			$beer = array(
				"id"		=> $id,
				"beer"		=> addslashes($params->beer),
				"abv"		=> addslashes($params->abv),
				"style"		=> array(
					"id"		=> $params->style->id,
					"style"		=> $params->style->style
					),
				"description"	=> $params->description,
				"active"	=> $params->active
				);

			$query = "UPDATE " . $dbprefix . "beers SET beer='" . $beer['beer'] . "', abv='" . $beer['abv'] . "', style=" . $beer['style']['id'] . ", description=";
			$query .= ($beer['description']) ? "'" . $beer['description'] . "'" : "NULL";
			$query .= ", active=" . $beer['active'] . " WHERE id=" . $beer['id'];
			echo json_encode(array("query"=>$query));
			if (!($result = $db->query($query))) {
				echo json_encode(array("error"=>"yep",$db->errno => $db->error));
				$app->status(500);
				$app->stop();
			}
			echo json_encode($beer);
			});

		$app->post('',function() use ($app) {
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

			$params = json_decode($app->request->getBody());
			$beer = array(
				"beer"		=> addslashes($params->beer),
				"abv"		=> addslashes($params->abv),
				"style"		=> $params->style->id,
				"description"	=> addslashes($params->description),
				"active"	=> $params->active,
				);

			$query = "INSERT INTO " . $dbprefix . "beers(beer, abv, style, description, active) VALUES('" . $beer['beer'] . "', '" . $beer['abv'] . "', " . $beer['style'] . ",";
			$query .= ($beer['description']) ? "'" . $beer['description'] . "'" : "NULL";
			$query .= ", " . $beer['active'] . ")";
			if (!$db->query($query)) {
			$app->status(500);
			$app->stop();
			}

			$id = $db->insert_id;
			if (!$id) {
				$app->status(500);
				$app->stop();
			}
			$beer['id'] = $id;
			echo json_encode($beer);
				});
		});

// what we have on tap
$app->group('/taps', function() use ($app) {
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

		// right now you can ONLY change the beer
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

			$params = json_decode($app->request->getBody());

			$query = "UPDATE " . $dbprefix . "taps SET beer=";
			$query .= ($params->beer) ? $params->beer->id : "NULL";
			$query .= " WHERE id=" . $id;
			if (!($result = $db->query($query))) {
				$app->status(500);
				$app->stop();
			}
			//echo json_encode(array("status"=>"success"));
			});
		});

// style-specific information
$app->group('/styles', function() use ($app) {
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

		$app->post('',function() {
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

			$params = json_decode($app->request->getBody());
			echo json_encode(array("params"=>$params));
			/*
			$query = "INSERT INTO " . $dbprefix . "beer_styles(style) VALUES('" . addslashes($params->style) . "')";
			if (!$db->query($query)) {
			$app->status(500);
			$app->stop();
			}

			$id = mysql_insert_id($db);
			if (!$id) {
				$app->status(500);
				$app->stop();
			}

			echo json_encode(array("id"=>$id, "style"=>$params->style));
			*/
				});
		});

$app->run();

?>
