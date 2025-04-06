<?php
	$HOST = 'localhost';
	$USER = 'root';
	$PASS = 'aXGnEnpUJZUgJHBZTNSOlpisswEMAtCr';
	$DB = 'railway';
	$conn = mysqli_connect($HOST,$USER,$PASS,$DB)
        or die("Couldn't connect to database");
	mysqli_set_charset($conn,"utf8");
?>