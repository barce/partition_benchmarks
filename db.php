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
	
	# print_r($dbh);
	return $dbh;
}


function bmark_query($sql, $dbh) {

	$result_set = null;
	
	if (is_a($dbh, "DrizzleCon")) {

		$sql = str_replace ("ENGINE=InnoDB DEFAULT CHARSET=utf8", "", $sql);
		$result = @drizzle_query($dbh, $sql) or
			die('ERROR: ' . drizzle_con_error($dbh) . "\n");
		
		// buffer result set
	  	drizzle_result_buffer($result)
	    	or die('ERROR: ' . drizzle_con_error($dbh) . "\n");

		if (drizzle_result_row_count($result)) {
			while (($row = drizzle_row_next($result))) {
				$result_set[] = $row;
			}
		}

		// free result set
		drizzle_result_free($result);

		// close connection
		// drizzle_con_close($dbh);

		return $result_set;

		
	}
	
	if (bmark_type($dbh) === 'mysql') {
		$result = mysql_query($sql, $dbh) or
			die('ERROR: ' . mysql_error($dbh));
		
		if (preg_match("/select.*/i", $sql)) {
			if (mysql_num_rows($result) > 0) {
				while ($row = mysql_fetch_assoc($result)) {
					$result_set[] = $row;
				}
			
			}
		}
		return $result_set;
	}

	return FALSE;
	
	
}

function bmark_type($db_handler) {

	ob_start();
	print_r($db_handler);
	$o_this = ob_get_contents();
	ob_end_clean();

	if (preg_match("/drizzle.*/i", $o_this)) {
		return 'drizzle';
	} 
	if (preg_match("/Resource.*/i", $o_this)) {
		return 'mysql';
	} 
	return null;

}


?>
