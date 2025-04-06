<?php
	$HOST = 'localhost';
	$USER = 'testbookproject';
	$PASS = '7jx2+-n7KE';
	$DB = 'testbookproject';
	$conn = mysqli_connect($HOST,$USER,$PASS,$DB)
        or die("Couldn't connect to database");
	mysqli_set_charset($conn,"utf8");
?>