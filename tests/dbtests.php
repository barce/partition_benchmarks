<?php

require_once('simpletest/unit_tester.php');
require_once('simpletest/reporter.php');
require_once('../db.php');
require_once('spyc.php');


class TestOfDrizzleConnect extends UnitTestCase {

	function testConnections() {
		// found bug where true is returned whether db is up or down
		$dbh = bmark_connect('drizzle');
		print_r($dbh);
		$this->assertTrue($dbh);
	}

}

class TestOfMysqlConnect extends UnitTestCase {

	function testConnections() {
		$dbh = bmark_connect('mysql');
		print_r($dbh);
		$this->assertTrue($dbh);
	}

}

$test = &new TestOfDrizzleConnect();
$test->run(new HtmlReporter());

$test2 = &new TestOfMysqlConnect();
$test2->run(new HtmlReporter());

class TestOfDrizzleQuery extends UnitTestCase {

	function testQuery() {
		$dbh = bmark_connect('drizzle');
		print_r($dbh);
		$sql = "select * from items order by stuff";
		$result = bmark_query($sql, $dbh);
		print_r($result);
		$this->assertTrue($result);
	}
}

$test3 = &new TestOfDrizzleQuery();
$test3->run(new HtmlReporter());

?>
