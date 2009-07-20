<?php

require_once 'Dao.php';
require_once 'functions.php';
require 'Benchmark/Timer.php';

// use software partition
$timer = new Benchmark_Timer();
$timer->start();
$login = 'notsolonely21';
$dao = new Dao('mysql', 'cbtester', 'YOUR_PASSWORD', 'localhost', 'cbtester');
$dao->connect();
$dao->find('users', 'login', $login, '=');
$dao->close();
$timer->setMarker('Test_Code_Partition');
echo "Elapsed time between Start and Test_Code_Partition: " .      
	$timer->timeElapsed('Start', 'Test_Code_Partition') . "\n";

// use backend partition
$dao2 = new Dao('mysql', 'cbtester', 'YOUR_PASSWORD', 'localhost', 'cbtester');
$dao2->connect();
$dao2->find('users', 'login', $login, '=', 'mysql');
$dao2->close();
$timer->setMarker('DB_Partition');
echo "Elapsed time between Test_Code_Partition and DB_Partition: " .      
	$timer->timeElapsed('Test_Code_Partition', 'DB_Partition') . "\n";

// use no partition
$dao3 = new Dao('mysql', 'cbtester', 'YOUR_PASSWORD', 'localhost', 'cbtester');
$dao3->connect();
$dao3->find('users', 'login', $login, '=', 'nopart');
$dao3->close();
$timer->setMarker('No_Partition');
echo "Elapsed time between DB_Partition and No_Partition: " .      
	$timer->timeElapsed('DB_Partition', 'No_Partition') . "\n";

$timer->stop();
$timer->display();



?>
