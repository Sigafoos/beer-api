<?php
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


