<?php
// where's Slim located?
// See http://www.slimframework.com/
$path_to_slim = "../Slim/Slim/Slim.php";

// where's the database info located?
// if local, edit the line below
$db = new mysqli("localhost","username","password","database");
// otherwise, uncomment and add the path here
//require('../db_connect.php');

// what table prefix should you use?
$dbprefix = "beer_";
?>
