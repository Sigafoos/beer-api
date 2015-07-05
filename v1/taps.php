<?php
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
?>
