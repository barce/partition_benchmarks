<?php

$user = "cbtester";
$pass = "YOUR_PASSWORD";
$host = "localhost";
$dbname = "cbtester";
mysql_connect("$host", "$user", "$pass") or die ("Cannot connect!");
mysql_select_db($dbname) or die ("Cannot select $dbname");


?>
