<?php

require_once 'db.php';
require_once 'functions.php';
require 'Benchmark/Timer.php';

$dbh = bmark_connect('mysql');

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

$max_rows = $_SERVER['argv'][1];
$parts    = $_SERVER['argv'][2];

$perpart     = ceil($max_rows / $parts);
$mid_perpart = ceil($perpart / 2);
$mid_id      = ceil($max_rows / 2);
$mid_table   = ceil($parts / 2);
$s_mid_table = padNumber($mid_table, 2);

$sql = "drop table users_no_partition";
$result = mysql_query($sql);

$sql = "CREATE TABLE users_no_partition ( id INT NOT NULL primary key AUTO_INCREMENT , login varchar(255), email varchar(255), im varchar(255), twitter varchar(255), pass varchar(255), datejoined datetime)";
$result = mysql_query($sql);

$sql = 'create index login_index on users_no_partition (login)';
$result = mysql_query($sql);

for ($i = 0; $i < $max_rows ; $i++) {
	$sql = "insert into users_no_partition (login, pass) values (\"" . md5(rand(1,5000). microtime()) . "user$i\", password('pass$i'))";
	$result = mysql_query($sql);
}

$timer->setMarker('No_Partition');
echo "Elapsed time between Start and Test_Code_Partition: " .
   $timer->timeElapsed('Start', 'No_Partition') . "\n";


$prefix = "users_";
$k = 1;
for ($i = 0; $i < $parts; $i++) {

	$table = $prefix . padNumber($i, 2);
	$sql = "drop table $table";
	$result = mysql_query($sql);
	
	$sql = "CREATE TABLE $table ( id INT NOT NULL primary key AUTO_INCREMENT , login varchar(255), email varchar(255), im varchar(255), twitter varchar(255), pass varchar(255), datejoined datetime)";
	$result = mysql_query($sql);
	
	$sql = 'create index login_index on $table (login)';
	$result = mysql_query($sql);
	
	for ($j = 0; $j < $perpart; $j++) {
		$sql = "insert into $table (id, login, pass) values ($k, \"" . md5(rand(1,5000). microtime()) . "user$j\", password('pass$j'))";
		$result = mysql_query($sql);
		$k++;
	}

}
// create & update the meta table used for the php partition
$sql = "CREATE TABLE meta_table ( id INT NOT NULL primary key AUTO_INCREMENT , tablename varchar(255), iterator int(10), last_user_id int(10))";
$result = mysql_query($sql);

$sql = "insert into meta_table (tablename, iterator) values ('users_00', 4)";
$result = mysql_query($sql);

print "last table for php partition: " . $table . "\n";
$sql = "update meta_table set tablename = '$table'";
$result = mysql_query($sql);
$sql = "update meta_table set iterator = '$perpart'";
$result = mysql_query($sql);

$timer->setMarker('Code_Partition');
echo "Elapsed time between No_Partition and Code_Partition: " .
   $timer->timeElapsed('No_Partition', 'Code_Partition') . "\n";

// create mysql partitioned table
$sql = "drop table users";
$result = mysql_query($sql);
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
$sql = "CREATE TABLE users ( id INT NOT NULL primary key AUTO_INCREMENT , login varchar(255), email varchar(255), im varchar(255), twitter varchar(255), pass varchar(255), datejoined datetime) PARTITION BY RANGE (id) ( $partition_string )";
print "partition sql: $sql\n";
$result = mysql_query($sql);

$sql = 'create index login_index on users (login)';
$result = mysql_query($sql);

for ($i = 0; $i < $max_rows; $i++) {
	$sql = "insert into users (login, pass) values (\"" . md5(rand(1,5000). microtime()) . "user$i\", password('pass$i'))";
	$result = mysql_query($sql);
}

$timer->setMarker('MySQL_Partition');
echo "Elapsed time between No_Partition and Code_Partition: " .
   $timer->timeElapsed('Code_Partition', 'MySQL_Partition') . "\n";

// update the 3 table types
$sql = "update users_no_partition set login = 'notsolonely21' where id = $mid_id";
$result = mysql_query($sql);

$id_for_partition = $max_rows - ceil($perpart / 3);
$sql = "update $table set login = 'notsolonely21' where id = " . $id_for_partition;
print "sql for updating last php partition: $sql\n";
$result = mysql_query($sql);

$sql = "update users set login = 'notsolonely21' where id = $mid_id";
$result = mysql_query($sql);

$timer->stop();
$timer->display();
?>
