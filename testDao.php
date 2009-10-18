<?php

require_once ("Dao.php");
require_once ("functions.php");
require 'Benchmark/Timer.php';

$shortopts = "h";
$options = getopt($shortopts);

if (isset($options['h'])) {
  print "usage: php testDoa.php <mysql|drizzle>\n";
  die();
}

$db_type = $_SERVER['argv'][1];

// db select is contained with bmark_connect
if (strlen ($db_type) <= 0) {
	$db_type = 'drizzle';
}

// phpinfo(); die();
// use software partition
$timer = new Benchmark_Timer();
$timer->start();
$login = 'notsolonely21';
$dao = new Dao($db_type, 'db.yaml');
$dao->connect();
$dao->find('users', 'login', $login, '=');
$dao->close();
$timer->setMarker('Test_Code_Partition');
echo "Elapsed time between Start and Test_Code_Partition: " .      
	$timer->timeElapsed('Start', 'Test_Code_Partition') . "\n";

// use backend partition
$dao2 = new Dao($db_type, 'db.yaml');
$dao2->connect();
$dao2->find('users', 'login', $login, '=', 'mysql');
$dao2->close();
$timer->setMarker('DB_Partition');
echo "Elapsed time between Test_Code_Partition and DB_Partition: " .      
	$timer->timeElapsed('Test_Code_Partition', 'DB_Partition') . "\n";

// use no partition
$dao3 = new Dao($db_type, 'db.yaml');
$dao3->connect();
$dao3->find('users', 'login', $login, '=', 'nopart');
$dao3->close();
$timer->setMarker('No_Partition');
echo "Elapsed time between DB_Partition and No_Partition: " .      
	$timer->timeElapsed('DB_Partition', 'No_Partition') . "\n";

$timer->stop();
$timer->display();



?>
