<?php

/*
 *
 * The Dao that can be spoken of is not the true Dao.
 * This Dao (Data Access Object) is used for connecting to 
 * different databases.
 * 
 * 
 *
 * usage: 
 * @params database_type, user, pass, host, database
 * @return database handler
 * @author barce@codebelay.com
 *
 */

require_once("spyc.php");
require_once("bmark_config.php");

class Dao
{

	var $database_type = ''; # e.g. oracle, mysql, drizzle?
	var $user          = '';
	var $pass          = '';
	var $host          = '';
	var $port          = '';
	var $database      = '';
	var $connection    = '';

	# create the Dao object. Oracle does not need a database parameter.
	function __construct($database_type, $db_file)
	{

		$Data = Spyc::YAMLLoad(BMARK . 'db.yaml');

		if ($database_type == 'mysql') {
			$this->database_type = $database_type;
			$this->user          = $Data['mysqld']['user'];
			$this->pass          = $Data['mysqld']['pass'];
			$this->host          = $Data['mysqld']['host'];
			$this->port          = $Data['mysqld']['port'];
			$this->database      = $Data['mysqld']['dbname'];
			$this->connection    = '';
	
		}

		if ($database_type == 'drizzle') {
			$this->database_type = $database_type;
			$this->user          = $Data['drizzled']['user'];
			$this->pass          = $Data['drizzled']['pass'];
			$this->host          = $Data['drizzled']['host'];
			$this->port          = $Data['drizzled']['port'];
			$this->database      = $Data['drizzled']['dbname'];
			$this->connection    = '';
	
		}
		
		
		if (strlen($this->port) <= 0) {
			if (strcmp($this->database_type, "mysql") == 0) {
				$this->port = '3306';
			}

			if (strcmp($this->database_type, "drizzle") == 0) {
				$this->port = '4427';
			}

		}

	}

	function connect() {

		if (strcmp($this->database_type, "oracle") == 0) {
				// do an oracle connect
		}

		if (strcmp($this->database_type, "mysql") == 0) {
			$connection = mysql_connect($this->host, $this->user, $this->pass);
			if (!$connection) {
				die("Could not connect: " . mysql_error());
			}

			if (!mysql_select_db($this->database, $connection)) {
				die("Could not select: " . mysql_error());
			}
			$this->connection = $connection;
		}

		if (strcmp($this->database_type, "drizzle") == 0) {
			$drizzle = drizzle_create();
			try {
				$this->connection = drizzle_con_add_tcp($drizzle, $this->host, $this->port, $this->user, $this->pass, $this->database, 0);
				if ($this->connection == FALSE) {
					throw new Exception('drizzle_con_add_tcp_error');
				}
			} catch (Exception $e) {
				echo "Caught active server error exception:\n", 
					$e->getMessage(), " ",
			        $e->getFile(), ": Line ",
			        $e->getLine(), "\n", $e->getTraceAsString(), "\n";
			}
		}
	}

	function close()
	{
		if (strcmp($this->database_type, "mysql") == 0) {
			if (!mysql_close($this->connection)) {
				die("Could not close connection: " . mysql_error());
			}
		}
		
		if (strcmp($this->database_type, "drizzle") == 0) {
			drizzle_con_close($this->connection);
			/*
			if (!drizzle_con_close($this->connection)) {
				die("Could not close connection: " . drizzle_con_error($this->connection));
			}
			*/
		}
		return 1;
	}


	/*
	 * This is where the fun stuff happens
	 * -----------------------------------
	 * $data = new Dao('mysql', 'user', 'pass', 'host', 'fundatabase');
	 * $connection = $data->connect();
	 * 
	 *
	 *
	 */
	function find($table_type, $field, $value, $like = '', $partition_flag = '')
	{


		// drizzle
		if (strcmp($this->database_type, 'drizzle') == 0)
		{
			if (strcmp($like, 'like') == 0) {
				$like = 'like';
				$value = "'%$value'";
			} else {
				$like = '='; 
			}
			
			if (strcmp($partition_flag, 'nopart') == 0) {
				$sql = "select * from $table_type" 
					. "_no_partition where $field $like '$value'";
	      		$result = @drizzle_query($this->connection, $sql);
	      		if (!$result) {
	        		die('Invalid query: ' . drizzle_con_error($this->connection));
	      		}
				drizzle_result_buffer($result)
			    	or die('ERROR: ' . drizzle_con_error($dbh) . "\n");
	
	      		while($row = drizzle_row_next($result))
	      		{
	        		$myarray[] = $row; 
	      		}
				return $myarray;
			}
			
			
			if (strcmp($partition_flag, 'drizzle') == 0) {
	      		$sql = "select * from $table_type where $field $like '$value'";
				print "partition sql: $sql\n";
	      		$result = @drizzle_query($this->connection, $sql);
	      		if (!$result) {
	        		die('Invalid query: ' . drizzle_con_error($this->connection));
	      		}
				drizzle_result_buffer($result)
			    	or die('ERROR: ' . drizzle_con_error($dbh) . "\n");

	      		while($row = drizzle_row_next($result))
	      		{
	        		$myarray[] = $row; 
	      		}
				return $myarray;
			}


			// iterate through the tables using the meta_table
			$sql = "select * from meta_table where tablename like \"" . 
				$table_type . "%\"";

			print $sql . "\n";
			try {
				$result = @drizzle_query($this->connection, $sql);
				if (!$result) {
					throw new Exception('meta_table query failed');
				}
			} catch (Exception $e) {
				echo drizzle_con_error($this->connection) . "\n";
				echo "Caught active server error exception:\n", 
					$e->getMessage(), " ",
			        $e->getFile(), ": Line ",
			        $e->getLine(), "\n", $e->getTraceAsString(), "\n";
			}
			
			drizzle_result_buffer($result)
		    	or die('ERROR: ' . drizzle_con_error($dbh) . "\n");
			
			
			while($row = drizzle_row_next($result))
			{
				print_r($row);
				$current_table = $row[1];
				$iterator      = $row[2];
				$last_user_id  = $row[3];
			}
			print "current_table: $current_table\n";

			// awesome, we got meta data
			// we need to now:
			// go through each table
			// find the info
			// return the info

			// get current_table number
			preg_match('/_[0-9]+/', $current_table, $matches);
			$bar_number     = $matches[0];
			preg_match('/[0-9]+/', $bar_number, $matches);
			$table_number   = $matches[0];

			// get how long the number is b/c of padding issues later
			$num_length     = strlen($table_number);

			$i_table_number = (int) $table_number;

			// for ($i = 0; $i <= $i_table_number; $i++) {

			$i = 0;
			$i_sent = 0;
			$myarray = array();
			while ($i_sent <= 0) {
				// search each partition
				$curr_table = $table_type . "_" . padNumber($i, $num_length);
				$sql = "select * from $curr_table where $field $like '$value'"; 
				print $sql . "\n";
				$result = @drizzle_query($this->connection, $sql);
				if (!$result) {
					die('Invalid query: ' . drizzle_con_error($this->connection));
				}
				drizzle_result_buffer($result)
			    	or die('ERROR: ' . drizzle_con_error($dbh) . "\n");
				
				while($row = drizzle_row_next($result))
				{
					$myarray[] = $row;
				}

				print "count (myarray) : " . count($myarray) . "\n";
				if (count($myarray) >= 1) {
					print "count > 1\n";
					$i_sent = 1;
				}

				if ($i >= $i_table_number) {
					print "$i <= $i_table_number\n";
					$i_sent = 1;
				}
				$i++;
			}


/*
			$sql = "select * from $table where $field $like $value";
			$result = mysql_query($sql, $this->connection);
			if (!$result) {
				die('Invalid query: ' . mysql_error());
			}
			while($row = mysql_fetch_assoc($result))
			{
			  $array[] = $row; 
			}
*/

			print_r($myarray);
			return $myarray;



		} // end of drizzle portion

		
		// mysql
		if (strcmp($this->database_type, 'mysql') == 0)
		{
			if (strcmp($like, 'like') == 0) {
				$like = 'like';
				$value = "'%$value'";
			} else {
				$like = '='; 
			}

			if (strcmp($partition_flag, 'nopart') == 0) {
				$sql = "select SQL_CACHE * from $table_type" 
					. "_no_partition where $field $like '$value'";
	      $result = mysql_query($sql, $this->connection);
	      if (!$result) {
	        die('Invalid query: ' . mysql_error());
	      }
	      while($row = mysql_fetch_assoc($result))
	      {
	        $myarray[] = $row; 
	      }
				return $myarray;
			}
			if (strcmp($partition_flag, 'mysql') == 0) {
	      $sql = "select SQL_CACHE * from $table_type where $field $like '$value'";
				print "partition sql: $sql\n";
	      $result = mysql_query($sql, $this->connection);
	      if (!$result) {
	        die('Invalid query: ' . mysql_error());
	      }
	      while($row = mysql_fetch_assoc($result))
	      {
	        $myarray[] = $row; 
	      }
				return $myarray;
			}


			// iterate through the tables using the meta_table
			$sql = "select SQL_CACHE * from meta_table where tablename like \"" . 
				$table_type . "%\"";

			print $sql . "\n";
			$result = mysql_query($sql, $this->connection);
			if (!$result) {
				die('Cannnot access meta table: ' . mysql_error());
			}
			while($row = mysql_fetch_assoc($result))
			{
				$current_table = $row['tablename'];
				$iterator      = $row['iterator'];
				$last_user_id  = $row['last_user_id'];
			}
			print "current_table: $current_table\n";

			// awesome, we got meta data
			// we need to now:
			// go through each table
			// find the info
			// return the info

			// get current_table number
			preg_match('/_[0-9]+/', $current_table, $matches);
			$bar_number     = $matches[0];
			preg_match('/[0-9]+/', $bar_number, $matches);
			$table_number   = $matches[0];

			// get how long the number is b/c of padding issues later
			$num_length     = strlen($table_number);

			$i_table_number = (int) $table_number;

			// for ($i = 0; $i <= $i_table_number; $i++) {

			$i = 0;
			$i_sent = 0;
			$myarray = array();
			while ($i_sent <= 0) {
				// search each partition
				$curr_table = $table_type . "_" . padNumber($i, $num_length);
				$sql = "select SQL_CACHE * from $curr_table where $field $like '$value'"; 
				print $sql . "\n";
				$result = mysql_query($sql, $this->connection);
				if (!$result) {
					die('Invalid query: ' . mysql_error());
				}
				while($row = mysql_fetch_assoc($result))
				{
					$myarray[] = $row;
				}

				print "count (myarray) : " . count($myarray) . "\n";
				if (count($myarray) >= 1) {
					print "count > 1\n";
					$i_sent = 1;
				}

				if ($i >= $i_table_number) {
					print "$i <= $i_table_number\n";
					$i_sent = 1;
				}
				$i++;
			}
			

/*
			$sql = "select * from $table where $field $like $value";
			$result = mysql_query($sql, $this->connection);
			if (!$result) {
				die('Invalid query: ' . mysql_error());
			}
			while($row = mysql_fetch_assoc($result))
			{
			  $array[] = $row; 
			}
*/

			print_r($myarray);
			return $myarray;
			
		} // end of mysql portion

	} // end of find()

}

?>
