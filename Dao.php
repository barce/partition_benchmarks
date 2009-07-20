<?

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

class Dao
{

	var $database_type = ''; # e.g. oracle, mysql?
	var $user          = '';
	var $pass          = '';
	var $host          = '';
	var $database      = '';
	var $connection    = '';

	# create the Dao object. Oracle does not need a database parameter.
	function __construct($database_type, $user, $pass, $host, $database) 
	{
		$this->database_type = $database_type;
		$this->user          = $user;
		$this->pass          = $pass;
		$this->host          = $host;
		$this->database      = $database;
		$this->connection    = '';

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
	}

	function close()
	{
		if (strcmp($this->database_type, "mysql") == 0) {
			if (!mysql_close($this->connection)) {
				die("Could not close connection: " . mysql_error());
			}
		}
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
			
		}

	}

}

?>
