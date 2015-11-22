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
/* 
   API functions
 */
function get_beers($where = NULL)
{
	global $db, $dbprefix;
	$query = "SELECT " . $dbprefix . "beers.id, beer, abv, " . $dbprefix . "beer_styles.id AS styleid, " . $dbprefix . "beer_styles.style, description, " . $dbprefix . "beers.active FROM " . $dbprefix . "beers INNER JOIN " . $dbprefix . "beer_styles ON " . $dbprefix . "beers.style=" . $dbprefix . "beer_styles.id " . $where . " ORDER BY " . $dbprefix . "beers.id";

	if (!($result = $db->query($query)))
	{
		echo "error";
		echo $query;
		// some form of error
	}
	$beers = array();
	while ($beer = $result->fetch_assoc())
	{
		$beers[] = array(
			"id"		=> $beer['id'],
			"beer"		=> stripslashes($beer['beer']),
			"abv"		=> stripslashes($beer['abv']),
			"style"		=> array(
				"id"		=> $beer['styleid'],
				"style"		=> stripslashes($beer['style'])
			),
			"description"	=> stripslashes($beer['description']),
			"active"	=> $beer['active']
		);
	}
	return $beers;
}

function get_taps($where = NULL)
{
	global $db, $dbprefix;
	$query = "SELECT " . $dbprefix . "taps.id AS tapid, " . $dbprefix . "taps.tap, " . $dbprefix . "taps.description AS tapdescription, " . $dbprefix . "beers.id, " . $dbprefix . "beers.beer, abv, " . $dbprefix . "beer_styles.id AS styleid, " . $dbprefix . "beer_styles.style, " . $dbprefix . "beers.description, " . $dbprefix . "beers.active, sort_order FROM " . $dbprefix . "taps LEFT OUTER JOIN " . $dbprefix . "beers ON " . $dbprefix . "taps.beer=" . $dbprefix . "beers.id LEFT OUTER JOIN " . $dbprefix . "beer_styles ON " . $dbprefix . "beers.style=" . $dbprefix . "beer_styles.id " . $where . " ORDER BY sort_order ASC, " . $dbprefix . "taps.id";
	if (!($result = $db->query($query)))
	{
		echo "error";
		echo $query;
		// some form of error
	}
	$taps = array();
	while ($tap = $result->fetch_assoc())
	{
		$taps[] = array(
			"id"		=> $tap['tapid'],
			"tap"		=> stripslashes($tap['tap']),
			"description"	=> stripslashes($tap['tapdescription']),
			"beer"		=> array(
				"id"		=> $tap['id'],
				"beer"		=> stripslashes($tap['beer']),
				"abv"		=> stripslashes($tap['abv']),
				"style"		=> array(
					"id"		=> $tap['styleid'],
					"style"		=> stripslashes($tap['style'])
				),
				"description"	=> stripslashes($tap['description']),
				"active"	=> $tap['active']
			),
			"sort_order"		=> $tap['sort_order']
		);
	}
	return $taps;
}

// how do you want it returned?
if ($app->request->get('format') == "object") $format = JSON_FORCE_OBJECT;
else $format = NULL;


// dealing with list of beers we have made
$app->group('/beers', function() use ($app)
{
	// all active and non-archived beers
	$app->get('', function() use ($app)
{
	global $format;
	$req = $app->request();
	$where = ($req->get('view') == 'all') ? null : ' WHERE active=1';
	//$where = 
	$beers = get_beers($where);
	echo json_encode($beers, $format);
});

// all beers, including archived
$app->get('/all', function()
{
	global $format;
	$beers = get_beers();
	echo json_encode($beers, $format);
});

// detail of specific beer
$app->get('/:id', function($id)
{
	if (!is_numeric($id))
	{
		$app->status(400);
		$app->stop();
	}
	global $dbprefix, $format;
	$beers = get_beers("WHERE " . $dbprefix . "beers.id=" . $id);
	echo json_encode($beers, $format);
});

$app->put('/:id', function($id) use($app)
	{
		global $db, $dbprefix, $format;
		$key = $app->request->headers->get('apikey');
		if (!$key)
		{
			$app->status(400);
			$app->stop();
		}
		$query = "SELECT user FROM " . $dbprefix . "apikeys WHERE apikey='" . addslashes($key) . "'";
		if (!($result = $db->query($query)))
		{
			$app->status(500);
			$app->stop();
		} else if (!$result->fetch_assoc())
		{
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
		if (!($result = $db->query($query)))
		{
			$app->status(500);
			$app->stop();
		}
		echo json_encode($beer, $format);
	});

$app->post('',function() use ($app)
		{
			global $db, $dbprefix, $format;
			$key = $app->request->headers->get('apikey');
			if (!$key)
			{
				$app->status(400);
				$app->stop();
			}
			$query = "SELECT user FROM " . $dbprefix . "apikeys WHERE apikey='" . addslashes($key) . "'";
			if (!($result = $db->query($query)))
			{
				$app->status(500);
				$app->stop();
			} else if (!$result->fetch_assoc())
			{
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
			if (!$db->query($query))
			{
				$app->status(500);
				$app->stop();
			}

			$id = $db->insert_id;
			if (!$id)
			{
				$app->status(500);
				$app->stop();
			}
			$beer['id'] = $id;
			echo json_encode($beer, $format);
		});
});

// what we have on tap
$app->group('/taps', function() use ($app)
			{
				// what's currently on tap
				$app->get('', function()
			{
				global $format;
				$taps = get_taps();
				echo json_encode($taps, $format);
			});

				// what's on a specific tap
				$app->get('/:id', function($id)
			{
				global $dbprefix, $format;
				$taps = get_taps("WHERE " . $dbprefix . "taps.id=" . $id);
				echo json_encode($taps, $format);
			});

				// right now you can ONLY change the beer
				$app->put('/:id', function($id) use($app)
			{
				global $db, $dbprefix, $format;
				$key = $app->request->headers->get('apikey');
				if (!$key)
				{
					$app->status(400);
					$app->stop();
				}
				$query = "SELECT user FROM " . $dbprefix . "apikeys WHERE apikey='" . addslashes($key) . "'";
				if (!($result = $db->query($query)))
				{
					$app->status(500);
					$app->stop();
				} else if (!$result->fetch_assoc())
				{
					$app->status(400);
					$app->stop();
				}

				$params = json_decode($app->request->getBody());

				$query = "UPDATE " . $dbprefix . "taps SET beer=";
				$query .= ($params->beer) ? $params->beer->id : "NULL";
				$query .= " WHERE id=" . $id;
				if (!($result = $db->query($query)))
				{
					$app->status(500);
					$app->stop();
				}
			});

// for Slackbot
$app->post('', function() use($app)
{				global $format;
				$taps = get_taps();
$tapped = '';
foreach ($taps as $tap)
{
if (!is_null($tap['beer']['id']))
{
$tapped .= 'Tap ' . $tap['tap'] . ': ' . $tap['beer']['beer'] . ' (' . $tap['beer']['style']['style'] . ', ' . $tap['beer']['abv'] . '%)
';
}
}

$data = [
'channel' => '#beer',
'username' => 'ontapbot',
'text' => $tapped,
'icon_emoji' => ':cbw:',
];

$ch = curl_init('https://hooks.slack.com/services/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');
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, ['payload' => json_encode($data)]);
curl_exec($ch);

});

			});

// style-specific information
$app->group('/styles', function() use ($app)
				{
					// style list
					$app->get('', function()
				{
					global $db, $dbprefix, $format;
					$query = "SELECT id, style FROM " . $dbprefix . "beer_styles ORDER BY id";
					if (!($result = $db->query($query)))
					{
						echo "error";
						echo $query;
						// some form of error
					}
					$styles = array();
					while ($row = $result->fetch_assoc()) $styles[] = array(
						"id"		=> $row['id'],
						"style"		=> stripslashes($row['style'])
					);
					echo json_encode($styles, $format);
				});

					// all beers with a style
					$app->get('/:id', function($id)
					{
						global $dbprefix, $format;
						$beers = get_beers("WHERE " . $dbprefix . "beer_styles.id=" . $id);
						echo json_encode($beers, $format);
					});

					$app->post('',function() use ($app)
					{
						global $db, $dbprefix, $format;
						$key = $app->request->headers->get('apikey');
						if (!$key)
						{
							$app->status(400);
							$app->stop();
						}
						$query = "SELECT user FROM " . $dbprefix . "apikeys WHERE apikey='" . addslashes($key) . "'";
						if (!($result = $db->query($query)))
						{
							$app->status(500);
							$app->stop();
						} else if (!$result->fetch_assoc())
						{
							$app->status(400);
							$app->stop();
						}

						$params = json_decode($app->request->getBody());
						$query = "INSERT INTO " . $dbprefix . "beer_styles(style) VALUES('" . addslashes($params->style) . "')";
						if (!$db->query($query))
						{
							$app->status(500);
							$app->stop();
						}

						$id = $db->insert_id;
						if (!$id)
						{
							$app->status(500);
							$app->stop();
						}

						echo json_encode(array("id"=>$id, "style"=>$params->style), $format);
					});
				});

$app->run();

?>
