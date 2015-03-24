<?php
require('config.inc.php');

echo "<pre>";
if (!$db) die("Fatal error: no database information specified");
if (!$dbprefix) die("Fatal error: no database prefix specified");

echo "Okay you beautiful human being, let's do this:\n\n";
// beer
$query = "CREATE TABLE IF NOT EXISTS " . $dbprefix . "beers (id int auto_increment primary key, beer varchar(100) not null, style int not null, abv varchar(10) not null, description varchar(500), active int(1) default 1)";
if (!($result = $db->query($query))) die("Fatal error creating table " . $dbprefix . "beers: #" . $db->errno . ": " . $db->error);
echo "Beer table created.\n";

// styles
$query = "CREATE TABLE IF NOT EXISTS " . $dbprefix . "beer_styles (id int auto_increment primary key, style varchar(150) not null)";
if (!($result = $db->query($query))) die("Fatal error creating table " . $dbprefix . "beer_styles: #" . $db->errno . ": " . $db->error);
echo "Style table created.\n";

// tap list (the physical places you can have them and what they contain)
$query = "CREATE TABLE IF NOT EXISTS " . $dbprefix . "taps (id int auto_increment primary key, tap varchar(100) not null, beer int default null, description varchar(500) default null)";
if (!($result = $db->query($query))) die("Fatal error creating table " . $dbprefix . "taps: #" . $db->errno . ": " . $db->error);
echo "Tap table created.\n";

// api keys / authorized users
$query = "CREATE TABLE IF NOT EXISTS " . $dbprefix . "apikeys (apikey varchar(32) primary key, user varchar(50) not null)";
if (!($result = $db->query($query))) die("Fatal error creating table " . $dbprefix . "apikeys: #" . $db->errno . ": " . $db->error);
echo "Tap table created.\n";

echo "\nYou're done! Delete this file now.";
echo "</pre>";
