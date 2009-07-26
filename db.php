<?php

require_once("spyc.php");

/*

A generic connection function

 */
function bmark_connect($database_type = 'mysql') {
	
	$Data = Spyc::YAMLLoad('db.yaml');
	
	if ($database_type == 'mysql')
	{
		$user = $Data['mysqld']['user'];
		$pass = $Data['mysqld']['pass'];
		$host = $Data['mysqld']['host'];
		$dbname = $Data['mysqld']['dbname'];
		$dbh = mysql_connect("$host", "$user", "$pass") or die ("Cannot connect!");
		mysql_select_db($dbname, $dbh) or die ("Cannot select $dbname");
	}
	
	if ($database_type = 'drizzle') {
		
		// set connection parameters
		$host = $Data['drizzled']['host'];
		$port = $Data['drizzled']['port'];
		$user = $Data['drizzled']['user'];
		$pass = $Data['drizzled']['pass'];
		$db   = $Data['drizzled']['dbname'];

		// create drizzle object
		$drizzle = drizzle_create();

		// connect to database server
		$dbh = drizzle_con_add_tcp($drizzle, $host, $port, $user, $pass, $db, 0) 
		    or die('ERROR: ' . drizzle_error($drizzle));
	}
	
	return $dbh;
}


function bmark_query($sql, $conn) {
	

	
	
	
}


?>
