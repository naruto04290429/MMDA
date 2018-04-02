<?php

// Set the database access information as constants.
//DEFINE ('DB_USER', 'root');
//DEFINE ('DB_PASSWORD', '');
//DEFINE ('DB_HOST', 'localhost');
//DEFINE ('DB_NAME', 'dagr');

DEFINE ('DB_USER', 'cmsc424-mmda');
DEFINE ('DB_PASSWORD', 't~{BYfV_v!k6yt{)');
DEFINE ('DB_HOST', 'cmsc389n-group-project.cmwyuj79unri.us-east-1.rds.amazonaws.com');
DEFINE ('DB_NAME', 'cmsc-424');

$dbc = @mysqli_connect (DB_HOST, DB_USER, DB_PASSWORD, DB_NAME) OR die ('Could not connect to MySQL: ' . mysqli_connect_error() );
//echo "connection success";
?>
