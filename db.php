<?php

require_once("spyc.php");
require_once("bmark_config.php");

/*

A generic connection function

 */
function bmark_connect($database_type = 'mysql') {
	
	$Data = Spyc::YAMLLoad(BMARK . 'db.yaml');
	
	if ($database_type == 'mysql')
	{
		$user = $Data['mysqld']['user'];
		$pass = $Data['mysqld']['pass'];
		$host = $Data['mysqld']['host'];
		$dbname = $Data['mysqld']['dbname'];
		
		// trying connect
		try {
			$dbh = mysql_connect("$host", "$user", "$pass");
			if ($dbh == FALSE) {
				throw new Exception('mysql_connect_error');
			} 
		} catch  (Exception $e) {
		    echo "Caught active server error exception:\n",  $e->getMessage(), " ",
		        $e->getFile(), ": Line ",
		        $e->getLine(), "\n", $e->getTraceAsString(), "\n";
		
		}
		
		// trying db select
		try {
			if (!mysql_select_db($dbname, $dbh)) { throw new Exception('mysql_select_db_erorr'); }
		} catch (Exception $e) {
	    	echo "Caught active server error exception:\n",  $e->getMessage(), " ",
		        $e->getFile(), ": Line ",
		        $e->getLine(), "\n", $e->getTraceAsString(), "\n";
		}

	}
	
	if ($database_type == 'drizzle') {
		
		// set connection parameters
		$host = $Data['drizzled']['host'];
		$port = $Data['drizzled']['port'];
		$user = $Data['drizzled']['user'];
		$pass = $Data['drizzled']['pass'];
		$db   = $Data['drizzled']['dbname'];

		// create drizzle object
		$drizzle = drizzle_create();

		// connect to database server
		try {
			$dbh = drizzle_con_add_tcp($drizzle, $host, $port, $user, $pass, $db, 0);
			if ($dbh == FALSE) {
				throw new Exception('drizzle_con_add_tcp_error');
			}
		} catch (Exception $e) {
			echo "Caught active server error exception:\n",  $e->getMessage(), " ",
		        $e->getFile(), ": Line ",
		        $e->getLine(), "\n", $e->getTraceAsString(), "\n";
		    
		}
	}
	
	print_r($dbh);
	return $dbh;
}


function bmark_query($sql, $conn) {
	

	
	
	
}


?>
