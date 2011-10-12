<?php

require_once 'db.php';
require_once 'functions.php';
require 'Benchmark/Timer.php';

$shortopts = "h";
$options = getopt($shortopts);

if (isset($options['h'])) {
  print "usage: php build_tables.php <rows> <partitions> <mysql|drizzle>\n";
  die();
}

$max_rows = $_SERVER['argv'][1];
$parts    = $_SERVER['argv'][2];
$db_type  = $_SERVER['argv'][3];

// db select is contained with bmark_connect
if (strlen ($db_type) <= 0) {
	$db_type = 'drizzle';
}
$dbh = bmark_connect($db_type);



$timer = new Benchmark_Timer();
$timer->start();


// build it 3 types of tables
// 1) a table with 5000 users indexed
// 2) a table with 5000 users code partitioned and indexed
// 3) a table with 5000 users mysql partitioned

/*
$max_rows    = 500;
$parts       = 5;
*/


$perpart     = ceil($max_rows / $parts);
$mid_perpart = ceil($perpart / 2);
$mid_id      = ceil($max_rows / 2);
$mid_table   = ceil($parts / 2);
$s_mid_table = padNumber($mid_table, 2);


$sql = "drop table if exists users_no_partition";
$result = bmark_query($sql, $dbh);
# print $sql . "\n";

$sql = "CREATE TABLE users_no_partition ( id INT NOT NULL primary key AUTO_INCREMENT , login varchar(255), email varchar(255), im varchar(255), twitter varchar(255), pass varchar(255), datejoined datetime) ENGINE=InnoDB DEFAULT CHARSET=utf8";
$result = bmark_query($sql, $dbh);
# print $sql . "\n";

$sql = 'create index login_index on users_no_partition (login)';
$result = bmark_query($sql, $dbh);
# print $sql . "\n";

for ($i = 0; $i < $max_rows ; $i++) {
	$sql = "insert into users_no_partition (login, pass) values (\"" . md5(rand(1,5000). microtime()) . "user$i\", \"" . md5("pass$i"). "\")";
	$result = bmark_query($sql, $dbh);
	# print $sql . "\n";
	
}

$timer->setMarker('No_Partition');
echo "Elapsed time between Start and Test_Code_Partition: " .
   $timer->timeElapsed('Start', 'No_Partition') . "\n";


$prefix = "users_";
$k = 1;
for ($i = 0; $i < $parts; $i++) {

	$table = $prefix . padNumber($i, 2);
	$sql = "drop table if exists $table";
	# print "table: $table\n";
	# print $sql . "\n";
	$result = bmark_query($sql, $dbh);
	
	$sql = "CREATE TABLE $table ( id INT NOT NULL primary key AUTO_INCREMENT , login varchar(255), email varchar(255), im varchar(255), twitter varchar(255), pass varchar(255), datejoined datetime)  ENGINE=InnoDB DEFAULT CHARSET=utf8";
	$result = bmark_query($sql, $dbh);
	
	$sql = "create index login_index on $table (login)";
	$result = bmark_query($sql, $dbh);
	
	for ($j = 0; $j < $perpart; $j++) {
		$sql = "insert into $table (id, login, pass) values ($k, \"" . md5(rand(1,5000). microtime()) . "user$j\", \"" . md5('pass$j') . "\")";
		$result = bmark_query($sql, $dbh);
		$k++;
	}


}
// create & update the meta table used for the php partition
// update below
$sql = "drop table if exists meta_table";
$result = bmark_query($sql, $dbh);

$sql = "CREATE TABLE meta_table ( id INT NOT NULL primary key AUTO_INCREMENT , tablename varchar(255), iterator int, last_user_id int)  ENGINE=InnoDB DEFAULT CHARSET=utf8";
$result = bmark_query($sql, $dbh);

$sql = "insert into meta_table (tablename, iterator) values ('users_00', 4)";
$result = bmark_query($sql, $dbh);

print "last table for php partition: " . $table . "\n";
$sql = "update meta_table set tablename = '$table'";
$result = bmark_query($sql, $dbh);
$sql = "update meta_table set iterator = '$perpart'";
$result = bmark_query($sql, $dbh);

$timer->setMarker('Code_Partition');
echo "Elapsed time between No_Partition and Code_Partition: " .
   $timer->timeElapsed('No_Partition', 'Code_Partition') . "\n";

// create mysql partitioned table
$sql = "drop table if exists users";
$result = bmark_query($sql, $dbh);
$partition_string = '';
$iter_part = $perpart;
for ($i = 0; $i <= $parts; $i++) {
	if ($i == $parts) {
		$partition_string .= "PARTITION p$i VALUES LESS THAN ($iter_part)\n";
	} else {
		$partition_string .= "PARTITION p$i VALUES LESS THAN ($iter_part),\n";
	}
	$iter_part += $perpart;
}

// TODO: native partitions aren't on drizzle
// start native partition code
if (strcmp(bmark_type($dbh), 'mysql') == 0)  {
// if (0 == 1) {
	$sql = "CREATE TABLE users ( id INT NOT NULL primary key AUTO_INCREMENT , login varchar(255), email varchar(255), im varchar(255), twitter varchar(255), pass varchar(255), datejoined datetime) ENGINE=InnoDB DEFAULT CHARSET=utf8 PARTITION BY RANGE (id) ( $partition_string )";

//ENGINE=InnoDB DEFAULT CHARSET=utf8";
	print "partition sql: $sql\n";
	$result = bmark_query($sql, $dbh);
	
	$sql = 'create index login_index on users (login)';
	$result = bmark_query($sql, $dbh);
	
	for ($i = 0; $i < $max_rows; $i++) {
		$sql = "insert into users (login, pass) values (\"" . md5(rand(1,5000). microtime()) . "user$i\", \"" . md5("pass$i") ."\")";
		$result = bmark_query($sql, $dbh);
	}
	
	$timer->setMarker('MySQL_Partition');
	echo "Elapsed time between No_Partition and Code_Partition: " .
	   $timer->timeElapsed('Code_Partition', 'MySQL_Partition') . "\n";
	
	// update the 3 table types
	$sql = "update users_no_partition set login = 'notsolonely21' where id = $mid_id";
	$result = bmark_query($sql, $dbh);
	
	$id_for_partition = $max_rows - ceil($perpart / 3);
	$sql = "update $table set login = 'notsolonely21' where id = " . $id_for_partition;
	print "sql for updating last php partition: $sql\n";
	$result = bmark_query($sql, $dbh);
	
	$sql = "update users set login = 'notsolonely21' where id = $mid_id";
	$result = bmark_query($sql, $dbh);

}

if (strcmp(bmark_type($dbh), 'drizzle') == 0)  {

	$sql = "CREATE TABLE users ( id INT NOT NULL primary key AUTO_INCREMENT , login varchar(255), email varchar(255), im varchar(255), twitter varchar(255), pass varchar(255), datejoined datetime) ENGINE=InnoDB DEFAULT CHARSET=utf8";

	print "partition sql: $sql\n";
	$result = bmark_query($sql, $dbh);
	
	$sql = 'create index login_index on users (login)';
	$result = bmark_query($sql, $dbh);
	
	for ($i = 0; $i < $max_rows; $i++) {
		$sql = "insert into users (login, pass) values (\"" . md5(rand(1,5000). microtime()) . "user$i\", \"" . md5('pass$i') . "\")";
		$result = bmark_query($sql, $dbh);
	}
	
	$timer->setMarker('Drizzle_Faux_Partition');
	echo "Elapsed time between No_Partition and Code_Partition: " .
	   $timer->timeElapsed('Code_Partition', 'Drizzle_Faux_Partition') . "\n";
	
	// update the 3 table types
	$sql = "update users_no_partition set login = 'notsolonely21' where id = $mid_id";
	$result = bmark_query($sql, $dbh);
	
	$id_for_partition = $max_rows - ceil($perpart / 3);
	$sql = "update $table set login = 'notsolonely21' where id = " . $id_for_partition;
	print "sql for updating last php partition: $sql\n";
	$result = bmark_query($sql, $dbh);
	
	$sql = "update users set login = 'notsolonely21' where id = $mid_id";
	$result = bmark_query($sql, $dbh);
	
	
	
}

// end native partition code
$timer->stop();
$timer->display();
?>
